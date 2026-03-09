<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once '../../fonctions/database.php';

// Vérifier si l'ID est fourni
$id_ticket = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_ticket <= 0) {
    header('Location: list_tickets.php');
    exit();
}

try {
    // 1. Récupérer les infos du ticket et du client
    $query = "SELECT t.*, c.nom_client, c.prenom_client, c.telephone as client_tel, c.adresse as client_adresse,
                     u.nom_complet as caissier, a.nom_agence
              FROM tickets t
              LEFT JOIN clients c ON t.id_client = c.id_client
              LEFT JOIN utilisateurs u ON t.id_utilisateur = u.id_utilisateur
              LEFT JOIN agences a ON t.id_agence = a.id_agence
              WHERE t.id_ticket = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id_ticket]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        die("Ticket introuvable.");
    }

    // 2. Récupérer les articles (lignes)
    $stmt_lignes = $pdo->prepare("SELECT l.*, s.nom_service 
                                 FROM lignes_ticket l 
                                 JOIN services s ON l.id_service = s.id_service 
                                 WHERE l.id_ticket = ?");
    $stmt_lignes->execute([$id_ticket]);
    $lignes = $stmt_lignes->fetchAll(PDO::FETCH_ASSOC);

    // 3. Récupérer l'historique des paiements
    $stmt_pay = $pdo->prepare("SELECT * FROM paiements WHERE id_ticket = ? ORDER BY date_paiement DESC");
    $stmt_pay->execute([$id_ticket]);
    $paiements = $stmt_pay->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

include '../../templates/header.php';
include '../../templates/navigation.php';

?>
</BR></BR>
<div class="container" style="margin-top: 20px;">
</BR>  
<div class="row" style="margin-bottom: 20px;">
        <div class="col-md-6">
            <h2>Ticket #<?= $ticket['numero_ticket'] ?></h2>
            <span class="label label-info" style="font-size: 1.2em;"><?= strtoupper($ticket['statut']) ?></span>
        </div>
        <div class="col-md-6 text-right">
    <?php 
    // On récupère l'activité actuelle (par défaut 'pressing' si non définie)
    $currentActivity = $_SESSION['user_activity'] ?? 'pressing'; 
    ?>

    <?php if ($currentActivity === 'pressing'): ?>
        <a href="print.php?id=<?= $id_ticket ?>" class="btn btn-default" target="_blank">
          Imprimer Reçu
        </a>
    <?php endif; ?>

    <?php if ($currentActivity === 'commerce'): ?>
        <a href="print_commerce.php?id=<?= $id_ticket ?>" class="btn btn-default" target="_blank">
             Imprimer Commerce
        </a>
    <?php endif; ?>

    <?php if ($currentActivity === 'hotel'): ?>
        <a href="print_hotel.php?id=<?= $id_ticket ?>" class="btn btn-default" target="_blank">
             Imprimer Hôtel
        </a>
    <?php endif; ?>

    <a href="list.php" class="btn btn-primary">Retour à la liste</a>
</div>
        
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading"><b>Informations Client</b></div>
                <div class="panel-body">
                    <p><strong>Nom :</strong> <?= htmlspecialchars($ticket['nom_client'] . ' ' . $ticket['prenom_client']) ?></p>
                    <p><strong>Tel :</strong> <?= htmlspecialchars($ticket['client_tel']) ?></p>
                    <p><strong>Adresse :</strong> <?= htmlspecialchars($ticket['client_adresse']) ?></p>
                    <hr>
                    <p><strong>Déposé le :</strong> <?= date('d/m/Y H:i', strtotime($ticket['date_depot'])) ?></p>
                    <p><strong>Retrait prévu :</strong> <span class="text-danger"><?= date('d/m/Y H:i', strtotime($ticket['date_retrait_prevue'])) ?></span></p>
                    <p><strong>Agence :</strong> <?= htmlspecialchars($ticket['nom_agence']) ?></p>
                    <p><strong>Réceptionné par :</strong> <?= htmlspecialchars($ticket['caissier']) ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading"><b>Détails des Vêtements</b></div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Étiquette</th>
                                <th>Service</th>
                                <th>Qté</th>
                                <th>P.U.</th>
                                <th>Sous-total</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lignes as $l): ?>
                            <tr>
                                <td><code><?= $l['numero_etiquette'] ?></code></td>
                                <td><?= htmlspecialchars($l['nom_service']) ?></td>
                                <td><?= $l['quantite'] ?></td>
                                <td><?= number_format($l['prix_unitaire'], 0, ',', ' ') ?></td>
                                <td><?= number_format($l['sous_total'], 0, ',', ' ') ?></td>
                                <td><span class="label label-default"><?= $l['statut_article'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-right">Total Brut :</th>
                                <th><?= number_format($ticket['montant_total'] + $ticket['montant_remise'], 0, ',', ' ') ?> FCFA</th>
                                <th></th>
                            </tr>
                            <?php if($ticket['montant_remise'] > 0): ?>
                            <tr>
                                <th colspan="4" class="text-right text-success">Remise :</th>
                                <th class="text-success">- <?= number_format($ticket['montant_remise'], 0, ',', ' ') ?> FCFA</th>
                                <th></th>
                            </tr>
                            <?php endif; ?>
                            <tr style="font-size: 1.3em;">
                                <th colspan="4" class="text-right">NET À PAYER :</th>
                                <th><?= number_format($ticket['montant_total'], 0, ',', ' ') ?> FCFA</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="panel panel-success">
                <div class="panel-heading"><b>Historique des Règlements</b></div>
                <div class="panel-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Mode</th>
                                <th>Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paiements as $p): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($p['date_paiement'])) ?></td>
                                <td><?= strtoupper($p['mode_paiement']) ?></td>
                                <td><?= number_format($p['montant'], 0, ',', ' ') ?> FCFA</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php 
                        $reste = $ticket['montant_total'] - $ticket['montant_verse'];
                    ?>
                    <div class="alert <?= $reste <= 0 ? 'alert-success' : 'alert-danger' ?> text-right">
                        <h4 style="margin:0;">
                            <?= $reste <= 0 ? "TICKET SOLDÉ" : "RESTE À PAYER : " . number_format($reste, 0, ',', ' ') . " FCFA" ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>