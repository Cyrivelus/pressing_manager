<?php
// pages/clients/liste_clients.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../fonctions/database.php';
// Supposons que ces fonctions existent et sont adaptées à la nouvelle DB
require_once '../../fonctions/gestion_clients.php'; 

$message = '';
$message_type = '';
$client = null;
$historique_tickets = [];
$liste_clients = [];

try {
    // 1. Récupération de tous les clients
    // Requête : SELECT * FROM clients ORDER BY nom_client ASC
    $liste_clients = listerClients($pdo); 

    // 2. Si un client spécifique est sélectionné
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id_client = intval($_GET['id']);
        
        // Récupération infos client
        $stmt = $pdo->prepare("SELECT c.*, a.nom_agence FROM clients c 
                               LEFT JOIN agences a ON c.id_agence = a.id_agence 
                               WHERE c.id_client = ?");
        $stmt->execute([$id_client]);
        $client = $stmt->fetch();
        
        if ($client) {
            // Récupération de l'historique des tickets (Pressing)
            $stmt_tk = $pdo->prepare("SELECT * FROM tickets WHERE id_client = ? ORDER BY date_depot DESC LIMIT 10");
            $stmt_tk->execute([$id_client]);
            $historique_tickets = $stmt_tk->fetchAll();
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
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="glyphicon glyphicon-user"></i> Gestion des Clients</h2>
        <a href="ajouter_client.php" class="btn btn-primary">
            <span class="glyphicon glyphicon-plus"></span> Nouveau Client
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="<?= $client ? 'col-md-7' : 'col-md-12' ?>">
            <div class="panel panel-default shadow-sm">
                <div class="panel-heading"><b>Répertoire Clients</b></div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Nom & Prénom</th>
                                <th>Téléphone</th>
                                <th>Points Fidélité</th>
                                <th>Statut</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($liste_clients as $c): ?>
                                <tr class="<?= (isset($id_client) && $id_client == $c['id_client']) ? 'info' : '' ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($c['nom_client']) ?></strong> 
                                        <?= htmlspecialchars($c['prenom_client'] ?? '') ?>
                                    </td>
                                    <td><?= htmlspecialchars($c['telephone']) ?></td>
                                    <td>
                                        <span class="label label-info"><?= $c['points_fidelite'] ?> pts</span>
                                    </td>
                                    <td>
                                        <span class="label label-<?= $c['est_actif'] ? 'success' : 'danger' ?>">
                                            <?= $c['est_actif'] ? 'Actif' : 'Inactif' ?>
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <a href="?id=<?= $c['id_client'] ?>" class="btn btn-xs btn-default" title="Détails">
                                            <span class="glyphicon glyphicon-eye-open"></span>
                                        </a>
                                        <a href="modifier_client.php?id=<?= $c['id_client'] ?>" class="btn btn-xs btn-warning">
                                            <span class="glyphicon glyphicon-pencil"></span>
                                        </a>
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
            <div class="panel panel-info shadow-sm">
                <div class="panel-heading">
                    <div class="d-flex justify-content-between">
                        <b>Fiche Client : #<?= $client['id_client'] ?></b>
                        <a href="supprimer_client.php?id=<?= $client['id_client'] ?>" class="text-danger pull-right" onclick="return confirm('Supprimer ce client ?');">
                            <span class="glyphicon glyphicon-trash"></span>
                        </a>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <p><small class="text-muted">Téléphone :</small><br><b><?= htmlspecialchars($client['telephone']) ?></b></p>
                            <p><small class="text-muted">Email :</small><br><?= htmlspecialchars($client['email'] ?: 'Non renseigné') ?></p>
                        </div>
                        <div class="col-sm-6">
                            <p><small class="text-muted">Remise permanente :</small><br><span class="label label-warning"><?= $client['remise_speciale'] ?>%</span></p>
                            <p><small class="text-muted">Agence :</small><br><?= htmlspecialchars($client['nom_agence'] ?: 'Principale') ?></p>
                        </div>
                    </div>
                    <hr>
                    <p><small class="text-muted">Adresse :</small><br><?= nl2br(htmlspecialchars($client['adresse'] ?? 'N/A')) ?></p>
                </div>
            </div>

            <div class="panel panel-default shadow-sm">
                <div class="panel-heading"><b>Derniers Dépôts (Pressing)</b></div>
                <div class="table-responsive">
                    <table class="table table-condensed">
                        <thead>
                            <tr>
                                <th>N° Ticket</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historique_tickets)): ?>
                                <tr><td colspan="4" class="text-center">Aucun ticket trouvé.</td></tr>
                            <?php else: ?>
                                <?php foreach ($historique_tickets as $tk): ?>
                                    <tr>
                                        <td><a href="../tickets/vue_ticket.php?id=<?= $tk['id_ticket'] ?>"><b><?= $tk['numero_ticket'] ?></b></a></td>
                                        <td><?= date('d/m/Y', strtotime($tk['date_depot'])) ?></td>
                                        <td><?= number_format($tk['montant_total'], 0, '.', ' ') ?> F</td>
                                        <td>
                                            <?php 
                                                $class = ['en_attente'=>'default', 'en_traitement'=>'warning', 'pret'=>'primary', 'recupere'=>'success', 'annule'=>'danger'];
                                                $st = $tk['statut'];
                                            ?>
                                            <span class="label label-<?= $class[$st] ?? 'default' ?>"><?= ucfirst(str_replace('_', ' ', $st)) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="panel-footer">
                    <a href="../tickets/nouveau_ticket.php?id_client=<?= $client['id_client'] ?>" class="btn btn-sm btn-block btn-success">
                        <span class="glyphicon glyphicon-plus-sign"></span> Créer un nouveau dépôt
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>