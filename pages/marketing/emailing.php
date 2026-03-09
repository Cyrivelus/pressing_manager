<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';


$titre = "Campagnes Emailing & Fidélisation";

// --- LOGIQUE DE TRAITEMENT DES ACTIONS ---
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_creer'])) {
        $message = "<div class='alert alert-success border-0 shadow-sm fw-bold'>[OK] Campagne '".$_POST['objet']."' programmée avec succès !</div>";
    }
    if (isset($_POST['action_pause'])) {
        $message = "<div class='alert alert-warning border-0 shadow-sm fw-bold'>[INFO] Campagne mise en pause.</div>";
    }
}

// 1. Statistiques rapides
$total_clients = $pdo->query("SELECT COUNT(*) FROM clients WHERE email IS NOT NULL AND email != ''")->fetchColumn();

require_once '../../templates/header.php'; 
require_once '../../templates/navigation.php'; 
?>

<style>
    /* Indicateurs sans icônes */
    .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    .bg-finished { background-color: #28a745; }
    .bg-active { background-color: #0dcaf0; }
    
    /* Panneau de création dynamique (Fini les modals) */
    #creationPanel { display: none; animation: slideDown 0.4s ease-out; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    
    .action-btn { font-size: 0.75rem; letter-spacing: 0.5px; border-width: 2px; }
    .stat-card { border-left: 4px solid #0d6efd; }
</style>

<div class="container-fluid py-5">
    
    <?= $message ?>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
        <div>
            <h2 class="fw-bold m-0 text-primary">[MARKETING] <?= $titre ?></h2>
            <p class="text-muted">Gérez vos envois massifs et analysez l'engagement client</p>
        </div>
        <button onclick="togglePanel('creationPanel')" class="btn btn-primary fw-bold shadow-sm px-4">
            + CRÉER UNE CAMPAGNE
        </button>
    </div>

    <div id="creationPanel" class="card border-0 shadow-lg mb-5 bg-light border-start border-primary border-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold m-0 text-primary">Conception d'une nouvelle campagne</h4>
                <button type="button" class="btn-close" onclick="togglePanel('creationPanel')"></button>
            </div>
            <form method="POST" class="row g-3">
                <input type="hidden" name="action_creer" value="1">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">OBJET DE L'EMAIL</label>
                    <input type="text" name="objet" class="form-control border-0 shadow-sm" placeholder="ex: -20% sur votre prochain dépôt !" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">SEGMENT CIBLE</label>
                    <select name="segment" class="form-select border-0 shadow-sm">
                        <option>Tous les clients (<?= $total_clients ?>)</option>
                        <option>Clients VIP</option>
                        <option>Inactifs > 60 jours</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">TEMPLATE</label>
                    <select name="template" class="form-select border-0 shadow-sm">
                        <option>Offre Saisonnière</option>
                        <option>Relance Fidélité</option>
                    </select>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary fw-bold px-5 py-2 shadow">LANCER L'ENVOI MASSIF</button>
                </div>
            </form>
        </div>
    </div>

    

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 stat-card">
                <small class="text-muted fw-bold d-block mb-1 text-uppercase">Audience</small>
                <h2 class="fw-bold m-0"><?= number_format($total_clients, 0, ',', ' ') ?></h2>
                <small class="text-success fw-bold">Emails valides</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 stat-card" style="border-color: #0dcaf0;">
                <small class="text-muted fw-bold d-block mb-1 text-uppercase">Ouverture</small>
                <h2 class="fw-bold m-0">24.8 %</h2>
                <small class="text-info fw-bold">+4% vs le mois dernier</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 stat-card" style="border-color: #198754;">
                <small class="text-muted fw-bold d-block mb-1 text-uppercase">Conversion</small>
                <h2 class="fw-bold m-0">8.5 %</h2>
                <small class="text-muted fw-bold">Dépôts post-clic</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 stat-card" style="border-color: #dc3545;">
                <small class="text-muted fw-bold d-block mb-1 text-uppercase">Désabonnement</small>
                <h2 class="fw-bold m-0 text-danger">0.2 %</h2>
                <small class="text-muted fw-bold">Taux très bas</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between">
                    <h6 class="fw-bold mb-0 text-uppercase small">Campagnes Récentes</h6>
                    <span class="badge bg-primary fw-bold">LIVE STATUS</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small text-uppercase">
                            <tr>
                                <th class="ps-4 py-3">Objet & Date</th>
                                <th>Cible</th>
                                <th>État</th>
                                <th class="text-center">Ouverts</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-dark">Promotion "Spécial Couettes"</span><br>
                                    <small class="text-muted fw-bold text-uppercase">15/01/2026 - 09:00</small>
                                </td>
                                <td><span class="badge bg-dark fw-bold">TOUS CLIENTS</span></td>
                                <td>
                                    <span class="status-dot bg-finished"></span>
                                    <small class="fw-bold text-success text-uppercase">Terminé</small>
                                </td>
                                <td class="text-center fw-bold fs-5 text-primary">142</td>
                                <td class="text-end pe-4">
                                    <form method="GET" action="rapport.php" class="d-inline">
                                        <button type="submit" class="btn btn-sm btn-outline-primary action-btn fw-bold">ANALYSE</button>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-dark">Vous nous manquez !</span><br>
                                    <small class="text-muted fw-bold text-uppercase">Automatique (Inactifs)</small>
                                </td>
                                <td><span class="badge bg-secondary fw-bold">SEGMENT INACTIF</span></td>
                                <td>
                                    <span class="status-dot bg-active"></span>
                                    <small class="fw-bold text-info text-uppercase">En cours</small>
                                </td>
                                <td class="text-center fw-bold fs-5 text-primary">28</td>
                                <td class="text-end pe-4">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action_pause" value="1">
                                        <button type="submit" class="btn btn-sm btn-outline-danger action-btn fw-bold">STOP</button>
                                    </form>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 bg-white border-top border-primary border-4">
                <h6 class="fw-bold mb-4 text-uppercase small text-muted">Aide au ciblage (IA)</h6>
                <div class="d-grid gap-3">
                    <div class="p-3 bg-light rounded d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold d-block">Segment VIP</span>
                            <small class="text-muted">Top 10% chiffre d'affaires</small>
                        </div>
                        <span class="badge bg-primary rounded-pill">42</span>
                    </div>
                    <div class="p-3 bg-light rounded d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold d-block">Nouveaux</span>
                            <small class="text-muted">Inscrits < 30 jours</small>
                        </div>
                        <span class="badge bg-primary rounded-pill">18</span>
                    </div>
                </div>
                <div class="mt-4 p-3 rounded" style="background-color: #e7f1ff; border: 1px dashed #0d6efd;">
                    <small class="fw-bold text-primary d-block mb-1">CONSEIL PRO :</small>
                    <small class="text-dark">Les emails envoyés le <strong>mardi à 10h</strong> affichent un taux d'ouverture 15% supérieur.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePanel(id) {
        const panel = document.getElementById(id);
        if (panel.style.display === 'block') {
            panel.style.display = 'none';
        } else {
            panel.style.display = 'block';
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
</script>

<?php require_once '../../templates/footer.php'; ?>