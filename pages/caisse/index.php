<?php
// pages/caisse/index.php
if (session_status() == PHP_SESSION_NONE) session_start();

require_once '../../fonctions/database.php';

// Simuler l'ID de l'agence de l'utilisateur connecté
$id_agence = $_SESSION['id_agence'] ?? 1;
$aujourdhui = date('Y-m-d');

try {
    // 1. Statistiques du jour (basé sur la table paiements)
    $stmt = $pdo->prepare("SELECT SUM(montant) as total, mode_paiement FROM paiements 
                           WHERE DATE(date_paiement) = ? GROUP BY mode_paiement");
    $stmt->execute([$aujourdhui]);
    $stats_paiements = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $total_jour = array_sum($stats_paiements);

    // 2. Liste des tickets "Prêts" en attente de paiement ou de retrait
    $stmt = $pdo->prepare("SELECT t.*, c.nom_client, c.prenom_client 
                           FROM tickets t 
                           JOIN clients c ON t.id_client = c.id_client 
                           WHERE t.statut IN ('pret', 'en_attente') 
                           AND (t.montant_total > t.montant_verse)
                           ORDER BY t.date_depot DESC");
    $stmt->execute();
    $tickets_a_encaisser = $stmt->fetchAll();

} catch (Exception $e) {
    $erreur = $e->getMessage();
}

include '../../templates/header.php';
include '../../templates/navigation.php';

?>
</BR></BR></BR>
<div class="container-fluid mt-4">
</BR></BR></BR>

    <div class="row">
        <div class="col-md-3">
            <div class="panel panel-primary shadow-sm">
                <div class="panel-heading text-center">
                    <h4 style="margin:0">Total Journée</h4>
                    <h2 style="margin:10px 0"><strong><?= number_format($total_jour, 0, '.', ' ') ?></strong> <small style="color:white">XAF</small></h2>
                </div>
                <div class="list-group">
                    <div class="list-group-item">Espèces: <span class="pull-right"><strong><?= number_format($stats_paiements['especes'] ?? 0, 0) ?></strong></span></div>
                    <div class="list-group-item">Mobile Money: <span class="pull-right"><strong><?= number_format($stats_paiements['mobile'] ?? 0, 0) ?></strong></span></div>
                    <div class="list-group-item">Autres: <span class="pull-right"><strong><?= number_format(($stats_paiements['carte'] ?? 0) + ($stats_paiements['cheque'] ?? 0), 0) ?></strong></span></div>
                </div>
                <div class="panel-footer">
                    <button class="btn btn-block btn-success" data-toggle="modal" data-target="#modalCloture">
                        Clôturer la Caisse
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="panel panel-default">
                <div class="panel-heading" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 class="panel-title">Tickets en attente de règlement</h3>
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <input type="text" id="searchTicket" class="form-control" placeholder="Rechercher ticket...">
                        <span class="input-group-btn"><button class="btn btn-default"></button></span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr class="active">
                                <th>N° Ticket</th>
                                <th>Client</th>
                                <th>Total</th>
                                <th>Déjà payé</th>
                                <th>Reste</th>
                                <th>Statut Linge</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets_a_encaisser as $t): 
                                $reste = $t['montant_total'] - $t['montant_verse'] - $t['montant_remise'];
                            ?>
                            <tr>
                                <td><strong><?= $t['numero_ticket'] ?></strong></td>
                                <td><?= htmlspecialchars($t['nom_client'] . ' ' . $t['prenom_client']) ?></td>
                                <td><?= number_format($t['montant_total'], 0) ?></td>
                                <td class="text-success"><?= number_format($t['montant_verse'], 0) ?></td>
                                <td class="text-danger"><strong><?= number_format($reste, 0) ?></strong></td>
                                <td>
                                    <span class="label label-<?= ($t['statut'] == 'pret') ? 'success' : 'warning' ?>">
                                        <?= strtoupper($t['statut']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="encaisser.php?id=<?= $t['id_ticket'] ?>" class="btn btn-xs btn-primary">
                                       Encaisser
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCloture" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Rapport de clôture (<?= date('d/m/Y') ?>)</h4>
      </div>
      <form action=".cloturer.php" method="POST">
          <div class="modal-body">
            <p>Voulez-vous générer le rapport de recette journalier pour l'agence ?</p>
            <div class="well">
                <strong>Total à déclarer : <?= number_format($total_jour, 0) ?> XAF</strong>
            </div>
            <textarea name="notes" class="form-control" placeholder="Notes éventuelles (écarts de caisse...)"></textarea>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-success">Confirmer la Clôture</button>
          </div>
      </form>
    </div>
  </div>
</div>

<?php include '../../templates/footer.php'; ?>