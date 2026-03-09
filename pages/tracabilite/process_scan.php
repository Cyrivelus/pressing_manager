<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../fonctions/database.php';

if (!isset($_POST['code'], $_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Données incomplètes']);
    exit;
}

$code = $_POST['code']; // Correspond généralement à numero_ticket ou un ID ligne
$status = $_POST['status'];

try {
    // 1. Vérifier si l'article existe (Exemple sur table lignes_ticket)
    $check = $pdo->prepare("SELECT id_ligne FROM lignes_ticket WHERE id_ligne = ? OR rfid_tag = ?");
    $check->execute([$code, $code]);
    $item = $check->fetch();

    if ($item) {
        // 2. Mettre à jour le statut
        $update = $pdo->prepare("UPDATE lignes_ticket SET statut_production = ? WHERE id_ligne = ?");
        $update->execute([$status, $item['id_ligne']]);

        // 3. Logger l'action dans un journal de flux
        $log = $pdo->prepare("INSERT INTO journal_production (id_ligne, action, date_action, utilisateur) VALUES (?, ?, NOW(), ?)");
        $log->execute([$item['id_ligne'], $status, $_SESSION['user_id'] ?? 0]);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Article non trouvé']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur DB : ' . $e->getMessage()]);
}