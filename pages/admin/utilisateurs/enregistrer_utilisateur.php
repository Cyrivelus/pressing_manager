<?php
// pages/admin/utilisateurs/enregistrer_utilisateur.php

// 1. Démarrer la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Définition de la fonction de génération d'URL (pour éviter l'erreur "undefined function")
if (!function_exists('generateUrl')) {
    function generateUrl($path) {
        // Ajustez le nom du dossier 'pressing_manager' si nécessaire
        return '/pressing_manager/' . ltrim($path, '/');
    }
}

// 3. Vérification des permissions
$roleUtilisateur = $_SESSION['role'] ?? 'Réceptionniste';
// On accepte 'Administrateur', 'Admin', 'patron' ou 'Responsable' selon vos tests précédents
$allowedRoles = ['patron', 'Responsable', 'Administrateur', 'Admin'];

if (!in_array($roleUtilisateur, $allowedRoles)) {
    header('Location: ' . generateUrl('pages/dashboard.php'));
    exit();
}

// 4. Inclusion de la base de données
require_once('../../../fonctions/database.php');

// 5. Vérifier si c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_msg'] = "Méthode non autorisée.";
    header('Location: ' . generateUrl('pages/admin/utilisateurs/ajouter.php'));
    exit();
}

// 6. Récupération et validation des données
$required_fields = ['nom_complet', 'login_utilisateur', 'mot_de_passe', 'confirm_password', 'id_role'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    $_SESSION['error_msg'] = "Champs obligatoires manquants.";
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . generateUrl('pages/admin/utilisateurs/ajouter.php'));
    exit();
}

// Vérifier que les mots de passe correspondent
if ($_POST['mot_de_passe'] !== $_POST['confirm_password']) {
    $_SESSION['error_msg'] = "Les mots de passe ne correspondent pas.";
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . generateUrl('pages/admin/utilisateurs/ajouter.php'));
    exit();
}

// Nettoyer les données
$nom_complet = trim($_POST['nom_complet']);
$login_utilisateur = trim($_POST['login_utilisateur']);
$email = !empty($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : null;
$telephone = !empty($_POST['telephone']) ? preg_replace('/[^0-9+]/', '', $_POST['telephone']) : null;
$id_role = intval($_POST['id_role']);
$code_agence = !empty($_POST['code_agence']) ? $_POST['code_agence'] : null;
$est_actif = isset($_POST['est_actif']) ? 1 : 0;
$mot_de_passe = $_POST['mot_de_passe'];

// Validations de longueur
if (strlen($login_utilisateur) < 3 || strlen($mot_de_passe) < 6) {
    $_SESSION['error_msg'] = "Login (min 3 car.) ou Mot de passe (min 6 car.) trop court.";
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . generateUrl('pages/admin/utilisateurs/ajouter.php'));
    exit();
}

try {
    // Vérifier l'unicité du login
    $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateurs WHERE login_utilisateur = ?");
    $stmt->execute([$login_utilisateur]);
    if ($stmt->fetch()) {
        $_SESSION['error_msg'] = "Ce nom d'utilisateur est déjà utilisé.";
        $_SESSION['form_data'] = $_POST;
        header('Location: ' . generateUrl('pages/admin/utilisateurs/ajouter.php'));
        exit();
    }

    // Hasher le mot de passe
    $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);

    // Début de la transaction
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        INSERT INTO utilisateurs (
            nom_complet, 
            login_utilisateur, 
            mot_de_passe, 
            email, 
            telephone, 
            id_role, 
            code_agence, 
            est_actif, 
            date_creation
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $nom_complet,
        $login_utilisateur,
        $hashed_password,
        $email,
        $telephone,
        $id_role,
        $code_agence,
        $est_actif
    ]);
    
    $new_user_id = $pdo->lastInsertId();
    
    // Journaliser l'action (Logs)
    $logStmt = $pdo->prepare("
        INSERT INTO logs_activite (
            id_utilisateur, 
            action, 
            table_concernée, 
            id_enregistrement, 
            ip_adresse, 
            user_agent
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $logStmt->execute([
        $_SESSION['user_id'] ?? null, // Assurez-vous que c'est 'user_id' dans votre session
        "Création de l'utilisateur : " . $login_utilisateur,
        'utilisateurs',
        $new_user_id,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu'
    ]);
    
    $pdo->commit();
    
    $_SESSION['success_msg'] = "Utilisateur créé avec succès !";
    // Redirection vers l'index (la liste)
    header('Location: ' . generateUrl('pages/admin/utilisateurs/index.php'));
    exit();
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur création utilisateur: " . $e->getMessage());
    $_SESSION['error_msg'] = "Erreur base de données : " . $e->getMessage();
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . generateUrl('pages/admin/utilisateurs/ajouter.php'));
    exit();
}