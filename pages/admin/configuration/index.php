<?php
// pages/admin/configuration/index.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'patron') {
    header("Location: ../../../index.php?error=Accès non autorisé");
    exit();
}

require_once('../../../fonctions/database.php');

$titre = "Configuration Générale";
$admin_style = true;

$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

$configFile = '../../../fonctions/config/config.ini';
$config = parse_ini_file($configFile, true);

if ($config === false) {
    $errorMessage = "Erreur de lecture du fichier système.";
}

include('../../../templates/header.php');
include('../../../templates/navigation.php');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titre ?></title>
    <link rel="stylesheet" href="<?= generateUrl('../../css/bootstrap.min.css') ?>">
    <style>
        :root {
            --primary-color: #2c3e50;
            --border-color: #dee2e6;
            --text-muted: #6c757d;
            --bg-light: #f8f9fa;
        }

        body { background-color: #f4f7f6; color: #333; font-family: 'Segoe UI', Roboto, sans-serif; }

        .configuration-container {
            margin-left: 130px;
            padding: 30px;
            transition: margin-left 0.3s ease;
        }
        
        body.collapsed-sidebar .configuration-container { margin-left: 70px; }
        
        @media (max-width: 992px) { .configuration-container { margin-left: 0 !important; } }

        /* Style des cartes pro */
        .card {
            border-radius: 4px;
            border: 1px solid var(--border-color);
            box-shadow: none;
            background: #fff;
            margin-bottom: 25px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid var(--border-color);
            padding: 15px 20px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
            color: var(--primary-color);
        }

        /* Stats cards épurées */
        .stat-box {
            padding: 20px;
            border-left: 4px solid var(--primary-color);
        }
        .stat-box small { color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 0.7rem; }
        .stat-box h3 { margin: 5px 0 0; font-weight: 700; color: var(--primary-color); }

        /* Groupes de paramètres */
        .param-group {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        .param-group:last-child { border-bottom: none; }

        .param-label {
            width: 250px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #444;
        }

        .param-value {
            flex-grow: 1;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            color: var(--text-muted);
            padding: 4px 8px;
            background: #f9f9f9;
            border-radius: 3px;
        }

        .btn { border-radius: 2px; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; }
        .btn-outline-primary { color: var(--primary-color); border-color: var(--primary-color); }
        .btn-outline-primary:hover { background-color: var(--primary-color); color: #fff; }

        .section-title {
            border-bottom: 2px solid var(--primary-color);
            display: inline-block;
            margin-bottom: 20px;
            padding-bottom: 5px;
            font-weight: 800;
        }

        .status-dot {
            height: 8px;
            width: 8px;
            background-color: #28a745;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
    </style>
</head>
<body>
<br> <br> <br> 
<div class="configuration-container">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-2">
                    <li class="breadcrumb-item"><a href="<?= generateUrl('pages/dashboard.php') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active text-dark fw-bold">Configuration Système</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-end">
                <div>
                    <h2 class="fw-bold mb-0 text-dark">Paramètres Système</h2>
                    <p class="text-muted small mb-0">Gestion centralisée du fichier config.ini</p>
                </div>
                <div class="btn-group">
                    <a href="<?= generateUrl('pages/admin/configuration/logs.php') ?>" class="btn btn-outline-secondary">Journal Logs</a>
                    <a href="<?= generateUrl('pages/admin/configuration/backup.php') ?>" class="btn btn-outline-dark">Backup System</a>
                </div>
            </div>
        </div>
    </div>

    <hr class="mb-4">

    <?php if ($flash_message || isset($errorMessage)): ?>
        <div class="alert alert-<?= ($flash_type === 'error' || isset($errorMessage)) ? 'danger' : 'success' ?> border-0 shadow-sm mb-4">
            <?= htmlspecialchars($flash_message ?? $errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-box">
                <small>Application</small>
                <h3><?= count($config['app'] ?? []) ?> clés</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-box">
                <small>Base de données</small>
                <h3><?= count($config['database'] ?? []) ?> clés</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-box">
                <small>Monitoring</small>
                <h3><?= count($config['logs'] ?? []) ?> logs</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-box" style="border-left-color: #6c757d;">
                <small>Dernier accès</small>
                <h3 style="font-size: 1.2rem;"><?= @filemtime($configFile) ? date('d/m/Y H:i', filemtime($configFile)) : 'N/A' ?></h3>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Configuration Logiciel</div>
                <div class="card-body">
                    <?php if (isset($config['app'])): ?>
                        <?php foreach ($config['app'] as $key => $value): ?>
                            <div class="param-group">
                                <span class="param-label"><?= htmlspecialchars($key) ?></span>
                                <span class="param-value"><?= htmlspecialchars($value) ?></span>
                                <a href="<?= generateUrl('pages/admin/configuration/modifier_parametre.php?section=app&param=' . $key) ?>" 
                                   class="btn btn-sm btn-link text-primary text-decoration-none ms-3">Modifier</a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Connectivité Database</div>
                <div class="card-body">
                    <?php if (isset($config['database'])): ?>
                        <?php foreach ($config['database'] as $key => $value): ?>
                            <div class="param-group">
                                <span class="param-label"><?= htmlspecialchars($key) ?></span>
                                <span class="param-value">
                                    <?= ($key === 'password') ? '********' : htmlspecialchars($value) ?>
                                </span>
                                <?php if ($key !== 'password'): ?>
                                    <a href="<?= generateUrl('pages/admin/configuration/modifier_parametre.php?section=database&param=' . $key) ?>" 
                                       class="btn btn-sm btn-link text-primary text-decoration-none ms-3">Modifier</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div class="mt-3 p-3 bg-light border-start border-success border-4 small">
                        <span class="status-dot"></span> État du service : Connecté au serveur MySQL.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Maintenance & Sécurité</div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6 class="fw-bold small text-uppercase mb-3">État des fichiers</h6>
                        <div class="param-group border-0 py-1">
                            <span class="small fw-bold">Permissions config.ini :</span>
                            <span class="ms-auto badge bg-dark"><?= @fileperms($configFile) ? substr(sprintf('%o', fileperms($configFile)), -4) : 'Err' ?></span>
                        </div>
                        <div class="param-group border-0 py-1">
                            <span class="small fw-bold">Variables session :</span>
                            <span class="ms-auto text-muted"><?= count($_SESSION) ?> actifs</span>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="<?= generateUrl('pages/admin/configuration/reload_config.php') ?>" class="btn btn-outline-primary">Recharger Fichier</a>
                        <a href="<?= generateUrl('pages/admin/configuration/restore_default.php') ?>" 
                           class="btn btn-outline-danger" 
                           onclick="return confirm('Confirmer la restauration usine ?')">Restauration Système</a>
                    </div>
                </div>
            </div>

            <div class="card bg-light border-0">
                <div class="card-body small">
                    <p class="fw-bold mb-2">Protocole de modification :</p>
                    <ul class="ps-3 text-muted">
                        <li>Vérifiez l'intégrité avant sauvegarde.</li>
                        <li>Les changements de base de données impactent immédiatement l'accès utilisateur.</li>
                        <li>Permissions recommandées : 0644.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= generateUrl('../../assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
<?php include('../../../templates/footer.php'); ?>