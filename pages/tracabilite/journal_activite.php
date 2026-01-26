<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Journal d'Activité Système (Logs)";

// 1. Récupération des logs récents (Table tracabilite_tickets + autres actions)
$sql = "SELECT t.*, u.nom_complet, u.role
        FROM tracabilite_tickets t
        JOIN utilisateurs u ON t.id_utilisateur = u.id_utilisateur
        ORDER BY t.date_heure DESC LIMIT 50";
$logs = $pdo->query($sql)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-history text-secondary me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Historique complet des actions utilisateurs et événements système</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark"><i class="fas fa-filter"></i> Filtrer par utilisateur</button>
            <button class="btn btn-dark shadow-sm"><i class="fas fa-file-export"></i> Exporter .CSV</button>
        </div>
    </div>

    

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white border-bottom border-4 border-primary">
                <small class="text-muted fw-bold">ACTIONS (24H)</small>
                <h2 class="fw-bold m-0">842</h2>
                <small class="text-muted">Volume d'activité normal</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white border-bottom border-4 border-warning">
                <small class="text-muted fw-bold">MODIFICATIONS DE PRIX</small>
                <h2 class="fw-bold m-0">14</h2>
                <small class="text-warning">Requiert vigilance</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white border-bottom border-4 border-danger">
                <small class="text-muted fw-bold">TICKETS ANNULÉS</small>
                <h2 class="fw-bold m-0 text-danger">3</h2>
                <small>Motifs à vérifier</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white">
                <small class="opacity-75 fw-bold">UTILISATEURS ACTIFS</small>
                <h2 class="fw-bold m-0">6</h2>
                <small class="text-success">Session en cours</small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4">Horodatage</th>
                        <th>Utilisateur</th>
                        <th>Action</th>
                        <th>Détails de l'événement</th>
                        <th class="text-end pe-4">ID Ticket</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($logs as $l): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="small fw-bold"><?= date('d/m/Y', strtotime($l['date_heure'])) ?></div>
                            <div class="extra-small text-muted"><?= date('H:i:s', strtotime($l['date_heure'])) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark fw-normal"><?= htmlspecialchars($l['nom_complet']) ?></span><br>
                            <small class="text-muted italic"><?= $l['role'] ?></small>
                        </td>
                        <td>
                            <?php 
                            $badge = 'bg-info';
                            if(strpos($l['action'], 'ANNUL') !== false) $badge = 'bg-danger';
                            if(strpos($l['action'], 'PAIEMENT') !== false) $badge = 'bg-success';
                            ?>
                            <span class="badge <?= $badge ?> shadow-sm"><?= $l['action'] ?></span>
                        </td>
                        <td>
                            <div class="small text-muted text-truncate" style="max-width: 400px;">
                                <?= htmlspecialchars($l['note_qualite'] ?: 'Aucune note supplémentaire') ?>
                            </div>
                        </td>
                        <td class="text-end pe-4 fw-bold">
                            <?= $l['id_ticket'] ? '#'.$l['id_ticket'] : '--' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .extra-small { font-size: 0.75rem; }
    .italic { font-style: italic; }
</style>

<?php require_once $root . '/templates/footer.php'; ?>