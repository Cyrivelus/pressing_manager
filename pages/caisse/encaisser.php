<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once '../../fonctions/database.php';

// --- 1. LOGIQUE DE TRAITEMENT DES ENCAISSEMENTS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $id_ticket = isset($_POST['id_ticket']) ? (int)$_POST['id_ticket'] : 0;
    $montant_saisi = isset($_POST['montant_paiement']) ? (float)$_POST['montant_paiement'] : 0;
    $mode = $_POST['mode_paiement'] ?? 'especes';
    $id_user = $_SESSION['id_utilisateur'] ?? 1;

    try {
        if ($id_ticket <= 0) throw new Exception("ID de ticket invalide.");
        
        $pdo->beginTransaction();

        // Récupérer les infos du ticket et vérifier si les articles sont prêts
        $stmt = $pdo->prepare("
            SELECT t.montant_total, t.montant_verse, t.montant_remise, t.numero_ticket, t.statut,
            (SELECT COUNT(*) FROM lignes_ticket WHERE id_ticket = t.id_ticket AND statut_article != 'conditionne') as articles_restants
            FROM tickets t WHERE t.id_ticket = ? FOR UPDATE
        ");
        $stmt->execute([$id_ticket]);
        $ticket = $stmt->fetch();

        if (!$ticket) throw new Exception("Ticket inexistant.");

        if ($montant_saisi != 0) {
            // 1. Enregistrer le paiement
            $stmt_pay = $pdo->prepare("INSERT INTO paiements (id_ticket, montant, mode_paiement, date_paiement, id_utilisateur, reference) 
                                       VALUES (?, ?, ?, NOW(), ?, ?)");
            $ref = ($montant_saisi > 0) ? "ENC-" . $ticket['numero_ticket'] : "REM-" . $ticket['numero_ticket'];
            $stmt_pay->execute([$id_ticket, $montant_saisi, $mode, $id_user, $ref]);

            // 2. Mettre à jour le montant versé
            $nouveau_verse = $ticket['montant_verse'] + $montant_saisi;
            
            // 3. Déterminer le nouveau statut
            // Calcul du reste : Total - (Nouveau Versé + Remise)
            $reste = $ticket['montant_total'] - ($nouveau_verse + $ticket['montant_remise']);
            
            $nouveau_statut = $ticket['statut'];
            if ($reste <= 0) {
                // Si tout est payé :
                // Si tous les articles sont conditionnés -> pret
                // Sinon -> en_attente (car pas encore fini techniquement)
                $nouveau_statut = ($ticket['articles_restants'] == 0) ? 'pret' : 'en_attente';
            }

            $stmt_upd = $pdo->prepare("UPDATE tickets SET montant_verse = ?, statut = ? WHERE id_ticket = ?");
            $stmt_upd->execute([$nouveau_verse, $nouveau_statut, $id_ticket]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Transaction validée. Statut actuel : ' . ($nouveau_statut ?? $ticket['statut'])]);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// --- 2. LOGIQUE D'AFFICHAGE (GET) ---
$id_ticket = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_ticket <= 0) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT t.*, c.nom_client, c.prenom_client FROM tickets t JOIN clients c ON t.id_client = c.id_client WHERE t.id_ticket = ?");
$stmt->execute([$id_ticket]);
$ticket = $stmt->fetch();

if (!$ticket) { header('Location: index.php'); exit; }

// Calcul précis du reste à payer
$reste_a_payer = $ticket['montant_total'] - ($ticket['montant_verse'] + $ticket['montant_remise']);

include '../../templates/header.php';
include '../../templates/navigation.php';
?>

<style>
    .panel { border-radius: 15px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    .panel-primary > .panel-heading { background: #2563eb; padding: 20px; border-radius: 15px 15px 0 0; }
    .panel-warning > .panel-heading { background: #f59e0b; padding: 20px; border-radius: 15px 15px 0 0; }
    .well { border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; }
    .btn { border-radius: 10px; padding: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
    .form-control { border-radius: 8px; height: 50px; border: 2px solid #f1f5f9; }
    .form-control:focus { border-color: #2563eb; box-shadow: none; }
</style>

<div class="container" style="margin-top: 50px; margin-bottom: 50px;">
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div class="panel <?= ($reste_a_payer >= 0) ? 'panel-primary' : 'panel-warning' ?>">
                <div class="panel-heading">
                    <h3 class="panel-title text-center" style="font-weight: 900;">
                        <?= ($reste_a_payer >= 0) ? 'ENCAISSEMENT' : 'REMBOURSEMENT' ?> #<?= $ticket['numero_ticket'] ?>
                    </h3>
                </div>
                <div class="panel-body" style="padding: 30px;">
                    <div class="text-center">
                        <p class="text-muted" style="margin-bottom: 5px;">Client</p>
                        <h3 style="margin-top: 0; font-weight: 700; color: #1e293b;">
                            <?= strtoupper(htmlspecialchars($ticket['nom_client'].' '.$ticket['prenom_client'])) ?>
                        </h3>
                        <hr style="border-top: 2px dashed #e2e8f0;">
                        
                        <div class="row" style="margin-bottom: 10px;">
                            <div class="col-xs-6 text-left">Total Facture</div>
                            <div class="col-xs-6 text-right"><strong><?= number_format($ticket['montant_total'], 0) ?> F</strong></div>
                        </div>
                        <?php if($ticket['montant_remise'] > 0): ?>
                        <div class="row" style="margin-bottom: 10px;">
                            <div class="col-xs-6 text-left text-primary">Remise Accordée</div>
                            <div class="col-xs-6 text-right text-primary"><strong>- <?= number_format($ticket['montant_remise'], 0) ?> F</strong></div>
                        </div>
                        <?php endif; ?>
                        <div class="row" style="margin-bottom: 15px;">
                            <div class="col-xs-6 text-left">Somme déjà versée</div>
                            <div class="col-xs-6 text-right text-success"><strong><?= number_format($ticket['montant_verse'], 0) ?> F</strong></div>
                        </div>

                        <div class="well">
                            <p style="margin-bottom: 5px; font-weight: bold; color: #64748b;">
                                <?= ($reste_a_payer >= 0) ? 'SOLDE À PERCEVOIR' : 'TROP PERÇU À RENDRE' ?>
                            </p>
                            <h1 style="margin:0; font-weight: 900; letter-spacing: -2px;" class="<?= ($reste_a_payer >= 0) ? 'text-danger' : 'text-primary' ?>">
                                <?= number_format(abs($reste_a_payer), 0) ?> <small style="font-size: 18px; color: inherit; font-weight: 700;">FCFA</small>
                            </h1>
                        </div>
                    </div>

                    <form id="formEncaisser" style="margin-top: 25px;">
                        <input type="hidden" name="id_ticket" value="<?= $id_ticket ?>">
                        
                        <div class="form-group">
                            <label style="color: #475569;">Montant de la transaction</label>
                            <input type="number" name="montant_paiement" class="form-control input-lg" 
                                   value="<?= $reste_a_payer ?>" step="any" required>
                        </div>

                        <div class="form-group">
                            <label style="color: #475569;">Mode de règlement</label>
                            <select name="mode_paiement" class="form-control">
                                <option value="especes">Espèces</option>
                                <option value="mobile">Mobile Money</option>
                                <option value="carte">Carte Bancaire</option>
                                <option value="cheque">Chèque</option>
                            </select>
                        </div>

                        <div class="row" style="margin-top: 30px;">
                            <div class="col-xs-6">
                                <a href="index.php" class="btn btn-default btn-block" style="background: #f1f5f9; border: none; color: #475569;">Retour</a>
                            </div>
                            <div class="col-xs-6">
                                <button type="submit" class="btn btn-success btn-block" style="background: #10b981; border: none; box-shadow: 0 4px 10px rgba(16,185,129,0.3);">Confirmer</button>
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
    if(!confirm("Valider l'enregistrement de cette somme ?")) return;

    const btn = $(this).find('button[type="submit"]');
    btn.prop('disabled', true).text('Chargement...');

    $.post('encaisser.php', $(this).serialize(), function(res) {
        if(res.success) {
            alert(res.message);
            window.location.href = 'index.php';
        } else {
            alert("Erreur: " + res.message);
            btn.prop('disabled', false).text('Confirmer');
        }
    });
});
</script>

<?php include '../../templates/footer.php'; ?>