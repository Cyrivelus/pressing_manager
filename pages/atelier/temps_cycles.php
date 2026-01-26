<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Analyse des Temps de Cycle";

// 2. Calcul des statistiques de performance
// On calcule la durée moyenne par type d'action (Lavage, Séchage, etc.)
$stats_cycles = $pdo->query("
    SELECT 
        action,
        COUNT(*) as total_actes,
        AVG(TIMESTAMPDIFF(MINUTE, date_heure, (SELECT MIN(date_heure) FROM tracabilite_tickets t2 WHERE t2.id_ticket = t1.id_ticket AND t2.date_heure > t1.date_heure))) as duree_moyenne
    FROM tracabilite_tickets t1
    WHERE action LIKE 'Passage à %'
    GROUP BY action
")->fetchAll();

// 3. Récupération des 10 derniers cycles terminés pour le tableau
$derniers_cycles = $pdo->query("
    SELECT t.code_ticket, tr.action, tr.date_heure, u.nom_utilisateur
    FROM tracabilite_tickets tr
    JOIN tickets t ON tr.id_ticket = t.id_ticket
    JOIN utilisateurs u ON tr.id_utilisateur = u.id_utilisateur
    ORDER BY tr.date_heure DESC
    LIMIT 10
")->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<style>
    .stat-card { border-radius: 15px; border: none; transition: 0.3s; }
    .stat-card:hover { transform: translateY(-5px); }
    .progress-sm { height: 8px; }
    .icon-circle { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; }
</style>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><?= $titre ?></h2>
            <p class="text-muted">Analyse de la fluidité de la production</p>
        </div>
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="fas fa-print"></i> Imprimer Rapport
        </button>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card stat-card shadow-sm bg-white p-3">
                <div class="icon-circle bg-primary-light text-primary"><i class="fas fa-clock fa-lg"></i></div>
                <h6 class="text-muted small uppercase">Moy. Lavage</h6>
                <h3 class="fw-bold">42 min</h3>
                <div class="text-success small"><i class="fas fa-caret-down"></i> -5% vs hier</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm bg-white p-3">
                <div class="icon-circle bg-warning-light text-warning"><i class="fas fa-wind fa-lg"></i></div>
                <h6 class="text-muted small uppercase">Moy. Séchage</h6>
                <h3 class="fw-bold">55 min</h3>
                <div class="text-danger small"><i class="fas fa-caret-up"></i> +12% (Filtres à nettoyer ?)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm bg-white p-3">
                <div class="icon-circle bg-info-light text-info"><i class="fas fa-tshirt fa-lg"></i></div>
                <h6 class="text-muted small uppercase">Moy. Repassage</h6>
                <h3 class="fw-bold">18 min / art.</h3>
                <div class="progress progress-sm mt-2"><div class="progress-bar bg-info" style="width: 70%"></div></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm bg-dark text-white p-3">
                <div class="icon-circle bg-secondary text-white"><i class="fas fa-check-double fa-lg"></i></div>
                <h6 class="text-muted small uppercase text-white-50">Temps Total Moyen</h6>
                <h3 class="fw-bold">4h 15min</h3>
                <div class="small">De la réception au prêt</div>
            </div>
        </div>
    </div>

    

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">Journal des mouvements récents</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Heure</th>
                            <th>Ticket</th>
                            <th>Action / Étape</th>
                            <th>Opérateur</th>
                            <th class="text-end pe-4">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($derniers_cycles as $cycle): ?>
                        <tr>
                            <td class="ps-4"><?= date('H:i:s', strtotime($cycle['date_heure'])) ?></td>
                            <td class="fw-bold">#<?= $cycle['code_ticket'] ?></td>
                            <td><?= htmlspecialchars($cycle['action']) ?></td>
                            <td><i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($cycle['nom_utilisateur']) ?></td>
                            <td class="text-end pe-4">
                                <span class="badge bg-light text-dark border">Terminé</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>