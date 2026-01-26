<?php
// pages/utilisateurs/modifier_mot_de_passe.php

// Démarrer la session si ce n'est pas déjà fait
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ⚠️ CORRECTION FATALE ERROR : 
// J'ai enlevé l'inclusion directe de generateUrl() ici,
// car elle est probablement déjà incluse via database.php ou header.php/navigation.php.
// Si elle n'est pas globale, vous devez vous assurer qu'elle est incluse une seule fois.
// Je suppose ici qu'elle est disponible via une autre inclusion.
require_once __DIR__ . '/../../fonctions/database.php'; 

// Rediriger si l'utilisateur n'est pas connecté
if (!isset($_SESSION['utilisateur_id'])) {
    // Si generateUrl n'est pas disponible, utilisez un chemin relatif simple comme fallback
    $redirect_url = function_exists('generateUrl') ? generateUrl('index.php') : '../../index.php';
    header('Location: ' . $redirect_url);
    exit();
}

$utilisateur_id = $_SESSION['utilisateur_id'];
$message_success = '';
$message_erreur = '';

// Traitement du formulaire lorsque le mot de passe est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 1. Validation côté serveur
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message_erreur = "Tous les champs sont obligatoires.";
    } elseif ($new_password !== $confirm_password) {
        $message_erreur = "Le nouveau mot de passe et la confirmation ne correspondent pas.";
    } elseif (strlen($new_password) < 8) { // Exemple: minimum 8 caractères
        $message_erreur = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
    } else {
        try {
            
            // 2. Récupérer le mot de passe haché actuel de l'utilisateur
            $stmt = $pdo->prepare("SELECT Mot_de_Passe FROM Utilisateurs WHERE ID_Utilisateur = :id");
            $stmt->bindParam(':id', $utilisateur_id, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $message_erreur = "Utilisateur non trouvé.";
            } else {
                $hashed_current_password_in_db = $user['Mot_de_Passe'];

                // 3. Vérifier le mot de passe actuel
                if (password_verify($current_password, $hashed_current_password_in_db)) {
                    // 4. Hacher le nouveau mot de passe
                    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // 5. Mettre à jour le mot de passe dans la base de données
                    $stmt_update = $pdo->prepare("UPDATE Utilisateurs SET Mot_de_Passe = :new_password WHERE ID_Utilisateur = :id");
                    $stmt_update->bindParam(':new_password', $new_hashed_password, PDO::PARAM_STR);
                    $stmt_update->bindParam(':id', $utilisateur_id, PDO::PARAM_INT);

                    if ($stmt_update->execute()) {
                        $message_success = "Votre mot de passe a été modifié avec succès.";
                    } else {
                        $message_erreur = "Erreur lors de la mise à jour du mot de passe.";
                        error_log("DB Error updating password for user ID " . $utilisateur_id . ": " . print_r($stmt_update->errorInfo(), true));
                    }
                } else {
                    $message_erreur = "Le mot de passe actuel est incorrect.";
                }
            }
        } catch (PDOException $e) {
            $message_erreur = "Erreur de base de données : " . $e->getMessage();
            error_log("PDO Exception in password change for user ID " . $utilisateur_id . ": " . $e->getMessage());
        } catch (Exception $e) {
            $message_erreur = "Une erreur inattendue est survenue : " . $e->getMessage();
            error_log("General Exception in password change for user ID " . $utilisateur_id . ": " . $e->getMessage());
        }
    }
}

// Inclure les templates
$titre = "Modifier Mot de Passe";
include_once __DIR__ . '/../../templates/header.php';
include_once __DIR__ . '/../../templates/navigation.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BailCompta 360 | Modifier Mot de Passe</title>
 
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
	<link rel="stylesheet" href="../css/bootstrap-3.4.1.min.css">
    <link rel="stylesheet" href="../css/tableau.css">
    <link rel="stylesheet" href="../css/select2.min.css">
    <link rel="stylesheet" href="../css/select2-bootstrap.min.css">
    <style>
        /* ------------------------------------------------ */
        /* 💡 STYLES CORRIGÉS POUR LA RÉACTIVITÉ (EN %) */
        /* ------------------------------------------------ */

        /* Variable pour la largeur de la sidebar */
        :root {
            --sidebar-width: 20%;
        }

        /* Conteneur principal du contenu, décalé vers la droite */
        .main-content-wrapper {
            margin-left: var(--sidebar-width); 
            width: calc(100% - var(--sidebar-width)); 
            min-height: 100vh;
            padding: 2%; 
            box-sizing: border-box; 
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        /* Conteneur interne pour le contenu du formulaire, remplace le .container par défaut */
        .page-container-content {
            width: 95%; 
            margin: 0 auto; 
        }

        /* Ajustements d'espacement en % */
        h2 { margin-top: 0; margin-bottom: 2%; }
        .alert { margin-bottom: 2%; }
        .form-group { margin-bottom: 1.5%; }
        
        /* Contrôler la largeur maximale du formulaire (pour ne pas qu'il soit trop étendu sur grand écran) */
        form {
            max-width: 50%; /* Le formulaire occupe 50% de la page (ou du conteneur) */
            padding-top: 1%;
        }

        /* Ajustement des boutons */
        .btn {
            margin-right: 1%;
        }

        /* ------------------------------------------------ */
        /* @media queries pour la réactivité */
        /* ------------------------------------------------ */

        @media (max-width: 992px) { 
            :root {
                --sidebar-width: 0; 
            }
            .main-content-wrapper {
                margin-left: 0; 
                width: 100%;
            }
            .page-container-content {
                width: 100%;
                padding: 1%;
            }
            form {
                max-width: 95%; /* Formulaire plus large sur les écrans moyens */
            }
        }
        
        @media (max-width: 576px) { 
            form {
                max-width: 100%;
            }
            /* Empiler les boutons sur les très petits écrans */
            .btn {
                display: block;
                width: 100%;
                margin-right: 0;
                margin-bottom: 1%;
            }
        }
    </style>
</head>
<body>
<div class="main-content-wrapper">
    <div class="page-container-content">
        <h2>&nbsp; </BR> Modifier votre mot de passe</h2>

        <?php if ($message_success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message_success); ?>
            </div>
        <?php endif; ?>

        <?php if ($message_erreur): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($message_erreur); ?>
            </div>
        <?php endif; ?>

        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
            <div class="form-group">
                <label for="current_password">Mot de passe actuel :</label>
                <input type="password" id="current_password" name="current_password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="new_password">Nouveau mot de passe :</label>
                <input type="password" id="new_password" name="new_password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmer le nouveau mot de passe :</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">
                </span> Modifier le mot de passe
            </button>
            <?php 
            // On utilise generateUrl si elle existe, sinon un chemin de fallback
            $dashboard_url = function_exists('generateUrl') ? generateUrl('pages/dashboard.php') : '../../pages/dashboard.php';
            ?>
            <a href="<?= $dashboard_url ?>" class="btn btn-default">
                 Annuler
            </a>
            <a href="javascript:history.back()" class="btn btn-info">
                Retour
            </a>
        </form>
    </div>
</div>

<script src="../js/jquery-3.6.0.min.js"></script>
<script src="../js/bootstrap-3.4.1.min.js"></script>
<script src="../js/select2.full.min.js"></script>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>
</body>
</html>