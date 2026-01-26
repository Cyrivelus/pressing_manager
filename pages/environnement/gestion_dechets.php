<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion & Recyclage des Déchets";

try {
    $annee = date('Y');
    // Statistiques par type
    $stats_dechets = $pdo->prepare("
        SELECT type_dechet, SUM(poids_volume) as total, unite
        FROM registre_dechets 
        WHERE YEAR(date_collecte) = ? 
        GROUP BY type_dechet
    ");
    $stats_dechets->execute([$annee]);
    $bilan = $stats_dechets->fetchAll();

    // Récupération du registre complet
    $registre = $pdo->query("SELECT * FROM registre_dechets ORDER BY date_collecte DESC LIMIT 15")->fetchAll();

} catch (PDOException $e) {
    $db_error = "Erreur : " . $e->getMessage();
    $bilan = [];
    $registre = [];
}

require_once '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
        <div>
            <h2 class="fw-bold m-0 text-success"><i class="fas fa-recycle me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Conformité environnementale et suivi du tri sélectif</p>
        </div>
        <button class="btn btn-primary shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalEnregistrement">
            <i class="fas fa-plus me-1"></i> Enregistrer un enlèvement
        </button>
    </div>

    <?php if (isset($db_error)): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            <i class="fas fa-database me-2"></i> <b>Base de données incomplète :</b> La table <code>registre_dechets</code> est absente. Utilisez le script SQL fourni.
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <?php 
        // Logique simplifiée pour les cartes de résumé
        $poids_plastique = 0; $vol_boues = 0;
        foreach($bilan as $b) {
            if(stripos($b['type_dechet'], 'plastique') !== false) $poids_plastique += $b['total'];
            if(stripos($b['type_dechet'], 'solvant') !== false) $vol_boues += $b['total'];
        }
        ?>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 border-start border-4 border-primary">
                <small class="text-muted fw-bold">PLASTIQUES RECYCLÉS</small>
                <h2 class="fw-bold m-0"><?= number_format($poids_plastique, 1) ?> <small class="fs-6">kg</small></h2>
                <div class="progress mt-2" style="height: 5px;"><div class="progress-bar bg-primary" style="width: 65%"></div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 border-start border-4 border-danger">
                <small class="text-muted fw-bold">BOUES DE SOLVANTS (DÉCHETS DANGEREUX)</small>
                <h2 class="fw-bold m-0 text-danger"><?= number_format($vol_boues, 1) ?> <small class="fs-6">L</small></h2>
                <small class="text-muted small"><i class="fas fa-exclamation-circle"></i> Enlèvement par pro obligatoire</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-success text-white">
                <small class="opacity-75 fw-bold text-uppercase">Taux de valorisation</small>
                <h2 class="fw-bold m-0">72%</h2>
                <small><i class="fas fa-leaf"></i> Performance Éco-responsable</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0">Registre Chronologique des Enlèvements</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Type de Déchet</th>
                                <th>Quantité</th>
                                <th>Prestataire</th>
                                <th class="text-end pe-4">Justificatif</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($registre)): ?>
                                <tr><td colspan="5" class="text-center py-5">Aucun enregistrement trouvé.</td></tr>
                            <?php else: ?>
                                <?php foreach($registre as $r): ?>
                                <tr>
                                    <td class="ps-4 small fw-bold"><?= date('d/m/Y', strtotime($r['date_collecte'])) ?></td>
                                    <td>
                                        <span class="badge <?= stripos($r['type_dechet'], 'boues') !== false ? 'bg-danger-soft text-danger' : 'bg-success-soft text-success' ?> rounded-pill">
                                            <?= htmlspecialchars($r['type_dechet']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold"><?= $r['poids_volume'] ?> <small><?= $r['unite'] ?></small></td>
                                    <td class="small"><?= htmlspecialchars($r['prestataire']) ?></td>
                                    <td class="text-end pe-4">
                                        <?php if($r['num_bordereau']): ?>
                                            <span class="badge bg-light text-dark border"><i class="fas fa-file-invoice me-1"></i> <?= $r['num_bordereau'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-light p-4">
                <h6 class="fw-bold text-uppercase small mb-3">Rappel Réglementaire</h6>
                <div class="d-flex mb-3">
                    <i class="fas fa-info-circle text-primary me-3 fs-4"></i>
                    <p class="small text-muted mb-0">Tous les déchets contenant des solvants (boues, filtres, eaux de séparateurs) doivent faire l'objet d'un Bordereau de Suivi de Déchets Dangereux (BSDD).</p>
                </div>
                <hr>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="small fw-bold">Prochain enlèvement :</span>
                    <span class="badge bg-warning text-dark">Prévu le 15/02</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
</style>

<?php require_once  '../../templates/footer.php'; ?>