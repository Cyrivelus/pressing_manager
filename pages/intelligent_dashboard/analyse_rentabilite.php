<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Analyse de Rentabilité par Service";

/**
 * LOGIQUE DE CALCUL
 * On croise les revenus (lignes_ticket) avec les services
 */
try {
    $query = "
        SELECT 
            s.nom_service,
            cs.nom_categorie,
            COUNT(lt.id_ligne) as volume_ventes,
            IFNULL(SUM(lt.sous_total), 0) as ca_total,
            s.prix_unitaire,
            -- Simulation d'un coût de revient (35% du CA pour consommables/énergie)
            (IFNULL(SUM(lt.sous_total), 0) * 0.35) as cout_estime_total
        FROM services s
        JOIN categories_service cs ON s.id_categorie = cs.id_categorie
        LEFT JOIN lignes_ticket lt ON s.id_service = lt.id_service
        GROUP BY s.id_service, s.nom_service, cs.nom_categorie
        ORDER BY ca_total DESC
    ";
    $analyses = $pdo->query($query)->fetchAll();
} catch (PDOException $e) {
    die("Erreur d'analyse : " . $e->getMessage());
}

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark">
                <i class="fas fa-chart-pie text-primary me-2"></i><?= $titre ?>
            </h2>
            <p class="text-muted">Déterminez vos services les plus profitables (Marge vs Volume)</p>
        </div>
        <div class="btn-group shadow-sm">
            <button class="btn btn-white border">Mensuel</button>
            <button class="btn btn-white border active">Trimestriel</button>
            <button class="btn btn-white border">Annuel</button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 border-start border-4 border-success h-100">
                <small class="text-muted fw-bold text-uppercase">Top Service (Marge)</small>
                <h4 class="fw-bold m-0 mt-2">Nettoyage à Sec</h4>
                <div class="mt-2">
                    <span class="badge bg-success-light text-success">Marge : 65%</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 border-start border-4 border-info h-100">
                <small class="text-muted fw-bold text-uppercase">Top Volume</small>
                <h4 class="fw-bold m-0 mt-2">Chemise Homme</h4>
                <div class="mt-2">
                    <span class="badge bg-info-light text-info">1 240 unités / mois</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 border-start border-4 border-warning h-100">
                <small class="text-muted fw-bold text-uppercase">Seuil de Rentabilité</small>
                <h4 class="fw-bold m-0 mt-2">18 Janvier</h4>
                <small class="text-muted">Charges fixes couvertes</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white h-100 shadow-lg">
                <small class="opacity-75 fw-bold text-uppercase">Marge Nette Globale</small>
                <h4 class="fw-bold m-0 mt-2 text-warning">32.4 %</h4>
                <small class="text-success"><i class="fas fa-caret-up"></i> +2.1% vs N-1</small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold mb-0">Performance Comparative des Services</h5>
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
                        <th class="text-end pe-4">Indice de Santé</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($analyses as $a): 
                        $marge = $a['ca_total'] - $a['cout_estime_total'];
                        // Correction du nom de la variable (ajout du x)
                        $taux_marge = ($a['ca_total'] > 0) ? ($marge / $a['ca_total']) * 100 : 0;
                        
                        // Attribution de la couleur selon le taux de marge corrigé
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
                            <?php if($taux_marge <= 0 && $a['volume_ventes'] == 0): ?>
                                <span class="badge bg-light text-muted fw-normal">Aucune donnée</span>
                            <?php elseif($taux_marge < 40): ?>
                                <span class="text-danger small fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> À optimiser</span>
                            <?php else: ?>
                                <span class="text-success small fw-bold"><i class="fas fa-check-circle me-1"></i> Rentable</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .bg-success-light { background-color: rgba(25, 135, 84, 0.1); }
    .bg-info-light { background-color: rgba(13, 202, 240, 0.1); }
</style>

<?php 
// Correction du chemin du footer
require_once '../../templates/footer.php'; 
?>