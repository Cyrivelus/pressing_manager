<?php
// pages/admin/configuration/logs.php
// Visualisation des logs (sécurité, erreurs, etc.)

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



// Définir le titre de la page
$titre = "Visualisation des Logs";
$admin_style = true;

// Inclure les fichiers nécessaires
require_once('../../../fonctions/database.php');

// Lire la configuration des logs
$configFile = '../../../fonctions/config/config.ini';
$config = parse_ini_file($configFile, true);
$log_dir = '../../../logs/';

// Nombre de lignes à afficher par défaut
$linesToShow = isset($_GET['lines']) && is_numeric($_GET['lines']) && $_GET['lines'] > 0 
    ? intval($_GET['lines']) 
    : 50;

// Fichier de log sélectionné
$selectedFile = $_GET['file'] ?? 'application.log';

// Liste des fichiers de logs disponibles
$logFiles = [];
if (is_dir($log_dir)) {
    $files = scandir($log_dir);
    foreach ($files as $file) {
        if (strpos($file, '.log') !== false || strpos($file, '.txt') !== false) {
            $filepath = $log_dir . $file;
            $logFiles[] = [
                'name' => $file,
                'size' => filesize($filepath),
                'modified' => filemtime($filepath),
                'path' => $filepath
            ];
        }
    }
}

// Trier par date de modification (du plus récent au plus ancien)
usort($logFiles, function($a, $b) {
    return $b['modified'] - $a['modified'];
});

// Lire le contenu du fichier sélectionné
$logContent = '';
$totalLines = 0;
$currentFilePath = $log_dir . $selectedFile;

