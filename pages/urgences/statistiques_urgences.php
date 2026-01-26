<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Analytique des Services Urgents";

// 1. Statistiques Globales (Simulation)
$stats = [
    'ca_express' => 450000,
    'taux_respect_delai' => 96.5,
    'volume_urgences' => 124,
    'panier_moyen_express' => 3620
];

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-chart-line text-danger me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Analyse de performance et rentabilité des prestations prioritaires</p>
        </div>
        <div class="btn-group shadow-sm">
            <button class="btn btn-outline-dark btn-sm">Mois en cours</button>
            <button class="btn btn-outline-dark btn-sm">Trimestre</button>
            <button class="btn btn-dark btn-sm"><i class="fas fa-download"></i> Rapport PDF</button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <small class="text-muted fw-bold">CA SUPPLÉMENTAIRE</small>
                <h3 class="fw-bold m-0 text-success">+ <?= number_format($stats['ca_express'], 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h3>
                <small class="text-muted">Impact direct des majorations</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white text-center">
                <small class="text-muted fw-bold">RESPECT DES DÉLAIS (SLA)</small>
                <h3 class="fw-bold m-0 text-primary"><?= $stats['taux_respect_delai'] ?>%</h3>
                <div class="progress mt-2" style="height: 5px;">
                    <div class="progress-bar bg-primary" style="width: <?= $stats['taux_respect_delai'] ?>%"></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <small class="text-muted fw-bold">PART DES URGENCES</small>
                <h3 class="fw-bold m-0">18%</h3>
                <small class="text-muted">Du volume total de linge</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-danger text-white">
                <small class="opacity-75 fw-bold">DÉPASSEMENTS DÉLAIS</small>
                <h3 class="fw-bold m-0">4</h3>
                <small>Alertes critiques ce mois</small>
            </div>
        </div>
    </div>

    

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 h-100">
                <h6 class="fw-bold mb-4">Répartition horaire des dépôts express</h6>
                <div class="bg-light rounded d-flex align-items-end justify-content-between p-3" style="height: 300px;">
                    <div class="bg-danger opacity-25" style="width: 10%; height: 20%;" title="8h-10h"></div>
                    <div class="bg-danger opacity-50" style="width: 10%; height: 45%;" title="10h-12h"></div>
                    <div class="bg-danger" style="width: 10%; height: 90%;" title="12h-14h"></div>
                    <div class="bg-danger opacity-75" style="width: 10%; height: 60%;" title="14h-16h"></div>
                    <div class="bg-danger opacity-50" style="width: 10%; height: 35%;" title="16h-18h"></div>
                    <div class="bg-danger opacity-25" style="width: 10%; height: 15%;" title="18h-20h"></div>
                </div>
                <div class="d-flex justify-content-between mt-2 px-2 small text-muted">
                    <span>08h</span><span>12h</span><span>14h</span><span>16h</span><span>20h</span>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0">Services les plus sollicités</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-bolt text-danger me-2"></i> Super Express 2h</span>
                            <span class="badge bg-danger rounded-pill">42%</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-clock text-warning me-2"></i> Express 6h</span>
                            <span class="badge bg-warning text-dark rounded-pill">35%</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-calendar-day text-primary me-2"></i> Veille / 24h</span>
                            <span class="badge bg-primary rounded-pill">23%</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>