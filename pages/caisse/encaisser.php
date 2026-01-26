<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once '../../fonctions/database.php';

// --- LOGIQUE DE TRAITEMENT POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $id_ticket = isset($_POST['id_ticket']) ? (int)$_POST['id_ticket'] : 0;
    $montant_saisi = isset($_POST['montant_paiement']) ? (float)$_POST['montant_paiement'] : 0;
    $mode = $_POST['mode_paiement'] ?? 'especes';
    $id_user = $_SESSION['id_utilisateur'] ?? 1;

    try {
        if ($id_ticket <= 0) throw new Exception("ID de ticket invalide.");
        
        $pdo->beginTransaction();

        // Récupérer les infos actuelles du ticket
        $stmt = $pdo->prepare("SELECT montant_total, montant_verse, numero_ticket FROM tickets WHERE id_ticket = ? FOR UPDATE");
        $stmt->execute([$id_ticket]);
        $ticket = $stmt->fetch();

        if (!$ticket) throw new Exception("Ticket inexistant.");

        // Calculer le solde théorique
        $solde_actuel = $ticket['montant_total'] - $ticket['montant_verse'];

        // Si le montant saisi est 0, on ne fait rien mais on ne bloque pas avec une erreur fatale
        if ($montant_saisi == 0) {
            echo json_encode(['success' => true, 'message' => 'Aucune modification effectuée (montant à 0).']);
            exit;
        }

        // 1. Enregistrer le mouvement dans la table paiements (positif ou négatif)
        $stmt_pay = $pdo->prepare("INSERT INTO paiements (id_ticket, montant, mode_paiement, date_paiement, id_utilisateur, reference) 
                                   VALUES (?, ?, ?, NOW(), ?, ?)");
        $ref = ($montant_saisi > 0) ? "ENC-" . $ticket['numero_ticket'] : "REM-" . $ticket['numero_ticket'];
        $stmt_pay->execute([$id_ticket, $montant_saisi, $mode, $id_user, $ref]);

        // 2. Mettre à jour le montant_verse dans la table tickets
        $nouveau_verse = $ticket['montant_verse'] + $montant_saisi;
        $stmt_upd = $pdo->prepare("UPDATE tickets SET montant_verse = ? WHERE id_ticket = ?");
        $stmt_upd->execute([$nouveau_verse, $id_ticket]);

        // 3. Si le ticket est totalement payé et était "pret", on peut le passer en "recupere" facultativement
        // Ou simplement laisser le statut tel quel.

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Transaction validée avec succès.']);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// --- LOGIQUE D'AFFICHAGE (GET) ---
$id_ticket = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_ticket <= 0) {
    header('Location: index.php'); // Redirection silencieuse si ID invalide
    exit;
}

$stmt = $pdo->prepare("SELECT t.*, c.nom_client, c.prenom_client FROM tickets t JOIN clients c ON t.id_client = c.id_client WHERE t.id_ticket = ?");
$stmt->execute([$id_ticket]);
$ticket = $stmt->fetch();

if (!$ticket) { header('Location: index.php'); exit; }

$reste_a_payer = $ticket['montant_total'] - $ticket['montant_verse'];

include '../../templates/header.php';
?>

<div class="container" style="margin-top: 50px;">
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div class="panel <?= ($reste_a_payer >= 0) ? 'panel-primary' : 'panel-warning' ?>">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <?= ($reste_a_payer >= 0) ? 'Encaisser le Ticket' : 'Rembourser le Client' ?> : #<?= $ticket['numero_ticket'] ?>
                    </h3>
                </div>
                <div class="panel-body">
                    <div class="text-center">
                        <h4>Client : <strong><?= htmlspecialchars($ticket['nom_client'].' '.$ticket['prenom_client']) ?></strong></h4>
                        <hr>
                        <div class="row">
                            <div class="col-xs-6"><h5>Total Ticket :</h5></div>
                            <div class="col-xs-6 text-right"><h5><?= number_format($ticket['montant_total'], 0) ?> XAF</h5></div>
                        </div>
                        <div class="row">
                            <div class="col-xs-6"><h5>Déjà Versé :</h5></div>
                            <div class="col-xs-6 text-right text-success"><h5><?= number_format($ticket['montant_verse'], 0) ?> XAF</h5></div>
                        </div>
                        <div class="well">
                            <h2 style="margin:0;">
                                <?= ($reste_a_payer >= 0) ? 'Reste à percevoir' : 'Trop perçu (à rendre)' ?> :<br>
                                <span class="<?= ($reste_a_payer >= 0) ? 'text-danger' : 'text-primary' ?>">
                                    <?= number_format(abs($reste_a_payer), 0) ?> XAF
                                </span>
                            </h2>
                        </div>
                    </div>

                    <form id="formEncaisser">
                        <input type="hidden" name="id_ticket" value="<?= $id_ticket ?>">
                        
                        <div class="form-group">
                            <label>Montant de la transaction (XAF)</label>
                            <input type="number" name="montant_paiement" class="form-control input-lg" 
                                   value="<?= $reste_a_payer ?>" step="any" required>
                            <p class="help-block"><i>Note: Un montant négatif sera considéré comme un remboursement.</i></p>
                        </div>

                        <div class="form-group">
    <label>Mode de règlement</label>
    <select name="mode_paiement" class="form-control" style="height: 45px; padding: 6px 12px; font-size: 16px;">
        <option value="especes">Espèces</option>
        <option value="mobile">Mobile Money</option>
        <option value="carte">Carte Bancaire</option>
        <option value="cheque">Chèque</option>
    </select>
</div>

                        <div class="row">
                            <div class="col-xs-6">
                                <a href="index.php" class="btn btn-default btn-block">Annuler</a>
                            </div>
                            <div class="col-xs-6">
                                <button type="submit" class="btn btn-success btn-block">Valider</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$('#formEncaisser').on('submit', function(e) {
    e.preventDefault();
    if(!confirm("Confirmer cette transaction ?")) return;

    $.post('encaisser.php', $(this).serialize(), function(res) {
        if(res.success) {
            alert(res.message);
            window.location.href = 'index.php';
        } else {
            alert("Erreur: " + res.message);
        }
    });
});
</script>

<?php include '../../templates/footer.php'; ?>