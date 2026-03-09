<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';


// 1. Récupération des paramètres (Simulation ou base de données)
$campagne_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$titre_campagne = "Promotion Spécial Couettes - Janvier 2026";

// 2. Simulation de données de performance (À remplacer par vos requêtes SQL réelles)
$stats = [
    'envoyes' => 850,
    'ouverts' => 210,
    'clics' => 45,
    'desabonnements' => 2,
    'ca_genere' => 125000,
    'date_envoi' => '15/01/2026 à 09:00'
];

// Calculs des taux
$taux_ouverture = round(($stats['ouverts'] / $stats['envoyes']) * 100, 1);
$taux_clic = round(($stats['clics'] / $stats['ouverts']) * 100, 1);

$titre = "Rapport Analytique Marketing";
require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    .progress-custom { height: 10px; border-radius: 5px; background-color: #e9ecef; overflow: hidden; }
    .progress-bar-fill { height: 100%; transition: width 0.6s ease; }
    .stat-box { border-left: 5px solid #0d6efd; background: #fff; }
    .btn-back { text-decoration: none; font-weight: bold; color: #6c757d; }
    .btn-back:hover { color: #0d6efd; }
    .print-zone { border: 1px dashed #dee2e6; padding: 20px; border-radius: 10px; }
</style>

<div class="container-fluid py-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
        <div>
            <a href="index.php" class="btn-back small text-uppercase">[ ← Retour aux campagnes ]</a>
            <h2 class="fw-bold m-0 text-dark mt-2">[RAPPORT] <?= $titre_campagne ?></h2>
            <p class="text-muted">Analyse de l'engagement et impact sur le chiffre d'affaires</p>
        </div>
        <style>
    @media print {
        /* Masque tout ce qui n'est pas le contenu principal */
        header, footer, nav, .navbar, .sidebar, .btn, .no-print {
            display: none !important;
        }
        
        /* Ajuste les marges de la page imprimée */
        @page {
            margin: 2cm;
        }

        /* Force le fond blanc et les couleurs pour l'impression */
        body {
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .container, .container-fluid {
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Garde les bordures et les couleurs des cartes */
        .card {
            border: 1px solid #dee2e6 !important;
            break-inside: avoid; /* Évite de couper une carte entre deux pages */
        }
    }
</style>

<div class="d-flex gap-2">
    <button onclick="window.print()" class="btn btn-outline-dark fw-bold px-4 shadow-sm" style="cursor: pointer;">
        IMPRIMER LE RAPPORT
    </button>
    <button class="btn btn-success fw-bold px-4 shadow-sm" style="cursor: pointer;">
        EXPORTER CSV
    </button>
</div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 stat-box" style="border-color: #6c757d;">
                <small class="text-muted fw-bold d-block mb-1 text-uppercase">Cible Totale</small>
                <h3 class="fw-bold m-0"><?= number_format($stats['envoyes'], 0, ',', ' ') ?></h3>
                <small class="text-muted fw-bold">Emails délivrés</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 stat-box" style="border-color: #0dcaf0;">
                <small class="text-muted fw-bold d-block mb-1 text-uppercase">Ouvertures Uniques</small>
                <h3 class="fw-bold m-0"><?= $stats['ouverts'] ?></h3>
                <small class="text-info fw-bold">Taux : <?= $taux_ouverture ?>%</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 stat-box" style="border-color: #fd7e14;">
                <small class="text-muted fw-bold d-block mb-1 text-uppercase">Clics sur liens</small>
                <h3 class="fw-bold m-0"><?= $stats['clics'] ?></h3>
                <small class="text-warning fw-bold">Réactivité : <?= $taux_clic ?>%</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 stat-box" style="border-color: #198754;">
                <small class="text-muted fw-bold d-block mb-1 text-uppercase">C.A. Estimé</small>
                <h3 class="fw-bold m-0 text-success"><?= number_format($stats['ca_genere'], 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h3>
                <small class="text-dark fw-bold">Retombées directes</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h5 class="fw-bold mb-4 text-uppercase small text-muted">Tunnel de conversion</h5>
                
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-bold small">Délivrabilité (Envois réussis)</span>
                        <span class="fw-bold small">100%</span>
                    </div>
                    <div class="progress-custom">
                        <div class="progress-bar-fill bg-secondary" style="width: 100%"></div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-bold small">Ouverture (Engagement)</span>
                        <span class="fw-bold small"><?= $taux_ouverture ?>%</span>
                    </div>
                    <div class="progress-custom">
                        <div class="progress-bar-fill bg-info" style="width: <?= $taux_ouverture ?>%"></div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-bold small">Clics (Intérêt produit)</span>
                        <span class="fw-bold small"><?= $taux_clic ?>%</span>
                    </div>
                    <div class="progress-custom">
                        <div class="progress-bar-fill bg-warning" style="width: <?= $taux_clic ?>%"></div>
                    </div>
                </div>
                
                <div class="mt-4 print-zone bg-light">
                    <h6 class="fw-bold text-uppercase small">[ Aperçu du message envoyé ]</h6>
                    <hr>
                    <p class="small text-muted mb-1"><strong>Objet :</strong> <?= $titre_campagne ?></p>
                    <p class="small text-muted mb-3"><strong>Expéditeur :</strong> Pressing Manager Cloud</p>
                    <div class="bg-white p-3 border rounded small">
                        Bonjour cher client,<br><br>
                        Profitez de notre offre exceptionnelle de <strong>-20% sur le nettoyage de vos couettes</strong> 
                        jusqu'à la fin du mois...
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 mb-4 border-top border-4 border-primary">
                <h5 class="fw-bold mb-3 text-uppercase small text-muted">Répartition de l'audience</h5>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <tbody>
                            <tr>
                                <td class="py-2">Clients Fidèles (VIP)</td>
                                <td class="text-end fw-bold">65%</td>
                            </tr>
                            <tr>
                                <td class="py-2">Nouveaux Clients</td>
                                <td class="text-end fw-bold">20%</td>
                            </tr>
                            <tr>
                                <td class="py-2">Clients Inactifs</td>
                                <td class="text-end fw-bold">15%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-4 bg-dark text-white">
                <h6 class="fw-bold text-uppercase small text-warning mb-3">Observation de l'Expert</h6>
                <p class="small mb-0 opacity-75">
                    Cette campagne présente un taux d'ouverture supérieur à la moyenne de votre pressing (24.8% contre 18%). 
                    L'offre "Couettes" semble particulièrement attractive en cette période de froid. 
                    <strong>Conseil :</strong> Relancez les clients qui ont cliqué mais n'ont pas encore déposé d'articles sous 48h.
                </p>
            </div>
        </div>
    </div>

</div>

<?php require_once '../../templates/footer.php'; ?>