<?php
// pages/Tickets/create.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../fonctions/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: /pressing_manager/pages/authentification.php');
    exit();
}

// Définir le titre de la page
$TITRE_PAGE = "Création de Ticket";

// Récupération des données pour le formulaire
$clients = $pdo->query("
    SELECT id_client, CONCAT(nom_client, ' ', COALESCE(prenom_client, '')) as nom_complet, telephone 
    FROM clients 
    WHERE est_actif = 1 
    ORDER BY nom_client
")->fetchAll();

$services = $pdo->query("
    SELECT id_service, nom_service, prix_unitaire 
    FROM services 
    WHERE est_disponible = 1 
    ORDER BY nom_service
")->fetchAll();

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

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($TITRE_PAGE) ?> | Pressing Manager</title>
    
    <!-- Select2 CSS local -->
    <link rel="stylesheet" href="../../css/select2.min.css">
    
    <style>
        /* Styles personnalisés */
        .panel-heading {
            background: #2c3e50 !important;
            color: white !important;
            border-bottom: none;
            padding: 12px 15px;
        }
        .panel-default > .panel-heading {
            background: #34495e !important;
        }
        .panel-title {
            font-weight: 600;
            font-size: 16px;
        }
        .select2-container--default .select2-selection {
            border: 1px solid #ced4da;
            border-radius: 4px;
            height: 38px;
            padding: 3px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 34px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .alert-custom {
            border-left: 5px solid #3498db;
            padding: 10px 15px;
            margin-bottom: 15px;
        }
        #grandTotal {
            color: #27ae60;
            font-weight: bold;
            margin: 0;
        }
        .article-row .select2-container {
            width: 100% !important;
        }
        .btn-remove {
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
        }
        .btn-remove:hover {
            background-color: #c0392b;
        }
        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
        }
        .table-success {
            background-color: #d4edda !important;
        }
        #messageContainer {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            width: 300px;
        }
        .alert {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .row-number {
            font-weight: bold;
            color: #2c3e50;
        }
        .sous-total, .prix-unitaire {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .btn-icon {
            margin-right: 5px;
        }
    </style>
</head>
<body>
<br><br><br>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <!-- Messages d'alerte -->
            <div id="messageContainer"></div>
            
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">📝 Création d'un nouveau ticket</h3>
                </div>
                <div class="panel-body">
                    <form id="ticketForm" method="POST">
                        <div class="row">
                            <!-- Colonne de gauche : Informations de dépôt -->
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-heading">📋 Informations de dépôt</div>
                                    <div class="panel-body">
                                        <div class="form-group">
                                            <label>Client *</label>
                                            <select name="id_client" 
                                                    class="form-control select2-client" 
                                                    id="clientSelect" 
                                                    required
                                                    data-placeholder="-- Sélectionnez un client --">
                                                <option value=""></option>
                                                <?php foreach($clients as $c): ?>
                                                    <option value="<?= htmlspecialchars($c['id_client']) ?>" 
                                                            data-telephone="<?= htmlspecialchars($c['telephone']) ?>">
                                                        <?= htmlspecialchars($c['nom_complet']) ?> - <?= htmlspecialchars($c['telephone']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="form-text text-muted" id="clientInfo"></small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Date de retrait prévue *</label>
                                            <input type="datetime-local" 
                                                   name="date_retrait_prevue" 
                                                   class="form-control" 
                                                   required 
                                                   value="<?= date('Y-m-d\TH:i', strtotime('+3 days')) ?>" 
                                                   min="<?= date('Y-m-d\TH:i') ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Délai estimé</label>
                                            <div class="alert alert-info" id="delaiEstime">72 heures</div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Remise spéciale (%)</label>
                                            <input type="number" 
                                                   name="remise_pourcentage" 
                                                   class="form-control" 
                                                   id="remisePourcentage" 
                                                   min="0" 
                                                   max="100" 
                                                   value="0" 
                                                   step="0.5"
                                                   oninput="calculateTotals()">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Notes</label>
                                            <textarea name="notes_client" 
                                                      class="form-control" 
                                                      rows="3" 
                                                      placeholder="Notes optionnelles..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Colonne de droite : Articles et paiement -->
                            <div class="col-md-8">
                                <!-- Section Articles -->
                                <div class="panel panel-default">
                                    <div class="panel-heading">🛒 Articles</div>
                                    <div class="panel-body">
                                        <table class="table table-bordered" id="tableArticles">
                                            <thead>
                                                <tr>
                                                    <th width="40">#</th>
                                                    <th>Service</th>
                                                    <th width="120">Quantité</th>
                                                    <th width="150">Prix unitaire</th>
                                                    <th width="150">Sous-total</th>
                                                    <th width="60">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="articleRows">
                                                <!-- Première ligne par défaut -->
                                                <tr class="article-row" id="row-template">
                                                    <td class="text-center row-number">1</td>
                                                    <td>
                                                        <select name="services[]" 
                                                                class="form-control select2-service service-select" 
                                                                required 
                                                                data-placeholder="-- Choisir un service --"
                                                                onchange="updateRow(this)">
                                                            <option value=""></option>
                                                            <?php foreach($services as $s): ?>
                                                                <option value="<?= htmlspecialchars($s['id_service']) ?>" 
                                                                        data-prix="<?= htmlspecialchars($s['prix_unitaire']) ?>">
                                                                    <?= htmlspecialchars($s['nom_service']) ?> - <?= number_format($s['prix_unitaire'], 0, ',', ' ') ?> FCFA
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="number" 
                                                               name="qtes[]" 
                                                               class="form-control qte-input" 
                                                               value="1" 
                                                               min="0.5" 
                                                               step="0.5" 
                                                               oninput="updateRow(this)">
                                                    </td>
                                                    <td>
                                                        <input type="text" 
                                                               class="form-control prix-unitaire text-right" 
                                                               readonly 
                                                               value="0">
                                                    </td>
                                                    <td>
                                                        <input type="text" 
                                                               class="form-control sous-total text-right" 
                                                               readonly 
                                                               value="0">
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" 
                                                                class="btn-remove" 
                                                                onclick="removeRow(this)"
                                                                title="Supprimer cette ligne">
                                                            ✕
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="3" class="text-right">
                                                        <strong>SOUS-TOTAL :</strong>
                                                    </td>
                                                    <td colspan="2" id="subtotalDisplay">0 FCFA</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="3" class="text-right">
                                                        <strong>REMISE :</strong>
                                                    </td>
                                                    <td colspan="2" id="discountDisplay">0 FCFA (0%)</td>
                                                </tr>
                                                <tr class="table-success">
                                                    <td colspan="3" class="text-right">
                                                        <strong>TOTAL À PAYER :</strong>
                                                    </td>
                                                    <td colspan="2">
                                                        <h4 id="grandTotal">0 FCFA</h4>
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                        
                                        <div class="text-center">
                                            <button type="button" 
                                                    class="btn btn-primary" 
                                                    id="addRowBtn">
                                                ➕ Ajouter un article
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section Paiement -->
                                <div class="panel panel-default mt-3">
                                    <div class="panel-heading">💰 Paiement</div>
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Mode de paiement *</label>
                                                <select name="mode_paiement" 
                                                        class="form-control" 
                                                        required>
                                                    <option value="especes">💵 Espèces</option>
                                                    <option value="mobile">📱 Mobile Money</option>
                                                    <option value="carte">💳 Carte Bancaire</option>
                                                    <option value="cheque">📝 Chèque</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Montant versé *</label>
                                                <div class="input-group">
                                                    <input type="number" 
                                                           name="montant_verse" 
                                                           id="montantVerse" 
                                                           class="form-control" 
                                                           required
                                                           min="0"
                                                           oninput="updateSolde()">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">FCFA</span>
                                                    </div>
                                                </div>
                                                <small class="form-text text-muted">
                                                    <span id="resteAPayer">Reste à payer : 0 FCFA</span>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Boutons d'action -->
                                <div class="text-right mt-4">
                                    <?php if ($limiteAtteinte): ?>
                                        <div class="alert alert-danger">
                                            ⚠️ Quota mensuel atteint (200 tickets maximum)
                                        </div>
                                        <button type="button" class="btn btn-secondary btn-lg" disabled>
                                            🚫 Création bloquée
                                        </button>
                                    <?php else: ?>
                                        <button type="button" 
                                                class="btn btn-secondary btn-lg mr-2" 
                                                onclick="resetForm()">
                                            🔄 Annuler
                                        </button>
                                        <button type="submit" 
                                                class="btn btn-success btn-lg" 
                                                id="submitBtn">
                                            💾 Enregistrer le Ticket
                                        </button>
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

<!-- jQuery local -->
<script src="../../js/jquery.min.js"></script>
<!-- Bootstrap JS local -->
<script src="../../js/bootstrap.min.js"></script>
<!-- Select2 JS local -->
<script src="../../js/select2.min.js"></script>

<script>
// Variables globales
let rowCounter = 1;

// Initialisation Select2
$(document).ready(function() {
    // Initialiser Select2 pour les clients
    $('.select2-client').select2({
        placeholder: "-- Sélectionnez un client --",
        allowClear: true,
        width: '100%'
    });

    // Initialiser Select2 pour les services
    $('.select2-service').select2({
        placeholder: "-- Choisir un service --",
        allowClear: false,
        width: '100%'
    });

    // Calcul initial
    calculateTotals();
});

// Formatage des nombres
function formatNumber(num) {
    return new Intl.NumberFormat('fr-FR').format(num);
}

// Mettre à jour une ligne
function updateRow(element) {
    const row = $(element).closest('tr');
    const selectedOption = row.find('.service-select option:selected');
    const prixUnitaire = parseFloat(selectedOption.data('prix')) || 0;
    const quantite = parseFloat(row.find('.qte-input').val()) || 0;
    const sousTotal = prixUnitaire * quantite;
    
    row.find('.prix-unitaire').val(formatNumber(prixUnitaire));
    row.find('.sous-total').val(formatNumber(sousTotal));
    
    calculateTotals();
}

// Calculer les totaux
function calculateTotals() {
    let sousTotal = 0;
    
    $('.article-row').each(function() {
        const sousTotalText = $(this).find('.sous-total').val().replace(/\s/g, '');
        const valeur = parseFloat(sousTotalText) || 0;
        sousTotal += valeur;
    });
    
    // Calculer la remise
    const remisePourcentage = parseFloat($('#remisePourcentage').val()) || 0;
    const montantRemise = sousTotal * (remisePourcentage / 100);
    const totalFinal = sousTotal - montantRemise;
    
    // Mettre à jour l'affichage
    $('#subtotalDisplay').text(formatNumber(sousTotal) + ' FCFA');
    $('#discountDisplay').text(formatNumber(montantRemise) + ' FCFA (' + remisePourcentage + '%)');
    $('#grandTotal').text(formatNumber(totalFinal) + ' FCFA');
    
    // Mettre à jour le solde
    updateSolde();
}

// Mettre à jour le solde
function updateSolde() {
    const totalText = $('#grandTotal').text().replace(/\s/g, '').replace('FCFA', '');
    const totalFinal = parseFloat(totalText) || 0;
    const montantVerse = parseFloat($('#montantVerse').val()) || 0;
    const resteAPayer = totalFinal - montantVerse;
    
    $('#resteAPayer').text('Reste à payer : ' + formatNumber(resteAPayer) + ' FCFA');
    
    // Changer la couleur selon le solde
    if (resteAPayer > 0) {
        $('#resteAPayer').css('color', '#e74c3c');
    } else if (resteAPayer < 0) {
        $('#resteAPayer').css('color', '#f39c12');
    } else {
        $('#resteAPayer').css('color', '#27ae60');
    }
}

// Ajouter une nouvelle ligne
$('#addRowBtn').click(function() {
    rowCounter++;
    
    const newRow = $('#row-template').clone();
    newRow.attr('id', 'row-' + rowCounter);
    newRow.find('.row-number').text(rowCounter);
    
    // Réinitialiser les valeurs
    newRow.find('.service-select').val('');
    newRow.find('.qte-input').val('1');
    newRow.find('.prix-unitaire').val('0');
    newRow.find('.sous-total').val('0');
    
    // Ajouter la ligne
    $('#articleRows').append(newRow);
    
    // Réinitialiser Select2 pour la nouvelle ligne
    newRow.find('.select2-service').select2({
        placeholder: '-- Choisir un service --',
        allowClear: false,
        width: '100%'
    });
    
    // Mettre à jour les événements
    newRow.find('.service-select').on('change', function() {
        updateRow(this);
    });
    
    newRow.find('.qte-input').on('input', function() {
        updateRow(this);
    });
    
    // Calculer les totaux
    calculateTotals();
});

// Supprimer une ligne
function removeRow(button) {
    if ($('.article-row').length > 1) {
        $(button).closest('tr').remove();
        renumeroterLignes();
        calculateTotals();
    } else {
        showMessage('Vous devez avoir au moins un article.', 'warning');
    }
}

// Renumérotation des lignes
function renumeroterLignes() {
    $('.article-row').each(function(index) {
        $(this).find('.row-number').text(index + 1);
    });
    rowCounter = $('.article-row').length;
}

// Réinitialiser le formulaire
function resetForm() {
    if (confirm('Voulez-vous vraiment réinitialiser le formulaire ? Toutes les données seront perdues.')) {
        // Conserver uniquement la première ligne
        $('.article-row:not(#row-template)').remove();
        
        // Réinitialiser la première ligne
        $('#row-template .service-select').val('');
        $('#row-template .qte-input').val('1');
        $('#row-template .prix-unitaire').val('0');
        $('#row-template .sous-total').val('0');
        $('#row-template .select2-service').val('').trigger('change');
        
        // Réinitialiser les autres champs
        $('.select2-client').val('').trigger('change');
        $('#remisePourcentage').val('0');
        $('#montantVerse').val('0');
        $('textarea[name="notes_client"]').val('');
        
        // Réinitialiser la date
        const dateRetrait = new Date();
        dateRetrait.setDate(dateRetrait.getDate() + 3);
        $('input[name="date_retrait_prevue"]').val(dateRetrait.toISOString().slice(0, 16));
        
        // Renumérotation
        renumeroterLignes();
        calculateTotals();
        
        // Message
        showMessage('Formulaire réinitialisé avec succès.', 'success');
    }
}

// Afficher un message
function showMessage(message, type = 'info') {
    const container = $('#messageContainer');
    const alertClass = type === 'success' ? 'alert-success' : 
                       type === 'error' ? 'alert-danger' : 
                       type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const icon = type === 'success' ? '✅' : 
                 type === 'error' ? '❌' : 
                 type === 'warning' ? '⚠️' : 'ℹ️';
    
    const alert = $('<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                   '<strong>' + icon + ' </strong> ' + message +
                   '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                   '<span aria-hidden="true">&times;</span>' +
                   '</button>' +
                   '</div>');
    
    container.html(alert);
    
    // Auto-dismiss après 5 secondes
    setTimeout(() => {
        alert.alert('close');
    }, 5000);
}

// Soumission du formulaire
$('#ticketForm').submit(function(e) {
    e.preventDefault();
    
    // Validation
    if (!$('.select2-client').val()) {
        showMessage('Veuillez sélectionner un client.', 'error');
        return;
    }
    
    // Vérifier qu'au moins un article est rempli
    let articlesValides = false;
    $('.article-row').each(function() {
        if ($(this).find('.service-select').val()) {
            articlesValides = true;
        }
    });
    
    if (!articlesValides) {
        showMessage('Veuillez ajouter au moins un article.', 'error');
        return;
    }
    
    // Vérifier les montants
    const montantVerse = parseFloat($('#montantVerse').val()) || 0;
    if (montantVerse < 0) {
        showMessage('Le montant versé ne peut pas être négatif.', 'error');
        return;
    }
    
    // Désactiver le bouton de soumission
    const submitBtn = $('#submitBtn');
    const originalText = submitBtn.html();
    submitBtn.prop('disabled', true).html('⏳ Enregistrement...');
    
    // Préparer les données
    const formData = new FormData(this);
    
    // Envoyer la requête AJAX
    $.ajax({
        url: 'save_ticket.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showMessage(response.message, 'success');
                
                // Redirection après 2 secondes
                setTimeout(() => {
                    window.location.href = response.redirect;
                }, 2000);
            } else {
                showMessage(response.message, 'error');
                submitBtn.prop('disabled', false).html(originalText);
            }
        },
        error: function(xhr, status, error) {
            showMessage('Erreur de connexion au serveur. Veuillez réessayer.', 'error');
            submitBtn.prop('disabled', false).html(originalText);
        }
    });
});

// Gestion de la date de retrait
$('input[name="date_retrait_prevue"]').on('change', function() {
    const dateSelectionnee = new Date($(this).val());
    const maintenant = new Date();
    const differenceHeures = Math.round((dateSelectionnee - maintenant) / (1000 * 60 * 60));
    
    let delaiTexte = '';
    if (differenceHeures < 24) {
        delaiTexte = `${differenceHeures} heures`;
    } else {
        delaiTexte = `${Math.round(differenceHeures / 24)} jours`;
    }
    
    $('#delaiEstime').text(delaiTexte);
});

// Chargement des informations client
$('.select2-client').on('change', function() {
    const selectedOption = $(this).find('option:selected');
    const telephone = selectedOption.data('telephone');
    if (telephone) {
        $('#clientInfo').text('Téléphone : ' + telephone);
    } else {
        $('#clientInfo').text('');
    }
});

// Validation des quantités
$(document).on('input', '.qte-input', function() {
    const valeur = parseFloat($(this).val());
    if (valeur < 0.5) {
        $(this).val(0.5);
        showMessage('La quantité minimum est 0.5', 'warning');
    }
});
</script>

<?php include '../../templates/footer.php'; ?>