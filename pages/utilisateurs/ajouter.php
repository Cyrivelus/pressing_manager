<?php
// pages/admin/utilisateurs/ajouter.php

// Démarrer la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


// Vérification des permissions (Correction selon votre SQL : id_role ou nom_role)
// Ici on utilise la logique de votre script précédent
$roleUtilisateur = $_SESSION['role'] ?? 'Réceptionniste';
$allowedRoles = ['patron', 'Responsable', 'Administrateur', 'Admin'];

if (!in_array($roleUtilisateur, $allowedRoles)) {
    header('Location: ' . generateUrl('pages/dashboard.php'));
    exit();
}

// Inclusion des fichiers locaux
require_once('../../fonctions/database.php');
require_once('../../templates/header.php');
require_once('../../templates/navigation.php');

// Récupération des données pour le formulaire
try {
    $roles = $pdo->query("SELECT * FROM roles ORDER BY niveau_permission DESC")->fetchAll();
    $agences = $pdo->query("SELECT * FROM agences WHERE est_actif = 1 ORDER BY nom_agence")->fetchAll();
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Messages flash
$error_msg = $_SESSION['error_msg'] ?? null;
$success_msg = $_SESSION['success_msg'] ?? null;
unset($_SESSION['error_msg'], $_SESSION['success_msg']);

$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvel Utilisateur - Pressing Manager</title>
    
    <link rel="stylesheet" href="../../../css/bootstrap-3.4.1.min.css">
    <link rel="stylesheet" href="../../../css/monstyle.css">
    <style>
        .create-container { padding: 20px; margin-top: 20px; }
        .required-field::after { content: " *"; color: #dc3545; }
        .form-section { 
            background-color: #f9f9f9; 
            border: 1px solid #ddd; 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 4px;
        }
        .password-strength { height: 5px; margin-top: 5px; transition: 0.3s; }
        .strength-weak { background-color: #d9534f; width: 33%; }
        .strength-good { background-color: #f0ad4e; width: 66%; }
        .strength-strong { background-color: #5cb85c; width: 100%; }
    </style>
</head>
<body>
<br><br><br>
<div class="container create-container">
    <div class="row">
        <div class="col-md-12">
            <ol class="breadcrumb">
                <li><a href="<?= generateUrl('pages/dashboard.php') ?>">Tableau de bord</a></li>
                <li><a href="<?= generateUrl('pages/admin/utilisateurs/index.php') ?>">Utilisateurs</a></li>
                <li class="active">Nouveau</li>
            </ol>
            
            <div class="page-header">
                <h1>Ajouter un Utilisateur <small>Gestion du personnel</small></h1>
            </div>
        </div>
    </div>

    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= generateUrl('pages/admin/utilisateurs/enregistrer_utilisateur.php') ?>" id="userForm">
        <div class="row">
            <div class="col-md-8">
                <div class="panel panel-default">
                    <div class="panel-heading"><b>Informations Personnelles</b></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="required-field">Nom Complet</label>
                                <input type="text" name="nom_complet" class="form-control" required value="<?= htmlspecialchars($form_data['nom_complet'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Téléphone</label>
                                <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($form_data['telephone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Affectation Agence</label>
                              <select name="code_agence" class="form-control" style="height: 40px;">
    <option value="">-- Aucune (Siège) --</option>
    <?php foreach ($agences as $ag): ?>
        <option value="<?= $ag['id_agence'] ?>"><?= htmlspecialchars($ag['nom_agence']) ?></option>
    <?php endforeach; ?>
</select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div class="panel-heading"><b>Identifiants de connexion</b></div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="required-field">Nom d'utilisateur (Login)</label>
                            <input type="text" name="login_utilisateur" id="login_utilisateur" class="form-control" required>
                            <span id="loginFeedback"></span>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="required-field">Mot de passe</label>
                                <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control" required>
                                <div id="passwordStrength" class="password-strength"></div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="required-field">Confirmer le mot de passe</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel panel-primary">
                    <div class="panel-heading"><b>Rôle & Statut</b></div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="required-field">Rôle Système</label>
                            <select name="id_role" class="form-control" style="height: 40px; padding: 10px; font-size: 16px;" required>
    <?php foreach ($roles as $role): ?>
        <option value="<?= $role['id_role'] ?>"><?= htmlspecialchars($role['nom_role']) ?></option>
    <?php endforeach; ?>
</select>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="est_actif" value="1" checked> Compte actif
                            </label>
                        </div>
                        <hr>
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                         Enregistrer
                        </button>
                        <a href="index.php" class="btn btn-default btn-block">Annuler</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="../../js/jquery-3.6.0.js" defer></script>
<script src="../../js/bootstrap-3.4.1.min.js" defer></script>

<script>
$(document).ready(function() {
    // Vérification force mot de passe
    $('#mot_de_passe').on('input', function() {
        var pswd = $(this).val();
        var strength = $('#passwordStrength');
        strength.removeClass('strength-weak strength-good strength-strong');
        
        if (pswd.length > 0 && pswd.length < 6) strength.addClass('strength-weak');
        else if (pswd.length >= 6 && pswd.length < 10) strength.addClass('strength-good');
        else if (pswd.length >= 10) strength.addClass('strength-strong');
    });

    // Validation basique correspondance
    $('#userForm').on('submit', function(e) {
        if ($('#mot_de_passe').val() !== $('#confirm_password').val()) {
            alert("Les mots de passe ne correspondent pas !");
            e.preventDefault();
        }
    });
});
</script>

<?php require_once('../../templates/footer.php'); ?>
</body>
</html>