<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion de l'E-Réputation";

try {
    // 2. Calcul de la note moyenne globale
    $sql_stats = "SELECT AVG(note_qualite) as moyenne, COUNT(*) as total FROM tracabilite_tickets WHERE note_qualite IS NOT NULL";
    $stats = $pdo->query($sql_stats)->fetch();

    $moyenne = $stats['moyenne'] ?? 0;
    $total_avis = $stats['total'] ?? 0;

    // 3. Récupération des avis (CORRECTION : u.nom_complet utilisé ici)
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
    $db_error = "Erreur SQL : " . $e->getMessage();
    $avis = [];
    $moyenne = 0;
    $total_avis = 0;
}

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<br><br><br>
<div class="container-fluid py-5">
    <?php if (isset($db_error)): ?>
        <div class="alert alert-danger shadow-sm">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= $db_error ?>
        </div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-warning"><i class="fas fa-star me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Analyse de la satisfaction client</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark" onclick="window.print()"><i class="fas fa-print me-1"></i> Imprimer</button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 text-center bg-white h-100 rounded-4">
                <h6 class="text-muted fw-bold mb-3 small">NOTE MOYENNE</h6>
                <div class="display-4 fw-bold text-warning"><?= number_format($moyenne, 1) ?> <small class="fs-4">/ 5</small></div>
                <div class="mt-2 text-warning">
                    <?php 
                    for($i=1; $i<=5; $i++) {
                        echo ($i <= round($moyenne)) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star text-opacity-50"></i>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white h-100 rounded-4">
                <h6 class="text-muted fw-bold mb-3 small">RÉPARTITION ESTIMÉE</h6>
                <div class="progress mb-3" style="height: 10px;">
                    <div class="progress-bar bg-success" style="width: 70%" title="Positifs"></div>
                    <div class="progress-bar bg-warning" style="width: 20%" title="Neutres"></div>
                    <div class="progress-bar bg-danger" style="width: 10%" title="Négatifs"></div>
                </div>
                <div class="d-flex justify-content-between small">
                    <span><i class="fas fa-circle text-success small"></i> Positifs (4-5)</span>
                    <span><i class="fas fa-circle text-danger small"></i> Négatifs (1-2)</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white h-100 d-flex flex-column justify-content-center rounded-4">
                <h6 class="opacity-75 fw-bold mb-1 small">TOTAL DES RETOURS</h6>
                <h2 class="fw-bold mb-0"><?= $total_avis ?> avis clients</h2>
                <small class="text-success mt-2"><i class="fas fa-sync-alt me-1"></i> Synchronisé</small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="fw-bold mb-0">Derniers retours d'expérience</h5>
        </div>
        <div class="list-group list-group-flush">
            <?php if(empty($avis)): ?>
                <div class="p-5 text-center text-muted">
                    <i class="fas fa-comment-slash fa-3x mb-3 opacity-25"></i>
                    <p>Aucun avis trouvé sur cette période.</p>
                </div>
            <?php else: ?>
                <?php foreach($avis as $a): ?>
                <div class="list-group-item p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex align-items-center">
                            <div class="bg-light p-3 rounded-circle me-3 text-secondary">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0"><?= htmlspecialchars($a['prenom_client'] . ' ' . $a['nom_client']) ?></h6>
                                <small class="text-muted">Ticket <b>#<?= $a['numero_ticket'] ?></b> • Enregistré par <b><?= htmlspecialchars($a['traite_par'] ?? 'Système') ?></b></small>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="text-warning mb-1">
                                <?php for($i=1; $i<=5; $i++) echo ($i <= $a['note_qualite']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                            </div>
                            <small class="text-muted"><?= date('d/m/Y', strtotime($a['date_heure'])) ?></small>
                        </div>
                    </div>
                    
                    <div class="mt-3 p-3 bg-light rounded-3 border-start border-warning border-4">
                        <span class="fst-italic text-dark">
                            "<?= htmlspecialchars($a['note_commentaire'] ?: 'Aucun commentaire laissé par le client.') ?>"
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>