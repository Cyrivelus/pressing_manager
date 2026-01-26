<?php
/**
 * Page : Mon Compte (Profil Utilisateur)
 * Améliorations : Responsive Design, Sécurité accrue, Gestion des chemins
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Définition des chemins dynamiques (évite les erreurs XAMPP/Linux)
$base_dir = dirname(__DIR__, 2); // Remonte de 2 niveaux depuis /pages/caisse/
require_once $base_dir . '/fonctions/database.php';

// Vérification de session
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../login.php');
    exit();
}

$id_user = $_SESSION['utilisateur_id'];
$message = '';
$message_type = '';

// 2. Récupération des données utilisateur
try {
    $stmt = $pdo->prepare("
        SELECT u.*, r.nom_role, a.nom_agence 
        FROM utilisateurs u 
        LEFT JOIN roles r ON u.id_role = r.id_role 
        LEFT JOIN agences a ON u.code_agence = a.nom_agence OR u.code_agence = CAST(a.id_agence AS CHAR)
        WHERE u.id_utilisateur = ?
    ");
    $stmt->execute([$id_user]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header('Location: ../../login.php?error=user_not_found');
        exit();
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Une erreur système est survenue.");
}

// 3. Traitement du changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $old_pwd = $_POST['old_password'];
    $new_pwd = $_POST['new_password'];
    $conf_pwd = $_POST['confirm_password'];

    if (password_verify($old_pwd, $user['mot_de_passe'])) {
        if ($new_pwd === $conf_pwd) {
            if (strlen($new_pwd) >= 6) {
                $hashed_pwd = password_hash($new_pwd, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id_utilisateur = ?");
                $update->execute([$hashed_pwd, $id_user]);
                
                $user['mot_de_passe'] = $hashed_pwd;
                $message = "Mot de passe mis à jour avec succès.";
                $message_type = "success";
            } else {
                $message = "Le nouveau mot de passe doit faire au moins 6 caractères.";
                $message_type = "danger";
            }
        } else {
            $message = "Les nouveaux mots de passe ne correspondent pas.";
            $message_type = "danger";
        }
    } else {
        $message = "L'ancien mot de passe est incorrect.";
        $message_type = "danger";
    }
}

// 4. Inclusion des headers
include $base_dir . '/templates/header.php';
include $base_dir . '/templates/navigation.php';
?>

<style>
    .profile-card { border-radius: 15px; overflow: hidden; border: none; }
    .profile-header { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 30px; }
    .avatar-circle { width: 100px; height: 100px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 40px; border: 3px solid rgba(255,255,255,0.5); }
    .info-list p { border-bottom: 1px solid #f1f1f1; padding: 10px 0; margin-bottom: 0; }
    .info-list p:last-child { border-bottom: none; }
    .btn-update { border-radius: 25px; padding: 10px 25px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
</style>

<div class="container py-4">
    <br> <br> <br> 
    <?php if ($message): ?>
        <br> <br> <br> 
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show shadow-sm" role="alert">
            <strong></strong> <?= $message ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
<br> <br> <br> 
    <div class="row">
        <div class="col-lg-4 col-md-5 mb-4">
            <div class="card shadow profile-card">
                <div class="profile-header text-center">
                    <div class="avatar-circle">
                      
                    </div>
                    <h4 class="mb-1"><?= htmlspecialchars($user['nom_complet']) ?></h4>
                    <span class="badge badge-pill badge-light text-dark px-3"><?= htmlspecialchars($user['nom_role']) ?></span>
                </div>
                <div class="card-body info-list">
                    <p><strong> Agence:</strong> <span class="float-right text-muted"><?= htmlspecialchars($user['nom_agence'] ?? 'N/A') ?></span></p>
                    <p><strong Email:</strong> <br><small class="text-muted"><?= htmlspecialchars($user['email']) ?></small></p>
                    <p><strong> Tel:</strong> <span class="float-right text-muted"><?= htmlspecialchars($user['telephone']) ?></span></p>
                </div>
            </div>
        </div>

        <div class="col-lg-8 col-md-7">
            <div class="card shadow profile-card">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary">Sécurité du compte</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <div class="form-group mb-4">
                            <label class="font-weight-bold">Ancien mot de passe</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"></span>
                                </div>
                                <input type="password" name="old_password" class="form-control" placeholder="Entrez le mot de passe actuel" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="font-weight-bold">Nouveau mot de passe</label>
                                    <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 caractères" required minlength="6">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="font-weight-bold">Confirmer le nouveau</label>
                                    <input type="password" name="confirm_password" class="form-control" placeholder="Répétez le mot de passe" required minlength="6">
                                </div>
                            </div>
                        </div>

                        <div class="text-right mt-3">
                            <button type="submit" name="update_password" class="btn btn-primary btn-update shadow-sm">
                           Mettre à jour
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="alert alert-light mt-4 border shadow-sm">
                <small class="text-muted">
                  
                    Conseil : Utilisez un mot de passe complexe mélangeant lettres, chiffres et symboles pour protéger l'accès à votre caisse.
                </small>
            </div>
        </div>
    </div>
</div>

<?php 
// Inclusion sécurisée du footer
if (file_exists($base_dir . '/templates/footer.php')) {
    include $base_dir . '/templates/footer.php';
} else {
    echo "</div></body></html>"; // Fallback si le fichier est manquant
}
?>