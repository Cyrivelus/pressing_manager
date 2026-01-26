<?php
// pages/admin/configuration/backup.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérification de sécurité (Rôle 'patron' requis)
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'patron') {
    header("Location: ../../../index.php?error=Accès non autorisé");
    exit();
}

require_once('../../../fonctions/database.php');

$titre = "Sauvegarde du Système";
$admin_style = true;

// Charger la config pour obtenir les infos de connexion
$configFile = '../../../fonctions/config/config.ini';
$config = parse_ini_file($configFile, true);

// Dossier de destination des sauvegardes
$backupDir = '../../../backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// --- LOGIQUE DE SAUVEGARDE ---
if (isset($_POST['action']) && $_POST['action'] === 'generate_backup') {
    try {
        $host = $config['database']['host'] ?? 'localhost';
        $dbname = $config['database']['dbname'] ?? '';
        $user = $config['database']['username'] ?? 'root';
        $pass = $config['database']['password'] ?? '';

        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
        
        $tables = [];
        $result = $pdo->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sql_content = "-- Sauvegarde Pressing Manager\n";
        $sql_content .= "-- Date: " . date('d-m-Y H:i:s') . "\n";
        $sql_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Structure
            $row2 = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM);
            $sql_content .= "\n\n" . $row2[1] . ";\n\n";

            // Données
            $result = $pdo->query("SELECT * FROM $table");
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $sql_content .= "INSERT INTO $table VALUES(";
                $values = array_map(function($val) use ($pdo) {
                    return $val === null ? "NULL" : $pdo->quote($val);
                }, array_values($row));
                $sql_content .= implode(',', $values) . ");\n";
            }
        }
        $sql_content .= "\nSET FOREIGN_KEY_CHECKS=1;";

        $filename = 'backup_' . $dbname . '_' . date('Y-m-d_H-i-s') . '.sql';
        file_put_contents($backupDir . $filename, $sql_content);

        $_SESSION['flash_message'] = "Sauvegarde réussie : $filename";
        $_SESSION['flash_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['flash_message'] = "Erreur de sauvegarde : " . $e->getMessage();
        $_SESSION['flash_type'] = "error";
    }
    header("Location: backup.php");
    exit();
}

// --- LOGIQUE DE SUPPRESSION ---
if (isset($_GET['delete'])) {
    $fileToDelete = basename($_GET['delete']);
    if (file_exists($backupDir . $fileToDelete)) {
        unlink($backupDir . $fileToDelete);
        $_SESSION['flash_message'] = "Fichier supprimé.";
        $_SESSION['flash_type'] = "success";
    }
    header("Location: backup.php");
    exit();
}

include('../../../templates/header.php');
include('../../../templates/navigation.php');
?>

<div class="configuration-container" style="margin-left: 230px; padding: 20px;">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= generateUrl('pages/dashboard.php') ?>">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="<?= generateUrl('pages/admin/configuration/index.php') ?>">Configuration</a></li>
                    <li class="breadcrumb-item active">Sauvegarde</li>
                </ol>
            </nav>
            <h2>Gestion des Sauvegardes</h2>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible fade show">
            <?= $_SESSION['flash_message']; unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <i class="fas fa-hdd me-2"></i>Nouvelle Sauvegarde
                </div>
                <div class="card-body text-center">
                    <p class="text-muted small">Cette action génère un fichier .sql complet de votre base de données actuelle.</p>
                    <form method="POST">
                        <button type="submit" name="action" value="generate_backup" class="btn btn-warning w-100">
                           Lancer la sauvegarde
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    Historique des sauvegardes
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Fichier</th>
                                    <th>Date</th>
                                    <th>Taille</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $files = glob($backupDir . "*.sql");
                                array_multisort(array_map('filemtime', $files), SORT_DESC, $files);
                                
                                if (empty($files)): ?>
                                    <tr><td colspan="4" class="text-center">Aucune sauvegarde disponible</td></tr>
                                <?php else: 
                                    foreach ($files as $file): 
                                        $fname = basename($file);
                                ?>
                                    <tr>
                                        <td><i class="fas fa-file-code text-primary me-2"></i><?= $fname ?></td>
                                        <td><?= date("d/m/Y H:i", filemtime($file)) ?></td>
                                        <td><?= round(filesize($file) / 1024, 2) ?> Ko</td>
                                        <td>
                                            <a href="<?= $backupDir . $fname ?>" class="btn btn-sm btn-outline-success" download>
                                               Télécharger
                                            </a>
                                            <a href="?delete=<?= $fname ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer définitivement cette sauvegarde ?')">
                                               Supprimer
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../../../templates/footer.php'); ?>