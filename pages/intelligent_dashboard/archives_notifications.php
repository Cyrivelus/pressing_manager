<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';


$titre = "Archives des Notifications";

// Logique de filtrage
$filtre_type = isset($_GET['type']) ? $_GET['type'] : 'tous';

try {
    $query = "SELECT a.*, u.nom_complet 
              FROM archives_alertes a
              LEFT JOIN utilisateurs u ON a.id_utilisateur_traitement = u.id_utilisateur";
    
    if ($filtre_type !== 'tous') {
        $query .= " WHERE a.type_alerte = :type";
    }
    $query .= " ORDER BY a.date_alerte DESC LIMIT 50";

    $stmt = $pdo->prepare($query);
    if ($filtre_type !== 'tous') $stmt->bindParam(':type', $filtre_type);
    $stmt->execute();
    $archives = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>
<br> <br> <br>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0"><<?= $titre ?></h2>
            <p class="text-muted small">Historique des incidents et alertes traitées</p>
        </div>
        <a href="index.php" class="btn btn-outline-primary">
           Alertes Actives
        </a>
    </div>

    <div class="mb-4">
        <div class="btn-group shadow-sm">
            <a href="?type=tous" class="btn btn-white border <?= $filtre_type == 'tous' ? 'active bg-light' : '' ?>">Toutes</a>
            <a href="?type=stock" class="btn btn-white border <?= $filtre_type == 'stock' ? 'active bg-light' : '' ?>">Stocks</a>
            <a href="?type=retard" class="btn btn-white border <?= $filtre_type == 'retard' ? 'active bg-light' : '' ?>">Retards</a>
            <a href="?type=oublie" class="btn btn-white border <?= $filtre_type == 'oublie' ? 'active bg-light' : '' ?>">Oublis</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 15px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Date Alerte</th>
                            <th>Type</th>
                            <th>Incident</th>
                            <th>Résolu par</th>
                            <th>Date Résolution</th>
                            <th class="pe-4">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($archives)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    
                                    Aucune archive disponible.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($archives as $row): ?>
                                <tr>
                                    <td class="ps-4">
                                        <small class="fw-bold"><?= date('d/m/Y', strtotime($row['date_alerte'])) ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                        $icon = 'fa-circle';
                                        $color = 'text-secondary';
                                        if($row['type_alerte'] == 'stock') { $icon = 'fa-box'; $color = 'text-primary'; }
                                        if($row['type_alerte'] == 'retard') { $icon = 'fa-clock'; $color = 'text-danger'; }
                                        if($row['type_alerte'] == 'oublie') { $icon = 'fa-archive'; $color = 'text-warning'; }
                                        ?>
                                        <i class="fas <?= $icon ?> <?= $color ?> me-2"></i>
                                        <span class="text-capitalize"><?= $row['type_alerte'] ?></span>
                                    </td>
                                    <td>
                                        <div class="small fw-bold"><?= htmlspecialchars($row['description']) ?></div>
                                        <?php if($row['notes_resolution']): ?>
                                            <div class="text-muted x-small italic text-truncate" style="max-width: 200px;">
                                                "<?= htmlspecialchars($row['notes_resolution']) ?>"
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['nom_complet'] ?? 'Système') ?></td>
                                    <td>
                                        <small><?= $row['date_resolution'] ? date('d/m/Y H:i', strtotime($row['date_resolution'])) : '-' ?></small>
                                    </td>
                                    <td class="pe-4">
                                        <span class="badge bg-success-soft text-success border border-success">
                                           Résolu
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-success-soft { background-color: rgba(40, 167, 69, 0.1); }
    .x-small { font-size: 0.75rem; }
    .btn-white { background: white; color: #333; }
    .btn-white.active { font-weight: bold; border-color: #0d6efd !important; }
</style>

<?php require_once '../../templates/footer.php'; ?>