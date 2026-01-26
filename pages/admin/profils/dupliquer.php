<?php
// pages/admin/profils/dupliquer.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Vérification des permissions
$roleUtilisateur = $_SESSION['role'] ?? 'Réceptionniste';
$allowedRoles = ['patron', 'Responsable', 'Administrateur'];
if (!in_array($roleUtilisateur, $allowedRoles)) {
    header('Location: ../../../pages/dashboard.php');
    exit();
}

require_once('../../../fonctions/database.php');

// 2. Récupération de l'ID source
$id_source = $_GET['id'] ?? null;

if (!$id_source) {
    $_SESSION['admin_message_error'] = "Aucun rôle sélectionné pour la duplication.";
    header('Location: index.php');
    exit();
}

try {
    // 3. Récupérer les données du rôle original
    $stmt = $pdo->prepare("SELECT nom_role, description, niveau_permission FROM roles WHERE id_role = ?");
    $stmt->execute([$id_source]);
    $roleOriginal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$roleOriginal) {
        $_SESSION['admin_message_error'] = "Le rôle source n'existe pas.";
        header('Location: index.php');
        exit();
    }

    // 4. Préparer les données pour la copie
    $nouveauNom = "Copie de " . $roleOriginal['nom_role'];
    $description = $roleOriginal['description'];
    $niveau = $roleOriginal['niveau_permission'];

    // 5. Insérer le nouveau rôle
    $insert = $pdo->prepare("INSERT INTO roles (nom_role, description, niveau_permission) VALUES (?, ?, ?)");
    $insert->execute([$nouveauNom, $description, $niveau]);

    $_SESSION['admin_message_success'] = "Le rôle a été dupliqué sous le nom : '$nouveauNom'. Vous pouvez maintenant le modifier.";
    
    // Redirection vers l'index ou directement vers la modification du nouveau rôle
    header('Location: index.php');
    exit();

} catch (PDOException $e) {
    // Gestion spécifique si le nom dupliqué existe déjà (contrainte UNIQUE)
    if ($e->getCode() == 23000) {
        $_SESSION['admin_message_error'] = "Une copie de ce rôle existe déjà. Modifiez le nom de la copie existante avant de dupliquer à nouveau.";
    } else {
        $_SESSION['admin_message_error'] = "Erreur lors de la duplication : " . $e->getMessage();
    }
    header('Location: index.php');
    exit();
}