<?php
session_start();

// 1. Inclusions des dépendances
require_once __DIR__ . "/../../fonctions/database.php";
require_once __DIR__ . "/../../fonctions/gestion_utilisateurs.php";

// 2. Sécurité : Vérification des autorisations d'accès
// On vérifie si l'ID est en session ET si un flag de changement obligatoire est présent
$can_change = isset($_SESSION['is_temp_password']) || isset($_SESSION['admin_message_warning']);

if (!isset($_SESSION['utilisateur_id']) || !$can_change) {
    header("Location: ../../index.php");
    exit();
}

// 3. Récupération et nettoyage du message d'alerte
$message = $_SESSION['admin_message_warning'] ?? "Pour votre sécurité, vous devez modifier votre mot de passe.";
unset($_SESSION['admin_message_warning']);

// 4. Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = "Veuillez remplir tous les champs.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($newPassword) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
        // Hachage sécurisé
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        try {
            // Mise à jour selon votre structure SQL : Table `utilisateurs`
            $sql = "UPDATE utilisateurs SET mot_de_passe = :password WHERE id_utilisateur = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':password' => $hashedPassword,
                ':id'       => $_SESSION['utilisateur_id']
            ]);
            
            // 5. Nettoyage de la session après succès
            unset($_SESSION['is_temp_password']);

            $_SESSION['success_message'] = "Votre mot de passe a été mis à jour avec succès.";
            header("Location: ../dashboard.php");
            exit();

        } catch (PDOException $e) {
            $error = "Une erreur technique est survenue. Veuillez réessayer.";
            error_log("Erreur changement MDP ID " . $_SESSION['utilisateur_id'] . " : " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sécuriser mon compte - Pressing Manager</title>
    <link rel="stylesheet" href="../css/bootstrap-3.4.1.min.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
        }
        .container-card {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .brand-logo {
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container-card">
        <div class="brand-logo">
            <h3>Pressing Manager</h3>
            <p class="text-muted">Mise à jour de sécurité</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-info small">
                <i class="glyphicon glyphicon-info-sign"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="new_password">Nouveau mot de passe</label>
                <input type="password" class="form-control" id="new_password" name="new_password" 
                       placeholder="Min. 8 caractères" required autofocus>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <hr>
            <button type="submit" class="btn btn-primary btn-block btn-lg">
                Valider le changement
            </button>
        </form>
    </div>
</body>
</html>