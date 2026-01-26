<?php
// api/get_tickets.php
header('Content-Type: application/json');
require_once '../fonctions/database.php';

$agence_id = $_GET['agence_id'] ?? 1;

try {
    $stmt = $pdo->prepare("
        SELECT t.*, c.nom_complet as client_nom, c.telephone as client_tel 
        FROM tickets t 
        JOIN clients c ON t.id_client = c.id_client 
        WHERE t.id_agence = :id 
        ORDER BY t.date_depot DESC 
        LIMIT 50
    ");
    $stmt->execute(['id' => $agence_id]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $tickets]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}