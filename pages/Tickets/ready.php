<?php
if (session_status() == PHP_SESSION_NONE) session_start();


require_once '../../fonctions/database.php';

// Récupération des tickets PRÊTS mais non récupérés
try {
    $query = "
        SELECT 
            t.*, 
            c.nom_client, 
            c.prenom_client, 
            c.telephone as client_tel,
            a.nom_agence
        FROM tickets t
        LEFT JOIN clients c ON t.id_client = c.id_client
        LEFT JOIN agences a ON t.id_agence = a.id_agence
        WHERE t.statut = 'pret'
        ORDER BY t.updated_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

include '../../templates/header.php';
include '../../templates/navigation.php';
?>
</BR></BR></BR>
<div class="container-fluid" style="margin-top: 20px;">
    <div class="panel panel-default">
        <div class="panel-heading" style="background-color: #27ae60; color: white;">
            <h3 class="panel-title">
                
                Vêtements Prêts pour Retrait (<?= count($tickets) ?>)
            </h3>
        </div>
        <div class="panel-body">
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>N° Ticket</th>
                            <th>Client</th>
                            <th>Montant Total</th>
                            <th>Déjà Payé</th>
                            <th>Reste à Payer</th>
                            <th>Depuis le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $t): 
                            $reste = $t['montant_total'] - $t['montant_verse'] - $t['montant_remise'];
                            $depuis = floor((time() - strtotime($t['updated_at'])) / 86400);
                        ?>
                        <tr>
                            <td class="text-center">
                                <span class="badge" style="font-size: 1.1em;"><?= htmlspecialchars($t['numero_ticket']) ?></span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($t['nom_client'] . ' ' . $t['prenom_client']) ?></strong><br>
                                <span class="text-success"> <?= htmlspecialchars($t['client_tel']) ?></span>
                            </td>
                            <td><?= number_format($t['montant_total'], 0, ',', ' ') ?></td>
                            <td><?= number_format($t['montant_verse'], 0, ',', ' ') ?></td>
                            <td>
                                <?php if($reste > 0): ?>
                                    <strong class="text-danger" style="font-size: 1.2em;"><?= number_format($reste, 0, ',', ' ') ?> FCFA</strong>
                                <?php else: ?>
                                    <span class="label label-success">Soldé</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($depuis > 0): ?>
                                    Il y a <?= $depuis ?> jour(s)
                                <?php else: ?>
                                    Aujourd'hui
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="deliver_ticket.php?id=<?= $t['id_ticket'] ?>" class="btn btn-sm btn-primary" title="Livrer au client">
                             Livrer
                                    </a>
                                    
                                    <button class="btn btn-sm btn-default" onclick="alert('Relance envoyée à <?= $t['client_tel'] ?>')" title="Relancer">
                                       
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted" style="padding: 30px;">
                              <br>
                                Aucun vêtement n'est actuellement en attente de retrait.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>