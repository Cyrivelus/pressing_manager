<?php
// actions/get_ticket_info.php
session_start();
require_once '../../fonctions/database.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID ticket requis']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            c.nom as client_nom,
            c.prenom as client_prenom,
            c.telephone as client_telephone,
            s.nom_service
        FROM tickets t
        LEFT JOIN clients c ON t.id_client = c.id_client
        LEFT JOIN services s ON t.id_service = s.id_service
        WHERE t.id_ticket = ?
    ");
    $stmt->execute([$_GET['id']]);
    $ticket = $stmt->fetch();
    
    if ($ticket) {
        echo json_encode([
            'success' => true,
            'client_nom' => $ticket['client_nom'],
            'client_prenom' => $ticket['client_prenom'],
            'client_telephone' => $ticket['client_telephone'],
            'nom_service' => $ticket['nom_service'],
            'statut' => $ticket['statut']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ticket non trouvé']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}