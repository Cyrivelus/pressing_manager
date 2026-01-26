<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité : On vérifie que le CLIENT est connecté
if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$client_id = $_SESSION['client_id'];
$titre = "Mon Historique";

// 2. Récupération des commandes livrées
// Correction : Utilisation de id_ticket pour le tri si la date pose problème
try {
    $query = "SELECT * FROM tickets 
              WHERE id_client = ? AND statut_livraison = 'livré' 
              ORDER BY id_ticket DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$client_id]);
    $historique = $stmt->fetchAll();
} catch (PDOException $e) {
    $historique = [];
}

// 3. Calcul des statistiques
$total_depense = array_sum(array_column($historique, 'montant_total')) ?: 0;
$total_commandes = count($historique);

require_once  '../../templates/header_client.php';
?>

<style>
    .history-card { border-radius: 15px; border: none; transition: 0.2s; }
    .history-card:hover { background-color: #f8f9fa; }
    .icon-circle-small { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #e8f4fd; color: #3498db; }
    .stat-mini-card { background: white; border-radius: 12px; padding: 15px; text-align: center; border: 1px solid #eee; }
    .badge-paid { font-size: 0.7rem; background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
</style>

<div class="container py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="index.php" class="btn btn-light rounded-circle me-3"><i class="fas fa-arrow-left"></i></a>
        <h2 class="fw-bold m-0"><?= $titre ?></h2>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="stat-mini-card shadow-sm">
                <small class="text-muted d-block">Commandes</small>
                <span class="fw-bold h5 mb-0"><?= $total_commandes ?></span>
            </div>
        </div>
        <div class="col-6">
            <div class="stat-mini-card shadow-sm">
                <small class="text-muted d-block">Total Dépensé</small>
                <span class="fw-bold h5 mb-0 text-primary"><?= number_format($total_depense, 0, ',', ' ') ?> <small class="fs-6">FCFA</small></span>
            </div>
        </div>
    </div>

    <h5 class="fw-bold mb-3">Mes anciennes prestations</h5>

    <?php if(empty($historique)): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
            <i class="fas fa-history fa-3x text-muted mb-3 opacity-25"></i>
            <p class="text-muted">Vous n'avez pas encore de commandes terminées.</p>
            <a href="reservation.php" class="btn btn-primary btn-sm rounded-pill px-4">Passer une commande</a>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm" style="border-radius: 20px; overflow: hidden;">
            <div class="list-group list-group-flush">
                <?php foreach($historique as $h): ?>
                <div class="list-group-item history-card p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center">
                            <div class="icon-circle-small me-3">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">Ticket #<?= $h['code_ticket'] ?? $h['id_ticket'] ?></h6>
                                <small class="text-muted">
                                    Terminé le <?= isset($h['date_livraison']) ? date('d/m/Y', strtotime($h['date_livraison'])) : 'Récemment' ?>
                                </small>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold d-block text-dark"><?= number_format($h['montant_total'], 0, ',', ' ') ?> FCFA</span>
                            <span class="badge badge-paid">PAYÉ</span>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2 mt-2">
                        <button onclick="recommander(<?= $h['id_ticket'] ?>)" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="fas fa-redo me-1"></i> Recommander
                        </button>
                        <a href="facture_pdf.php?id=<?= $h['id_ticket'] ?>" class="btn btn-sm btn-light rounded-pill border px-3">
                            <i class="fas fa-download me-1 text-danger"></i> PDF
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i> Besoin d'aide sur une commande ? <a href="contact.php" class="text-decoration-none">Contactez-nous</a>
        </small>
    </div>
</div>

<script>
function recommander(id) {
    if(confirm('Voulez-vous recréer une commande identique à celle-ci ?')) {
        window.location.href = `reservation.php?from_history=${id}`;
    }
}
</script>

<?php require_once  '../../templates/footer_client.php'; ?>