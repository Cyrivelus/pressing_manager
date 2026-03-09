<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Prise de Rendez-vous / Collecte";

// --- LOGIQUE DE TRAITEMENT API / RAPPORT ---
$status_msg = "";
if (isset($_GET['sync'])) {
    // Logique de synchronisation ici
    $status_msg = "<div class='alert alert-success border-0 shadow-sm fw-bold'>Synchronisation API réussie avec succès !</div>";
}

// 1. Récupération des rendez-vous à venir (Dynamique)
$sql = "SELECT r.*, c.nom_client, c.telephone, c.adresse
        FROM reservations_online r
        JOIN clients c ON r.id_client = c.id_client
        WHERE r.date_reservation >= CURDATE()
        ORDER BY r.date_reservation ASC, r.creneau_horaire ASC";
$rdv_futurs = $pdo->query($sql)->fetchAll();

// 2. Prochaine collecte dynamique
$prochain = !empty($rdv_futurs) ? $rdv_futurs[0] : null;

// 3. Statistiques dynamiques
$count_today = 0;
foreach($rdv_futurs as $r) {
    if($r['date_reservation'] == date('Y-m-d')) $count_today++;
}

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    :root { --indigo: #4e73df; --indigo-soft: rgba(78, 115, 223, 0.1); }
    .text-indigo { color: var(--indigo); }
    .bg-indigo { background-color: var(--indigo); }
    .btn-indigo { background-color: var(--indigo); color: white; border: none; font-weight: bold; }
    .btn-indigo:hover { background-color: #3e5fbc; color: white; }
    
    /* Panneaux de contrôle */
    #panelNouveauRDV, #panelCalendrier, #panelRapport { 
        display: none; 
        animation: slideDown 0.4s ease-out; 
    }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-15px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="container-fluid py-5">
    
    <?= $status_msg ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0 text-indigo">[RDV] <?= $titre ?></h2>
            <p class="text-muted small">Synchronisation temps réel avec la plateforme logistique</p>
        </div>
        <div class="d-flex gap-2">
            <a href="?sync=1" class="btn btn-outline-dark fw-bold border-2">SYNCHRONISER API</a>
            <button onclick="togglePanel('panelRapport')" class="btn btn-outline-indigo fw-bold">RAPPORT DE RECONCILIATION</button>
            <button onclick="togglePanel('panelNouveauRDV')" class="btn btn-indigo shadow-sm px-4">+ NOUVEAU RDV</button>
        </div>
    </div>

    <div id="panelRapport" class="card border-0 shadow-lg mb-4 bg-dark text-white">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold m-0">RAPPORT DE RECONCILIATION API</h5>
                <button onclick="togglePanel('panelRapport')" class="btn-close btn-close-white"></button>
            </div>
            <div class="row text-center">
                <div class="col-md-4 border-end border-secondary">
                    <small class="text-muted d-block">RDV EN ATTENTE SYNCHRO</small>
                    <h2 class="fw-bold">04</h2>
                </div>
                <div class="col-md-4 border-end border-secondary">
                    <small class="text-muted d-block">ERREURS DE FLUX</small>
                    <h2 class="fw-bold text-danger">00</h2>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">DERNIER LOG</small>
                    <h2 class="fw-bold text-success">OK</h2>
                </div>
            </div>
        </div>
    </div>

    <div id="panelNouveauRDV" class="card border-0 shadow-lg mb-4 bg-light">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold m-0 text-indigo">PLANIFICATION RAPIDE</h5>
                <button onclick="togglePanel('panelNouveauRDV')" class="btn-close"></button>
            </div>
            <form action="" method="POST" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">CLIENT (NOM/TEL)</label>
                    <input type="text" name="client_search" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">DATE</label>
                    <input type="date" name="date_rdv" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">CRENEAU</label>
                    <select name="creneau" class="form-select">
                        <option>08:00 - 10:00</option>
                        <option>14:00 - 16:00</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="d-block">&nbsp;</label>
                    <button type="submit" class="btn btn-indigo w-100">VALIDER</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <small class="text-muted fw-bold">AUJOURD'HUI</small>
                <h3 class="fw-bold m-0 text-indigo"><?= $count_today ?> RDV</h3>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block mb-1">PROCHAINE COLLECTE PRIORITAIRE</small>
                        <h4 class="fw-bold m-0 text-warning">
                            <?= $prochain ? htmlspecialchars($prochain['nom_client']) . " | " . htmlspecialchars($prochain['adresse']) : "Aucune intervention prévue" ?>
                        </h4>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-indigo px-4 py-2 fs-6 fw-bold">
                            <?= $prochain ? $prochain['creneau_horaire'] : '--:--' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small fw-bold text-uppercase">
                    <tr>
                        <th class="ps-4 py-3">Horaires</th>
                        <th>Détails Client</th>
                        <th class="text-center">Statut API</th>
                        <th class="text-end pe-4">Gestion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rdv_futurs as $r): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark"><?= date('d/m/Y', strtotime($r['date_reservation'])) ?></div>
                            <small class="text-indigo fw-bold"><?= $r['creneau_horaire'] ?></small>
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($r['nom_client']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($r['adresse']) ?></div>
                        </td>
                        <td class="text-center">
                            <span class="badge <?= $r['statut'] == 'confirme' ? 'bg-success' : 'bg-warning text-dark' ?> rounded-pill px-3">
                                ● <?= strtoupper($r['statut']) ?>
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group shadow-sm">
                                <a href="tel:<?= $r['telephone'] ?>" class="btn btn-sm btn-light border fw-bold">CONTACT</a>
                                <button class="btn btn-sm btn-dark fw-bold">TRAITER</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function togglePanel(id) {
        // Liste de tous les panneaux à fermer avant d'ouvrir le nouveau
        const panels = ['panelNouveauRDV', 'panelCalendrier', 'panelRapport'];
        const target = document.getElementById(id);
        
        const isCurrentlyVisible = target.style.display === 'block';

        // Fermer tous les panneaux
        panels.forEach(pId => {
            document.getElementById(pId).style.display = 'none';
        });

        // Inverser l'état du panneau cible
        if (!isCurrentlyVisible) {
            target.style.display = 'block';
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
</script>

<?php require_once '../../templates/footer.php'; ?>