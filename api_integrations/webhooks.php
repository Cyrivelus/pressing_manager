<?php
// Réception de données externes (ex: paiement validé par une gateway)
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (isset($data['event']) && $data['event'] == 'payment.success') {
    // Logique de mise à jour du ticket
    require_once '../fonctions/database.php';
    $stmt = $pdo->prepare("UPDATE tickets SET statut_paiement = 'paye' WHERE reference_externe = ?");
    $stmt->execute([$data['reference']]);
}
http_response_code(200);