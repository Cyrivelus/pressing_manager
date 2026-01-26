<?php
// api/get_stats.php
header('Content-Type: application/json');
require_once '../fonctions/database.php';

$agence_id = $_GET['agence_id'] ?? 1;
$today = date('Y-m-d');

try {
    // 1. CA du jour
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(montant_total), 0) as total FROM tickets WHERE id_agence = :id AND DATE(date_depot) = :today AND statut != 'annule'");
    $stmt->execute(['id' => $agence_id, 'today' => $today]);
    $ca_jour = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 2. Clients actifs
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM clients WHERE id_agence = :id AND est_actif = TRUE");
    $stmt->execute(['id' => $agence_id]);
    $total_clients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 3. Tickets en attente
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE id_agence = :id AND statut = 'en_attente'");
    $stmt->execute(['id' => $agence_id]);
    $pending = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        "success" => true,
        "data" => [
            "revenue" => number_format($ca_jour, 0, '.', ' '),
            "clients" => $total_clients,
            "pending" => $pending,
            "date" => date('d/m/Y')
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}