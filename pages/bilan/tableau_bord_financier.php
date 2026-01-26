<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Tableau de Bord Financier";

// Statistiques du mois en cours
$moisActuel = date('m');
$anneeActuelle = date('Y');

$stats = $pdo->query("
    SELECT 
        (SELECT SUM(montant_total) FROM tickets WHERE MONTH(date_depot) = $moisActuel AND YEAR(date_depot) = $anneeActuelle) as recettes,
        (SELECT SUM(montant) FROM depenses WHERE MONTH(date_depense) = $moisActuel AND YEAR(date_depense) = $anneeActuelle) as charges
")->fetch();

$recettes = $stats['recettes'] ?? 0;
$charges = $stats['charges'] ?? 0;
$solde = $recettes - $charges;

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <h2 class="fw-bold m-0"><i class="fas fa-chart-line text-primary me-2"></i><?= $titre ?></h2>
        <span class="badge bg-light text-dark p-2"><?= date('F Y') ?></span>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 border-start border-5 border-success">
                <small class="text-uppercase fw-bold text-muted">Chiffre d'Affaires</small>
                <h2 class="fw-bold mt-2"><?= number_format($recettes, 0, ',', ' ') ?> F</h2>
                <div class="progress mt-3" style="height: 5px;"><div class="progress-bar bg-success" style="width: 100%"></div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 border-start border-5 border-danger">
                <small class="text-uppercase fw-bold text-muted">Charges Totales</small>
                <h2 class="fw-bold mt-2"><?= number_format($charges, 0, ',', ' ') ?> F</h2>
                <div class="progress mt-3" style="height: 5px;"><div class="progress-bar bg-danger" style="width: <?= ($recettes > 0) ? ($charges/$recettes)*100 : 0 ?>%"></div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow p-4 bg-primary text-white">
                <small class="text-uppercase fw-bold opacity-75">Solde Caisse</small>
                <h2 class="fw-bold mt-2"><?= number_format($solde, 0, ',', ' ') ?> F</h2>
                <i class="fas fa-wallet position-absolute end-0 bottom-0 m-3 opacity-25 fa-3x"></i>
            </div>
        </div>
    </div>
</div>