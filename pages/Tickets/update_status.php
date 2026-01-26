<?php
// pages/tickets/update_status.php

// Démarrer la session si nécessaire
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../fonctions/database.php';

// Définir l'en-tête JSON
header('Content-Type: application/json');

// 1. Accepter les requêtes GET ou POST
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

// 2. Récupérer l'ID du ticket et le nouveau statut
// Essayer d'abord POST, puis GET
$id_ticket = 0;
$nouveau_statut = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_ticket = isset($_POST['id_ticket']) ? (int)$_POST['id_ticket'] : 0;
    $nouveau_statut = $_POST['statut'] ?? '';
} else {
    // Méthode GET
    $id_ticket = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $nouveau_statut = $_GET['status'] ?? '';
    
    // Si le statut n'est pas fourni dans GET, utiliser une valeur par défaut
    if (empty($nouveau_statut) && $id_ticket > 0) {
        $nouveau_statut = 'pret'; // Valeur par défaut
    }
}

// 3. Validation des données
if ($id_ticket <= 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'ID ticket invalide ou manquant.',
        'debug' => [
            'method' => $_SERVER['REQUEST_METHOD'],
            'post_data' => $_POST,
            'get_data' => $_GET
        ]
    ]);
    exit;
}

// Liste des statuts autorisés
$statuts_valides = ['en_attente', 'en_traitement', 'pret', 'recupere', 'annule'];

if (!in_array($nouveau_statut, $statuts_valides)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Statut invalide. Statuts autorisés: ' . implode(', ', $statuts_valides),
        'provided_status' => $nouveau_statut
    ]);
    exit;
}

try {
    // 4. Vérifier que le ticket existe
    $stmt_check = $pdo->prepare("SELECT id_ticket, numero_ticket, id_agence, id_utilisateur FROM tickets WHERE id_ticket = ?");
    $stmt_check->execute([$id_ticket]);
    $ticket = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        echo json_encode([
            'success' => false, 
            'message' => 'Ticket non trouvé.',
            'id_ticket' => $id_ticket
        ]);
        exit;
    }
    
    // 5. Récupérer ou créer un utilisateur pour la mise à jour
    $id_user = 1; // Utilisateur par défaut
    
    if (!empty($_SESSION['id_utilisateur'])) {
        $id_user = (int)$_SESSION['id_utilisateur'];
    } else {
        // Chercher un utilisateur actif
        $stmt_user = $pdo->query("SELECT id_utilisateur FROM utilisateurs WHERE est_actif = 1 LIMIT 1");
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $id_user = (int)$user['id_utilisateur'];
        }
    }
    
    // 6. Vérifier et créer l'agence si nécessaire
    $id_agence = (int)$ticket['id_agence'];
    
    $stmt_agence = $pdo->prepare("SELECT id_agence FROM agences WHERE id_agence = ?");
    $stmt_agence->execute([$id_agence]);
    if (!$stmt_agence->fetch()) {
        // Créer une agence par défaut si elle n'existe pas
        $pdo->query("INSERT INTO agences (nom_agence, adresse, telephone, email, est_actif) 
                    VALUES ('Pressing Principal', 'Adresse par défaut', '00000000', 'contact@pressing.com', 1)");
        $id_agence = $pdo->lastInsertId();
        
        // Mettre à jour l'agence du ticket
        $pdo->prepare("UPDATE tickets SET id_agence = ? WHERE id_ticket = ?")->execute([$id_agence, $id_ticket]);
    }
    
    // 7. Démarrer la transaction
    $pdo->beginTransaction();
    
    // 8. Préparation de la requête de mise à jour
    // Si le statut est 'recupere', on met à jour la date_retrait_reelle
    if ($nouveau_statut === 'recupere') {
        $stmt = $pdo->prepare("
            UPDATE tickets 
            SET statut = ?, date_retrait_reelle = NOW(), updated_at = NOW() 
            WHERE id_ticket = ?
        ");
        
        // Mettre à jour toutes les lignes du ticket en 'livre'
        $stmt_lignes = $pdo->prepare("UPDATE lignes_ticket SET statut_article = 'livre' WHERE id_ticket = ?");
        $stmt_lignes->execute([$id_ticket]);
        
    } elseif ($nouveau_statut === 'pret') {
        $stmt = $pdo->prepare("
            UPDATE tickets 
            SET statut = ?, updated_at = NOW() 
            WHERE id_ticket = ?
        ");
        
        // Mettre à jour les articles en 'conditionne'
        $stmt_lignes = $pdo->prepare("UPDATE lignes_ticket SET statut_article = 'conditionne' WHERE id_ticket = ?");
        $stmt_lignes->execute([$id_ticket]);
        
    } else {
        $stmt = $pdo->prepare("
            UPDATE tickets 
            SET statut = ?, updated_at = NOW() 
            WHERE id_ticket = ?
        ");
    }

    $stmt->execute([$nouveau_statut, $id_ticket]);

    // 9. Log de l'action
    $stmt_log = $pdo->prepare("
        INSERT INTO logs_activite (id_utilisateur, action, table_concernée, id_enregistrement, nouvelles_valeurs) 
        VALUES (?, ?, 'tickets', ?, ?)
    ");
    
    $log_data = json_encode([
        'ticket_id' => $id_ticket,
        'numero_ticket' => $ticket['numero_ticket'],
        'ancien_statut' => $ticket['statut'] ?? 'unknown',
        'nouveau_statut' => $nouveau_statut,
        'date_maj' => date('Y-m-d H:i:s')
    ]);
    
    $stmt_log->execute([
        $id_user, 
        "Changement statut ticket vers: $nouveau_statut", 
        $id_ticket,
        $log_data
    ]);

    // 10. Notification automatique (si le ticket est 'pret')
    if ($nouveau_statut === 'pret') {
        try {
            $stmt_notif = $pdo->prepare("
                INSERT INTO notifications 
                (id_utilisateur, id_agence, type_notification, titre, message, lien) 
                VALUES (?, ?, 'ticket_pret', ?, ?, ?)
            ");
            
            $titre = "Vêtements prêts - Ticket #" . $ticket['numero_ticket'];
            $message = "Le ticket #" . $ticket['numero_ticket'] . " est prêt pour retrait.";
            $lien = "pages/tickets/view.php?id=" . $id_ticket;
            
            $stmt_notif->execute([
                $id_user,
                $id_agence,
                $titre,
                $message,
                $lien
            ]);
        } catch (Exception $e) {
            // Ignorer les erreurs de notification
            error_log("Erreur notification: " . $e->getMessage());
        }
    }

    $pdo->commit();
    
    // 11. Réponse de succès
    $response = [
        'success' => true, 
        'message' => 'Statut mis à jour avec succès.',
        'data' => [
            'id_ticket' => $id_ticket,
            'numero_ticket' => $ticket['numero_ticket'],
            'nouveau_statut' => $nouveau_statut,
            'date_maj' => date('Y-m-d H:i:s')
        ]
    ];
    
    // Si c'est une requête GET, rediriger vers la liste
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $response['redirect'] = 'list.php?msg=updated&id=' . $id_ticket;
        
        // Pour les appels AJAX, retourner JSON
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode($response);
        } else {
            // Redirection pour les requêtes GET normales
            header('Location: list.php?msg=updated&id=' . $id_ticket);
        }
    } else {
        echo json_encode($response);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur : ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}