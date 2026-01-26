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

// 2. Récupération des promotions (Actives, Planifiées et Terminées)
$query = "SELECT * FROM promotions ORDER BY date_fin DESC";
$promos = $pdo->query($query)->fetchAll();

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-danger"><i class="fas fa-percentage me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Créez des offres attractives pour stimuler votre activité</p>
        </div>
        <button class="btn btn-danger shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNouvellePromo">
            <i class="fas fa-plus-circle"></i> Créer une promotion
        </button>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 bg-primary text-white" style="border-radius: 20px;">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="opacity-75 fw-bold">Impact Promo en cours</h6>
                        <h3 class="fw-bold">+24% de volume</h3>
                    </div>
                    <i class="fas fa-rocket fa-3x opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white" style="border-radius: 20px;">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="opacity-75 fw-bold">Clients réactivés</h6>
                        <h3 class="fw-bold">112 ce mois</h3>
                    </div>
                    <i class="fas fa-user-check fa-3x opacity-25"></i>
                </div>
            </div>
        </div>
    </div>

    

    <div class="row row-cols-1 row-cols-md-2 g-4">
        <?php foreach($promos as $p): 
            $now = date('Y-m-d');
            $status = ($p['date_fin'] < $now) ? 'Terminée' : (($p['date_debut'] > $now) ? 'Planifiée' : 'Active');
            $badge_color = ($status == 'Active') ? 'bg-success' : (($status == 'Planifiée') ? 'bg-info' : 'bg-secondary');
        ?>
        <div class="col">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 15px; overflow: hidden;">
                <div class="card-header <?= $badge_color ?> text-white border-0 py-3 d-flex justify-content-between">
                    <span class="fw-bold"><?= $status ?></span>
                    <span class="small"><?= date('d/m', strtotime($p['date_debut'])) ?> au <?= date('d/m/Y', strtotime($p['date_fin'])) ?></span>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="h3 fw-bold text-danger m-0 me-3">-<?= $p['valeur_remise'] ?><?= ($p['type_remise'] == 'pourcentage') ? '%' : ' FCFA' ?></div>
                        <h5 class="fw-bold m-0"><?= htmlspecialchars($p['nom_promo']) ?></h5>
                    </div>
                    <p class="text-muted small mb-3"><?= htmlspecialchars($p['description']) ?></p>
                    
                    <div class="p-2 bg-light rounded small">
                        <strong>Cible :</strong> <?= $p['cible_client'] ?> <br>
                        <strong>Code :</strong> <code class="fw-bold"><?= $p['code_promo'] ?? 'Automatique' ?></code>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 pb-3">
                    <div class="btn-group w-100">
                        <button class="btn btn-outline-secondary btn-sm"><i class="fas fa-edit"></i> Modifier</button>
                        <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                        <button class="btn btn-dark btn-sm"><i class="fas fa-chart-bar"></i> Résultats</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="modalNouvellePromo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="fw-bold">Créer une offre promotionnelle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nom de la Campagne</label>
                        <input type="text" class="form-control" placeholder="ex: Spécial Fête des Mères">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Code Promo (Optionnel)</label>
                        <input type="text" class="form-control" placeholder="ex: MAMAN2026">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Type de remise</label>
                        <select class="form-select">
                            <option value="pourcentage">Pourcentage (%)</option>
                            <option value="fixe">Montant fixe (FCFA)</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Valeur</label>
                        <input type="number" class="form-control" placeholder="20">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Cible</label>
                        <select class="form-select">
                            <option>Tous les clients</option>
                            <option>Nouveaux uniquement</option>
                            <option>Clients inactifs (>3 mois)</option>
                            <option>Membres VIP</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date de début</label>
                        <input type="date" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date de fin</label>
                        <input type="date" class="form-control">
                    </div>
                </div>
                <button type="submit" class="btn btn-danger w-100">Lancer la campagne</button>
            </form>
        </div>
    </div>
</div>

<?php require_once  '../../templates/footer.php'; ?>