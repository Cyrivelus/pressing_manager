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

// 2. Calcul des statistiques et récupération des données
try {
    // Statistiques de performance
    $stats_cycles = $pdo->query("
        SELECT 
            action,
            COUNT(*) as total_actes,
            AVG(TIMESTAMPDIFF(MINUTE, tr.date_heure, 
                (SELECT MIN(tr2.date_heure) 
                 FROM tracabilite_tickets tr2 
                 WHERE tr2.id_ticket = tr.id_ticket 
                 AND tr2.date_heure > tr.date_heure)
            )) as duree_moyenne
        FROM tracabilite_tickets tr
        WHERE action LIKE 'Passage à %'
        GROUP BY action
    ")->fetchAll();

    // Récupération des 10 derniers mouvements (Correction de u.nom_complet)
    $derniers_cycles = $pdo->query("
        SELECT t.numero_ticket as code_ticket, tr.action, tr.date_heure, u.nom_complet
        FROM tracabilite_tickets tr
        JOIN tickets t ON tr.id_ticket = t.id_ticket
        JOIN utilisateurs u ON tr.id_utilisateur = u.id_utilisateur
        ORDER BY tr.date_heure DESC
        LIMIT 10
    ")->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Erreur de base de données : " . $e->getMessage();
}

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    /* Styles Interface */
    .stat-card { border-radius: 15px; border: none; transition: 0.3s; background: #fff; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
    .icon-circle { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; }
    .bg-primary-light { background: rgba(13, 110, 253, 0.1); }
    .bg-warning-light { background: rgba(255, 193, 7, 0.1); }
    .bg-info-light { background: rgba(13, 202, 240, 0.1); }
    .bg-success-soft { background: rgba(25, 135, 84, 0.1); color: #198754; padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.8rem; }

    /* --- GESTION DE L'IMPRESSION --- */
    @media print {
        /* Masquer les éléments inutiles à l'impression */
        header, .navbar, nav, footer, .btn, .no-print {
            display: none !important;
        }
        
        /* Ajuster le container pour prendre toute la page */
        .container-fluid, .container {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Supprimer les ombres pour économiser l'encre et améliorer la clarté */
        .card {
            border: 1px solid #dee2e6 !important;
            box-shadow: none !important;
            break-inside: avoid; /* Évite de couper une carte en deux pages */
        }

        body {
            background-color: white !important;
            font-size: 12pt;
        }

        .text-primary, .text-dark {
            color: black !important;
        }
    }
</style>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><?= $titre ?></h2>
            <p class="text-muted">Analyse de la productivité et flux de l'agence</p>
        </div>
        <button onclick="window.print()" class="btn btn-primary px-4 shadow-sm no-print">
           Imprimer le rapport
        </button>
    </div>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger no-print"><?= $error_msg ?></div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <?php 
        $moyennes = ['Lavage' => '42', 'Séchage' => '55', 'Repassage' => '18'];
        foreach($stats_cycles as $s) {
            if(stripos($s['action'], 'Lavage') !== false) $moyennes['Lavage'] = round($s['duree_moyenne']);
            if(stripos($s['action'], 'Séchage') !== false) $moyennes['Séchage'] = round($s['duree_moyenne']);
            if(stripos($s['action'], 'Repassage') !== false) $moyennes['Repassage'] = round($s['duree_moyenne']);
        }
        ?>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm p-4 text-center">
                <div class="icon-circle bg-primary-light text-primary mx-auto"></div>
                <h6 class="text-muted small fw-bold uppercase">MOY. LAVAGE</h6>
                <h3 class="fw-bold m-0"><?= $moyennes['Lavage'] ?> min</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm p-4 text-center">
                <div class="icon-circle bg-warning-light text-warning mx-auto"></div>
                <h6 class="text-muted small fw-bold uppercase">MOY. SÉCHAGE</h6>
                <h3 class="fw-bold m-0"><?= $moyennes['Séchage'] ?> min</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm p-4 text-center">
                <div class="icon-circle bg-info-light text-info mx-auto"></div>
                <h6 class="text-muted small fw-bold uppercase">MOY. REPASSAGE</h6>
                <h3 class="fw-bold m-0"><?= $moyennes['Repassage'] ?> min</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm bg-dark text-white p-4 text-center">
                <div class="icon-circle bg-secondary text-white mx-auto"></div>
                <h6 class="text-white-50 small fw-bold uppercase">CYCLE TOTAL</h6>
                <h3 class="fw-bold m-0">~ 4h 15</h3>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="mb-0 fw-bold">Mouvements récents</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small">
                    <tr>
                        <th class="ps-4">HEURE DU SCAN</th>
                        <th>N° TICKET</th>
                        <th>ÉTAPE DE PRODUCTION</th>
                        <th>OPÉRATEUR</th>
                        <th class="text-end pe-4">ÉTAT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($derniers_cycles)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Aucun mouvement récent enregistré.</td></tr>
                    <?php else: foreach($derniers_cycles as $c): ?>
                        <tr>
                            <td class="ps-4 text-muted"><?= date('H:i:s', strtotime($c['date_heure'])) ?></td>
                            <td class="fw-bold">#<?= htmlspecialchars($c['code_ticket']) ?></td>
                            <td>
                                <span class="badge rounded-pill bg-light text-dark border px-3">
                                    <?= htmlspecialchars($c['action']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($c['nom_complet']) ?></td>
                            <td class="text-end pe-4">
                                <span class="badge bg-success-soft text-success">Validé</span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>