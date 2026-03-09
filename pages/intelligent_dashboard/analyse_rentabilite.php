<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';


$titre = "Analyse de Rentabilité par Service";

// 1. Gestion de la période (Filtre intelligent)
$periode = $_GET['periode'] ?? 'trimestriel';
$condition_date = "";

switch ($periode) {
    case 'mensuel':
        $condition_date = "AND t.date_depot >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $label_periode = "30 derniers jours";
        break;
    case 'annuel':
        $condition_date = "AND t.date_depot >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $label_periode = "12 derniers mois";
        break;
    default: // trimestriel
        $condition_date = "AND t.date_depot >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
        $label_periode = "90 derniers jours";
        $periode = 'trimestriel';
        break;
}

/**
 * LOGIQUE DE CALCUL
 */
try {
    $query = "
        SELECT 
            s.nom_service,
            cs.nom_categorie,
            COUNT(lt.id_ligne) as volume_ventes,
            IFNULL(SUM(lt.sous_total), 0) as ca_total,
            s.prix_unitaire,
            -- Simulation d'un coût de revient (35% du CA)
            (IFNULL(SUM(lt.sous_total), 0) * 0.35) as cout_estime_total
        FROM services s
        JOIN categories_service cs ON s.id_categorie = cs.id_categorie
        LEFT JOIN lignes_ticket lt ON s.id_service = lt.id_service
        LEFT JOIN tickets t ON lt.id_ticket = t.id_ticket $condition_date
        GROUP BY s.id_service, s.nom_service, cs.nom_categorie
        ORDER BY ca_total DESC
    ";
    $analyses = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

    // Calcul des KPIs globaux pour les boîtes du haut
    $total_ca = array_sum(array_column($analyses, 'ca_total'));
    $top_service = $analyses[0] ?? ['nom_service' => 'N/A', 'volume_ventes' => 0];
} catch (PDOException $e) {
    die("Erreur d'analyse : " . $e->getMessage());
}

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<style>
    .bg-success-light { background-color: rgba(25, 135, 84, 0.1); }
    .bg-info-light { background-color: rgba(13, 202, 240, 0.1); }
    .btn-white { background: white; color: #333; transition: all 0.2s; }
    .btn-white.active { background: #0d6efd !important; color: white !important; border-color: #0d6efd !important; }
    .card { border-radius: 15px; }
</style>
<br><br><br>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><?= $titre ?></h2>
            <p class="text-muted small">Analyse basée sur la période : <strong><?= $label_periode ?></strong></p>
        </div>
        
        <div class="btn-group shadow-sm">
            <a href="?periode=mensuel" class="btn btn-white border <?= $periode == 'mensuel' ? 'active' : '' ?>">Mensuel</a>
            <a href="?periode=trimestriel" class="btn btn-white border <?= $periode == 'trimestriel' ? 'active' : '' ?>">Trimestriel</a>
            <a href="?periode=annuel" class="btn btn-white border <?= $periode == 'annuel' ? 'active' : '' ?>">Annuel</a>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 border-start border-4 border-success h-100">
                <small class="text-muted fw-bold text-uppercase">Top Revenu</small>
                <h4 class="fw-bold m-0 mt-2"><?= htmlspecialchars($top_service['nom_service']) ?></h4>
                <div class="mt-2">
                    <span class="badge bg-success-light text-success">Marge Est. : 65%</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 border-start border-4 border-info h-100">
                <small class="text-muted fw-bold text-uppercase">Volume Total</small>
                <h4 class="fw-bold m-0 mt-2"><?= array_sum(array_column($analyses, 'volume_ventes')) ?> articles</h4>
                <div class="mt-2 text-info small">Traités sur la période</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 border-start border-4 border-warning h-100">
                <small class="text-muted fw-bold text-uppercase">CA Total (Période)</small>
                <h4 class="fw-bold m-0 mt-2"><?= number_format($total_ca, 0, ',', ' ') ?> <small>FCFA</small></h4>
                <small class="text-muted">Revenu brut encaissé</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white h-100 shadow-lg">
                <small class="opacity-75 fw-bold text-uppercase">Performance Globale</small>
                <h4 class="fw-bold m-0 mt-2 text-warning">Score A+</h4>
                <small class="text-success">Analyse de rentabilité fluide</small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="overflow: hidden;">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between">
            <h5 class="fw-bold mb-0">Performance Comparative des Services</h5>
            <button onclick="window.print()" class="btn btn-sm btn-outline-dark"></button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Service</th>
                        <th>Catégorie</th>
                        <th class="text-center">Ventes</th>
                        <th class="text-center">CA Généré</th>
                        <th class="text-center">Marge Brut Est.</th>
                        <th class="text-end pe-4">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($analyses as $a): 
                        $marge_val = $a['ca_total'] - $a['cout_estime_total'];
                        $taux_marge = ($a['ca_total'] > 0) ? ($marge_val / $a['ca_total']) * 100 : 0;
                        $sante_color = ($taux_marge > 50) ? 'success' : (($taux_marge > 30) ? 'info' : 'danger');
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($a['nom_service']) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($a['nom_categorie']) ?></span>
                        </td>
                        <td class="text-center fw-bold"><?= number_format($a['volume_ventes'], 0) ?></td>
                        <td class="text-center fw-bold text-primary"><?= number_format($a['ca_total'], 0, ',', ' ') ?> <small>FCFA</small></td>
                        <td class="text-center">
                            <div class="d-flex align-items-center justify-content-center" style="min-width: 150px;">
                                <span class="me-2 small fw-bold"><?= round($taux_marge) ?>%</span>
                                <div class="progress w-100" style="height: 6px; border-radius: 10px;">
                                    <div class="progress-bar bg-<?= $sante_color ?>" style="width: <?= $taux_marge ?>%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-end pe-4">
                            <?php if($a['volume_ventes'] == 0): ?>
                                <span class="badge bg-light text-muted">Inactif</span>
                            <?php elseif($taux_marge < 40): ?>
                              <span class="text-danger small fw-bold">Optimiser</span>
                            <?php else: ?>
                                <span class="text-success small fw-bold">Rentable</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>