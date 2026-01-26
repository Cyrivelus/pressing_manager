<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Carnet de Santé des Équipements";

// 1. Récupération des statistiques globales par machine
$sql = "SELECT 
            e.id_equipement, e.nom_equipement, e.date_achat, e.prix_achat,
            COUNT(i.id_intervention) as total_interventions,
            SUM(i.cout_total) as cumuls_frais,
            MAX(i.date_intervention) as derniere_reparation
        FROM equipements e
        LEFT JOIN interventions i ON e.id_equipement = i.id_equipement
        GROUP BY e.id_equipement";
$machines = $pdo->query($sql)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-history text-info me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Analyse du cycle de vie et rentabilité de vos actifs matériels</p>
        </div>
        <div class="btn-group">
            <button class="btn btn-outline-primary btn-sm"><i class="fas fa-file-pdf"></i> Rapport Annuel</button>
            <button class="btn btn-outline-primary btn-sm"><i class="fas fa-print"></i> Imprimer fiches</button>
        </div>
    </div>

    

    <div class="row g-4">
        <?php foreach($machines as $m): 
            $age_jours = (time() - strtotime($m['date_achat'])) / (60 * 60 * 24);
            $ratio_maintenance = ($m['prix_achat'] > 0) ? ($m['cumuls_frais'] / $m['prix_achat']) * 100 : 0;
            $etat_color = ($ratio_maintenance > 50) ? 'danger' : (($ratio_maintenance > 20) ? 'warning' : 'success');
        ?>
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-uppercase"><?= htmlspecialchars($m['nom_equipement']) ?></h5>
                            <small class="text-muted">Acquis le : <?= date('d/m/Y', strtotime($m['date_achat'])) ?></small>
                        </div>
                        <span class="badge bg-<?= $etat_color ?>-soft text-<?= $etat_color ?> px-3 py-2">
                            Index d'usure : <?= round($ratio_maintenance, 1) ?>%
                        </span>
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-4">
                            <div class="p-3 bg-light rounded text-center">
                                <small class="text-muted d-block">Interventions</small>
                                <span class="fw-bold h5"><?= $m['total_interventions'] ?></span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 bg-light rounded text-center">
                                <small class="text-muted d-block">Frais Cumulés</small>
                                <span class="fw-bold h5"><?= number_format($m['cumuls_frais'], 0, ',', ' ') ?></span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 bg-light rounded text-center">
                                <small class="text-muted d-block">Dernière Op.</small>
                                <span class="fw-bold small"><?= $m['derniere_reparation'] ? date('d/m/y', strtotime($m['derniere_reparation'])) : 'N/A' ?></span>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3 small text-muted text-uppercase">Timeline technique</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="text-muted">
                                <tr>
                                    <th>Date</th>
                                    <th>Nature</th>
                                    <th>Coût</th>
                                    <th class="text-end">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>12/11/25</td>
                                    <td>Remplacement roulements</td>
                                    <td>45 000</td>
                                    <td class="text-end text-success"><i class="fas fa-check-circle"></i></td>
                                </tr>
                                <tr>
                                    <td>05/08/25</td>
                                    <td>Vidange & Filtres</td>
                                    <td>12 000</td>
                                    <td class="text-end text-success"><i class="fas fa-check-circle"></i></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-top-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-muted">Alerte : Prochaine révision suggérée dans 15 jours.</span>
                        <a href="interventions.php?id=<?= $m['id_equipement'] ?>" class="btn btn-sm btn-outline-info">Fiche Complète</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>