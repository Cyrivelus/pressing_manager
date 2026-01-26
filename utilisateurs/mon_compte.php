<?php
// pages/utilisateurs/mon_compte.php

// 1. Démarrage de la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}



// 2. Inclusions avec chemins sécurisés
// Correction : utiliser le chemin correct vers database.php
$database_path = __DIR__ . '/../../fonctions/database.php';
if (!file_exists($database_path)) {
    die("Erreur : Fichier database.php introuvable à l'emplacement : $database_path");
}
require_once $database_path;

// 3. Initialisation
$id_user = $_SESSION['utilisateur_id'];
$message = '';
$message_type = '';

// 4. Récupération des données de l'utilisateur
try {
    if (!isset($pdo)) {
        die("Erreur : Connexion à la base de données non établie.");
    }
    
    $stmt = $pdo->prepare("
        SELECT u.*, r.nom_role 
        FROM utilisateurs u 
        LEFT JOIN roles r ON u.id_role = r.id_role 
        WHERE u.id_utilisateur = ?
    ");
    $stmt->execute([$id_user]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        die("Erreur critique : Votre compte n'existe plus dans la base de données.");
    }

} catch (Exception $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// 5. Traitement du formulaire de changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $old_pwd = $_POST['old_password'] ?? '';
    $new_pwd = $_POST['new_password'] ?? '';
    $conf_pwd = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($old_pwd) || empty($new_pwd) || empty($conf_pwd)) {
        $message = "Tous les champs sont obligatoires.";
        $message_type = "danger";
    } elseif (!password_verify($old_pwd, $user['mot_de_passe'])) {
        $message = "L'ancien mot de passe est incorrect.";
        $message_type = "danger";
    } elseif ($new_pwd !== $conf_pwd) {
        $message = "Les nouveaux mots de passe ne correspondent pas.";
        $message_type = "danger";
    } elseif (strlen($new_pwd) < 6) {
        $message = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
        $message_type = "danger";
    } else {
        // Tout est valide, procéder à la mise à jour
        $hashed_pwd = password_hash($new_pwd, PASSWORD_DEFAULT);
        try {
            $update = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id_utilisateur = ?");
            if ($update->execute([$hashed_pwd, $id_user])) {
                $user['mot_de_passe'] = $hashed_pwd; // Mise à jour locale
                $message = "Mot de passe mis à jour avec succès.";
                $message_type = "success";
            } else {
                $message = "Erreur lors de la mise à jour du mot de passe.";
                $message_type = "danger";
            }
        } catch (Exception $e) {
            $message = "Erreur technique : " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// 6. Inclusions des templates (Header et Navigation)
$header_path = __DIR__ . '/../../templates/header.php';
$navigation_path = __DIR__ . '/../../templates/navigation.php';

if (!file_exists($header_path)) {
    die("Erreur : Fichier header.php introuvable à l'emplacement : $header_path");
}
if (!file_exists($navigation_path)) {
    die("Erreur : Fichier navigation.php introuvable à l'emplacement : $navigation_path");
}

require_once $header_path;
require_once $navigation_path;
?>

<div class="container" style="margin-top: 20px; padding-left: 220px;">
    
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade in shadow-sm" role="alert" style="border-radius: 8px;">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <i class="fa fa-info-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0" style="border-radius: 10px;">
                <div class="card-body text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px 10px 0 0;">
                    <div style="font-size: 80px; margin: 20px 0; opacity: 0.8;">
                        <i class="fa fa-user-circle"></i>
                    </div>
                    <h3 style="margin-top: 0; font-weight: 600;"><?= htmlspecialchars($user['nom_complet']) ?></h3>
                    <p><span class="badge" style="background: rgba(255,255,255,0.2); font-size: 0.9em; padding: 5px 15px;"><?= htmlspecialchars($user['nom_role'] ?? 'Agent') ?></span></p>
                </div>
                <div class="card-body" style="background: #f8f9fa; border-radius: 0 0 10px 10px;">
                    <div class="text-left" style="padding: 10px;">
                        <p><strong><i class="fa fa-briefcase text-muted"></i> Agence :</strong><br>
                        <span class="text-muted"><?= htmlspecialchars($user['nom_agence'] ?? $user['code_agence'] ?? 'Non assignée') ?></span></p>
                        
                        <p><strong><i class="fa fa-envelope text-muted"></i> Email :</strong><br>
                        <span class="text-muted"><?= htmlspecialchars($user['email'] ?? 'Non renseigné') ?></span></p>
                        
                        <p><strong><i class="fa fa-phone text-muted"></i> Téléphone :</strong><br>
                        <span class="text-muted"><?= htmlspecialchars($user['telephone'] ?? 'Non renseigné') ?></span></p>
                        
                        <p><strong><i class="fa fa-calendar text-muted"></i> Date création :</strong><br>
                        <span class="text-muted"><?= date('d/m/Y', strtotime($user['date_creation'])) ?></span></p>
                        
                        <p><strong><i class="fa fa-sign-in-alt text-muted"></i> Dernière connexion :</strong><br>
                        <span class="text-muted">
                            <?= $user['derniere_connexion'] ? date('d/m/Y H:i', strtotime($user['derniere_connexion'])) : 'Jamais' ?>
                        </span></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm" style="border-radius: 10px;">
                <div class="card-header" style="background: linear-gradient(90deg, #2c3e50, #3498db); color: white; border: none; border-radius: 10px 10px 0 0;">
                    <h3 class="card-title mb-0"><i class="fa fa-lock"></i> Sécurité du compte</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <h4 style="font-weight: 600; color: #2c3e50; margin-bottom: 20px;">
                            <i class="fa fa-key"></i> Changer votre mot de passe
                        </h4>
                        
                        <div class="form-group">
                            <label>Ancien mot de passe <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                                <input type="password" name="old_password" class="form-control" 
                                       placeholder="Entrez votre mot de passe actuel" required 
                                       style="border-radius: 0 5px 5px 0;">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nouveau mot de passe <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-key"></i></span>
                                        <input type="password" name="new_password" class="form-control" 
                                               placeholder="6 caractères minimum" required minlength="6"
                                               style="border-radius: 0 5px 5px 0;">
                                    </div>
                                    <small class="text-muted">Minimum 6 caractères</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Confirmation <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-key"></i></span>
                                        <input type="password" name="confirm_password" class="form-control" 
                                               placeholder="Confirmez le nouveau mot de passe" required minlength="6"
                                               style="border-radius: 0 5px 5px 0;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-top: 20px;">
                            <button type="submit" name="update_password" class="btn btn-warning btn-block shadow-sm"
                                    style="border-radius: 8px; padding: 12px; font-weight: 600; font-size: 1.1em;">
                                <i class="fa fa-save"></i> Mettre à jour le mot de passe
                            </button>
                        </div>
                    </form>

                    <hr style="border-color: #e0e0e0; margin: 30px 0;">
                    
                    <h4 style="color: #2c3e50; font-weight: 600; margin-bottom: 20px;">
                        <i class="fa fa-history"></i> Mes activités récentes
                    </h4>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" style="font-size: 0.95em; border-radius: 8px; overflow: hidden;">
                            <thead>
                                <tr style="background: linear-gradient(90deg, #2c3e50, #3498db); color: white;">
                                    <th style="border: none; padding: 12px 15px;">Date</th>
                                    <th style="border: none; padding: 12px 15px;">Action</th>
                                    <th style="border: none; padding: 12px 15px;">Table concernée</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    // Vérifier si la table logs_activite existe
                                    $table_exists = $pdo->query("SHOW TABLES LIKE 'logs_activite'")->rowCount() > 0;
                                    
                                    if ($table_exists) {
                                        $logs = $pdo->prepare("
                                            SELECT * FROM logs_activite 
                                            WHERE id_utilisateur = ? 
                                            ORDER BY date_action DESC 
                                            LIMIT 5
                                        ");
                                        $logs->execute([$id_user]);
                                        $count = 0;
                                        
                                        while ($l = $logs->fetch(PDO::FETCH_ASSOC)):
                                            $count++;
                                            $bg_color = $count % 2 == 0 ? '#f8f9fa' : 'white';
                                ?>
                                <tr style="background-color: <?= $bg_color ?>;">
                                    <td style="padding: 12px 15px;">
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($l['date_action'])) ?>
                                        </small>
                                    </td>
                                    <td style="padding: 12px 15px;">
                                        <?= htmlspecialchars($l['action']) ?>
                                    </td>
                                    <td style="padding: 12px 15px;">
                                        <span class="badge" style="background: #e74c3c; color: white; padding: 5px 10px;">
                                            <?= htmlspecialchars($l['table_concernée']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php 
                                        endwhile;
                                        
                                        if ($count == 0) {
                                            echo '<tr><td colspan="3" class="text-center text-muted" style="padding: 20px;">Aucune activité enregistrée.</td></tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="3" class="text-center text-muted" style="padding: 20px;">Table des logs non disponible.</td></tr>';
                                    }
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="3" class="text-center text-danger" style="padding: 20px;">Erreur lors de la récupération des logs.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Section informations complémentaires -->
                    <div class="row" style="margin-top: 30px;">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm" style="background: #f8f9fa; border-radius: 8px;">
                                <div class="card-body">
                                    <h5 style="color: #2c3e50; font-weight: 600;">
                                        <i class="fa fa-info-circle"></i> Statut du compte
                                    </h5>
                                    <p>
                                        <span class="badge <?= $user['est_actif'] ? 'bg-success' : 'bg-danger' ?>" 
                                              style="padding: 8px 15px; font-size: 0.9em;">
                                            <?= $user['est_actif'] ? 'ACTIF' : 'INACTIF' ?>
                                        </span>
                                    </p>
                                    <p class="text-muted" style="font-size: 0.9em;">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        Tentatives d'échec : <?= $user['tentatives_echec'] ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm" style="background: #f8f9fa; border-radius: 8px;">
                                <div class="card-body">
                                    <h5 style="color: #2c3e50; font-weight: 600;">
                                        <i class="fa fa-shield-alt"></i> Conseils de sécurité
                                    </h5>
                                    <ul class="text-muted" style="font-size: 0.9em; padding-left: 20px;">
                                        <li>Changez votre mot de passe régulièrement</li>
                                        <li>Utilisez un mot de passe complexe</li>
                                        <li>Ne partagez jamais vos identifiants</li>
                                        <li>Déconnectez-vous après utilisation</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript pour améliorer l'expérience utilisateur -->
<script>
$(document).ready(function() {
    // Animation pour les alertes
    $('.alert').fadeIn('slow');
    
    // Toggle pour afficher/masquer les mots de passe
    $('.toggle-password').click(function() {
        var input = $(this).closest('.input-group').find('input');
        var icon = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Validation du formulaire en temps réel
    $('form').on('submit', function(e) {
        var newPwd = $('input[name="new_password"]').val();
        var confirmPwd = $('input[name="confirm_password"]').val();
        
        if (newPwd !== confirmPwd) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas !');
            $('input[name="confirm_password"]').focus().addClass('is-invalid');
        }
    });
});
</script>

<?php 
// Inclure le footer
$footer_path = __DIR__ . '/../../templates/footer.php';
if (file_exists($footer_path)) {
    require_once $footer_path;
} else {
    echo '<footer class="mt-4 text-center text-muted" style="padding: 20px;">
            <p>&copy; ' . date('Y') . ' Pressing Manager Pro</p>
          </footer>';
}
?>