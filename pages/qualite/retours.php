<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion des Retours Clients";

// 1. Récupération des articles renvoyés en atelier pour correction
$sql = "SELECT t.numero_ticket, c.nom_client, l.description as article,
               tr.note_qualite as motif_retour, tr.date_heure as date_retour,
               u.nom_complet as responsable_initial
        FROM tracabilite_tickets tr
        JOIN tickets t ON tr.id_ticket = t.id_ticket
        JOIN clients c ON t.id_client = c.id_client
        JOIN lignes_ticket l ON t.id_ticket = l.id_ticket
        JOIN utilisateurs u ON tr.id_utilisateur = u.id_utilisateur
        WHERE tr.action = 'RETOUR_ATELIER' AND t.statut != 'recupere'
        ORDER BY tr.date_heure DESC";
$retours_actifs = $pdo->query($sql)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-orange"><i class="fas fa-undo-alt me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Suivi du linge retourné en production pour non-conformité</p>
        </div>
        <div class="badge bg-orange-soft text-orange p-2">
            <i class="fas fa-sync fa-spin me-1"></i> <?= count($retours_actifs) ?> Retours en cours
        </div>
    </div>

    

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white border-start border-4 border-orange">
                <small class="text-muted fw-bold">TAUX DE RETOUR (SEMAINE)</small>
                <h2 class="fw-bold m-0">1.8 %</h2>
                <small class="text-success"><i class="fas fa-arrow-down"></i> -0.5% vs mois dernier</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <small class="text-muted fw-bold">MOTIF PRINCIPAL</small>
                <h2 class="fw-bold m-0 text-dark">Repassage</h2>
                <small class="text-muted">42% des retours</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white">
                <small class="opacity-75 fw-bold">OBJECTIF QUALITÉ</small>
                <h2 class="fw-bold m-0 text-warning">< 1.0 %</h2>
                <div class="progress mt-2" style="height: 5px; background: rgba(255,255,255,0.1);">
                    <div class="progress-bar bg-warning" style="width: 70%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between">
            <h6 class="fw-bold mb-0">Articles en cours de rectification</h6>
            <button class="btn btn-sm btn-orange text-white fw-bold"><i class="fas fa-plus"></i> Enregistrer un retour</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small">
                    <tr>
                        <th class="ps-4">Ticket / Article</th>
                        <th>Client</th>
                        <th>Motif du Refus</th>
                        <th class="text-center">Responsable Initial</th>
                        <th class="text-center">Temps Écoulé</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($retours_actifs as $ret): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold">#<?= $ret['numero_ticket'] ?></span><br>
                            <small class="text-muted"><?= htmlspecialchars($ret['article']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($ret['nom_client']) ?></td>
                        <td>
                            <span class="text-danger small fw-bold italic">"<?= htmlspecialchars($ret['motif_retour']) ?>"</span>
                        </td>
                        <td class="text-center">
                            <small class="badge bg-light text-dark"><?= $ret['responsable_initial'] ?></small>
                        </td>
                        <td class="text-center">
                            <?php 
                                $heures = round((time() - strtotime($ret['date_retour'])) / 3600);
                                $color = ($heures > 24) ? 'text-danger fw-bold' : 'text-muted';
                            ?>
                            <span class="<?= $color ?>"><?= $heures ?>h</span>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-success shadow-sm">
                                <i class="fas fa-check"></i> Prêt à nouveau
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .text-orange { color: #fd7e14; }
    .bg-orange { background-color: #fd7e14; }
    .bg-orange-soft { background-color: rgba(253, 126, 20, 0.1); }
    .btn-orange { background-color: #fd7e14; border-color: #fd7e14; }
</style>

<?php require_once $root . '/templates/footer.php'; ?>