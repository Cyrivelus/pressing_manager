<?php
/**
 * Génère une réponse JSON standardisée
 */
function sendJsonResponse($status, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ]);
    exit;
}

/**
 * Récupère l'état d'un vêtement via API pour le client
 */
function getStatutArticleApi($id_article) {
    global $pdo;
    $sql = "SELECT etat_avancement FROM lignes_ticket WHERE rfid_tag = ? OR id_ligne = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_article, $id_article]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}