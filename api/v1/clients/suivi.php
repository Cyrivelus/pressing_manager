<?php
header('Content-Type: application/json');
require_once '../../../fonctions/database.php';

$code_suivi = $_GET['code'] ?? '';

$stmt = $pdo->prepare("SELECT t.statut, t.date_retrait_prevue, c.nom_client 
                       FROM tickets t 
                       JOIN clients c ON t.id_client = c.id_client 
                       WHERE t.code_suivi = ?");
$stmt->execute([$code_suivi]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($ticket ?: ['error' => 'Ticket non trouvé']);