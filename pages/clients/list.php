<?php
// pages/clients/liste_clients.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../fonctions/database.php';
// On suppose que ces fonctions sont adaptées à la nouvelle DB pressing_manager
require_once '../../fonctions/gestion_clients.php'; 

$message = '';
$message_type = '';
$client = null;
$tickets_recents = [];
$liste_clients = [];

try {
    // Récupération de la liste (Assurez-vous que listerClients utilise la table 'clients')
    $liste_clients = listerClients($pdo);

    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id_client = intval($_GET['id']);
        $client = trouverClientParId($pdo, $id_client);
        
        if ($client) {
            // Remplacement des contrats d'assurance par les tickets du pressing
            $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id_client = ? ORDER BY date_depot DESC LIMIT 5");
            $stmt->execute([$id_client]);
            $tickets_recents = $stmt->fetchAll();
        } else {
            $message = "Client introuvable.";
            $message_type = 'warning';
        }
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
    $message_type = 'danger';
}

include '../../templates/header.php';
include '../../templates/navigation.php';

?>
</BR></BR></BR>
<div class="container-fluid" style="margin-top: 20px;">
    
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="<?= $client ? 'col-md-7' : 'col-md-12' ?>">
            <div class="panel panel-default shadow-sm">
                <div class="panel-heading" style="background-color: #2c3e50; color: white; display: flex; justify-content: space-between; align-items: center;">
                    <h3 class="panel-title">> Base de données Clients</h3>
                    <a href="ajouter_client.php" class="btn btn-xs btn-success"><span class="glyphicon glyphicon-plus"></span> Nouveau Client</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Téléphone</th>
                                <th class="hidden-xs">Fidélité</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($liste_clients as $c): ?>
                                <tr class="<?= (isset($id_client) && $id_client == $c['id_client']) ? 'info' : '' ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($c['nom_client'] . ' ' . ($c['prenom_client'] ?? '')) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($c['email'] ?? 'Pas d\'email') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($c['telephone']) ?></td>
                                    <td class="hidden-xs">
                                        <span class="label label-primary"><?= $c['points_fidelite'] ?> pts</span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="?id=<?= $c['id_client'] ?>" class="btn btn-sm btn-default" title="Voir">Détails</a>
                                            <a href="modifier_client.php?id=<?= $c['id_client'] ?>" class="btn btn-sm btn-warning"><span class="glyphicon glyphicon-pencil"></span></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($client): ?>
        <div class="col-md-5">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title">Détails : <?= htmlspecialchars($client['nom_client']) ?></h3>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <p><small class="text-muted">Téléphone</small><br><strong><?= htmlspecialchars($client['telephone']) ?></strong></p>
                            <p><small class="text-muted">Adresse</small><br><strong><?= htmlspecialchars($client['adresse'] ?? 'Non renseignée') ?></strong></p>
                        </div>
                        <div class="col-sm-6 text-right">
                            <p><small class="text-muted">Remise habituelle</small><br><span class="badge" style="background: #27ae60;"><?= $client['remise_speciale'] ?>%</span></p>
                            <p><small class="text-muted">Membre depuis</small><br><strong><?= date('d/m/Y', strtotime($client['date_inscription'])) ?></strong></p>
                        </div>
                    </div>
                    <?php if(!empty($client['notes'])): ?>
                        <div class="well well-sm"><strong>Note:</strong> <?= htmlspecialchars($client['notes']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">Derniers Dépôts (Pressing)</div>
                <div class="table-responsive">
                    <table class="table table-condensed">
                        <thead>
                            <tr>
                                <th>N° Ticket</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($tickets_recents)): ?>
                                <?php foreach ($tickets_recents as $t): ?>
                                    <tr>
                                        <td><a href="../tickets/vue_ticket.php?id=<?= $t['id_ticket'] ?>"><strong><?= $t['numero_ticket'] ?></strong></a></td>
                                        <td><?= date('d/m/y', strtotime($t['date_depot'])) ?></td>
                                        <td>
                                            <?php 
                                                $labels = ['en_attente'=>'default', 'en_traitement'=>'primary', 'pret'=>'success', 'recupere'=>'info', 'annule'=>'danger'];
                                                $label = $labels[$t['statut']] ?? 'default';
                                            ?>
                                            <span class="label label-<?= $label ?>"><?= $t['statut'] ?></span>
                                        </td>
                                        <td><?= number_format($t['montant_total'], 0, '.', ' ') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center">Aucun ticket trouvé.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>