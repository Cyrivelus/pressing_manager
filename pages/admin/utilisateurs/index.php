<?php
// pages/admin/utilisateurs/index.php

// Démarrer la session pour la gestion de l'authentification
session_start();

// Inclure les fichiers nécessaires
require_once(__DIR__ . '/../../../fonctions/database.php');
require_once(__DIR__ . '/../../../fonctions/gestion_utilisateurs.php');

// Vérifier si l'utilisateur est connecté et est un administrateur (patron)
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'patron') {
    // Rediriger si non autorisé
    header("Location: ../../../index.php?error=Accès non autorisé");
    exit();
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once(__DIR__ . '/../../../fonctions/gestion_utilisateurs.php');
    
    switch ($_POST['action']) {
        case 'toggle_status':
            if (isset($_POST['id'])) {
                $result = toggleUserStatus($pdo, $_POST['id']);
                $_SESSION['flash_message'] = $result['message'];
                $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
            }
            break;
            
        case 'reset_password':
            if (isset($_POST['id'])) {
                $result = resetUserPassword($pdo, $_POST['id']);
                $_SESSION['flash_message'] = $result['message'];
                $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
            }
            break;
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Récupérer la liste de tous les utilisateurs avec leurs rôles
$utilisateurs = getTousLesUtilisateurs($pdo);

// Définir un seuil pour "connecté récemment" (15 minutes)
$seuil_minutes = 15;
$seuil_timestamp = date('Y-m-d H:i:s', strtotime("-{$seuil_minutes} minutes"));

// Configuration de la page
$title = "Gestion des Utilisateurs";
// Inclure les templates APRÈS tout le traitement PHP
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title> Kayade | Administration - Gestion des Utilisateurs</title>
    <link rel="shortcut icon" href="../../../images/Logo Kayade.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="../../../css/bootstrap-3.4.1.min.css">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/admin_style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* Style pour l'indicateur "Connecté" */
        .status-dot {
            height: 12px;
            width: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-online {
            background-color: #28a745; /* Vert pour connecté */
        }
        .status-offline {
            background-color: #6c757d; /* Gris pour déconnecté */
        }
        .status-inactive {
            background-color: #ffc107; /* Jaune pour inactif */
        }
        
        /* Styles pour le tableau */
        .table-responsive {
            margin-top: 20px;
        }
        
        .table th {
            background-color: #f8f9fa;
            border-top: 2px solid #dee2e6;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        /* Badges pour les rôles */
        .badge-role {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
        }
        
        .badge-patron {
            background-color: #dc3545;
            color: white;
        }
        
        .badge-receptionniste {
            background-color: #007bff;
            color: white;
        }
        
        .badge-caissier {
            background-color: #28a745;
            color: white;
        }
        
        .badge-stock {
            background-color: #6f42c1;
            color: white;
        }
        
        .badge-employe {
            background-color: #17a2b8;
            color: white;
        }
        
        /* Boutons d'action */
        .btn-action {
            margin: 2px;
            font-size: 0.8em;
        }
        
        /* Modal styles */
        .modal-content {
            border-radius: 8px;
        }
        
        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .btn-action {
                display: block;
                width: 100%;
                margin-bottom: 5px;
            }
            
            .table-responsive {
                font-size: 0.9em;
            }
        }
        
        /* Filtres */
        .filter-container {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .filter-label {
            font-weight: bold;
            margin-right: 10px;
        }
        
        /* Statistiques */
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stats-number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <?php include(__DIR__ . '/../../../templates/header.php'); ?>
    <?php include(__DIR__ . '/../../../templates/navigation.php'); ?>
    <br> <br> <br> 
    <div class="container-fluid">
        <div class="row">
            <main role="main" class="col-md-12 ml-sm-auto col-lg-12 px-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestion des Utilisateurs</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group mr-2">
                            <a href="ajouter.php" class="btn btn-sm btn-primary">
                                <span class="glyphicon glyphicon-plus"></span> Ajouter un Utilisateur
                            </a>
                        </div>
                    </div>
                </div>

                <?php
                // Afficher les messages flash de session
                if (isset($_SESSION['flash_message'])) {
                    $alertClass = ($_SESSION['flash_type'] === 'success') ? 'alert-success' : 'alert-danger';
                    echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
                    echo htmlspecialchars($_SESSION['flash_message']);
                    echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
                    echo '<span aria-hidden="true">&times;</span>';
                    echo '</button>';
                    echo '</div>';
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                }
                ?>

                <!-- Statistiques -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number"><?= count($utilisateurs) ?></div>
                            <div class="stats-label">Utilisateurs totaux</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number">
                                <?= count(array_filter($utilisateurs, function($u) use ($seuil_timestamp) {
                                    return $u['derniere_connexion'] && $u['derniere_connexion'] > $seuil_timestamp;
                                })) ?>
                            </div>
                            <div class="stats-label">Connectés récemment</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number">
                                <?= count(array_filter($utilisateurs, function($u) {
                                    return $u['est_actif'] == 1;
                                })) ?>
                            </div>
                            <div class="stats-label">Actifs</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number">
                                <?= count(array_filter($utilisateurs, function($u) {
                                    return $u['est_actif'] == 0;
                                })) ?>
                            </div>
                            <div class="stats-label">Inactifs</div>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="filter-container">
                    <form method="GET" class="form-inline">
                        <div class="form-group mr-3">
    <label class="filter-label">Statut:</label>
    <select name="statut" 
            class="form-control" 
            style="height: 40px !important; padding: 5px 15px; font-size: 1.5rem;" 
            onchange="this.form.submit()">
        <option value="">Tous</option>
        <option value="actif" <?= isset($_GET['statut']) && $_GET['statut'] == 'actif' ? 'selected' : '' ?>>Actifs</option>
        <option value="inactif" <?= isset($_GET['statut']) && $_GET['statut'] == 'inactif' ? 'selected' : '' ?>>Inactifs</option>
    </select>
</div>
                        <div class="form-group mr-3">
                            <label class="filter-label">Rôle:</label>
                            <select name="role" class="form-control form-control-sm" 
        style="height: auto; padding: 10px 12px; min-height: 40px;" 
        onchange="this.form.submit()">
                                <option value="">Tous</option>
                                <option value="patron" <?= isset($_GET['role']) && $_GET['role'] == 'patron' ? 'selected' : '' ?>>Patron</option>
                                <option value="receptionniste" <?= isset($_GET['role']) && $_GET['role'] == 'receptionniste' ? 'selected' : '' ?>>Réceptionniste</option>
                                <option value="caissier" <?= isset($_GET['role']) && $_GET['role'] == 'caissier' ? 'selected' : '' ?>>Caissier</option>
                                <option value="gestionnaire_stock" <?= isset($_GET['role']) && $_GET['role'] == 'gestionnaire_stock' ? 'selected' : '' ?>>Gestionnaire Stock</option>
                                <option value="employe_pressing" <?= isset($_GET['role']) && $_GET['role'] == 'employe_pressing' ? 'selected' : '' ?>>Employé Pressing</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Rechercher..." 
                                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                            <button type="submit" class="btn btn-sm btn-primary ml-2">
                             Rechercher
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tableau des utilisateurs -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom complet</th>
                                <th>Login</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Dernière connexion</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Filtrer les utilisateurs selon les critères
                            $utilisateurs_filtres = $utilisateurs;
                            
                            if (isset($_GET['statut']) && $_GET['statut'] == 'actif') {
                                $utilisateurs_filtres = array_filter($utilisateurs_filtres, function($u) {
                                    return $u['est_actif'] == 1;
                                });
                            } elseif (isset($_GET['statut']) && $_GET['statut'] == 'inactif') {
                                $utilisateurs_filtres = array_filter($utilisateurs_filtres, function($u) {
                                    return $u['est_actif'] == 0;
                                });
                            }
                            
                            if (isset($_GET['role']) && $_GET['role']) {
    // Retrait du "use ($_GET)" car $_GET est auto-global
    $utilisateurs_filtres = array_filter($utilisateurs_filtres, function($u) {
        return $u['nom_role'] == $_GET['role'];
    });
}
                            
                            if (isset($_GET['search']) && $_GET['search']) {
                                $search = strtolower($_GET['search']);
                                $utilisateurs_filtres = array_filter($utilisateurs_filtres, function($u) use ($search) {
                                    return stripos($u['nom_complet'], $search) !== false || 
                                           stripos($u['login_utilisateur'], $search) !== false ||
                                           stripos($u['email'], $search) !== false;
                                });
                            }
                            
                            if (empty($utilisateurs_filtres)): 
                            ?>
                                <tr>
                                    <td colspan="8" class="text-center">
                                        Aucun utilisateur trouvé.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($utilisateurs_filtres as $utilisateur): ?>
                                    <?php
                                    // Déterminer le statut de connexion
                                    $est_connecte_recemment = $utilisateur['derniere_connexion'] && 
                                                               $utilisateur['derniere_connexion'] > $seuil_timestamp;
                                    
                                    // Classe CSS pour le badge de rôle
                                    $badge_class = 'badge-secondary';
                                    switch ($utilisateur['nom_role']) {
                                        case 'patron': $badge_class = 'badge-patron'; break;
                                        case 'receptionniste': $badge_class = 'badge-receptionniste'; break;
                                        case 'caissier': $badge_class = 'badge-caissier'; break;
                                        case 'gestionnaire_stock': $badge_class = 'badge-stock'; break;
                                        case 'employe_pressing': $badge_class = 'badge-employe'; break;
                                    }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($utilisateur['id_utilisateur']) ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($utilisateur['nom_complet']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($utilisateur['login_utilisateur']) ?></td>
                                        <td><?= htmlspecialchars($utilisateur['email'] ?? 'Non renseigné') ?></td>
                                        <td>
                                            <span class="badge-role <?= $badge_class ?>">
                                                <?= htmlspecialchars($utilisateur['nom_role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($utilisateur['derniere_connexion']): ?>
                                                <?= date('d/m/Y H:i', strtotime($utilisateur['derniere_connexion'])) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Jamais connecté</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-dot <?= $est_connecte_recemment ? 'status-online' : 'status-offline' ?>"></span>
                                            <?= $est_connecte_recemment ? 'En ligne' : 'Hors ligne' ?>
                                            <br>
                                            <small class="<?= $utilisateur['est_actif'] ? 'text-success' : 'text-danger' ?>">
                                                <?= $utilisateur['est_actif'] ? 'Actif' : 'Inactif' ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="modifier.php?id=<?= $utilisateur['id_utilisateur'] ?>" 
                                                   class="btn btn-sm btn-outline-primary btn-action" 
                                                   title="Modifier">
                                                  Modifier
                                                </a>
                                                
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Êtes-vous sûr de vouloir <?= $utilisateur['est_actif'] ? 'désactiver' : 'activer' ?> cet utilisateur ?');">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="id" value="<?= $utilisateur['id_utilisateur'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-<?= $utilisateur['est_actif'] ? 'warning' : 'success' ?> btn-action"
                                                            title="<?= $utilisateur['est_actif'] ? 'Désactiver' : 'Activer' ?>">
                                                        <span class="-<?= $utilisateur['est_actif'] ? 'remove' : 'ok' ?>">Désactiver</span>
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Réinitialiser le mot de passe de cet utilisateur ? Un email sera envoyé.');">
                                                    <input type="hidden" name="action" value="reset_password">
                                                    <input type="hidden" name="id" value="<?= $utilisateur['id_utilisateur'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-info btn-action" 
                                                            title="Réinitialiser mot de passe">
                                                       Réinitialiser
                                                    </button>
                                                </form>
                                                
                                                <?php if ($utilisateur['id_utilisateur'] != $_SESSION['utilisateur_id']): ?>
                                                    <?php
// Connexion à la base de données (à adapter avec vos variables)
// $pdo = new PDO(...);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id_a_supprimer = intval($_POST['id']);

    try {
        // Option A : Suppression logique (Recommandé pour garder l'historique)
        // On ne supprime pas la ligne, on met est_actif à FALSE
        $sql = "UPDATE utilisateurs SET est_actif = FALSE WHERE id_utilisateur = :id";
        
        /* // Option B : Suppression physique (Si vous tenez vraiment à effacer)
        // Attention : Cela peut échouer si l'utilisateur a créé des tickets
        $sql = "DELETE FROM utilisateurs WHERE id_utilisateur = :id"; 
        */

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id_a_supprimer]);

        // Redirection avec message de succès
        header("Location: liste_utilisateurs.php?msg=success");
        exit();

    } catch (PDOException $e) {
        // En cas d'erreur de contrainte SQL
        $error = "Erreur : Impossible de supprimer cet utilisateur car il est lié à d'autres enregistrements (Agences, Tickets...).";
    }
}
?>
                                                   <form method="POST" action="supprimer.php" style="display: inline;" 
      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
    
    <input type="hidden" name="id" value="<?= $utilisateur['id_utilisateur'] ?>">
    
    <button type="submit" class="btn btn-sm btn-outline-danger btn-action" title="Supprimer">
        Supprimer
    </button>
</form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Modal pour la réinitialisation de mot de passe -->
                <div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="resetPasswordModalLabel">Réinitialiser le mot de passe</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="resetPasswordForm">
                                    <input type="hidden" name="user_id" id="reset_user_id">
                                    <div class="form-group">
                                        <label for="new_password">Nouveau mot de passe:</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_password">Confirmer le mot de passe:</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                                <button type="button" class="btn btn-primary" onclick="submitResetPassword()">Réinitialiser</button>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <?php include(__DIR__ . '/../../../templates/footer.php'); ?>

    <script src="../../../js/jquery-1.12.4.min.js"></script>
    <script src="../../../js/bootstrap-3.4.1.min.js"></script>
    <script>
        // Fonction pour afficher la modal de réinitialisation de mot de passe
        function showResetPasswordModal(userId, userName) {
            $('#reset_user_id').val(userId);
            $('#resetPasswordModalLabel').text('Réinitialiser le mot de passe pour ' + userName);
            $('#resetPasswordModal').modal('show');
        }
        
        // Fonction pour soumettre le formulaire de réinitialisation
        function submitResetPassword() {
            var form = $('#resetPasswordForm');
            var newPassword = $('#new_password').val();
            var confirmPassword = $('#confirm_password').val();
            
            if (newPassword !== confirmPassword) {
                alert('Les mots de passe ne correspondent pas.');
                return;
            }
            
            if (newPassword.length < 6) {
                alert('Le mot de passe doit contenir au moins 6 caractères.');
                return;
            }
            
            // Soumettre le formulaire via AJAX
            $.ajax({
                url: 'reset_password.php',
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        alert(result.message);
                        $('#resetPasswordModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Erreur: ' + result.message);
                    }
                },
                error: function() {
                    alert('Erreur lors de la communication avec le serveur.');
                }
            });
        }
        
        // Initialiser les tooltips
        $(function () {
            $('[title]').tooltip();
        });
        
        // Confirmation pour les actions critiques
        $(document).on('submit', 'form[onsubmit*="confirm"]', function(e) {
            var confirmMessage = $(this).attr('onsubmit').match(/confirm\('([^']+)'/);
            if (confirmMessage && confirmMessage[1]) {
                return confirm(confirmMessage[1]);
            }
            return true;
        });
    </script>
</body>
</html>