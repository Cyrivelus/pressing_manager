<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$logs = $pdo->query("SELECT * FROM historique_alertes ORDER BY date_alerte DESC LIMIT 50")->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between">
            <h5 class="fw-bold mb-0">Historique des Notifications</h5>
            <span class="badge bg-light text-dark"><?= count($logs) ?> derniers événements</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Date & Heure</th>
                        <th>Type</th>
                        <th>Message</th>
                        <th class="text-center">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($logs as $l): ?>
                    <tr>
                        <td class="ps-4 small"><?= date('d/m/Y H:i', strtotime($l['date_alerte'])) ?></td>
                        <td><span class="badge bg-secondary"><?= $l['type_alerte'] ?></span></td>
                        <td><?= htmlspecialchars($l['message_alerte']) ?></td>
                        <td class="text-center">
                            <i class="fas fa-check-circle text-success" title="Notification envoyée"></i>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>