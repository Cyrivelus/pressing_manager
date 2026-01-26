<?php
// api/save_ticket.php

// -----------------------------------------------------------
// ADAPTATION POUR REACT NATIVE : Lecture du JSON
// -----------------------------------------------------------
header('Content-Type: application/json');
$json_data = json_decode(file_get_contents('php://input'), true);

if ($json_data) {
    // Si on reçoit du JSON (Mobile), on remplit $_POST avec
    $_POST = $json_data;
}

// -----------------------------------------------------------
// VOTRE LOGIQUE ORIGINALE (avec quelques ajustements de sécurité)
// -----------------------------------------------------------
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../fonctions/database.php'; // Ajustez le chemin vers votre database.php

function cleanInput($data) {
    if (is_array($data)) return array_map('cleanInput', $data);
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

try {
    $pdo->beginTransaction();

    // Vérifications de base
    if (empty($_POST['id_client'])) throw new Exception("Client non sélectionné.");
    
    // Pour le mobile, on peut définir une date de retrait par défaut (+48h) si absente
    $date_retrait = !empty($_POST['date_retrait_prevue']) ? $_POST['date_retrait_prevue'] : date('Y-m-d H:i:s', strtotime('+2 days'));

    // Récupération de l'utilisateur et de l'agence (votre logique)
    $id_user = $_SESSION['id_utilisateur'] ?? 1; 
    $id_agence = $_SESSION['id_agence'] ?? 1;

    // Calcul des montants
    $montant_total = 0;
    $articles = [];
    
    if (empty($_POST['services'])) throw new Exception("Aucun article sélectionné.");

    foreach ($_POST['services'] as $index => $service_id) {
        $quantite = (float)($_POST['qtes'][$index] ?? 1);
        
        $stmt_service = $pdo->prepare("SELECT prix_unitaire, nom_service FROM services WHERE id_service = ?");
        $stmt_service->execute([$service_id]);
        $service = $stmt_service->fetch(PDO::FETCH_ASSOC);
        
        $prix_unitaire = $service ? (float)$service['prix_unitaire'] : 1000;
        $sous_total = $prix_unitaire * $quantite;
        $montant_total += $sous_total;
        
        $articles[] = [
            'id_service' => $service_id,
            'quantite' => $quantite,
            'prix_unitaire' => $prix_unitaire,
            'sous_total' => $sous_total
        ];
    }

    // Génération Numéro Ticket unique (TK-AAMMJJ-XXXX)
    $prefix = "TK-" . date('ymd');
    $stmt_count = $pdo->prepare("SELECT COUNT(*) as count FROM tickets WHERE numero_ticket LIKE ?");
    $stmt_count->execute([$prefix . "%"]);
    $count = $stmt_count->fetch(PDO::FETCH_ASSOC)['count'];
    $numero_ticket = $prefix . "-" . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

    // Insertion du Ticket
    $stmt_ticket = $pdo->prepare("
        INSERT INTO tickets (numero_ticket, id_client, id_agence, id_utilisateur, date_depot, date_retrait_prevue, montant_total, montant_verse, statut) 
        VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, 'en_attente')
    ");
    $stmt_ticket->execute([$numero_ticket, $_POST['id_client'], $id_agence, $id_user, $date_retrait, $montant_total, $_POST['montant_verse'] ?? 0]);
    
    $id_ticket = $pdo->lastInsertId();

    // Insertion des Lignes (vêtements)
    $stmt_ligne = $pdo->prepare("INSERT INTO lignes_ticket (id_ticket, id_service, quantite, prix_unitaire, sous_total, numero_etiquette, statut_article) VALUES (?, ?, ?, ?, ?, ?, 'depose')");
    
    foreach ($articles as $idx => $art) {
        $etiq = $numero_ticket . "-" . ($idx + 1);
        $stmt_ligne->execute([$id_ticket, $art['id_service'], $art['quantite'], $art['prix_unitaire'], $art['sous_total'], $etiq]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "Ticket créé : $numero_ticket",
        'id_ticket' => $id_ticket
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}