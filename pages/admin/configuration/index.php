<?php
// pages/admin/configuration/index.php

// Démarrer la session pour la gestion de l'authentification
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'patron') {
    // Rediriger si non autorisé
    header("Location: ../../../index.php?error=Accès non autorisé");
    exit();
}

// Inclure les fichiers nécessaires
require_once('../../../fonctions/database.php');

// Définir le titre de la page
$titre = "Configuration Générale";
$admin_style = true;


// Gérer les messages flash
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Lire la configuration depuis le fichier config.ini
$configFile = '../../../fonctions/config/config.ini';
$config = parse_ini_file($configFile, true);

// Vérifier si le fichier de configuration a été lu correctement
if ($config === false) {
    $errorMessage = "Erreur lors de la lecture du fichier de configuration.";
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
    <link rel="stylesheet" href="<?= generateUrl('../../css/all.min.css') ?>">
    <style>
        .configuration-container {
            margin-left: 230px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        
        body.collapsed-sidebar .configuration-container { 
            margin-left: 70px; 
        }
        
        @media (max-width: 992px) { 
            .configuration-container { 
                margin-left: 0 !important; 
            } 
        }
        
        .card {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .param-card {
            transition: transform 0.2s;
        }
        
        .param-card:hover {
            transform: translateY(-3px);
        }
        
        .config-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .config-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .param-label {
            font-weight: 500;
            color: #495057;
            min-width: 200px;
        }
        
        .param-value {
            background: white;
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            flex-grow: 1;
        }
        
        .param-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .badge-config {
            font-size: 0.8em;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .config-logs {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        
        .config-database {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
        }
        
        .config-app {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        
        .config-security {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        
        .status-active { background-color: #28a745; }
        .status-warning { background-color: #ffc107; }
        .status-inactive { background-color: #dc3545; }
        
        .backup-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="configuration-container">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= generateUrl('pages/dashboard.php') ?>">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="<?= generateUrl('pages/admin/index.php') ?>">Administration</a></li>
                    <li class="breadcrumb-item active">Configuration</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center">
                <h2>Configuration du Système</h2>
                <div class="action-buttons">
                    <a href="<?= generateUrl('pages/admin/configuration/logs.php') ?>" class="btn btn-outline-secondary">
                        Voir les logs
                    </a>
                    <a href="<?= generateUrl('pages/admin/configuration/backup.php') ?>" class="btn btn-outline-warning">
                       Sauvegarde
                    </a>
                </div>
            </div>
            
            <p class="text-muted">Gérez les paramètres de l'application, de la base de données et des logs système.</p>
        </div>
    </div>

    <?php if ($flash_message): ?>
        <div class="alert alert-<?= $flash_type === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
            <i class="fas <?= $flash_type === 'error' ? 'fa-exclamation-triangle' : 'fa-check-circle' ?> me-2"></i>
            <?= htmlspecialchars($flash_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($_GET['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($errorMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white p-3 param-card">
                <small>Paramètres Application</small>
                <h3 class="mb-0"><?= count($config['app'] ?? []) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white p-3 param-card">
                <small>Paramètres Base de données</small>
                <h3 class="mb-0"><?= count($config['database'] ?? []) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark p-3 param-card">
                <small>Paramètres Logs</small>
                <h3 class="mb-0"><?= count($config['logs'] ?? []) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white p-3 param-card">
                <small>Dernière modification</small>
                <?php
                $modif_time = @filemtime($configFile);
                if ($modif_time) {
                    echo '<h6 class="mb-0">' . date('d/m/Y H:i', $modif_time) . '</h6>';
                } else {
                    echo '<h6 class="mb-0">Inconnue</h6>';
                }
                ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="config-section config-app">
                <div class="d-flex align-items-center mb-3">
                    <div class="config-icon">
                   
                    </div>
                    <div>
                        <h4 class="mb-0">Paramètres de l'Application</h4>
                        <p class="text-muted mb-0">Configuration générale de l'application</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <?php if (isset($config['app'])): ?>
                            <?php foreach ($config['app'] as $key => $value): ?>
                                <div class="param-group">
                                    <span class="param-label"><?= htmlspecialchars($key) ?></span>
                                    <div class="param-value"><?= htmlspecialchars($value) ?></div>
                                    <a href="<?= generateUrl('pages/admin/configuration/modifier_parametre.php?section=app&param=' . $key) ?>" 
                                       class="btn btn-sm btn-outline-primary ms-2">
                                        Modifier
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">Aucun paramètre d'application trouvé</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="config-section config-database">
                <div class="d-flex align-items-center mb-3">
                    <div class="config-icon">
                        
                    </div>
                    <div>
                        <h4 class="mb-0">Configuration Base de Données</h4>
                        <p class="text-muted mb-0">Paramètres de connexion à la base de données</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <?php if (isset($config['database'])): ?>
                            <?php foreach ($config['database'] as $key => $value): ?>
                                <div class="param-group">
                                    <span class="param-label"><?= htmlspecialchars($key) ?></span>
                                    <div class="param-value">
                                        <?php if ($key === 'password'): ?>
                                            <span class="text-muted">••••••••</span>
                                        <?php else: ?>
                                            <?= htmlspecialchars($value) ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($key !== 'password'): ?>
                                        <a href="<?= generateUrl('pages/admin/configuration/modifier_parametre.php?section=database&param=' . $key) ?>" 
                                           class="btn btn-sm btn-outline-primary ms-2">
                                            Modifier
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php
                            // Tester la connexion à la base de données
                            try {
                                $pdo = new PDO(
                                    "mysql:host=" . ($config['database']['host'] ?? 'localhost') . 
                                    ";dbname=" . ($config['database']['dbname'] ?? ''),
                                    $config['database']['username'] ?? 'root',
                                    $config['database']['password'] ?? ''
                                );
                                echo '<div class="alert alert-success mt-3">
                                        
                                        Connexion à la base de données réussie
                                      </div>';
                            } catch (PDOException $e) {
                                echo '<div class="alert alert-danger mt-3">
                                       
                                        Erreur de connexion : ' . htmlspecialchars($e->getMessage()) . '
                                      </div>';
                            }
                            ?>
                        <?php else: ?>
                            <div class="alert alert-warning">Aucun paramètre de base de données trouvé</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="config-section config-logs">
                <div class="d-flex align-items-center mb-3">
                    <div class="config-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">Configuration des Logs</h4>
                        <p class="text-muted mb-0">Gestion des fichiers de logs</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <?php if (isset($config['logs'])): ?>
                            <?php foreach ($config['logs'] as $key => $value): ?>
                                <div class="param-group">
                                    <span class="param-label"><?= htmlspecialchars($key) ?></span>
                                    <div class="param-value"><?= htmlspecialchars($value) ?></div>
                                    <a href="<?= generateUrl('pages/admin/configuration/modifier_parametre.php?section=logs&param=' . $key) ?>" 
                                       class="btn btn-sm btn-outline-primary ms-2">
                                        Modifier
                                    </a>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php
                            // Vérifier les fichiers de logs
                            $log_dir = '../../../logs/';
                            $logs = [];
                            if (is_dir($log_dir)) {
                                $files = scandir($log_dir);
                                foreach ($files as $file) {
                                    if (strpos($file, '.log') !== false) {
                                        $filepath = $log_dir . $file;
                                        $logs[] = [
                                            'name' => $file,
                                            'size' => filesize($filepath),
                                            'modified' => filemtime($filepath)
                                        ];
                                    }
                                }
                            }
                            
                            if (!empty($logs)): ?>
                                <div class="backup-info">
                                    <h6><i class="fas fa-file-alt me-1"></i>Fichiers de logs</h6>
                                    <ul class="list-unstyled mb-0">
                                        <?php foreach ($logs as $log): ?>
                                            <li class="small py-1">
                                                <i class="far fa-file me-1"></i>
                                                <?= htmlspecialchars($log['name']) ?> 
                                                <span class="text-muted">(<?= round($log['size'] / 1024, 2) ?> Ko)</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">Aucun paramètre de logs trouvé</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="config-section config-security mt-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="config-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">Sécurité</h4>
                        <p class="text-muted mb-0">Paramètres de sécurité du système</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="param-group">
                            <span class="param-label">Fichier config.ini</span>
                            <div class="param-value">
                                <?php
                                $perms = @fileperms($configFile);
                                if ($perms !== false) {
                                    echo substr(sprintf('%o', $perms), -4);
                                } else {
                                    echo 'Inaccessible';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="param-group">
                            <span class="param-label">Sessions actives</span>
                            <div class="param-value">
                                <span class="status-indicator status-active"></span>
                                <?= count($_SESSION) ?> variable(s)
                            </div>
                        </div>
                        
                        <div class="param-group">
                            <span class="param-label">Dernier accès</span>
                            <div class="param-value">
                                <?= date('d/m/Y H:i:s', $_SESSION['last_activity'] ?? time()) ?>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="<?= generateUrl('pages/admin/configuration/reload_config.php') ?>" 
                               class="btn btn-outline-primary btn-sm me-2">
                                <i class="fas fa-sync-alt me-1"></i>Recharger config
                            </a>
                            <a href="<?= generateUrl('pages/admin/configuration/restore_default.php') ?>" 
                               class="btn btn-outline-warning btn-sm"
                               onclick="return confirm('Restaurer la configuration par défaut ?')">
                               Restauration
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-light mt-4">
                <h6><i class="fas fa-lightbulb text-warning me-2"></i>Bonnes pratiques</h6>
                <ul class="small mb-0">
                    <li>Toujours sauvegarder avant modification</li>
                    <li>Vérifier les permissions des fichiers de config</li>
                    <li>Consulter les logs après changement</li>
                    <li>Tester la connexion BD après modification</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="<?= generateUrl('../../assets/js/bootstrap.bundle.min.js') ?>"></script>
<script>
    // Gestion des messages de confirmation
    document.addEventListener('DOMContentLoaded', function() {
        // Confirmation pour les modifications sensibles
        const sensitiveLinks = document.querySelectorAll('a[href*="restore"], a[href*="reset"]');
        sensitiveLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Êtes-vous sûr de vouloir effectuer cette action ?')) {
                    e.preventDefault();
                }
            });
        });
        
        // Mettre à jour l'heure de la dernière modification
        function updateLastModified() {
            const modifElement = document.querySelector('.card.bg-info h6');
            if (modifElement) {
                const now = new Date();
                modifElement.textContent = now.toLocaleDateString('fr-FR') + ' ' + now.toLocaleTimeString('fr-FR');
            }
        }
        
        // Rafraîchir automatiquement toutes les 5 minutes
        setInterval(updateLastModified, 300000);
    });
</script>
</body>
</html>
<?php include('../../../templates/footer.php'); ?>