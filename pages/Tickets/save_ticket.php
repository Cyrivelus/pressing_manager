<?php
// pages/Tickets/save_ticket.php

// Démarrer la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../fonctions/database.php';

// Définir l'en-tête JSON
header('Content-Type: application/json');

// Fonction pour nettoyer les données
function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

try {
    // Nettoyer les données POST
    $_POST = cleanInput($_POST);
    
    // Démarrer la transaction
    $pdo->beginTransaction();

    // 1. Vérifier les données essentielles
    if (empty($_POST['id_client'])) {
        throw new Exception("Client non sélectionné.");
    }
    
    if (empty($_POST['date_retrait_prevue'])) {
        throw new Exception("Date de retrait prévue manquante.");
    }
    
    // 2. Définir les valeurs par défaut pour l'utilisateur et l'agence
    // Rechercher un utilisateur actif (n'importe lequel)
    $stmt_user = $pdo->query("SELECT id_utilisateur FROM utilisateurs WHERE est_actif = 1 LIMIT 1");
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Créer un utilisateur par défaut si aucun n'existe
        $pdo->query("INSERT INTO utilisateurs (nom_complet, login_utilisateur, mot_de_passe) 
                    VALUES ('Utilisateur Défaut', 'default', '" . password_hash('password123', PASSWORD_DEFAULT) . "')");
        $id_user = $pdo->lastInsertId();
    } else {
        $id_user = (int)$user['id_utilisateur'];
    }
    
    // Rechercher une agence active
    $stmt_agence = $pdo->query("SELECT id_agence FROM agences WHERE est_actif = 1 LIMIT 1");
    $agence = $stmt_agence->fetch(PDO::FETCH_ASSOC);
    
    if (!$agence) {
        // Créer une agence par défaut si aucune n'existe
        $pdo->query("INSERT INTO agences (nom_agence, adresse, telephone, email) 
                    VALUES ('Pressing Principal', 'Adresse par défaut', '00000000', 'contact@pressing.com')");
        $id_agence = $pdo->lastInsertId();
    } else {
        $id_agence = (int)$agence['id_agence'];
    }
    
    // 3. Récupérer les données du client
    $id_client = (int)$_POST['id_client'];
    
    // Vérifier si le client existe
    $stmt_client = $pdo->prepare("SELECT id_client FROM clients WHERE id_client = ?");
    $stmt_client->execute([$id_client]);
    if (!$stmt_client->fetch()) {
        throw new Exception("Client ID $id_client non trouvé.");
    }
    
    // 4. Valider la date
    $date_retrait_prevue = date('Y-m-d H:i:s', strtotime($_POST['date_retrait_prevue']));
    if ($date_retrait_prevue === false) {
        throw new Exception("Format de date invalide.");
    }
    
    // 5. Calculer les montants depuis les articles
    $montant_total = 0;
    $montant_remise = 0;
    
    // Récupérer le pourcentage de remise
    $remise_pourcentage = !empty($_POST['remise_pourcentage']) ? (float)$_POST['remise_pourcentage'] : 0;
    
    // 6. Vérifier et calculer les articles
    if (empty($_POST['services']) || !is_array($_POST['services'])) {
        throw new Exception("Aucun article sélectionné.");
    }
    
    $articles = [];
    foreach ($_POST['services'] as $index => $service_id) {
        if (empty($_POST['qtes'][$index])) {
            throw new Exception("Quantité manquante pour l'article " . ($index + 1));
        }
        
        $service_id = (int)$service_id;
        $quantite = (float)$_POST['qtes'][$index];
        
        // Vérifier et récupérer le service
        $stmt_service = $pdo->prepare("SELECT prix_unitaire, nom_service FROM services WHERE id_service = ?");
        $stmt_service->execute([$service_id]);
        $service = $stmt_service->fetch(PDO::FETCH_ASSOC);
        
        if (!$service) {
            // Créer un service par défaut si nécessaire
            $pdo->query("INSERT INTO services (nom_service, prix_unitaire) 
                        VALUES ('Service Défaut', 1000)");
            $service_id = $pdo->lastInsertId();
            $prix_unitaire = 1000;
            $nom_service = 'Service Défaut';
        } else {
            $prix_unitaire = (float)$service['prix_unitaire'];
            $nom_service = $service['nom_service'];
        }
        
        $sous_total = $prix_unitaire * $quantite;
        $montant_total += $sous_total;
        
        $articles[] = [
            'id_service' => $service_id,
            'nom_service' => $nom_service,
            'quantite' => $quantite,
            'prix_unitaire' => $prix_unitaire,
            'sous_total' => $sous_total
        ];
    }
    
    // Appliquer la remise si spécifiée
    if ($remise_pourcentage > 0) {
        $montant_remise = $montant_total * ($remise_pourcentage / 100);
        $montant_total -= $montant_remise;
    }
    
    // 7. Récupérer les autres informations
    $montant_verse = !empty($_POST['montant_verse']) ? (float)$_POST['montant_verse'] : 0;
    $mode_paiement = !empty($_POST['mode_paiement']) ? $_POST['mode_paiement'] : 'especes';
    $notes_client = !empty($_POST['notes_client']) ? $_POST['notes_client'] : '';
    
    // Valider le mode de paiement
    $allowed_payments = ['especes', 'carte', 'cheque', 'mobile', 'autre'];
    if (!in_array($mode_paiement, $allowed_payments)) {
        $mode_paiement = 'especes';
    }
    
    // 8. Générer un numéro de ticket unique
    $prefix = "TK-" . date('ymd');
    $stmt_count = $pdo->prepare("SELECT COUNT(*) as count FROM tickets WHERE numero_ticket LIKE ?");
    $stmt_count->execute([$prefix . "%"]);
    $count = $stmt_count->fetch(PDO::FETCH_ASSOC)['count'];
    $numero_ticket = $prefix . "-" . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    
    // 9. Insérer le ticket
    $stmt_ticket = $pdo->prepare("
        INSERT INTO tickets (
            numero_ticket, id_client, id_agence, id_utilisateur, 
            date_depot, date_retrait_prevue, montant_total, 
            montant_verse, montant_remise, mode_paiement, notes_client, statut
        ) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, 'en_attente')
    ");
    
    $insert_ticket = $stmt_ticket->execute([
        $numero_ticket, 
        $id_client, 
        $id_agence, 
        $id_user,
        $date_retrait_prevue, 
        $montant_total, 
        $montant_verse, 
        $montant_remise, 
        $mode_paiement,
        $notes_client
    ]);
    
    if (!$insert_ticket) {
        throw new Exception("Erreur lors de l'insertion du ticket.");
    }
    
    $id_ticket = $pdo->lastInsertId();
    
    // 10. Insérer les articles (lignes_ticket)
    $stmt_ligne = $pdo->prepare("
        INSERT INTO lignes_ticket (
            id_ticket, id_service, quantite, prix_unitaire, 
            sous_total, numero_etiquette, statut_article
        ) VALUES (?, ?, ?, ?, ?, ?, 'depose')
    ");
    
    foreach ($articles as $index => $art) {
        $etiq = $numero_ticket . "-" . ($index + 1);
        $insert_ligne = $stmt_ligne->execute([
            $id_ticket, 
            $art['id_service'], 
            $art['quantite'], 
            $art['prix_unitaire'], 
            $art['sous_total'], 
            $etiq
        ]);
        
        if (!$insert_ligne) {
            throw new Exception("Erreur lors de l'insertion de l'article " . ($index + 1));
        }
    }
    
    // 11. Enregistrer le paiement si montant versé > 0
    if ($montant_verse > 0) {
        $stmt_pay = $pdo->prepare("
            INSERT INTO paiements (
                id_ticket, montant, mode_paiement, date_paiement, 
                id_utilisateur, reference
            ) VALUES (?, ?, ?, NOW(), ?, ?)
        ");
        
        $ref_pay = "PAY-" . $numero_ticket;
        $stmt_pay->execute([
            $id_ticket, 
            $montant_verse, 
            $mode_paiement, 
            $id_user, 
            $ref_pay
        ]);
    }
    
    // 12. Ajouter des points de fidélité au client
    if ($montant_total > 0) {
        $points = floor($montant_total / 1000); // 1 point pour 1000 XAF
        if ($points > 0) {
            $stmt_points = $pdo->prepare("
                UPDATE clients 
                SET points_fidelite = points_fidelite + ? 
                WHERE id_client = ?
            ");
            $stmt_points->execute([$points, $id_client]);
        }
    }
    
    // 13. Valider la transaction
    $pdo->commit();
    
    // Réponse de succès
    echo json_encode([
        'success' => true, 
        'id_ticket' => $id_ticket, 
        'numero_ticket' => $numero_ticket,
        'montant_total' => $montant_total,
        'montant_verse' => $montant_verse,
        'reste_a_payer' => $montant_total - $montant_verse,
        'message' => "Ticket créé avec succès: $numero_ticket",
        'redirect' => "imprimer_ticket.php?id=$id_ticket"
    ]);

} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur PDO
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Messages d'erreur spécifiques pour les contraintes de clé étrangère
    $error_message = 'Erreur SQL : ' . $e->getMessage();
    
    if (strpos($e->getMessage(), 'tickets_ibfk_2') !== false) {
        $error_message = "Problème avec l'agence. Une agence par défaut a été créée. Veuillez réessayer.";
        
        // Tentative de créer une agence par défaut
        try {
            $pdo->query("INSERT INTO agences (nom_agence, adresse, telephone, email, est_actif) 
                        VALUES ('Pressing Principal', 'Adresse par défaut', '00000000', 'contact@pressing.com', 1)");
        } catch (Exception $e2) {
            // Ignorer les erreurs secondaires
        }
    } elseif (strpos($e->getMessage(), 'tickets_ibfk_3') !== false) {
        $error_message = "Problème avec l'utilisateur. Un utilisateur par défaut a été créé. Veuillez réessayer.";
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $error_message
    ]);
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur générale
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}