if (file_exists($currentFilePath) && is_readable($currentFilePath)) {
    // Lire le fichier de log en commençant par la fin
    $file = new SplFileObject($currentFilePath, 'r');
    $file->seek(PHP_INT_MAX);
    $lastLine = $file->key();
    
    $logLines = [];
    $linesRead = 0;
    
    // Lire les lignes depuis la fin
    for ($i = $lastLine; $i >= 0 && $linesRead < $linesToShow; $i--) {
        $file->seek($i);
        $line = $file->current();
        if ($line !== false && trim($line) !== '') {
            $logLines[] = $line;
            $linesRead++;
        }
    }
    
    // Inverser l'ordre pour afficher du plus ancien au plus récent
    $logLines = array_reverse($logLines);
    $logContent = implode("", $logLines);
    
    // Compter le nombre total de lignes
    $file->seek(0);
    while (!$file->eof()) {
        $file->fgets();
        $totalLines++;
    }
    
    $fileSize = filesize($currentFilePath);
} else {
    $logContent = '<div class="alert alert-warning">Le fichier de log est introuvable ou illisible.</div>';
    $fileSize = 0;
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
    <link rel="stylesheet" href="<?= generateUrl('../../assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= generateUrl('../../assets/css/all.min.css') ?>">
    <style>
        .logs-container {
            margin-left: 230px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        
        body.collapsed-sidebar .logs-container { 
            margin-left: 70px; 
        }
        
        @media (max-width: 992px) { 
            .logs-container { 
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
        
        .log-pre {
            max-height: 500px;
            overflow-y: auto;
            font-size: 0.75rem;
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 0;
        }
        
        .log-line {
            padding: 2px 5px;
            border-bottom: 1px solid #eee;
            white-space: pre-wrap;
            word-break: break-all;
        }
        
        .log-line:hover {
            background-color: #f0f0f0;
        }
        
        .log-level-error { color: #dc3545; background-color: rgba(220, 53, 69, 0.1); }
        .log-level-warning { color: #ffc107; background-color: rgba(255, 193, 7, 0.1); }
        .log-level-info { color: #17a2b8; }
        .log-level-success { color: #28a745; }
        
        .log-file-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .log-file-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .log-file-item:hover {
            background-color: #f8f9fa;
        }
        
        .log-file-item.active {
            background-color: #e3f2fd;
            border-left: 3px solid #2196f3;
        }
        
        .file-size {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .file-date {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .log-stats {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .stat-item {
            display: inline-block;
            margin-right: 20px;
            padding: 5px 10px;
            background: white;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .filter-badge {
            cursor: pointer;
            margin-right: 5px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

<div class="logs-container">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= generateUrl('pages/dashboard.php') ?>">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="<?= generateUrl('pages/admin/index.php') ?>">Administration</a></li>
                    <li class="breadcrumb-item"><a href="<?= generateUrl('pages/admin/configuration/index.php') ?>">Configuration</a></li>
                    <li class="breadcrumb-item active">Logs</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center">
                <h2>Visualisation des Logs</h2>
                <div class="action-buttons">
                   <a href="javascript:history.back()" class="btn btn-outline-secondary">
    <- Retour
</a>
                    <?php if (file_exists($currentFilePath)): ?>
                        <a href="<?= generateUrl('pages/admin/configuration/download_log.php?file=' . urlencode($selectedFile)) ?>" 
                           class="btn btn-outline-success">
                            Télécharger
                        </a>
                        <button type="button" class="btn btn-outline-danger" onclick="clearLog()">
                           Vider
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <p class="text-muted">Consultez les logs système pour le débogage et la surveillance.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                   Fichiers de logs
                </div>
                <div class="card-body p-0">
                    <div class="log-file-list">
                        <?php if (empty($logFiles)): ?>
                            <div class="alert alert-warning m-3">
                                Aucun fichier de log trouvé
                            </div>
                        <?php else: ?>
                            <?php foreach ($logFiles as $logFile): ?>
                                <div class="log-file-item <?= $selectedFile == $logFile['name'] ? 'active' : '' ?>"
                                     onclick="window.location.href='?file=<?= urlencode($logFile['name']) ?>&lines=<?= $linesToShow ?>'">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                           
                                            <strong><?= htmlspecialchars($logFile['name']) ?></strong>
                                        </div>
                                        <span class="badge bg-light text-dark">
                                            <?= round($logFile['size'] / 1024, 1) ?> Ko
                                        </span>
                                    </div>
                                    <div class="file-date mt-1">
                                        <small>
                                         
                                            <?= date('d/m/Y H:i', $logFile['modified']) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    Filtres
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Niveau de log :</label>
                        <div>
                            <span class="badge bg-danger filter-badge" onclick="filterLog('ERROR')">ERROR</span>
                            <span class="badge bg-warning text-dark filter-badge" onclick="filterLog('WARNING')">WARNING</span>
                            <span class="badge bg-info filter-badge" onclick="filterLog('INFO')">INFO</span>
                            <span class="badge bg-success filter-badge" onclick="filterLog('SUCCESS')">SUCCESS</span>
                            <span class="badge bg-secondary filter-badge" onclick="filterLog('ALL')">TOUT</span>
                        </div>
                    </div>
                    
                    <form method="get" class="mt-3">
                        <input type="hidden" name="file" value="<?= htmlspecialchars($selectedFile) ?>">
                        <div class="mb-3">
                            <label for="lines" class="form-label">Lignes à afficher :</label>
                            <input type="number" class="form-control" id="lines" name="lines" 
                                   value="<?= $linesToShow ?>" min="1" max="1000">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                          Actualiser
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <?php if (!empty($logFiles)): ?>
                <div class="log-stats">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= htmlspecialchars($selectedFile) ?></strong>
                            <span class="file-size ms-2">(<?= round($fileSize / 1024, 2) ?> Ko)</span>
                        </div>
                        <div>
                            <span class="stat-item">
                                <i class="fas fa-file-alt me-1"></i>
                                <?= $totalLines ?> lignes
                            </span>
                            <span class="stat-item">
                                <i class="fas fa-calendar-alt me-1"></i>
                                Dernière modif : <?= date('d/m/Y H:i', filemtime($currentFilePath)) ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>
                        
                        Contenu du log (<?= $linesToShow ?> dernières lignes)
                    </span>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="copyLogContent()">
                            Copier
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="refreshLog()">
                           Raffraichir
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <pre class="log-pre" id="logContent">
<?php
if (!empty($logContent)):
    $lines = explode("\n", $logContent);
    foreach ($lines as $line):
        if (empty(trim($line))) continue;
        
        $class = '';
        if (stripos($line, '[ERROR]') !== false) {
            $class = 'log-level-error';
        } elseif (stripos($line, '[WARNING]') !== false) {
            $class = 'log-level-warning';
        } elseif (stripos($line, '[INFO]') !== false) {
            $class = 'log-level-info';
        } elseif (stripos($line, '[SUCCESS]') !== false) {
            $class = 'log-level-success';
        }
?>
<div class="log-line <?= $class ?>"><?= htmlspecialchars($line) ?></div>
<?php
    endforeach;
else:
    echo '<div class="alert alert-warning m-3">Aucun contenu à afficher</div>';
endif;
?>
                    </pre>
                </div>
            </div>
            
            <?php if ($totalLines > $linesToShow): ?>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Affichage des <?= $linesToShow ?> dernières lignes sur <?= $totalLines ?> au total.
                    <a href="?file=<?= urlencode($selectedFile) ?>&lines=<?= $totalLines ?>" class="alert-link">
                        Afficher toutes les lignes
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="<?= generateUrl('../../assets/js/bootstrap.bundle.min.js') ?>"></script>
<script>
    function refreshLog() {
        window.location.reload();
    }
    
    function copyLogContent() {
        const logContent = document.getElementById('logContent').textContent;
        navigator.clipboard.writeText(logContent).then(() => {
            alert('Contenu du log copié dans le presse-papier !');
        }).catch(err => {
            console.error('Erreur lors de la copie : ', err);
        });
    }
    
    function filterLog(level) {
        const logLines = document.querySelectorAll('.log-line');
        
        if (level === 'ALL') {
            logLines.forEach(line => {
                line.style.display = 'block';
            });
            return;
        }
        
        logLines.forEach(line => {
            if (line.textContent.includes('[' + level + ']')) {
                line.style.display = 'block';
            } else {
                line.style.display = 'none';
            }
        });
    }
    
    function clearLog() {
        if (confirm('Êtes-vous sûr de vouloir vider ce fichier de log ? Cette action est irréversible.')) {
            window.location.href = '<?= generateUrl('pages/admin/configuration/clear_log.php?file=' . urlencode($selectedFile)) ?>';
        }
    }
    
    // Auto-scroll vers le bas du log
    document.addEventListener('DOMContentLoaded', function() {
        const logPre = document.querySelector('.log-pre');
        if (logPre) {
            logPre.scrollTop = logPre.scrollHeight;
        }
        
        // Ajouter la recherche rapide
        const logLines = document.querySelectorAll('.log-line');
        logLines.forEach(line => {
            line.addEventListener('click', function() {
                const text = this.textContent;
                if (text.includes('#')) {
                    // Extraire une référence potentielle
                    const match = text.match(/#[A-Z0-9]+/);
                    if (match) {
                        prompt('Référence trouvée :', match[0]);
                    }
                }
            });
        });
    });
</script>
</body>
</html>
<?php include('../../../templates/footer.php'); ?>