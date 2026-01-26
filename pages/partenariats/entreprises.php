<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Contrats & Conventions Entreprises";

// 1. Récupération des entreprises partenaires (basé sur un flag ou une catégorie client)
$sql = "SELECT c.*, 
        c.remise_speciale,
        (SELECT COUNT(*) FROM tickets WHERE id_client = c.id_client AND date_depot >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as nb_tickets_mois,
        (SELECT SUM(montant_total) FROM tickets WHERE id_client = c.id_client AND statut != 'recupere') as solde_en_cours
        FROM clients c 
        WHERE c.notes LIKE '%PARTENAIRE_ENTREPRISE%'";
$entreprises = $pdo->query($sql)->fetchAll();

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-success"><i class="fas fa-building me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Gestion des uniformes, EPI et conventions de nettoyage pour collaborateurs</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-success"><i class="fas fa-file-signature"></i> Nouvelle Convention</button>
            <button class="btn btn-success shadow-sm"><i class="fas fa-file-invoice"></i> Facturation Groupée</button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white border-bottom border-4 border-success">
                <small class="text-muted fw-bold">DÉLAI MOYEN DE TRAITEMENT (SLA)</small>
                <h2 class="fw-bold m-0">24h <small class="fs-6 text-success fw-normal">Garanti</small></h2>
                <div class="progress mt-2" style="height: 5px;">
                    <div class="progress-bar bg-success" style="width: 95%"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white border-bottom border-4 border-primary">
                <small class="text-muted fw-bold">TOTAL À RECOUVRER (B2B)</small>
                <h2 class="fw-bold m-0">4 850 000 <small class="fs-6 text-muted">FCFA</small></h2>
                <p class="small text-primary mb-0"><i class="fas fa-clock"></i> Échéance moyenne : 15 jours</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white">
                <small class="opacity-75 fw-bold">PROCHAIN PASSAGE LOGISTIQUE</small>
                <h4 class="fw-bold mb-1">Zone Industrielle Magzi</h4>
                <p class="small text-warning mb-0"><i class="fas fa-truck"></i> Demain à 10h00 (Camion N°2)</p>
            </div>
        </div>
    </div>

    

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="fw-bold mb-0">Portefeuille Entreprises Actif</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4">Raison Sociale</th>
                        <th>Type de Contrat</th>
                        <th class="text-center">Tickets (30j)</th>
                        <th class="text-center">Remise Accordée</th>
                        <th class="text-end">Encours Client</th>
                        <th class="text-end pe-4">Gestion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($entreprises as $e): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold"><?= htmlspecialchars($e['nom_client']) ?></div>
                            <small class="text-muted"><?= $e['telephone'] ?></small>
                        </td>
                        <td>
                            <span class="badge bg-outline-success border border-success text-success">Convention Annuelle</span>
                        </td>
                        <td class="text-center fw-bold"><?= $e['nb_tickets_mois'] ?></td>
                        <td class="text-center text-danger fw-bold">-<?= $e['remise_speciale'] ?>%</td>
                        <td class="text-end fw-bold"><?= number_format($e['solde_en_cours'], 0, ',', ' ') ?> FCFA</td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-light border" title="Liste nominative des employés">
                                <i class="fas fa-users"></i>
                            </button>
                            <button class="btn btn-sm btn-dark" title="Générer relevé mensuel">
                                <i class="fas fa-print"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once  '../../templates/footer.php'; ?>