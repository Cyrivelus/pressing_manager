<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité : Le client doit être connecté
if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$client_id = $_SESSION['client_id'];

// 2. Récupération des infos client
$stmt = $pdo->prepare("
    SELECT c.*, f.points_cumules, f.niveau, a.type_abonnement
    FROM clients c
    LEFT JOIN programme_fidelite f ON c.id_client = f.id_client
    LEFT JOIN abonnements a ON c.id_client = a.id_client AND a.statut = 'actif'
    WHERE c.id_client = ?
");
$stmt->execute([$client_id]);
$infos = $stmt->fetch();

// 3. Commandes en cours 
// Note : J'utilise id_ticket comme valeur de secours si code_ticket n'existe pas
$commandes = $pdo->prepare("SELECT * FROM tickets WHERE id_client = ? AND statut_livraison != 'livré' ORDER BY id_ticket DESC");
$commandes->execute([$client_id]);
$en_cours = $commandes->fetchAll();

$titre = "Mon Espace Pressing";
require_once $root . '/templates/header_client.php'; 
?>

<style>
    .portal-header { background: linear-gradient(135deg, #3498db, #2c3e50); color: white; border-radius: 0 0 30px 30px; padding: 40px 20px; }
    .points-circle { width: 60px; height: 60px; border: 3px solid #f1c40f; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 5px; font-weight: bold; }
    .action-card { border: none; border-radius: 15px; text-align: center; padding: 15px; transition: 0.3s; background: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.05); height: 100%; }
    .action-card:hover { transform: translateY(-3px); box-shadow: 0 6px 12px rgba(0,0,0,0.1); }
    .badge-status { font-size: 0.7rem; padding: 5px 10px; border-radius: 10px; }
</style>

<div class="portal-header text-center mb-4">
    <h2 class="fw-bold">Bonjour, <?= htmlspecialchars($infos['prenom_client'] ?? 'Client') ?> !</h2>
    <div class="d-flex justify-content-center gap-5 mt-3">
        <div>
            <div class="points-circle"><?= $infos['points_cumules'] ?? 0 ?></div>
            <small class="small">Points</small>
        </div>
        <div>
            <div class="points-circle" style="border-color: #2ecc71;"><i class="fas fa-crown"></i></div>
            <small class="small"><?= htmlspecialchars($infos['type_abonnement'] ?? 'Standard') ?></small>
        </div>
    </div>
</div>

<div class="container mb-5">
    <h6 class="fw-bold mb-3"><i class="fas fa-clock text-primary me-2"></i>Mes dépôts en cours</h6>
    
    <?php if(empty($en_cours)): ?>
        <div class="p-4 text-center bg-white rounded-3 shadow-sm mb-4">
            <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
            <p class="text-muted small m-0">Tout votre linge a été livré !</p>
        </div>
    <?php else: ?>
        <?php foreach($en_cours as $t): ?>
        <div class="card border-0 shadow-sm mb-2" style="border-radius: 12px;">
            <div class="card-body d-flex justify-content-between align-items-center py-3">
                <div>
                    <div class="fw-bold text-dark">
                        Ticket #<?= $t['code_ticket'] ?? $t['id_ticket'] ?? $t['numero_ticket'] ?? 'N/A' ?>
                    </div>
                    <small class="text-muted">Statut : <?= htmlspecialchars($t['statut_livraison']) ?></small>
                </div>
                <div class="text-end">
                    <span class="badge bg-soft-primary text-primary border badge-status">
                        <?= strtoupper($t['statut_livraison']) ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="row g-3 mt-3">
        <div class="col-6">
            <a href="reservation.php" class="text-decoration-none">
                <div class="action-card">
                    <i class="fas fa-calendar-plus text-primary fa-lg mb-2"></i>
                    <div class="small fw-bold text-dark">Réserver</div>
                </div>
            </a>
        </div>
        <div class="col-6">
            <a href="historique.php" class="text-decoration-none">
                <div class="action-card">
                    <i class="fas fa-history text-warning fa-lg mb-2"></i>
                    <div class="small fw-bold text-dark">Historique</div>
                </div>
            </a>
        </div>
        <div class="col-6">
            <a href="preferences.php" class="text-decoration-none">
                <div class="action-card">
                    <i class="fas fa-user-edit text-info fa-lg mb-2"></i>
                    <div class="small fw-bold text-dark">Mon Profil</div>
                </div>
            </a>
        </div>
        <div class="col-6">
            <a href="logout.php" class="text-decoration-none">
                <div class="action-card">
                    <i class="fas fa-sign-out-alt text-danger fa-lg mb-2"></i>
                    <div class="small fw-bold text-dark">Quitter</div>
                </div>
            </a>
        </div>
    </div>
</div>

<?php 
// Correction du chemin du footer
require_once  '../../templates/footer_client.php'; 
?>