<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$id_service = $_GET['id'] ?? null;
$titre = "Historique des Changements de Prix";

$sql = "SELECT h.*, s.nom_service, u.nom_complet as auteur
        FROM historique_tarifs h
        JOIN services s ON h.id_service = s.id_service
        JOIN utilisateurs u ON h.id_utilisateur = u.id_utilisateur";

if ($id_service) {
    $sql .= " WHERE h.id_service = " . intval($id_service);
}
$sql .= " ORDER BY h.date_changement DESC";
$logs = $pdo->query($sql)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="mb-4 mt-4">
        <h2 class="fw-bold m-0 text-dark"><i class="fas fa-history text-secondary me-2"></i><?= $titre ?></h2>
        <p class="text-muted">Traçabilité complète des modifications tarifaires</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-dark text-white">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Service</th>
                        <th class="text-center">Ancien Prix</th>
                        <th class="text-center">Nouveau Prix</th>
                        <th>Auteur</th>
                        <th class="text-end pe-4">Variation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($logs as $l): 
                        $diff = $l['nouveau_prix'] - $l['ancien_prix'];
                        $color = ($diff > 0) ? 'text-success' : 'text-danger';
                    ?>
                    <tr>
                        <td class="ps-4 small"><?= date('d/m/Y H:i', strtotime($l['date_changement'])) ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($l['nom_service']) ?></td>
                        <td class="text-center text-muted"><?= number_format($l['ancien_prix'],0) ?> F</td>
                        <td class="text-center fw-bold"><?= number_format($l['nouveau_prix'],0) ?> F</td>
                        <td><i class="fas fa-user-shield me-1 small"></i> <?= $l['auteur'] ?></td>
                        <td class="text-end pe-4 fw-bold <?= $color ?>">
                            <?= ($diff > 0) ? '+' : '' ?><?= number_format($diff,0) ?> F
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>