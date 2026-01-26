<?php
// pages/admin/profils/enregistrer_profil.php
session_start();
require_once('../../../fonctions/database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['admin_message_error'] = "Erreur de sécurité (CSRF).";
        header('Location: ajouter.php');
        exit();
    }

    $nom = trim($_POST['nom_role']);
    $desc = trim($_POST['description']);
    $niv = intval($_POST['niveau_permission']);

    if (empty($nom)) {
        $_SESSION['admin_message_error'] = "Le nom du rôle est obligatoire.";
        header('Location: ajouter.php');
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO roles (nom_role, description, niveau_permission) VALUES (?, ?, ?)");
        $stmt->execute([$nom, $desc, $niv]);
        
        $_SESSION['admin_message_success'] = "Le rôle '$nom' a été créé avec succès.";
        header('Location: index.php');
        exit();
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Erreur duplicata
            $_SESSION['admin_message_error'] = "Ce nom de rôle existe déjà.";
        } else {
            $_SESSION['admin_message_error'] = "Erreur base de données : " . $e->getMessage();
        }
        header('Location: ajouter.php');
        exit();
    }
}