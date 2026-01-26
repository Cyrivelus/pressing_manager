<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Historique des Ventes Boutique";

// 2. Filtres (Date début / Date fin)
$date_debut = $_GET['debut'] ?? date('Y-m-01');
$date_fin = $_GET['fin'] ?? date('Y-m-d');

try {
    // 3. Récupération des ventes (Jointure avec clients et utilisateurs)
    $query = "SELECT v.*, c.nom_client, c.prenom_client, u.nom_utilisateur 
              FROM ventes_boutique v
              LEFT JOIN clients c ON v.id_client = c.id_client
              LEFT JOIN utilisateurs u ON v.id_utilisateur = u.id_utilisateur
              WHERE DATE(v.date_vente) BETWEEN :debut AND :fin
              ORDER BY v.date_vente DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute(['debut' => $date_debut, 'fin' => $date_fin]);
    $ventes = $stmt->fetchAll();
} catch (PDOException $e) {
    $ventes = [];
    $error_db = "La table 'ventes_boutique' n'existe pas. Veuillez l'importer dans SQL.";
}

// 4. Calcul du CA total
$total_ca = array_sum(array_column($ventes, 'montant_total'));

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <?php if(isset($error_db)): ?>
        <div class="alert alert-danger mt-4"><?= $error_db ?></div>
    <?php endif; ?>

    <div class="row mt-4">
        <div class="col-12 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
            <div>
                <h2 class="fw-bold m-0 text-dark"><?= $titre ?></h2>
                <p class="text-muted mb-0">Analyse des revenus produits boutique</p>
            </div>
            <div class="bg-dark text-white p-3 rounded-3 shadow-sm text-end border-start border-success border-5">
                <small class="d-block opacity-75">Chiffre d'Affaires Période</small>
                <h3 class="m-0 fw-bold text-success"><?= number_format($total_ca, 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h3>
            </div>
        </div>

        <div class="col-12 mb-4">
            <form class="card border-0 shadow-sm p-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Du</label>
                        <input type="date" name="debut" class="form-control" value="<?= $date_debut ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Au</label>
                        <input type="date" name="fin" class="form-control" value="<?= $date_fin ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-sync-alt me-1"></i> Actualiser
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary">
                            <tr>
                                <th class="ps-4">Réf.</th>
                                <th>Date & Heure</th>
                                <th>Client</th>
                                <th>Articles</th>
                                <th>Paiement</th>
                                <th>Vendeur</th>
                                <th>Total</th>
                                <th class="text-center pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ventes as $v): ?>
                            <tr>
                                <td class="ps-4 fw-bold">#V-<?= str_pad($v['id_vente'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <div class="small fw-bold"><?= date('d/m/Y', strtotime($v['date_vente'])) ?></div>
                                    <div class="text-muted small"><?= date('H:i', strtotime($v['date_vente'])) ?></div>
                                </td>
                                <td>
                                    <?php if($v['id_client']): ?>
                                        <div class="fw-bold small"><?= htmlspecialchars($v['prenom_client'].' '.$v['nom_client']) ?></div>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted fw-normal">Client de passage</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge rounded-pill bg-info text-dark px-3">
                                        <?= $v['nb_articles'] ?> article(s)
                                    </span>
                                </td>
                                <td>
                                    <small class="fw-bold"><i class="fas fa-wallet text-muted me-1"></i> <?= strtoupper($v['mode_paiement']) ?></small>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($v['nom_utilisateur'] ?? 'Système') ?></td>
                                <td class="fw-bold text-primary"><?= number_format($v['montant_total'], 0, ',', ' ') ?> FCFA</td>
                                <td class="text-center pe-4">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-secondary" onclick="printReceipt(<?= $v['id_vente'] ?>)">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDetails<?= $v['id_vente'] ?>">
                                            <i class="fas fa-list"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($ventes)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <img src="../../assets/img/empty-data.svg" alt="" style="width: 80px;" class="mb-3 opacity-25">
                                    <p class="text-muted">Aucune transaction trouvée pour cette période.</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
function printReceipt(id) {
    const width = 400;
    const height = 600;
    const left = (window.innerWidth / 2) - (width / 2);
    const top = (window.innerHeight / 2) - (height / 2);
    window.open(`imprimer_recu.php?id=${id}`, 'Impression Ticket', 
                `width=${width},height=${height},left=${left},top=${top},toolbar=0,scrollbars=1`);
}
</script>

<?php require_once  '../../templates/footer.php'; ?>