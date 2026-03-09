<?php
// fonctions/database.php

// 1. Détection de l'environnement (Local vs Production)
$isLocal = ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1');

if ($isLocal) {
    // CONFIGURATION XAMPP (Local)
    $dbHost = "localhost";
    $dbName = "pressing_manager";
    $dbUser = "root";
    $dbPass = "";
} else {
    // CONFIGURATION INFINITYFREE (Production)
    // IMPORTANT : Récupère ces infos dans ton Panel InfinityFree > MySQL Databases
    $dbHost = "sql103.infinityfree.com"; // EXEMPLE : Vérifie le tien dans le panel
    $dbName = "if0_41343012_pressing_manager"; // EXEMPLE : Le nom que tu as créé
    $dbUser = "if0_41343012";             // Ton nom d'utilisateur hosting
    $dbPass = "dHMa1eiAPMwY"; // Ton mot de passe (celui du compte client)
}

$dbCharset = "utf8mb4";
$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=$dbCharset";

$connectionOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $connectionOptions);
} catch (PDOException $e) {
    // En production, on cache les détails sensibles
    if ($isLocal) {
        die("Erreur de connexion (DEBUG) : " . $e->getMessage());
    } else {
        error_log("DB Error: " . $e->getMessage());
        die("Le service est momentanément indisponible.");
    }
}

// Ta fonction utilitaire reste inchangée
function executeQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Erreur SQL : " . $e->getMessage());
        throw $e;
    }
}