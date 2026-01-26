<?php
if (session_status() == PHP_SESSION_NONE) session_start();



require_once '../../fonctions/database.php';

// Récupération des tickets en attente ou en traitement
try {
    $query = "
        SELECT 
            t.*, 
            c.nom_client, 
            c.prenom_client, 
            c.telephone as client_tel,
            u.nom_complet as receptionniste,
            a.nom_agence
        FROM tickets t
        LEFT JOIN clients c ON t.id_client = c.id_client
        LEFT JOIN utilisateurs u ON t.id_utilisateur = u.id_utilisateur
        LEFT JOIN agences a ON t.id_agence = a.id_agence
        WHERE t.statut IN ('en_attente', 'en_traitement')
        ORDER BY t.date_retrait_prevue ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Erreur lors de la récupération : " . $e->getMessage());
}

include '../../templates/header.php';
include '../../templates/navigation.php';
?>
</BR></BR></BR>
<div class="container-fluid" style="margin-top: 20px;">
    <div class="panel panel-default">
        <div class="panel-heading" style="background-color: #f39c12; color: white;">
            <h3 class="panel-title">
                
                Tickets en cours de traitement (<?= count($tickets) ?>)
            </h3>
        </div>
        <div class="panel-body">
            
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="tablePending">
                    <thead>
                        <tr>
                            <th>N° Ticket</th>
                            <th>Date Dépôt</th>
                            <th>Client</th>
                            <th>Montant</th>
                            <th>Reste à Payer</th>
                            <th>Retrait Prévu</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $t): 
                            // Calcul du reste à payer
                            $reste = $t['montant_total'] - $t['montant_verse'] - $t['montant_remise'];
                            $date_retrait = new DateTime($t['date_retrait_prevue']);
                            $aujourdhui = new DateTime();
                            $retard = ($date_retrait < $aujourdhui && $t['statut'] != 'pret') ? 'text-danger font-weight-bold' : '';
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($t['numero_ticket']) ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($t['date_depot'])) ?></td>
                            <td>
                                <?= htmlspecialchars($t['nom_client'] . ' ' . $t['prenom_client']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($t['client_tel']) ?></small>
                            </td>
                            <td><?= number_format($t['montant_total'], 0, ',', ' ') ?> FCFA</td>
                            <td>
                                <span class="label <?= $reste > 0 ? 'label-warning' : 'label-success' ?>">
                                    <?= number_format($reste, 0, ',', ' ') ?> FCFA
                                </span>
                            </td>
                            <td class="<?= $retard ?>">
                                <?= date('d/m/Y H:i', strtotime($t['date_retrait_prevue'])) ?>
                                <?php if($retard): ?> <span class="glyphicon glyphicon-warning-sign"></span> <?php endif; ?>
                            </td>
                            <td>
                                <?php if($t['statut'] == 'en_attente'): ?>
                                    <span class="label label-default">En attente</span>
                                <?php else: ?>
                                    <span class="label label-primary">En traitement</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="view.php?id=<?= $t['id_ticket'] ?>" class="btn btn-xs btn-default" title="Voir">
                                        Voir
                                    </a>
                                    <a href="update_status.php?id=<?= $t['id_ticket'] ?>&status=pret" class="btn btn-xs btn-success" title="Marquer comme PRÊT" onclick="return confirm('L\'article est-il prêt pour le retrait ?')">
                                      Mise à jour du statut  
                                    </a>
                                  <a href="print.php?id=<?= $t['id_ticket'] ?>" class="btn btn-xs btn-primary">Imprimer</a>
                                        
                                    
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">Aucun ticket en attente ou en traitement.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function printTicket(id) {
    window.open('print_ticket.php?id=' + id, '_blank', 'width=400,height=600');
}
</script>

<?php include '../../templates/footer.php'; ?>