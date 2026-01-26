<?php
// pages/tickets/list.php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once '../../fonctions/database.php';

$statut_filtre = $_GET['statut'] ?? '';

$sql = "SELECT t.*, c.nom_client, c.telephone, u.nom_complet as caissier 
        FROM tickets t 
        JOIN clients c ON t.id_client = c.id_client 
        JOIN utilisateurs u ON t.id_utilisateur = u.id_utilisateur";

if ($statut_filtre) {
    $sql .= " WHERE t.statut = " . $pdo->quote($statut_filtre);
}
$sql .= " ORDER BY t.date_depot DESC";

$tickets = $pdo->query($sql)->fetchAll();

include '../../templates/header.php';
include '../../templates/navigation.php';
;
?>
</BR></BR></BR>
<div class="container-fluid mt-4">
    <div class="panel panel-default">
        <div class="panel-heading" style="display:flex; justify-content:space-between;">
            <h3 class="panel-title">Gestion des Tickets</h3>
            <a href="create.php" class="btn btn-primary btn-sm">Nouveau Dépôt</a>
        </div>
        <div class="panel-body">
            <div class="btn-group mb-3">
                <a href="list.php" class="btn btn-default">Tous</a>
                <a href="list.php?statut=en_attente" class="btn btn-warning">En attente</a>
                <a href="list.php?statut=pret" class="btn btn-success">Prêts</a>
                <a href="list.php?statut=recupere" class="btn btn-info">Récunérés</a>
            </div>

            <table class="table table-striped table-hover mt-3">
                <thead>
                    <tr>
                        <th>N° Ticket</th>
                        <th>Date Dépôt</th>
                        <th>Client</th>
                        <th>Prévu le</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($tickets as $t): ?>
                    <tr>
                        <td><strong><?= $t['numero_ticket'] ?></strong></td>
                        <td><?= date('d/m/Y H:i', strtotime($t['date_depot'])) ?></td>
                        <td><?= $t['nom_client'] ?> <br> <small><?= $t['telephone'] ?></small></td>
                        <td><?= date('d/m/Y', strtotime($t['date_retrait_prevue'])) ?></td>
                        <td><?= number_format($t['montant_total'], 0) ?> XAF</td>
                        <td>
                            <?php 
                                $class = ['en_attente'=>'warning', 'pret'=>'success', 'recupere'=>'info', 'annule'=>'danger'];
                                $lbl = $class[$t['statut']] ?? 'default';
                            ?>
                            <span class="label label-<?= $lbl ?>"><?= strtoupper($t['statut']) ?></span>
                        </td>
                        <td>
                            <a href="view.php?id=<?= $t['id_ticket'] ?>" class="btn btn-xs btn-default">Voir</a>
                            <a href="print.php?id=<?= $t['id_ticket'] ?>" class="btn btn-xs btn-primary">Imprimer</a>
                            <a href="print_commerce.php?id=<?= $t['id_ticket'] ?>" class="btn btn-xs btn-primary">Imprimer pour commerce</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>