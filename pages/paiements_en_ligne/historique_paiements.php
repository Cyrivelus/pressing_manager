<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Archives & Historique des Flux";

// 1. Filtres de recherche
$date_debut = $_GET['debut'] ?? date('Y-m-01');
$date_fin = $_GET['fin'] ?? date('Y-m-d');
$mode_filtre = $_GET['mode'] ?? 'tous';

// 2. Construction de la requête avec filtres
$sql = "SELECT p.*, t.numero_ticket, c.nom_client, u.nom_complet as caissier
        FROM paiements p
        JOIN tickets t ON p.id_ticket = t.id_ticket
        JOIN clients c ON t.id_client = c.id_client
        JOIN utilisateurs u ON p.id_utilisateur = u.id_utilisateur
        WHERE p.date_paiement BETWEEN :debut AND :fin";

if ($mode_filtre !== 'tous') {
    $sql .= " AND p.mode_paiement = :mode";
}

$sql .= " ORDER BY p.date_paiement DESC";

$stmt = $pdo->prepare($sql);
$params = ['debut' => $date_debut . ' 00:00:00', 'fin' => $date_fin . ' 23:59:59'];
if ($mode_filtre !== 'tous') $params['mode'] = $mode_filtre;
$stmt->execute($params);
$historique = $stmt->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-history text-secondary me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Audit complet des transactions et traçabilité des encaissements</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark" onclick="window.print()"><i class="fas fa-print"></i></button>
            <button class="btn btn-success"><i class="fas fa-file-excel"></i> Export .CSV</button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <form class="card-body row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Période du</label>
                <input type="date" name="debut" class="form-control" value="<?= $date_debut ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Au</label>
                <input type="date" name="fin" class="form-control" value="<?= $date_fin ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Mode de paiement</label>
                <select name="mode" class="form-select">
                    <option value="tous">Tous les modes</option>
                    <option value="especes">Espèces</option>
                    <option value="mobile">Mobile Money</option>
                    <option value="carte">Carte Bancaire</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-dark w-100">Filtrer l'historique</button>
            </div>
        </form>
    </div>

    

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4">Date & Heure</th>
                        <th>Référence / Ticket</th>
                        <th>Client</th>
                        <th>Caissier</th>
                        <th class="text-center">Mode</th>
                        <th class="text-end pe-4">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_periode = 0;
                    foreach($historique as $h): 
                        $total_periode += $h['montant'];
                    ?>
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold"><?= date('d/m/Y', strtotime($h['date_paiement'])) ?></span><br>
                            <small class="text-muted"><?= date('H:i', strtotime($h['date_paiement'])) ?></small>
                        </td>
                        <td>
                            <span class="text-primary fw-bold">#<?= $h['numero_ticket'] ?></span><br>
                            <small class="text-muted"><?= $h['reference'] ?: 'Saisie directe' ?></small>
                        </td>
                        <td><?= htmlspecialchars($h['nom_client']) ?></td>
                        <td><small class="badge bg-light text-dark"><?= $h['caissier'] ?></small></td>
                        <td class="text-center">
                            <?php 
                            $icon = ($h['mode_paiement'] == 'mobile') ? 'fa-mobile-alt text-warning' : 
                                    (($h['mode_paiement'] == 'carte') ? 'fa-credit-card text-info' : 'fa-money-bill-wave text-success');
                            ?>
                            <i class="fas <?= $icon ?> shadow-sm p-2 rounded bg-light"></i>
                        </td>
                        <td class="text-end pe-4 fw-bold"><?= number_format($h['montant'], 0, ',', ' ') ?> FCFA</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-dark text-white">
                    <tr>
                        <td colspan="5" class="ps-4 fw-bold">TOTAL SUR LA PÉRIODE SÉLECTIONNÉE</td>
                        <td class="text-end pe-4 fw-bold h5 mb-0"><?= number_format($total_periode, 0, ',', ' ') ?> FCFA</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>