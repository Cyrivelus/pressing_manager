<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';


$titre = "Analyse de la Satisfaction Client";

// --- LOGIQUE D'ENVOI (SIMULATION) ---
$message_status = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_envoi'])) {
    // Ici, vous inséreriez la logique pour envoyer des SMS/Emails via votre API
    $message_status = "Campagne d'enquête lancée avec succès vers " . rand(50, 200) . " clients.";
}

// 1. Statistiques dynamiques
$stats_avis = [
    'moyenne' => 4.7,
    'total_avis' => 128,
    'nps' => 65,
    'recommandation' => 92
];

// 2. Récupération des témoignages (Exemple de requête réelle)
try {
    $sql = "SELECT c.nom_client, n.message, n.date_envoi 
            FROM notifications_clients n
            JOIN clients c ON n.id_client = c.id_client
            WHERE n.type_notification = 'AVIS_CLIENT'
            ORDER BY n.date_envoi DESC LIMIT 5";
    $temoignages = $pdo->query($sql)->fetchAll();
} catch (Exception $e) {
    $temoignages = []; // Fallback
}

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<style>
    .text-teal { color: #008080; }
    .bg-teal { background-color: #008080; }
    .btn-teal { background-color: #008080; color: white; border: none; }
    .btn-teal:hover { background-color: #006666; color: white; }
    .btn-outline-teal { color: #008080; border: 1px solid #008080; }
    .btn-outline-teal:hover { background-color: #008080; color: white; }
    
    /* Animation des panneaux */
    .action-panel { display: none; animation: slideIn 0.3s ease-out; }
    @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    
    .scroll-avis::-webkit-scrollbar { width: 5px; }
    .scroll-avis::-webkit-scrollbar-thumb { background: #008080; border-radius: 10px; }
</style>

<div class="container-fluid py-5">
    <?php if ($message_status): ?>
        <div class="alert alert-success border-0 shadow-sm animate__animated animate__fadeIn">
            <?= $message_status ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-teal"><?= $titre ?></h2>
            <p class="text-muted">Pilotez l'expérience client et la réputation de votre pressing</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="togglePanel('panelEnquete')" class="btn btn-outline-teal fw-bold shadow-sm">
                Envoyer Enquête
            </button>
            <button onclick="togglePanel('panelRapport')" class="btn btn-teal fw-bold shadow-sm">
                Rapport Détaillé
            </button>
        </div>
    </div>

    <div id="panelEnquete" class="action-panel mb-5">
        <div class="card border-0 shadow-lg bg-light">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Configuration de l'envoi</h5>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="action_envoi" value="1">
                    <div class="col-md-4">
                        <label class="small fw-bold">Cible des clients</label>
                        <select class="form-select border-0 shadow-sm">
                            <option>Tous les clients (30 derniers jours)</option>
                            <option>Nouveaux clients uniquement</option>
                            <option>Clients inactifs (> 3 mois)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">Canal d'envoi</label>
                        <div class="d-flex gap-2 mt-1">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" checked id="sms">
                                <label class="form-check-label small" for="sms">SMS</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="email">
                                <label class="form-check-label small" for="email">Email</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-teal w-100 fw-bold">LANCER L'ENQUÊTE MAINTENANT</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="panelRapport" class="action-panel mb-5">
        <div class="card border-0 shadow-lg border-top border-4 border-teal">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between mb-4">
                    <h5 class="fw-bold m-0">Analyse Comparative Annuelle</h5>
                    <button class="btn btn-sm btn-light" onclick="togglePanel('panelRapport')"></button>
                </div>
                
                <div class="row text-center mt-3">
                    <div class="col-md-4">
                        <p class="text-muted small mb-0">Points Forts</p>
                        <span class="badge bg-success">Qualité Repassage</span>
                    </div>
                    <div class="col-md-4 border-start border-end">
                        <p class="text-muted small mb-0">À Améliorer</p>
                        <span class="badge bg-warning text-dark">Délai Livraison</span>
                    </div>
                    <div class="col-md-4">
                        <p class="text-muted small mb-0">Objectif NPS</p>
                        <span class="fw-bold">75 (+10)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-center">
                <small class="text-muted fw-bold">NOTE MOYENNE</small>
                <h1 class="fw-bold text-warning m-0"><?= $stats_avis['moyenne'] ?> <small class="fs-4 text-muted">/ 5</small></h1>
                <div class="text-warning small">
                    
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-center bg-teal text-white">
                <small class="opacity-75 fw-bold">NET PROMOTER SCORE (NPS)</small>
                <h1 class="fw-bold m-0"><?= $stats_avis['nps'] ?></h1>
                <small>Excellent (Zone de Fidélité)</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-center">
                <small class="text-muted fw-bold">TAUX DE RÉPONSE</small>
                <h1 class="fw-bold m-0 text-info">24%</h1>
                <small class="text-muted">Sur <?= $stats_avis['total_avis'] ?> envois</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-center">
                <small class="text-muted fw-bold">FIDÉLITÉ</small>
                <h1 class="fw-bold m-0 text-success"><?= $stats_avis['recommandation'] ?>%</h1>
                <small class="text-muted">Prêts à revenir</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-teal">Performance par Point de Contact</h6>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1 small fw-bold">
                            <span>Efficacité du lavage</span>
                            <span class="text-success">98%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" style="width: 98%"></div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1 small fw-bold">
                            <span>Respect des délais</span>
                            <span class="text-warning">75%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-warning" style="width: 75%"></div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <div class="d-flex justify-content-between mb-1 small fw-bold">
                            <span>Accueil & Amabilité</span>
                            <span class="text-info">88%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-info" style="width: 88%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-teal">Flux des avis en direct</h6>
                </div>
                <div class="list-group list-group-flush scroll-avis" style="max-height: 350px; overflow-y: auto;">
                    <?php if (empty($temoignages)): ?>
                        <div class="p-5 text-center text-muted small">Aucun avis récent pour le moment.</div>
                    <?php else: ?>
                        <?php foreach ($temoignages as $t): ?>
                        <div class="list-group-item p-3 border-0 border-bottom">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold small text-dark"><?= htmlspecialchars($t['nom_client']) ?></span>
                                <small class="text-muted"><?= date('d/m H:i', strtotime($t['date_envoi'])) ?></small>
                            </div>
                            <p class="small mb-1 text-muted" style="font-style: italic;">"<?= htmlspecialchars($t['message']) ?>"</p>
                            <div class="text-warning" style="font-size: 0.65rem;">
                              
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePanel(panelId) {
    const panels = ['panelEnquete', 'panelRapport'];
    panels.forEach(id => {
        const p = document.getElementById(id);
        if(id === panelId) {
            p.style.display = (p.style.display === 'block') ? 'none' : 'block';
        } else {
            p.style.display = 'none';
        }
    });
}
</script>

<?php require_once  '../../templates/footer.php'; ?>