<?php
// pages/securite/audit.php
session_start();

// 1. Vérification stricte des permissions
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patron') {
    header("Location: ../../index.php");
    exit();
}

// 2. Inclusion des fichiers (Assurez-vous que les chemins sont corrects)
require_once '../../fonctions/database.php'; 
require_once '../../fonctions/gestion_audit.php'; 

// Initialisation des variables pour éviter les erreurs "Undefined variable"
$message = '';
$message_type = '';
$logs = [];
$total_pages = 0; // Initialisation importante
$total_logs = 0;

// 3. Paramètres de Pagination
$par_page = 20;
$page_courante = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($page_courante < 1) $page_courante = 1;
$offset = ($page_courante - 1) * $par_page;

try {
    // Vérification de la connexion PDO
    if (!isset($pdo)) {
        throw new Exception("La connexion à la base de données a échoué.");
    }

    // Récupération des données
    $total_logs = getNombreTotalLogs($pdo);
    $total_pages = ceil($total_logs / $par_page);
    
    // Si la page demandée est supérieure au total, on réinitialise à la dernière page
    if ($total_pages > 0 && $page_courante > $total_pages) {
        $page_courante = $total_pages;
        $offset = ($page_courante - 1) * $par_page;
    }

    $logs = getLogsAudit($pdo, $par_page, $offset);

} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
    $message_type = 'danger';
}

// 4. Inclusion des templates
include '../../templates/header.php';
include '../../templates/navigation.php';
?>
<br> <br> <br>
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-dark"> Journal d'Audit</h2>
        <a href="javascript:history.back()" class="btn btn-secondary">
    <- Retour
</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 text-primary">Activités récentes (Total : <?= $total_logs ?>)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Date et Heure</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Table</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>#<?= $log['ID_Log'] ?? $log['id_log'] ?></td>
                                    <td><?= isset($log['Date_Heure_Action']) ? date('d/m/Y H:i', strtotime($log['Date_Heure_Action'])) : (isset($log['date_action']) ? date('d/m/Y H:i', strtotime($log['date_action'])) : 'N/A') ?></td>
                                    <td><strong><?= htmlspecialchars($log['Nom'] ?? $log['nom_complet'] ?? 'Système') ?></strong></td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?= htmlspecialchars($log['Action'] ?? $log['action'] ?? 'Inconnue') ?></span>
                                    </td>
                                    <td><small class="text-muted"><?= htmlspecialchars($log['table_concernée'] ?? 'N/A') ?></small></td>
                                    <td><code class="small"><?= htmlspecialchars($log['ip_adresse'] ?? '0.0.0.0') ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4">Aucun log trouvé dans la base de données.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= ($page_courante <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page_courante - 1 ?>">Précédent</a>
            </li>
            
            <?php 
            // Limitation de l'affichage des numéros de page si trop nombreux
            $range = 2;
            for ($i = 1; $i <= $total_pages; $i++): 
                if ($i == 1 || $i == $total_pages || ($i >= $page_courante - $range && $i <= $page_courante + $range)):
            ?>
                <li class="page-item <?= ($page_courante == $i) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php 
                elseif ($i == $page_courante - $range - 1 || $i == $page_courante + $range + 1):
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                endif;
            endfor; 
            ?>

            <li class="page-item <?= ($page_courante >= $total_pages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page_courante + 1 ?>">Suivant</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php include '../../templates/footer.php'; ?>