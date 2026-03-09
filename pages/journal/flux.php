<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Journal du Flux de Production";

// 1. Gestion des filtres
$filtre_statut = $_GET['statut'] ?? '';
$params = [];

$sql = "SELECT j.*, l.description, t.numero_ticket, c.nom_client 
        FROM journal_production j
        JOIN lignes_ticket l ON j.id_ligne = l.id_ligne
        JOIN tickets t ON l.id_ticket = t.id_ticket
        JOIN clients c ON t.id_client = c.id_client";

if (!empty($filtre_statut)) {
    $sql .= " WHERE j.action = ?";
    $params[] = $filtre_statut;
}

$sql .= " ORDER BY j.date_action DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once  '../../templates/header.php';
require_once   '../../templates/navigation.php';
?>
<br><br><br>
<div class="container-fluid py-5">
    <div class="row mb-4 mt-4 align-items-end">
        <div class="col-md-6">
            <h2 class="fw-bold text-dark"><?= $titre ?></h2>
            <p class="text-muted">Historique complet des scans et mouvements d'articles</p>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="btn-group shadow-sm">
                <a href="?statut=" class="btn btn-white <?= $filtre_statut == '' ? 'active border-primary' : '' ?>">Tous</a>
                <a href="?statut=LAVAGE" class="btn btn-white <?= $filtre_statut == 'LAVAGE' ? 'active border-info' : '' ?>">Lavage</a>
                <a href="?statut=REPASSAGE" class="btn btn-white <?= $filtre_statut == 'REPASSAGE' ? 'active border-warning' : '' ?>">Repassage</a>
                <a href="?statut=PRET" class="btn btn-white <?= $filtre_statut == 'PRET' ? 'active border-success' : '' ?>">Prêt</a>
            </div>
            <button onclick="window.print()" class="btn btn-outline-secondary ms-2"></button>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 15px;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="text-muted small text-uppercase">
                        <th class="ps-4 py-3">Horodatage</th>
                        <th>Article / Ticket</th>
                        <th>Client</th>
                        <th>Action / Étape</th>
                        <th class="text-end pe-4">Opérateur</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                
                                <p class="text-muted">Aucune activité enregistrée pour ce filtre.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php 
                                // Définition des couleurs selon l'action
                                $badge_class = 'bg-secondary';
                                if($log['action'] == 'LAVAGE') $badge_class = 'bg-info';
                                if($log['action'] == 'REPASSAGE') $badge_class = 'bg-warning text-dark';
                                if($log['action'] == 'PRET') $badge_class = 'bg-success';
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?= date('d/m/Y', strtotime($log['date_action'])) ?></div>
                                    <small class="text-muted"><?= date('H:i:s', strtotime($log['date_action'])) ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($log['description']) ?></div>
                                    <span class="badge bg-light text-dark border small">Ticket #<?= $log['numero_ticket'] ?></span>
                                </td>
                                <td><?= htmlspecialchars($log['nom_client']) ?></td>
                                <td>
                                    <span class="badge <?= $badge_class ?> px-3 py-2">
                                      <?= $log['action'] ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4 text-muted small">
                                  ID: <?= $log['utilisateur'] ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .btn-white { background: white; border: 1px solid #dee2e6; color: #6c757d; font-weight: 600; font-size: 0.85rem; }
    .btn-white.active { color: #000; background: #f8f9fa; pointer-events: none; }
    .table thead th { font-weight: 700; letter-spacing: 0.5px; border-bottom: none; }
    .table tbody tr { transition: all 0.2s; }
    .table tbody tr:hover { background-color: rgba(13, 110, 253, 0.02); }
    @media print {
        .navigation, .btn-group, .btn-outline-secondary { display: none !important; }
        .card { shadow: none !important; border: 1px solid #ccc !important; }
    }
</style>

<?php require_once  '../../templates/footer.php'; ?>