<?php
header('Content-Type: application/json');
require_once '../../../fonctions/database.php';

$id_ticket = $_POST['id_ticket'] ?? null;
$montant = $_POST['montant'] ?? 0;

if ($id_ticket) {
    // Simulation d'appel vers une API Mobile Money (Orange/MTN/Wave/CinetPay)
    $response = [
        'status' => 'pending',
        'checkout_url' => 'https://gateway-paiement.com/pay/' . uniqid(),
        'reference' => 'TICK-' . $id_ticket
    ];
    echo json_encode($response);
} else {
    echo json_encode(['error' => 'ID Ticket manquant']);
}