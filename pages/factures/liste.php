<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Historique des Factures";

// 1. Récupération des factures avec infos clients
$sql = "SELECT f.*, c.nom_client 
        FROM factures f
        LEFT JOIN clients c ON f.id_client = c.id_client
        ORDER BY f.date_facture DESC";
$factures = $pdo->query($sql)->fetchAll();

// 2. Calcul des statistiques rapides (Exemple)
$stats = $pdo->query("SELECT 
    SUM(montant_ttc) as total_ca,
    COUNT(id_facture) as nb_factures,
    SUM(CASE WHEN statut_paiement = 'En attente' THEN montant_ttc ELSE 0 END) as impayes
    FROM factures")->fetch();

require_once '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>
<br><br><br>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0 text-primary"><i class="fas fa-file-invoice-dollar me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Consultez et gérez l'ensemble de vos transactions commerciales</p>
        </div>
        <a href="creer.php" class="btn btn-primary shadow-sm px-4">
            + Nouvelle Facture
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-white border-start border-4 border-primary">
                <small class="text-muted fw-bold">CHIFFRE D'AFFAIRES GLOBAL</small>
                <h3 class="fw-bold m-0"><?= number_format($stats['total_ca'] ?? 0, 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-white border-start border-4 border-warning">
                <small class="text-muted fw-bold">EN ATTENTE DE PAIEMENT</small>
                <h3 class="fw-bold m-0 text-warning"><?= number_format($stats['impayes'] ?? 0, 0, ',', ' ') ?> <small class="fs-6 text-dark">FCFA</small></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-white border-start border-4 border-info">
                <small class="text-muted fw-bold">TOTAL FACTURES</small>
                <h3 class="fw-bold m-0"><?= $stats['nb_factures'] ?? 0 ?></h3>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">N° Facture</th>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Montant TTC</th>
                            <th class="text-center">Statut</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($factures)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                 
                                    Aucune facture trouvée dans le système.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($factures as $f): ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold text-dark"><?= $f['numero_facture'] ?></span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($f['date_facture'])) ?></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($f['nom_client'] ?? 'Client de passage') ?></div>
                                    </td>
                                    <td class="fw-bold"><?= number_format($f['montant_ttc'], 0, ',', ' ') ?> FCFA</td>
                                    <td class="text-center">
                                        <?php 
                                            $badgeClass = 'bg-secondary';
                                            if($f['statut_paiement'] == 'Payé') $badgeClass = 'bg-success';
                                            if($f['statut_paiement'] == 'Partiel') $badgeClass = 'bg-info';
                                            if($f['statut_paiement'] == 'En attente') $badgeClass = 'bg-warning text-dark';
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= $f['statut_paiement'] ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group shadow-sm">
                                            <a href="voir.php?id=<?= $f['id_facture'] ?>" class="btn btn-sm btn-white border" title="Voir">
                                                Voir
                                            </a>
                                            <a href="imprimer.php?id=<?= $f['id_facture'] ?>" target="_blank" class="btn btn-sm btn-white border" title="Imprimer">
                                                Imprimer
                                            </a>
                                            <button type="button" class="btn btn-sm btn-white border text-danger" title="Supprimer" onclick="confirmDelete(<?= $f['id_facture'] ?>)">
                                               Supprimer
                                            </button>
                                        </div>
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

<script>
function confirmDelete(id) {
    if(confirm('Êtes-vous sûr de vouloir supprimer cette facture ? Cette action est irréversible.')) {
        window.location.href = 'supprimer.php?id=' + id;
    }
}
</script>

<style>
    .btn-white { background: #fff; }
    .btn-white:hover { background: #f8f9fa; }
    .badge { font-weight: 500; padding: 0.5em 0.8em; border-radius: 6px; }
    .table thead th { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; border-top: none; }
</style>

<?php require_once '../../templates/footer.php'; ?>