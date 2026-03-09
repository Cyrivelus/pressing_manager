<?php
// pages/admin/utilisateurs/index.php
session_start();
require_once(__DIR__ . '/../../../fonctions/database.php');
require_once(__DIR__ . '/../../../fonctions/gestion_utilisateurs.php');

if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'patron') {
    header("Location: ../../../index.php?error=Accès non autorisé");
    exit();
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
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

$utilisateurs = getTousLesUtilisateurs($pdo);
$seuil_minutes = 15;
$seuil_timestamp = date('Y-m-d H:i:s', strtotime("-{$seuil_minutes} minutes"));
$title = "Gestion des Utilisateurs";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Kayade | Administration</title>
    <link rel="stylesheet" href="../../../css/bootstrap-3.4.1.min.css">
    <link rel="stylesheet" href="../../../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --primary-color: #2c3e50; /* Bleu pro */
            --accent-color: #3498db;  /* Bleu clair */
            --bg-light: #f4f7f6;
            --border-color: #dee2e6;
        }

        body { background-color: var(--bg-light); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        /* Conteneurs épurés */
        .stats-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .stats-number { font-size: 24px; font-weight: 600; color: var(--primary-color); }
        .stats-label { color: #7f8c8d; text-transform: uppercase; font-size: 11px; letter-spacing: 1px; }

        /* Tableau professionnel */
        .table-container { background: #fff; border-radius: 4px; border: 1px solid var(--border-color); padding: 15px; }
        .table thead th { 
            background-color: #fff; 
            color: var(--primary-color); 
            text-transform: uppercase; 
            font-size: 12px; 
            border-bottom: 2px solid var(--primary-color) !important;
        }
        
        /* Suppression des couleurs kitch - Badges sobres */
        .badge-role {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #eceff1;
            color: #546e7a;
            border: 1px solid #cfd8dc;
        }
        .badge-patron { background: #feebee; color: #c62828; border-color: #ffcdd2; }

        /* Statut connexion */
        .status-dot {
            height: 10px; width: 10px; border-radius: 50%;
            display: inline-block; margin-right: 5px;
        }
        .status-online { background-color: #27ae60; }
        .status-offline { background-color: #bdc3c7; }

        /* Filtres */
        .filter-container { background: #fff; padding: 20px; border-bottom: 1px solid var(--border-color); margin-bottom: 0; }
        .form-control { border-radius: 2px; box-shadow: none; border: 1px solid #ccc; height: 34px !important; }
        
        /* Boutons */
        .btn { border-radius: 2px; font-weight: 600; text-transform: uppercase; font-size: 11px; }
        .btn-primary { background: var(--primary-color); border: none; }
        .btn-outline-primary { color: var(--accent-color); border: 1px solid var(--accent-color); background: transparent; }
        .btn-outline-primary:hover { background: var(--accent-color); color: #fff; }

        h1.h2 { color: var(--primary-color); font-weight: 300; }
    </style>
</head>
<body>
    <?php include(__DIR__ . '/../../../templates/header.php'); ?>
    <?php include(__DIR__ . '/../../../templates/navigation.php'); ?>

    <br> <br> <br>
    <div class="container-fluid">
        <div class="row">
            <main class="col-md-12 px-4">
                <div class="d-flex justify-content-between align-items-center pt-4 pb-2 mb-4 border-bottom">
                    <h1 class="h2">Annuaire Utilisateurs</h1>
                    <a href="ajouter.php" class="btn btn-primary">
                        <span class="glyphicon glyphicon-plus"></span> Nouveau compte
                    </a>
                </div>

                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        <?= htmlspecialchars($_SESSION['flash_message']) ?>
                    </div>
                    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-3 col-xs-6">
                        <div class="stats-card">
                            <div class="stats-number"><?= count($utilisateurs) ?></div>
                            <div class="stats-label">Total</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-xs-6">
                        <div class="stats-card">
                            <div class="stats-number"><?= count(array_filter($utilisateurs, fn($u) => $u['est_actif'] == 1)) ?></div>
                            <div class="stats-label">Comptes Actifs</div>
                        </div>
                    </div>
                </div>

                <div class="filter-container">
                    <form method="GET" class="form-inline">
                        <select name="statut" class="form-control" onchange="this.form.submit()">
                            <option value="">Tous les statuts</option>
                            <option value="actif" <?= @$_GET['statut']=='actif'?'selected':'' ?>>Actif</option>
                            <option value="inactif" <?= @$_GET['statut']=='inactif'?'selected':'' ?>>Inactif</option>
                        </select>
                        <select name="role" class="form-control" onchange="this.form.submit()">
                            <option value="">Tous les rôles</option>
                            <option value="patron" <?= @$_GET['role']=='patron'?'selected':'' ?>>Patron</option>
                            <option value="receptionniste" <?= @$_GET['role']=='receptionniste'?'selected':'' ?>>Réceptionniste</option>
                        </select>
                        <input type="text" name="search" class="form-control" placeholder="Rechercher un nom..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        <button type="submit" class="btn btn-default">Filtrer</button>
                    </form>
                </div>

                <div class="table-container">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Utilisateur</th>
                                    <th>Contact</th>
                                    <th>Rôle</th>
                                    <th>Dernière Visite</th>
                                    <th>Statut</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $filtres = $utilisateurs;
                                // Appliquer les filtres PHP ici (identique à votre logique existante)
                                if (isset($_GET['statut']) && $_GET['statut'] != '') {
                                    $val = ($_GET['statut'] == 'actif') ? 1 : 0;
                                    $filtres = array_filter($filtres, fn($u) => $u['est_actif'] == $val);
                                }
                                ?>
                                
                                <?php foreach ($filtres as $u): 
                                    $online = $u['derniere_connexion'] && $u['derniere_connexion'] > $seuil_timestamp;
                                ?>
                                    <tr>
                                        <td><span class="text-muted">#<?= $u['id_utilisateur'] ?></span></td>
                                        <td><strong><?= htmlspecialchars($u['nom_complet'] ?? '') ?></strong><br><small><?= htmlspecialchars($u['login_utilisateur'] ?? '') ?></small></td>
                                        <td><?= htmlspecialchars($u['email'] ?? '---') ?></td>
                                        <td><span class="badge-role <?= $u['nom_role'] == 'patron' ? 'badge-patron' : '' ?>"><?= strtoupper($u['nom_role'] ?? '') ?></span></td>
                                        <td><?= $u['derniere_connexion'] ? date('d/m/y H:i', strtotime($u['derniere_connexion'])) : 'Jamais' ?></td>
                                        <td>
                                            <span class="status-dot <?= $online ? 'status-online' : 'status-offline' ?>"></span>
                                            <small><?= $u['est_actif'] ? 'ACTIF' : 'INACTIF' ?></small>
                                        </td>
                                        <td class="text-right">
                                            <div class="btn-group">
                                                <a href="modifier.php?id=<?= $u['id_utilisateur'] ?>" class="btn btn-xs btn-outline-primary">Modifier</a>
                                                <form method="POST" style="display:inline">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="id" value="<?= $u['id_utilisateur'] ?>">
                                                    <button type="submit" class="btn btn-xs btn-default"><?= $u['est_actif'] ? 'Désactiver' : 'Activer' ?></button>
                                                </form>
                                                <form method="POST" action="supprimer.php" style="display:inline" onsubmit="return confirm('Supprimer ?')">
                                                    <input type="hidden" name="id" value="<?= $u['id_utilisateur'] ?>">
                                                    <button type="submit" class="btn btn-xs btn-link text-danger">Supprimer</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include(__DIR__ . '/../../../templates/footer.php'); ?>
    <script src="../../../js/jquery-1.12.4.min.js"></script>
    <script src="../../../js/bootstrap-3.4.1.min.js"></script>
</body>
</html>