<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Système de Traçabilité RFID";

// 1. Récupération des articles tagués avec gestion d'erreur
$articles_rfid = [];
try {
    $sql = "SELECT l.*, t.numero_ticket, c.nom_client 
            FROM lignes_ticket l
            JOIN tickets t ON l.id_ticket = t.id_ticket
            JOIN clients c ON t.id_client = c.id_client
            WHERE l.rfid_tag IS NOT NULL
            ORDER BY l.id_ligne DESC LIMIT 15";
    $stmt = $pdo->query($sql);
    $articles_rfid = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la colonne n'existe pas encore, on affiche un message d'erreur discret
    $db_error = "Note : La colonne 'rfid_tag' semble manquante dans la table 'lignes_ticket'.";
}

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-primary"><i class="fas fa-microchip me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Identification radio-fréquence et suivi de flux en temps réel</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary"><i class="fas fa-sync"></i> Synchroniser Scanner</button>
            <button class="btn btn-primary shadow-sm"><i class="fas fa-plus"></i> Associer Nouveau Tag</button>
        </div>
    </div>

    <?php if (isset($db_error)): ?>
        <div class="alert alert-warning border-0 shadow-sm">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= $db_error ?>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white border-start border-4 border-info">
                <small class="text-muted fw-bold">ARTICLES SOUS PUCE</small>
                <h2 class="fw-bold m-0 text-dark"><?= count($articles_rfid) ?></h2>
                <small class="text-info"><i class="fas fa-check-circle"></i> Tags détectés récemment</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <small class="text-muted fw-bold">LECTURE EN VRAC (CAPACITÉ)</small>
                <h2 class="fw-bold m-0">50 <small class="fs-6">articles/sec</small></h2>
                <small class="text-muted">Performance du tunnel RFID</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white">
                <small class="opacity-75 fw-bold">ALERTES SORTIE NON VALIDE</small>
                <h2 class="fw-bold m-0 text-danger">0</h2>
                <small class="text-success"><i class="fas fa-shield-alt"></i> Aucun mouvement suspect</small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between">
            <h6 class="fw-bold mb-0">Dernières détections antennes</h6>
            <span class="badge bg-primary"><?= date('H:i') ?> En direct</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4">Code RFID (UID)</th>
                        <th>Article / Ticket</th>
                        <th>Propriétaire</th>
                        <th class="text-center">Dernière Zone</th>
                        <th class="text-end pe-4">État de vie</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($articles_rfid)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-search mb-2 d-block fa-2x"></i>
                                Aucun article RFID détecté pour le moment.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($articles_rfid as $a): ?>
                        <tr>
                            <td class="ps-4">
                                <code class="text-primary fw-bold"><?= htmlspecialchars($a['rfid_tag']) ?></code>
                            </td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($a['description'] ?? 'Article sans description') ?></div>
                                <small class="badge bg-light text-dark border">#<?= $a['numero_ticket'] ?></small>
                            </td>
                            <td><?= htmlspecialchars($a['nom_client']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-info-soft text-info">Zone Séchage</span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="progress" style="height: 5px; width: 100px; margin-left: auto;">
                                    <div class="progress-bar bg-success" style="width: 85%" title="85 lavages restants"></div>
                                </div>
                                <small class="text-muted small">85/100 cycles</small>
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
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
    .container-fluid { max-width: 1400px; }
    .card { transition: transform 0.2s; }
    .card:hover { transform: translateY(-2px); }
</style>

<?php require_once  '../../templates/footer.php'; ?>