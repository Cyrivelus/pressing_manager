<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion du Secteur Hôtelier";

// 1. Récupération des hôtels partenaires
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM tickets WHERE id_client = c.id_client AND statut != 'recupere') as encours,
        (SELECT SUM(montant_total) FROM tickets WHERE id_client = c.id_client AND date_depot >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as volume_mensuel
        FROM clients c 
        WHERE c.notes LIKE '%PARTENAIRE_HOTEL%'";
$hotels = $pdo->query($sql)->fetchAll();

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-hotel text-primary me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Suivi des comptes corporatifs et blanchisserie industrielle</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNouvelHotel">
            <i class="fas fa-plus"></i> Nouveau Partenaire
        </button>
    </div>

    

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-primary text-white">
                <small class="opacity-75 fw-bold">PART DES HÔTELS (CA)</small>
                <h2 class="fw-bold m-0">35 %</h2>
                <small>Contribution au revenu total</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <small class="text-muted fw-bold">LINGE EN TRAITEMENT</small>
                <h2 class="fw-bold m-0 text-dark">1 240 <small class="fs-6">KG</small></h2>
                <small class="text-info">Capacité occupée : 60%</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <small class="text-muted fw-bold">FACTURATION EN ATTENTE</small>
                <h2 class="fw-bold m-0 text-danger">2 150 000 <small class="fs-6 text-muted">FCFA</small></h2>
                <small>Encours total partenaires</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white text-center">
                <small class="opacity-75 fw-bold">QUALITÉ SERVICES</small>
                <h2 class="fw-bold m-0 text-warning">4.9 / 5</h2>
                <small>Score satisfaction Hôtels</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Hôtels sous contrat</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th class="ps-4">Établissement</th>
                                <th>Contact / Téléphone</th>
                                <th class="text-center">Tickets En cours</th>
                                <th class="text-center">Volume (30j)</th>
                                <th>Statut Paiement</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($hotels as $h): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold"><?= htmlspecialchars($h['nom_client']) ?></span><br>
                                    <small class="badge bg-light text-dark">Contrat Or</small>
                                </td>
                                <td>
                                    <div class="small"><?= htmlspecialchars($h['prenom_client']) ?></div>
                                    <div class="small text-muted"><?= $h['telephone'] ?></div>
                                </td>
                                <td class="text-center fw-bold"><?= $h['encours'] ?></td>
                                <td class="text-center"><?= number_format($h['volume_mensuel'], 0, ',', ' ') ?> FCFA</td>
                                <td>
                                    <span class="badge bg-success-soft text-success">À jour</span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" title="Planning de ramassage"><i class="fas fa-truck"></i></button>
                                        <button class="btn btn-sm btn-outline-dark" title="Facture mensuelle"><i class="fas fa-file-invoice-dollar"></i></button>
                                        <button class="btn btn-sm btn-outline-info" title="Modifier tarifs"><i class="fas fa-tags"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>