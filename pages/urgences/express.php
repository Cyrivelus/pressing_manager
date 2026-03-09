<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion du Service Express";

try {
    // 1. Récupération des tickets express non encore récupérés
    // On calcule le temps restant en minutes jusqu'à l'heure de retrait prévue
    $sql = "SELECT t.*, c.nom_client, c.telephone,
                   TIMESTAMPDIFF(MINUTE, NOW(), t.date_retrait_prevue) as minutes_restantes
            FROM tickets t
            JOIN clients c ON t.id_client = c.id_client
            WHERE t.statut NOT IN ('recupere', 'annule')
            AND (t.notes LIKE '%EXPRESS%' OR t.montant_total > t.montant_hors_taxe) 
            ORDER BY t.date_retrait_prevue ASC";
            
    $urgences = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    // En cas d'erreur, on initialise un tableau vide pour éviter de bloquer l'affichage
    $urgences = [];
    $error_db = "Erreur SQL : " . $e->getMessage();
}

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <?php if (isset($error_db)): ?>
        <div class="alert alert-danger shadow-sm border-0 mb-4"><?= $error_db ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-danger"><?= $titre ?></h2>
            <p class="text-muted">Suivi temps réel des commandes à haute priorité et délais critiques</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-danger p-2 px-3 shadow-sm d-flex align-items-center" style="border-radius: 10px;">
                 <?= count($urgences) ?> Commande(s) Urgente(s)
            </span>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white border-start border-4 border-danger h-100">
                <small class="text-muted fw-bold text-uppercase">Prochaine Échéance</small>
                <?php if(!empty($urgences)): ?>
                    <h2 class="fw-bold m-0 text-danger mt-2"><?= date('H:i', strtotime($urgences[0]['date_retrait_prevue'])) ?></h2>
                    <small class="fw-bold text-dark">Client : <?= htmlspecialchars($urgences[0]['nom_client']) ?></small>
                <?php else: ?>
                    <h2 class="fw-bold m-0 text-success mt-2">Aucune</h2>
                    <small>Tout est à jour</small>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white h-100 shadow-lg">
                <small class="opacity-75 fw-bold text-uppercase">Revenu Majoré (Estimation)</small>
                <h2 class="fw-bold m-0 text-warning mt-2">+ 125 000 <small class="fs-6">FCFA</small></h2>
                <small class="opacity-50">Impact financier du service express</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white h-100">
                <small class="text-muted fw-bold text-uppercase">SLA Respecté</small>
                <h2 class="fw-bold m-0 mt-2">94 %</h2>
                <div class="progress mt-2" style="height: 8px; border-radius: 10px;">
                    <div class="progress-bar bg-success" style="width: 94%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4">Ticket</th>
                        <th>Client</th>
                        <th>Notes / Service</th>
                        <th class="text-center">Compte à rebours</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($urgences as $u): ?>
                    <?php 
                        $min = $u['minutes_restantes'];
                        $alert_class = ($min < 60) ? 'table-danger' : (($min < 180) ? 'table-warning' : '');
                        $timer_color = ($min < 60) ? 'text-danger fw-bold blink' : 'text-dark';
                    ?>
                    <tr class="<?= $alert_class ?>">
                        <td class="ps-4">
                            <span class="badge bg-dark">#<?= $u['numero_ticket'] ?></span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($u['nom_client']) ?></div>
                            <small class="text-muted"> <?= $u['telephone'] ?></small>
                        </td>
                        <td>
                            <span class="badge bg-danger text-uppercase p-2 mb-1">Express 4H</span><br>
                            <small class="text-muted text-truncate d-inline-block" style="max-width: 150px;">
                                <?= !empty($u['notes']) ? htmlspecialchars($u['notes']) : 'Aucune note' ?>
                            </small>
                        </td>
                        <td class="text-center">
                            <span class="<?= $timer_color ?> d-flex align-items-center justify-content-center">
                               
                                <?php 
                                    if($min < 0) echo "<span class='badge bg-danger'>EN RETARD (" . abs($min) . "m)</span>";
                                    else echo "<strong>".floor($min/60)."h ".($min%60)."m</strong>";
                                ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-white text-dark border shadow-sm px-3 py-2"><?= ucfirst($u['statut']) ?></span>
                        </td>
                        <td class="text-end pe-4">
                            <a href="../production/marquer_pret.php?id=<?= $u['id_ticket'] ?>" class="btn btn-sm btn-dark px-3 rounded-pill">
                                Prêt
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($urgences)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                               
                                <p>Aucune commande express en attente.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .blink { animation: blinker 1.5s linear infinite; }
    @keyframes blinker { 50% { opacity: 0; } }
    .table-danger { background-color: rgba(220, 53, 69, 0.05) !important; }
    .table-warning { background-color: rgba(255, 193, 7, 0.05) !important; }
</style>

<?php require_once  '../../templates/footer.php'; ?>