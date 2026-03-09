<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion des Partenariats B2B";

try {
    // 2. Récupération des statistiques (Correction de l'erreur Undefined variable)
    
    // Nombre d'entreprises (Conventions classiques)
    $sql_ent = "SELECT COUNT(*) FROM clients WHERE type_client = 'Entreprise'";
    $nb_entreprises = $pdo->query($sql_ent)->fetchColumn() ?: 0;

    // Nombre d'hôtels partenaires
    $sql_hotel = "SELECT COUNT(*) FROM clients WHERE type_client = 'Hôtel'";
    $nb_hotels = $pdo->query($sql_hotel)->fetchColumn() ?: 0;

    // Total des commissions à reverser (Exemple basé sur une colonne dans factures)
    $sql_comm = "SELECT SUM(montant_commission) FROM factures WHERE statut_commission = 'En attente'";
    $total_commissions = $pdo->query($sql_comm)->fetchColumn() ?: 0;

    // 3. Récupération des 5 derniers contrats/conventions
    $sql_recent = "SELECT nom_client, telephone_client, date_creation 
                   FROM clients 
                   WHERE type_client IN ('Entreprise', 'Hôtel') 
                   ORDER BY date_creation DESC LIMIT 5";
    $partenaires_recents = $pdo->query($sql_recent)->fetchAll();

} catch (PDOException $e) {
    $error = "Erreur de chargement des données : " . $e->getMessage();
    $nb_entreprises = 0; $nb_hotels = 0; $total_commissions = 0;
}

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-primary"><?= $titre ?></h2>
            <p class="text-muted">Gérez vos relations entreprises, hôtels et apporteurs d'affaires.</p>
        </div>
    </div>

    <div class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="bg-primary text-white p-3 rounded-3 me-3">
                       
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small fw-bold">ENTREPRISES</h6>
                        <h2 class="fw-bold mb-0"><?= $nb_entreprises ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="bg-info text-white p-3 rounded-3 me-3">
                       
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small fw-bold">HÔTELLERIE</h6>
                        <h2 class="fw-bold mb-0"><?= $nb_hotels ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-dark text-white">
                <div class="d-flex align-items-center">
                    <div class="bg-warning text-dark p-3 rounded-3 me-3">
                      
                    </div>
                    <div>
                        <h6 class="text-white-50 mb-0 small fw-bold">COMMISSIONS DUES</h6>
                        <h2 class="fw-bold mb-0"><?= number_format($total_commissions, 0, ',', ' ') ?> <small class="fs-6">F</small></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <a href="entreprises.php" class="card border-0 shadow-sm rounded-4 text-center p-4 text-decoration-none hover-card">
             
                <h5 class="fw-bold text-dark">Entreprises</h5>
                <p class="text-muted small">Gestion des comptes sociétés et facturation groupée.</p>
            </a>
        </div>
        <div class="col-md-3">
            <a href="hotellerie.php" class="card border-0 shadow-sm rounded-4 text-center p-4 text-decoration-none hover-card">
              
                <h5 class="fw-bold text-dark">Hôtellerie</h5>
                <p class="text-muted small">Suivi des dépôts linges et conciergerie hôtels.</p>
            </a>
        </div>
        <div class="col-md-3">
            <a href="conventions.php" class="card border-0 shadow-sm rounded-4 text-center p-4 text-decoration-none hover-card">
             
                <h5 class="fw-bold text-dark">Conventions</h5>
                <p class="text-muted small">Archives des contrats et tarifs préférentiels.</p>
            </a>
        </div>
        <div class="col-md-3">
            <a href="commissions.php" class="card border-0 shadow-sm rounded-4 text-center p-4 text-decoration-none hover-card">
             
                <h5 class="fw-bold text-dark">Commissions</h5>
                <p class="text-muted small">Calcul et versement des parts apporteurs.</p>
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="fw-bold mb-0">Nouveaux partenaires récents</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Nom / Raison Sociale</th>
                        <th>Téléphone</th>
                        <th>Date d'adhésion</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($partenaires_recents)): ?>
                        <tr><td colspan="4" class="text-center py-4">Aucun partenaire enregistré.</td></tr>
                    <?php else: ?>
                        <?php foreach($partenaires_recents as $p): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?= htmlspecialchars($p['nom_client']) ?></td>
                            <td><?= htmlspecialchars($p['telephone_client']) ?></td>
                            <td><?= date('d/m/Y', strtotime($p['date_creation'])) ?></td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light border"></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .hover-card { transition: all 0.3s ease; border: 1px solid transparent !important; }
    .hover-card:hover { transform: translateY(-10px); background-color: #f8f9fa; border-color: #0d6efd !important; }
</style>

<?php require_once '../../templates/footer.php'; ?>