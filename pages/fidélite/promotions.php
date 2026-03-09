<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';


$titre = "Campagnes Promotionnelles";

// --- LOGIQUE D'ENREGISTREMENT (TRAITEMENT PHP) ---
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_creer_promo'])) {
    try {
        $ins = $pdo->prepare("INSERT INTO promotions (nom_promo, code_promo, type_remise, valeur_remise, cible_client, date_debut, date_fin, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([
            $_POST['nom_promo'],
            $_POST['code_promo'],
            $_POST['type_remise'],
            $_POST['valeur_remise'],
            $_POST['cible_client'],
            $_POST['date_debut'],
            $_POST['date_fin'],
            $_POST['description']
        ]);
        $message = "<div class='alert alert-success border-0 shadow-sm'>La promotion a été créée et activée avec succès !</div>";
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger border-0 shadow-sm'>Erreur lors de la création : " . $e->getMessage() . "</div>";
    }
}

// 2. Récupération des promotions
$query = "SELECT * FROM promotions ORDER BY date_fin DESC";
$promos = $pdo->query($query)->fetchAll();

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<style>
    .promo-card { border-radius: 15px; transition: transform 0.2s; }
    .promo-card:hover { transform: translateY(-5px); }
    #panelNouvellePromo { display: none; animation: slideDown 0.4s ease-out; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    .btn-custom-danger { background-color: #dc3545; color: white; border: none; font-weight: bold; }
    .btn-custom-danger:hover { background-color: #bb2d3b; color: white; }
    .badge-status { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
</style>

<div class="container-fluid py-5">
    
    <?= $message ?>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
        <div>
            <h2 class="fw-bold m-0 text-danger"><?= $titre ?></h2>
            <p class="text-muted">Pilotez vos offres marketing sans changer de page</p>
        </div>
        <button onclick="togglePromoPanel()" class="btn btn-custom-danger shadow-sm px-4 py-2">
            [+] CRÉER UNE PROMOTION
        </button>
    </div>

    <div id="panelNouvellePromo" class="card border-0 shadow-lg mb-5" style="border-radius: 20px; border-top: 5px solid #dc3545 !important;">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold m-0">Nouvelle Offre Promotionnelle</h4>
                <button onclick="togglePromoPanel()" class="btn-close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action_creer_promo" value="1">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Nom de la Campagne</label>
                        <input type="text" name="nom_promo" class="form-control" placeholder="Ex: Soldes d'Hiver" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Code Coupon (Laisser vide pour Automatique)</label>
                        <input type="text" name="code_promo" class="form-control" placeholder="Ex: HIVER25">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label small fw-bold">Type de remise</label>
                        <select name="type_remise" class="form-select">
                            <option value="pourcentage">Pourcentage (%)</option>
                            <option value="fixe">Montant fixe (FCFA)</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label small fw-bold">Valeur</label>
                        <input type="number" name="valeur_remise" class="form-control" placeholder="20" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Cible Clientèle</label>
                        <select name="cible_client" class="form-select">
                            <option value="Tous les clients">Tous les clients</option>
                            <option value="Nouveaux clients">Nouveaux uniquement</option>
                            <option value="Inactifs">Clients inactifs (>3 mois)</option>
                            <option value="VIP">Membres VIP</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label small fw-bold">Date de début</label>
                        <input type="date" name="date_debut" class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label small fw-bold">Date de fin</label>
                        <input type="date" name="date_fin" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Description courte</label>
                        <input type="text" name="description" class="form-control" placeholder="Ex: -20% sur tous les costumes">
                    </div>
                </div>
                <div class="text-end mt-3">
                    <button type="button" onclick="togglePromoPanel()" class="btn btn-light fw-bold me-2">ANNULER</button>
                    <button type="submit" class="btn btn-danger fw-bold px-5 shadow">LANCER LA CAMPAGNE</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 bg-primary text-white" style="border-radius: 20px;">
                <h6 class="opacity-75 fw-bold">Impact Promo en cours</h6>
                <h3 class="fw-bold m-0">+24% de volume ventes</h3>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white" style="border-radius: 20px;">
                <h6 class="opacity-75 fw-bold">Clients réactivés</h6>
                <h3 class="fw-bold m-0">112 ce mois-ci</h3>
            </div>
        </div>
    </div>

    

    <div class="row row-cols-1 row-cols-md-2 g-4">
        <?php foreach($promos as $p): 
            $now = date('Y-m-d');
            $status = ($p['date_fin'] < $now) ? 'Terminée' : (($p['date_debut'] > $now) ? 'Planifiée' : 'Active');
            $status_class = ($status == 'Active') ? 'bg-success' : (($status == 'Planifiée') ? 'bg-info' : 'bg-secondary');
        ?>
        <div class="col">
            <div class="card border-0 shadow-sm h-100 promo-card">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <span class="badge-status <?= $status_class ?> text-white"><?= $status ?></span>
                    <span class="text-muted small fw-bold">Du <?= date('d/m/y', strtotime($p['date_debut'])) ?> au <?= date('d/m/y', strtotime($p['date_fin'])) ?></span>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex align-items-center mb-2">
                        <div class="h2 fw-bold text-danger m-0 me-3">
                            -<?= $p['valeur_remise'] ?><?= ($p['type_remise'] == 'pourcentage') ? '%' : ' F' ?>
                        </div>
                        <h5 class="fw-bold m-0 text-dark"><?= htmlspecialchars($p['nom_promo']) ?></h5>
                    </div>
                    <p class="text-muted small"><?= htmlspecialchars($p['description']) ?></p>
                    
                    <div class="row g-2 mt-2">
                        <div class="col-6">
                            <div class="p-2 border rounded text-center">
                                <small class="d-block text-muted">CIBLE</small>
                                <span class="fw-bold small"><?= $p['cible_client'] ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 border rounded text-center bg-light">
                                <small class="d-block text-muted">CODE</small>
                                <code class="fw-bold text-dark"><?= $p['code_promo'] ?: 'AUTOMATIQUE' ?></code>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 pb-3">
                    <div class="btn-group w-100 shadow-sm rounded">
                        <button class="btn btn-outline-dark btn-sm fw-bold">MODIFIER</button>
                        <button class="btn btn-outline-danger btn-sm fw-bold">SUPPR.</button>
                        <button class="btn btn-dark btn-sm fw-bold">RAPPORTS</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    function togglePromoPanel() {
        const panel = document.getElementById('panelNouvellePromo');
        if (panel.style.display === 'block') {
            panel.style.display = 'none';
        } else {
            panel.style.display = 'block';
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
</script>

<?php require_once  '../../templates/footer.php'; ?>