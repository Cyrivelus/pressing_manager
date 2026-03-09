<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Inventaire des Chambres & Suites";

// 1. Récupération des hôtels pour le filtre
try {
    $hotels = $pdo->query("SELECT id_client, nom_client FROM clients WHERE notes LIKE '%PARTENAIRE_HOTEL%'")->fetchAll();
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// 2. Filtre par hôtel
$id_hotel_filtre = $_GET['hotel_id'] ?? ($hotels[0]['id_client'] ?? null);

// 3. Récupération des chambres et de leur état
$chambres = [];
if ($id_hotel_filtre) {
    /* Note technique : Ici on utilise 'numero_ticket' au lieu de 'reference_ticket'.
       On suppose que le numéro de chambre est la partie après le tiret dans le numéro de ticket.
    */
    $sql = "SELECT 
                SUBSTRING_INDEX(t.numero_ticket, '-', -1) as num_chambre,
                COUNT(t.id_ticket) as nb_tickets_actifs,
                SUM(t.montant_total) as montant_encours,
                MAX(t.date_depot) as dernier_depot
            FROM tickets t
            WHERE t.id_client = :id_hotel
            AND t.statut != 'recupere'
            GROUP BY num_chambre
            ORDER BY num_chambre ASC";
            
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id_hotel' => $id_hotel_filtre]);
        $chambres = $stmt->fetchAll();
    } catch (PDOException $e) {
        // En cas d'erreur sur la colonne, on affiche un message explicatif
        $debug_error = "Erreur SQL : " . $e->getMessage();
    }
}

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    .fw-black { font-weight: 900; }
    .bg-soft-primary { background-color: #e7f0ff; color: #0d6efd; }
    .bg-soft-info { background-color: #e0f7fa; color: #00acc1; }
    .hover-lift { transition: transform 0.25s ease, box-shadow 0.25s ease; border: 1px solid #eee !important; }
    .hover-lift:hover { transform: translateY(-10px); box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important; }
    .icon-shape { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
    .status-dot { height: 10px; width: 10px; background-color: #28a745; border-radius: 50%; display: inline-block; margin-right: 5px; }
</style>

<br><br><br>
<div class="container-fluid py-5 px-4">
    
    <?php if (isset($debug_error)): ?>
        <div class="alert alert-danger shadow-sm"><?= $debug_error ?></div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5 mt-4 gap-3">
        <div>
            <h2 class="fw-black text-dark m-0" style="letter-spacing: -1px;">🏨 <?= $titre ?></h2>
            <p class="text-muted mb-0">Suivi analytique du linge par numéro de chambre</p>
        </div>
        
        <form method="GET" class="d-flex gap-3 align-items-center bg-white p-2 px-3 rounded-pill shadow-sm border">
            <span class="small fw-bold text-muted text-uppercase" style="font-size: 0.7rem;">Sélection Hôtel</span>
            <select name="hotel_id" class="form-select border-0 bg-transparent fw-bold" style="cursor:pointer;" onchange="this.form.submit()">
                <?php if (empty($hotels)): ?>
                    <option>Aucun hôtel partenaire</option>
                <?php endif; ?>
                <?php foreach($hotels as $h): ?>
                    <option value="<?= $h['id_client'] ?>" <?= ($id_hotel_filtre == $h['id_client']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($h['nom_client']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white rounded-4">
                <small class="text-muted fw-bold d-block mb-1 text-uppercase" style="font-size: 0.7rem;">Chambres Actives</small>
                <div class="d-flex justify-content-between align-items-end">
                    <h2 class="fw-black m-0"><?= count($chambres) ?></h2>
                    <div class="icon-shape bg-soft-primary rounded-circle">🏠</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white rounded-4">
                <small class="text-muted fw-bold d-block mb-1 text-uppercase" style="font-size: 0.7rem;">Sacs / Tickets</small>
                <div class="d-flex justify-content-between align-items-end">
                    <h2 class="fw-black m-0 text-primary"><?= array_sum(array_column($chambres, 'nb_tickets_actifs')) ?></h2>
                    <div class="icon-shape bg-soft-info rounded-circle">🧺</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white rounded-4">
                <small class="opacity-50 fw-bold d-block mb-1 text-uppercase" style="font-size: 0.7rem;">Valorisation Encours</small>
                <div class="d-flex justify-content-between align-items-end">
                    <h2 class="fw-black m-0 text-warning"><?= number_format(array_sum(array_column($chambres, 'montant_encours')), 0, ',', ' ') ?> <small style="font-size: 1rem;">F</small></h2>
                    <div class="icon-shape bg-secondary rounded-circle">💰</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <?php if (empty($chambres)): ?>
            <div class="col-12 text-center py-5">
                <div class="display-1 mb-3">📭</div>
                <h5 class="text-muted">Aucune activité de linge pour cet établissement.</h5>
                <p>Les tickets créés avec ce client apparaîtront ici.</p>
            </div>
        <?php else: ?>
            <?php foreach($chambres as $c): ?>
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden hover-lift">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h4 class="fw-black m-0">CH. <?= htmlspecialchars($c['num_chambre']) ?></h4>
                                <small class="text-muted">Depuis le : <?= date('d/m/Y', strtotime($c['dernier_depot'])) ?></small>
                            </div>
                            <span class="badge rounded-pill bg-light text-success border border-success small px-2">
                                <span class="status-dot"></span> Actif
                            </span>
                        </div>
                        
                        <div class="bg-light p-3 rounded-3 mb-4">
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-muted">Tickets cumulés :</span>
                                <span class="fw-bold"><?= $c['nb_tickets_actifs'] ?> sac(s)</span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Total facturé :</span>
                                <span class="fw-bold text-primary"><?= number_format($c['montant_encours'], 0, ',', ' ') ?> F</span>
                            </div>
                        </div>

                        <div class="d-grid">
                            <a href="../tickets/liste.php?recherche=<?= urlencode($c['num_chambre']) ?>" class="btn btn-primary btn-sm rounded-pill fw-bold py-2">
                                Détails du linge
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>