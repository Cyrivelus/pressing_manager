<?php
// pressing_manager/api/v1/test_connexion.php

// Autoriser les requêtes provenant de l'application mobile
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . '/../../config/database.php';

try {
    $db = Database::getInstance();
    
    // Test simple : compter les clients
    $query = "SELECT COUNT(*) as total FROM clients";
    $stmt = $db->query($query);
    $row = $stmt->fetch();

    echo json_encode([
        "status" => "success",
        "message" => "Connexion établie avec Pressing Manager Pro",
        "data" => [
            "total_clients" => $row['total'],
            "server_time" => date('Y-m-d H:i:s'),
            "version_api" => "v1.0"
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Erreur de connexion : " . $e->getMessage()
    ]);
}