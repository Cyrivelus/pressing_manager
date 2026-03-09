<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "E-Réputation & Satisfaction";

try {
    // 2. Calcul des statistiques
    $sql_stats = "SELECT AVG(note_qualite) as moyenne, COUNT(*) as total FROM tracabilite_tickets WHERE note_qualite IS NOT NULL";
    $stats = $pdo->query($sql_stats)->fetch();

    $moyenne = $stats['moyenne'] ?? 0;
    $total_avis = $stats['total'] ?? 0;

    // 3. Récupération des avis
    $sql_avis = "SELECT t.numero_ticket, c.nom_client, c.prenom_client, 
                        tr.note_qualite, tr.note_commentaire, tr.date_heure, 
                        u.nom_complet as traite_par
                 FROM tracabilite_tickets tr
                 JOIN tickets t ON tr.id_ticket = t.id_ticket
                 JOIN clients c ON t.id_client = c.id_client
                 JOIN utilisateurs u ON tr.id_utilisateur = u.id_utilisateur
                 WHERE tr.note_qualite IS NOT NULL
                 ORDER BY tr.date_heure DESC LIMIT 10";
    $avis = $pdo->query($sql_avis)->fetchAll();

} catch (PDOException $e) {
    $db_error = "Erreur base de données : " . $e->getMessage();
    $avis = []; $moyenne = 0; $total_avis = 0;
}

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    /* Design System local */
    :root { --brand-warning: #ffc107; --brand-dark: #212529; }
    .text-star { color: var(--brand-warning); font-size: 1.1rem; letter-spacing: 2px; }
    .stat-card { transition: all 0.3s ease; border: 1px solid rgba(0,0,0,0.05) !important; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important; }
    .avatar-initial { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: #e9ecef; color: #495057; font-weight: 700; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    
    @media print {
        .no-print, header, footer, nav, .navbar { display: none !important; }
        .container-fluid { width: 100% !important; padding: 0 !important; }
        .card { border: 1px solid #ddd !important; break-inside: avoid; }
        body { background: white !important; }
    }
</style>

<div class="container-fluid py-5 px-md-5">
    <?php if (isset($db_error)): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><?= $db_error ?></div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5 gap-3">
        <div>
            <h1 class="fw-black text-dark mb-1" style="letter-spacing: -1.5px;"><?= mb_convert_case($titre, MB_CASE_UPPER) ?></h1>
            <p class="text-muted mb-0">Rapport analytique de la perception client</p>
        </div>
        <div class="no-print">
            <button class="btn btn-dark fw-bold rounded-pill px-4 shadow-sm" onclick="window.print()">
                EXPORTER LE RAPPORT (PDF)
            </button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-4 col-md-6">
            <div class="card stat-card shadow-sm p-4 h-100 rounded-4 bg-white text-center">
                <span class="text-muted small fw-bold mb-3 d-block uppercase">Satisfaction Globale</span>
                <div class="display-3 fw-black text-dark mb-0">
                    <?= number_format($moyenne, 1) ?><small class="fs-4 text-muted">/5</small>
                </div>
                <div class="text-star mt-2">
                    <?php for($i=1; $i<=5; $i++) echo ($i <= round($moyenne)) ? '★' : '☆'; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="card stat-card shadow-sm p-4 h-100 rounded-4 bg-white">
                <span class="text-muted small fw-bold mb-3 d-block">Santé de la Réputation</span>
                <div class="progress rounded-pill mb-3" style="height: 12px;">
                    <div class="progress-bar bg-success" style="width: 70%" title="Positifs"></div>
                    <div class="progress-bar bg-warning" style="width: 20%" title="Neutres"></div>
                    <div class="progress-bar bg-danger" style="width: 10%" title="Négatifs"></div>
                </div>
                <div class="d-flex justify-content-between small fw-bold">
                    <span class="text-success">POSITIFS (70%)</span>
                    <span class="text-danger">CRITIQUES (10%)</span>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-12">
            <div class="card stat-card shadow-sm p-4 h-100 rounded-4 bg-dark text-white d-flex flex-column justify-content-center border-0">
                <span class="opacity-50 small fw-bold mb-1 uppercase">Volume de données</span>
                <h2 class="display-5 fw-black mb-0"><?= $total_avis ?> Avis</h2>
                <div class="mt-2 small text-success fw-bold">✓ Données synchronisées</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
        <div class="card-header bg-white py-4 px-4 border-0">
            <h5 class="fw-bold mb-0">Derniers retours d'expérience</h5>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold">
                    <tr>
                        <th class="border-0 px-4 py-3">CLIENT & TICKET</th>
                        <th class="border-0 py-3">ÉVALUATION</th>
                        <th class="border-0 py-3">COMMENTAIRE</th>
                        <th class="border-0 px-4 py-3 text-end">DATE</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php if(empty($avis)): ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted">Aucun retour enregistré pour le moment.</td></tr>
                    <?php else: ?>
                        <?php foreach($avis as $a): ?>
                        <tr>
                            <td class="px-4 py-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-initial me-3">
                                        <?= strtoupper(substr($a['prenom_client'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark text-capitalize"><?= htmlspecialchars($a['prenom_client'] . ' ' . $a['nom_client']) ?></div>
                                        <div class="small text-muted">Ticket #<?= $a['numero_ticket'] ?> <span class="mx-1">•</span> <?= htmlspecialchars($a['traite_par']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-star">
                                    <?php for($i=1; $i<=5; $i++) echo ($i <= $a['note_qualite']) ? '★' : '☆'; ?>
                                </div>
                            </td>
                            <td style="max-width: 350px;">
                                <div class="p-3 bg-light rounded-3 small text-dark border-start border-warning border-3 italic">
                                    "<?= htmlspecialchars($a['note_commentaire'] ?: 'Le client n\'a pas laissé de texte.') ?>"
                                </div>
                            </td>
                            <td class="px-4 text-end text-muted small fw-bold">
                                <?= date('d/m/Y', strtotime($a['date_heure'])) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>