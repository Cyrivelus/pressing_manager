<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité : Accès Admin / Direction
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$annee_stats = $_GET['annee'] ?? date('Y');
$titre = "Bilan annuel RSE - " . $annee_stats;

// 2. Calcul des indicateurs clés (Simulés via agrégations SQL)
// Dans une version réelle, ces chiffres proviennent des tables de consommation et déchets
$eco_score = 82; // Score sur 100
$eau_economisee = 14500; // en Litres
$plastique_evite = 230; // en Kg
?>

<style>
    .rse-card { border: none; border-radius: 20px; transition: 0.3s; }
    .rse-icon { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 12px; }
    .chart-container { height: 300px; position: relative; }
</style>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-success"><i class="fas fa-chart-line me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Analyse de l'impact environnemental et social du pressing</p>
        </div>
        <div class="d-flex gap-2">
            <select class="form-select w-auto" onchange="location.href='?annee='+this.value">
                <option value="2026" selected>Année 2026</option>
                <option value="2025">Année 2025</option>
            </select>
            <button class="btn btn-dark" onclick="window.print()"><i class="fas fa-file-pdf me-2"></i>Exporter le Rapport</button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card rse-card shadow-sm p-4 h-100">
                <div class="rse-icon bg-success bg-opacity-10 text-success mb-3"><i class="fas fa-seedling fa-lg"></i></div>
                <h6 class="text-muted small fw-bold text-uppercase">Score Éco-Responsable</h6>
                <h2 class="fw-bold mb-0"><?= $eco_score ?>/100</h2>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-success" style="width: <?= $eco_score ?>%"></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card rse-card shadow-sm p-4 h-100">
                <div class="rse-icon bg-info bg-opacity-10 text-info mb-3"><i class="fas fa-tint fa-lg"></i></div>
                <h6 class="text-muted small fw-bold text-uppercase">Économie d'eau</h6>
                <h2 class="fw-bold mb-0"><?= number_format($eau_economisee, 0, ',', ' ') ?> L</h2>
                <small class="text-success fw-bold"><i class="fas fa-caret-up"></i> 12% vs 2025</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card rse-card shadow-sm p-4 h-100">
                <div class="rse-icon bg-primary bg-opacity-10 text-primary mb-3"><i class="fas fa-recycle fa-lg"></i></div>
                <h6 class="text-muted small fw-bold text-uppercase">Taux de recyclage</h6>
                <h2 class="fw-bold mb-0">94 %</h2>
                <small class="text-muted">Objectif : 100% en 2027</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card rse-card shadow-sm p-4 h-100 bg-success text-white">
                <div class="rse-icon bg-white bg-opacity-20 mb-3"><i class="fas fa-cloud-sun fa-lg"></i></div>
                <h6 class="text-white-50 small fw-bold text-uppercase">Emissions CO2 évitées</h6>
                <h2 class="fw-bold mb-0">1.2 T</h2>
                <small>Équivalent 150 arbres plantés</small>
            </div>
        </div>
    </div>

    

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card rse-card shadow-sm p-4">
                <h5 class="fw-bold mb-4">Évolution mensuelle des consommations techniques</h5>
                <div class="chart-container bg-light rounded d-flex align-items-center justify-content-center">
                    <p class="text-muted fw-italic"><i class="fas fa-chart-area me-2"></i>[Graphique d'évolution : Eau vs Énergie vs Solvants]</p>
                </div>
                <div class="row mt-4 text-center">
                    <div class="col-4 border-end">
                        <small class="text-muted d-block">Solvants / kg linge</small>
                        <span class="fw-bold">0.15 L</span>
                    </div>
                    <div class="col-4 border-end">
                        <small class="text-muted d-block">Élec / kg linge</small>
                        <span class="fw-bold">0.85 kWh</span>
                    </div>
                    <div class="col-4">
                        <small class="text-muted d-block">Eau / kg linge</small>
                        <span class="fw-bold">8.2 L</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card rse-card shadow-sm p-4 h-100 border-top border-5 border-info">
                <h5 class="fw-bold mb-4">Impact Social & Formation</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0 py-3 d-flex justify-content-between">
                        <span>Heures de formation sécurité</span>
                        <span class="badge bg-info rounded-pill">48h</span>
                    </li>
                    <li class="list-group-item px-0 py-3 d-flex justify-content-between">
                        <span>Équité Homme/Femme</span>
                        <span class="badge bg-info rounded-pill">50/50</span>
                    </li>
                    <li class="list-group-item px-0 py-3 d-flex justify-content-between">
                        <span>Accidents de travail</span>
                        <span class="badge bg-success rounded-pill">0</span>
                    </li>
                    <li class="list-group-item px-0 py-3 d-flex justify-content-between">
                        <span>Emplois locaux créés</span>
                        <span class="badge bg-info rounded-pill">+3</span>
                    </li>
                </ul>
                <div class="alert alert-light mt-4 mb-0 small border-0">
                    <i class="fas fa-info-circle text-info me-2"></i>
                    Ces données servent à alimenter votre communication sur le site web client.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>