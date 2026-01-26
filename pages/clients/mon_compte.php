<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérification si l'utilisateur est passé par la page de login
if (!isset($_SESSION['utilisateur_id'])) {
    // Option A : Rediriger vers le login (Recommandé)
    // header('Location: ../../login.php'); exit(); 
    
    // Option B : Message d'erreur explicite pour le développement
    die("Désolé, votre session a expiré ou vous n'êtes pas connecté. Veuillez retourner à la page de connexion.");
}

require_once '../../fonctions/database.php';

$id_user = $_SESSION['utilisateur_id'];
$message = '';
$message_type = '';

// 3. Récupération des données
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
        die("Erreur critique : Votre compte n'existe plus dans la base de données.");
    }

} catch (Exception $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// 4. Traitement du formulaire de mot de passe
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
                
                $user['mot_de_passe'] = $hashed_pwd; // Mise à jour locale
                $message = "Mot de passe mis à jour avec succès.";
                $message_type = "success";
            } else {
                $message = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
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

// 5. Inclusion des templates
include '../../templates/header.php';
include '../../templates/navigation.php';
// include '../../templates/navigation.php'; // Décommentez si nécessaire
?>
</BR></BR></BR>
<div class="container-fluid" style="margin-top: 20px;">
    
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="panel panel-default shadow-sm">
                <div class="panel-body text-center" style="background: #f8f9fa;">
                    <div style="font-size: 80px; color: #bdc3c7; margin-bottom:15px;">
                        
                    </div>
                    <h3><?= htmlspecialchars($user['nom_complet']) ?></h3>
                    <p><span class="label label-primary"><?= htmlspecialchars($user['nom_role'] ?? 'Agent') ?></span></p>
                    <hr>
                    <div class="text-left">
                        <p><strong> Agence :</strong> <?= htmlspecialchars($user['nom_agence'] ?? 'Non assignée') ?></p>
                        <p><strong><span class="glyphicon glyphicon-envelope"></span> Email :</strong> <?= htmlspecialchars($user['email'] ?? 'N/A') ?></p>
                        <p><strong>Tel :</strong> <?= htmlspecialchars($user['telephone'] ?? 'N/A') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading" style="background: #2c3e50; color: white;">
                    <h3 class="panel-title">Sécurité du compte</h3>
                </div>
                <div class="panel-body">
                    <form method="POST">
                        <legend>Changer le mot de passe</legend>
                        <div class="form-group">
                            <label>Mot de passe actuel</label>
                            <input type="password" name="old_password" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nouveau mot de passe</label>
                                    <input type="password" name="new_password" class="form-control" required minlength="6">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Confirmation</label>
                                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="update_password" class="btn btn-warning btn-block">Mettre à jour</button>
                    </form>

                    <hr>
                    <h4>Activités récentes</h4>
                    <table class="table table-striped table-condensed">
                        <thead>
                            <tr><th>Date</th><th>Action</th><th>Table</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $logs = $pdo->prepare("SELECT * FROM logs_activite WHERE id_utilisateur = ? ORDER BY date_action DESC LIMIT 5");
                            $logs->execute([$id_user]);
                            while ($l = $logs->fetch()):
                            ?>
                            <tr>
                                <td><small><?= date('d/m/Y H:i', strtotime($l['date_action'])) ?></small></td>
                                <td><?= htmlspecialchars($l['action']) ?></td>
                                <td><span class="label label-default"><?= htmlspecialchars($l['table_concernée']) ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>