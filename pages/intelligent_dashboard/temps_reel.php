<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Live Monitor - Temps Réel";
$aujourdhui = date('Y-m-d');

// 2. Récupération des données réelles
try {
    // CA du jour (Basé sur date_depot au lieu de date_reception)
    $stmt_ca = $pdo->prepare("SELECT SUM(montant_total) FROM tickets WHERE date_depot >= ?");
    $stmt_ca->execute([$aujourdhui . ' 00:00:00']);
    $ca_jour = $stmt_ca->fetchColumn() ?? 0;

    // Flux clients (Nombre de dépôts aujourd'hui)
    $stmt_flux = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE date_depot >= ?");
    $stmt_flux->execute([$aujourdhui . ' 00:00:00']);
    $nb_tickets = $stmt_flux->fetchColumn();

    // Statistiques Atelier (Articles prêts vs Total en cours)
    $stmt_atelier = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM tickets WHERE statut = 'pret' AND date_depot >= '$aujourdhui 00:00:00') as prets,
        (SELECT COUNT(*) FROM tickets WHERE statut != 'recupere' AND statut != 'annule') as total_encours");
    $stats_atelier = $stmt_atelier->fetch();

    // Récupération des 10 dernières activités réelles
    $sql_activite = "SELECT t.*, c.nom_client, c.prenom_client 
                     FROM tickets t 
                     JOIN clients c ON t.id_client = c.id_client 
                     ORDER BY t.date_depot DESC LIMIT 10";
    $activites = $pdo->query($sql_activite)->fetchAll();

} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}

require_once  '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    .live-dot { height: 10px; width: 10px; background-color: #2ecc71; border-radius: 50%; display: inline-block; animation: pulse 2s infinite; }
    @keyframes pulse { 0% { box-shadow: 0 0 0 0px rgba(46, 204, 113, 0.7); } 100% { box-shadow: 0 0 0 10px rgba(46, 204, 113, 0); } }
    .stat-card { border: none; border-radius: 15px; transition: transform 0.3s; }
    .stat-card:hover { transform: translateY(-5px); }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); color: #0dcaf0; }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
</style>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0"><span class="live-dot me-2"></span> <?= $titre ?></h2>
            <p class="text-muted">Activités du <?= date('d/m/Y') ?> <span class="badge bg-light text-dark border ms-2">Live</span></p>
        </div>
        <div class="text-end">
            <span class="badge bg-dark p-2 fs-6 shadow-sm">🕒 <span id="clock">--:--:--</span></span>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card stat-card shadow-sm bg-primary text-white p-4">
                <small class="text-uppercase opacity-75">Chiffre d'Affaires</small>
                <h2 class="fw-bold m-0"><?= number_format($ca_jour, 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h2>
                <div class="mt-3 small"><i class="fas fa-chart-line"></i> Cumul des dépôts jour</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm bg-white p-4">
                <small class="text-uppercase text-muted fw-bold">Nouveaux Dépôts</small>
                <h2 class="fw-bold m-0 text-dark"><?= $nb_tickets ?> <small class="fs-6 text-muted">Tickets</small></h2>
                <div class="mt-3 small text-primary fw-bold">Aujourd'hui</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm bg-success text-white p-4">
                <small class="text-uppercase opacity-75">Production Atelier</small>
                <h2 class="fw-bold m-0"><?= $stats_atelier['prets'] ?> / <?= $stats_atelier['total_encours'] ?></h2>
                <div class="mt-3 small">Tickets prêts vs Total en cours</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm bg-warning text-dark p-4">
                <small class="text-uppercase opacity-75 fw-bold">Alertes / Retards</small>
                <?php
                // Simulation ou requête réelle pour les retards
                $retards = 2; 
                ?>
                <h2 class="fw-bold m-0"><?= $retards ?> <small class="fs-6">Actions</small></h2>
                <div class="mt-3 small fw-bold text-danger"><i class="fas fa-clock"></i> Délais à surveiller</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-history me-2"></i>Dernières opérations enregistrées</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Heure</th>
                                    <th>Type</th>
                                    <th>Client</th>
                                    <th>Statut</th>
                                    <th class="text-end pe-4">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($activites)): ?>
                                    <tr><td colspan="5" class="text-center py-4">Aucune activité aujourd'hui</td></tr>
                                <?php else: ?>
                                    <?php foreach($activites as $act): ?>
                                    <tr>
                                        <td class="ps-4 text-muted small"><?= date('H:i', strtotime($act['date_depot'])) ?></td>
                                        <td><span class="badge bg-info-soft">Dépôt</span></td>
                                        <td><strong><?= htmlspecialchars($act['nom_client'] . ' ' . $act['prenom_client']) ?></strong></td>
                                        <td>
                                            <?php 
                                            $badge = ($act['statut'] == 'pret') ? 'success' : 'warning';
                                            echo "<span class='badge bg-$badge'>".$act['statut']."</span>";
                                            ?>
                                        </td>
                                        <td class="text-end pe-4 fw-bold"><?= number_format($act['montant_total'], 0, ',', ' ') ?> FCFA</td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 mb-4">
                <h6 class="fw-bold mb-4">Efficacité Production</h6>
                <?php
                $pct_efficacite = ($stats_atelier['total_encours'] > 0) ? ($stats_atelier['prets'] / $stats_atelier['total_encours']) * 100 : 0;
                ?>
                <div class="text-center">
                   <div class="display-4 fw-bold text-primary"><?= round($pct_efficacite) ?>%</div>
                   <p class="text-muted small">Taux de commandes prêtes</p>
                </div>
                <div class="progress mt-2" style="height: 10px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $pct_efficacite ?>%"></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-4 rounded-4 bg-dark text-white">
                <h6 class="fw-bold mb-3 text-warning">Rappel Entretien</h6>
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-tools me-3 fa-2x opacity-50"></i>
                    <div>
                        <small class="d-block opacity-75">Machine N°2</small>
                        <span class="small">Nettoyage filtre requis dans 4h</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function updateClock() {
        const now = new Date();
        document.getElementById('clock').innerText = now.toLocaleTimeString('fr-FR');
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Rafraîchir les données toutes les 60 secondes sans recharger toute la page (Optionnel)
    setTimeout(function(){ location.reload(); }, 60000);
</script>

<?php require_once  '../../templates/footer.php'; ?>