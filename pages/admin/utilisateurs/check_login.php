<?php
// pages/admin/utilisateurs/check_login.php

// Démarrer la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérification de l'authentification
if (!isset($_SESSION['utilisateur_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit();
}

// Vérification des permissions
$roleUtilisateur = $_SESSION['nom_role'] ?? 'Réceptionniste';
$allowedRoles = ['patron', 'Responsable'];
if (!in_array($roleUtilisateur, $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

require_once('../../../fonctions/database.php');

// Vérifier si c'est une requête GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

// Récupérer le login à vérifier
$login = isset($_GET['login']) ? trim($_GET['login']) : '';

if (strlen($login) < 3) {
    echo json_encode(['available' => false, 'message' => 'Login trop court']);
    exit();
}

// Vérifier si le login existe déjà
try {
    $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateurs WHERE login_utilisateur = ?");
    $stmt->execute([$login]);
    
    if ($stmt->fetch()) {
        echo json_encode(['available' => false, 'message' => 'Login déjà utilisé']);
    } else {
        echo json_encode(['available' => true, 'message' => 'Login disponible']);
    }
} catch (PDOException $e) {
    error_log("Erreur vérification login: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur de vérification']);
}
?>