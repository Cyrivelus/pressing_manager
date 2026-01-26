<?php
session_start();

// 1. Protection de la page
if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: ../../index.php");
    exit();
}

// 2. Dépendances
require_once __DIR__ . "/../../fonctions/database.php";

$error = '';
$message = $_SESSION['admin_message_warning'] ?? "Pour des raisons de sécurité, veuillez changer votre mot de passe temporaire.";
unset($_SESSION['admin_message_warning']);

// 3. LOGIQUE DE TRAITEMENT (Doit être avant TOUT HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword) || empty($confirmPassword)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (strlen($newPassword) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Les deux mots de passe ne correspondent pas.";
    } else {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE utilisateurs SET mot_de_passe = :password, tentatives_echec = 0 WHERE id_utilisateur = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':password' => $hashedPassword,
                ':id' => $_SESSION['utilisateur_id']
            ]);

            unset($_SESSION['is_temp_password']);
            $_SESSION['success_message'] = "Mot de passe mis à jour avec succès.";
            
            // CETTE REDIRECTION FONCTIONNERA MAINTENANT
            header("Location: ../dashboard/index.php");
            exit();

        } catch (PDOException $e) {
            error_log("Erreur changement MDP : " . $e->getMessage());
            $error = "Une erreur technique est survenue.";
        }
    }
}

// 4. INCLUSION DES TEMPLATES (Seulement après la logique de redirection)
// Note : Si navigation.php contient du HTML, il ne doit être inclus qu'ici
require_once('../../templates/header.php'); 
require_once('../../templates/navigation.php'); 
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Sécurité - Pressing/Commerce Manager</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    <style>
        /* Vos styles ici */
        .password-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            margin: 10% auto; /* Centrage si navigation présente */
        }
    </style>
</head>
<body>

<div class="password-card">
    <h3 class="text-center">Pressing/Commerce <span style="color: #3498db;">Manager</span></h3>
    <h4 class="text-center">Nouveau mot de passe</h4>
    <hr>

    <?php if ($message && !$error): ?>
        <div class="alert alert-warning small"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Nouveau mot de passe</label>
            <input type="password" name="new_password" class="form-control" placeholder="8 caractères min." required autofocus>
        </div>
        <div class="form-group">
            <label>Confirmer le mot de passe</label>
            <input type="password" name="confirm_password" class="form-control" placeholder="Répétez le mot de passe" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Mettre à jour mon accès</button>
    </form>
</div>

<?php require_once('../../templates/footer.php'); ?>
</body>
</html>