<?php
// pages/tickets/create.php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once '../../fonctions/database.php';

// CORRECTION : Import de la classe de calcul
// Assurez-vous que le chemin est correct par rapport à l'emplacement de create.php
$cheminCalcul = '../../fonctions/tarifs/calcul_tarifs.php';
if (file_exists($cheminCalcul)) {
    require_once $cheminCalcul;
    // On utilise $pdo car c'est la variable définie dans database.php
    $calculTarifs = new CalculTarifs($pdo); 
} else {
    die("Erreur : Le fichier de calcul des tarifs est introuvable à l'adresse : $cheminCalcul");
}

// Récupération des données pour le formulaire
$clients = $pdo->query("SELECT id_client, CONCAT(nom_client, ' ', COALESCE(prenom_client, '')) as nom_complet, telephone FROM clients WHERE est_actif = 1 ORDER BY nom_client")->fetchAll();
$services = $pdo->query("SELECT id_service, nom_service, prix_unitaire FROM services WHERE est_disponible = 1 ORDER BY nom_service")->fetchAll();

// Gestion du Quota
$debutMois = date('Y-m-01 00:00:00');
$finMois = date('Y-m-t 23:59:59');
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE date_depot BETWEEN ? AND ?");
$stmtCount->execute([$debutMois, $finMois]);
$totalTicketsMois = $stmtCount->fetchColumn();
$limiteAtteinte = ($totalTicketsMois >= 200);

include '../../templates/header.php';
include '../../templates/navigation.php';
?>
<br> <br> <br>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-primary">
                <div class="panel-heading" style="background:#2c3e50; color:white;">
                    <h3 class="panel-title"><i class="fas fa-ticket-alt"></i> Création d'un nouveau ticket</h3>
                </div>
                <div class="panel-body">
                    <form action="save_ticket.php" method="POST" id="ticketForm">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-heading" style="background:#34495e; color:white;">Informations de dépôt</div>
                                    <div class="panel-body">
                                        <div class="form-group">
                                            <label>Client *</label>
                                            <select name="id_client" class="form-control select2" id="clientSelect" required>
                                                <option value="">-- Sélectionner un client --</option>
                                                <?php foreach($clients as $c): ?>
                                                    <option value="<?= $c['id_client'] ?>" data-telephone="<?= $c['telephone'] ?>">
                                                        <?= htmlspecialchars($c['nom_complet']) ?> - <?= $c['telephone'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Date de retrait prévue *</label>
                                            <input type="datetime-local" name="date_retrait_prevue" class="form-control" required 
                                                   value="<?= date('Y-m-d\TH:i', strtotime('+3 days')) ?>" min="<?= date('Y-m-d\TH:i') ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Délai estimé</label>
                                            <div class="alert alert-info" id="delaiEstime">72 heures</div>
                                        </div>
                                        <div class="form-group">
                                            <label>Remise spéciale (%)</label>
                                            <input type="number" name="remise_pourcentage" class="form-control" id="remisePourcentage" min="0" max="100" value="0" step="0.5">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="panel panel-default">
                                    <div class="panel-heading" style="background:#34495e; color:white;">Articles</div>
                                    <div class="panel-body">
                                        <table class="table table-bordered" id="tableArticles">
                                            <thead>
                                                <tr>
                                                    <th width="30">#</th>
                                                    <th>Service</th>
                                                    <th width="100">Qté</th>
                                                    <th width="150">Sous-total</th>
                                                    <th width="50"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="articleRows">
                                                <tr class="article-row">
                                                    <td class="text-center">1</td>
                                                    <td>
                                                        <select name="services[]" class="form-control service-select" required onchange="updateRow(this)">
                                                            <option value="" data-prix="0">-- Choisir --</option>
                                                            <?php foreach($services as $s): ?>
                                                                <option value="<?= $s['id_service'] ?>" data-prix="<?= $s['prix_unitaire'] ?>">
                                                                    <?= htmlspecialchars($s['nom_service']) ?> (<?= $s['prix_unitaire'] ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="qtes[]" class="form-control qte-input" value="1" min="1" step="0.5" oninput="updateRow(this)">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control stotal text-right" readonly>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">X</button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="3" class="text-right"><strong>TOTAL À PAYER :</strong></td>
                                                    <td colspan="2"><h3 id="grandTotal" style="margin:0">0 FCFA</h3></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                        <button type="button" class="btn btn-info" id="addRow">Ajouter un article</button>
                                    </div>
                                </div>

                                <div class="panel panel-default mt-3">
                                    <div class="panel-heading" style="background:#34495e; color:white;">Paiement</div>
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Mode de paiement *</label>
                                                <select name="mode_paiement" class="form-control" required>
                                                    <option value="especes">Espèces</option>
                                                    <option value="mobile">Mobile Money</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Montant versé *</label>
                                                <input type="number" name="montant_verse" id="montantVerse" class="form-control" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right mt-3">
                                    <?php if ($limiteAtteinte): ?>
                                        <div class="alert alert-danger">Quota de 200 tickets atteint !</div>
                                        <button type="button" class="btn btn-secondary btn-lg" disabled>Bloqué</button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-success btn-lg" id="submitBtn">Enregistrer le Ticket</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script>
// Logique JS pour calcul dynamique
function formatNumber(num) { return new Intl.NumberFormat('fr-FR').format(num); }

function updateRow(el) {
    const row = $(el).closest('tr');
    const prix = parseFloat(row.find('.service-select option:selected').data('prix')) || 0;
    const qte = parseFloat(row.find('.qte-input').val()) || 0;
    row.find('.stotal').val(formatNumber(prix * qte));
    calculateTotals();
}

function calculateTotals() {
    let total = 0;
    $('.article-row').each(function() {
        const prix = parseFloat($(this).find('.service-select option:selected').data('prix')) || 0;
        const qte = parseFloat($(this).find('.qte-input').val()) || 0;
        total += (prix * qte);
    });
    const remise = parseFloat($('#remisePourcentage').val()) || 0;
    const final = total - (total * (remise / 100));
    $('#grandTotal').text(formatNumber(final) + ' FCFA');
}

$('#addRow').click(function() {
    const clone = $('.article-row:first').clone();
    clone.find('input').val(1);
    clone.find('.stotal').val('');
    clone.find('select').val('');
    $('#articleRows').append(clone);
});

function removeRow(btn) {
    if ($('.article-row').length > 1) $(btn).closest('tr').remove();
    calculateTotals();
}

$('#remisePourcentage').on('input', calculateTotals);
</script>

<?php include '../../templates/footer.php'; ?>