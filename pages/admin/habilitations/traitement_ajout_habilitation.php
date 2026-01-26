<?php
// pages/admin/habilitations/traitement_ajout_habilitation.php
session_start();

require_once(__DIR__ . '/../../../fonctions/database.php');
require_once(__DIR__ . '/../../../fonctions/gestion_habilitations.php');

// Vérification de la méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage des entrées
    $nom = trim($_POST['nom_role'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $niveau = intval($_POST['niveau_permission'] ?? 1);

    // Validation simple
    if (empty($nom)) {
        $_SESSION['flash_message'] = "Le nom du rôle est obligatoire.";
        $_SESSION['flash_type'] = "error";
        header('Location: index.php');
        exit();
    }

    // Appel de la fonction définie dans gestion_habilitations.php
    $resultat = ajouterHabilitationProfil($pdo, $nom, $description, $niveau);

    if ($resultat) {
        $_SESSION['flash_message'] = "Le nouveau rôle a été créé avec succès.";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Erreur lors de la création : le nom existe peut-être déjà.";
        $_SESSION['flash_type'] = "error";
    }

    header('Location: index.php');
    exit();
} else {
    // Redirection si accès direct sans POST
    header('Location: index.php');
    exit();
}