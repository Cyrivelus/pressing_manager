<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Live Tracker : Commandes Prioritaires";

// 1. Récupération des articles urgents avec leur position actuelle
$sql = "SELECT l.id_ligne, l.description, l.etat_avancement, t.numero_ticket,
               t.date_retrait_prevue, TIMESTAMPDIFF(MINUTE, NOW(), t.date_retrait_prevue) as countdown
        FROM lignes_ticket l
        JOIN tickets t ON l.id_ticket = t.id_ticket
        WHERE t.statut != 'recupere' AND t.statut != 'annule'
        AND t.notes LIKE '%EXPRESS%'
        ORDER BY t.date_retrait_prevue ASC";
$tracker = $pdo->query($sql)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-danger"><i class="fas fa-satellite-dish me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Visualisation du flux de production pour les délais critiques</p>
        </div>
        <div class="text-end">
            <div class="small fw-bold text-uppercase text-muted">Mise à jour automatique</div>
            <div class="spinner-grow spinner-grow-sm text-danger" role="status"></div>
        </div>
    </div>

    

    <div class="row g-3">
        <?php 
        $etapes = [
            'LAVAGE' => ['icon' => 'fa-soap', 'color' => 'info'],
            'REPASSAGE' => ['icon' => 'fa-tshirt', 'color' => 'warning'],
            'CONTROLE' => ['icon' => 'fa-search', 'color' => 'primary'],
            'PRET' => ['icon' => 'fa-check-double', 'color' => 'success']
        ];
        
        foreach($etapes as $code => $info): 
        ?>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-<?= $info['color'] ?> text-white fw-bold py-3">
                    <i class="fas <?= $info['icon'] ?> me-2"></i> <?= $code ?>
                </div>
                <div class="card-body bg-light p-2" style="min-height: 400px;">
                    <?php foreach($tracker as $item): 
                        if($item['etat_avancement'] == $code): 
                    ?>
                        <div class="card border-0 shadow-sm mb-2 p-3 border-start border-4 border-danger">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold small">#<?= $item['numero_ticket'] ?></span>
                                <span class="badge <?= $item['countdown'] < 30 ? 'bg-danger' : 'bg-dark' ?> extra-small">
                                    <?= $item['countdown'] ?> min
                                </span>
                            </div>
                            <div class="small text-truncate"><?= htmlspecialchars($item['description']) ?></div>
                            <div class="mt-2 d-flex justify-content-end">
                                <button class="btn btn-xs btn-outline-<?= $info['color'] ?> py-0">Déplacer <i class="fas fa-arrow-right ms-1"></i></button>
                            </div>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .extra-small { font-size: 0.65rem; }
    .btn-xs { font-size: 0.7rem; padding: 2px 5px; }
    .card-body { overflow-y: auto; max-height: 600px; }
</style>

<?php require_once $root . '/templates/footer.php'; ?>