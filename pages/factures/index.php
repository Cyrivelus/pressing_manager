<?php
// pages/ecritures/index.php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Configuration et inclusions
$titre = 'Gestion des factures fournisseurs';
$current_page = basename($_SERVER['PHP_SELF']);

// Pour éviter l'erreur d'en-têtes, démarrer la temporisation de sortie
ob_start();

require_once '../../fonctions/database.php';
require_once '../../fonctions/gestion_utilisateurs.php';
require_once '../../fonctions/gestion_ecritures.php';

// Vérifier la connexion à la base de données
if (!isset($pdo)) {
    $errorMessage = "Erreur de connexion à la base de données";
    $factures = [];
} else {
    // Récupérer les factures fournisseurs avec une jointure
    $sql = "SELECT 
                f.ID_Facture,
                f.Date_Emission,
                f.Commentaire,
                f.Montant_TTC,
                f.Numero_Bon_Commande,
                f.ID_Journal,
                fr.nom_fournisseur,
                f.id_fournisseur
            FROM 
                Factures f
            LEFT JOIN 
                fournisseurs fr ON f.id_fournisseur = fr.id_fournisseur
            ORDER BY 
                f.Date_Emission DESC
            LIMIT 10";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $factures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errorMessage = "Erreur lors de la récupération des factures: " . $e->getMessage();
        $factures = [];
    }
}

// Gestion des messages
$successMessage = null;
if (isset($_GET['success_facture'])) {
    $successMessage = "La facture a été enregistrée avec succès.";
} elseif (isset($_GET['update_success'])) {
    $successMessage = "La facture a été mise à jour avec succès.";
} elseif (isset($_GET['delete_success'])) {
    $successMessage = "La facture a été supprimée avec succès.";
}

// Nettoyer la temporisation avant d'inclure header.php
ob_clean();

// Inclure les templates
require_once('../../templates/header.php');
require_once('../../templates/navigation.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($titre) ?> | Pressing Manager Pro</title>
    <link rel="shortcut icon" href="../../assets/images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-theme/0.1.0-beta.10/select2-bootstrap.min.css" rel="stylesheet" />
    <style>
        .select2-container--bootstrap .select2-selection--single {
            height: 34px;
            padding: 6px 12px;
        }

        .select2-container--bootstrap .select2-selection--single .select2-selection__rendered {
            line-height: 20px;
        }

        .select2-container--bootstrap .select2-selection--single .select2-selection__arrow {
            height: 32px;
        }

        .select2-container {
            width: 100% !important;
        }

        .panel {
            margin-bottom: 20px;
        }

        .page-header {
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }

        .btn-actions {
            margin-top: 20px;
        }

        .total-field-display {
            font-weight: bold;
            background-color: #eee;
        }

        .form-control[readonly] {
            background-color: #eee;
            opacity: 1;
            cursor: not-allowed;
        }

        .table th {
            background-color: #2c3e50;
            color: white;
        }

        .table-hover tbody tr:hover {
            background-color: #f5f5f5;
        }

        .badge-statut {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-en-attente { background-color: #f39c12; color: white; }
        .badge-payee { background-color: #27ae60; color: white; }
        .badge-impayee { background-color: #e74c3c; color: white; }
        .badge-partiel { background-color: #3498db; color: white; }
    </style>
</head>
<body>
<div class="container">
    <h2 class="page-header"><?= htmlspecialchars($titre) ?></h2>

    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?= $successMessage ?>
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?= $errorMessage ?>
        </div>
    <?php endif; ?>

    <div class="action-buttons" style="margin-bottom: 20px;">
        <a href="integration.php" class="btn btn-primary">
            <span class="glyphicon glyphicon-plus"></span> Nouvelle facture fournisseur
        </a>
        <a href="listes_factures.php" class="btn btn-info">
            <span class="glyphicon glyphicon-list"></span> Liste complète
        </a>
        <a href="../../views/rapports/factures.php" class="btn btn-success">
            <span class="glyphicon glyphicon-stats"></span> Rapports
        </a>
    </div>

    <div class="well" style="background-color: #f8f9fa; border: 1px solid #ddd; padding: 15px; margin-bottom: 20px;">
        <h4 style="margin-top: 0;">Recherche et filtres</h4>
        <form method="GET" action="listes_factures.php" class="form-inline">
            <div class="form-group" style="margin-right: 10px;">
                <input type="text" class="form-control" name="search" placeholder="N° facture ou fournisseur" style="width: 200px;">
            </div>
            <div class="form-group" style="margin-right: 10px;">
                <select class="form-control" name="fournisseur" style="width: 180px;">
                    <option value="">Tous les fournisseurs</option>
                    <?php
                    // Récupérer la liste des fournisseurs
                    $sql_fournisseurs = "SELECT id_fournisseur, nom_fournisseur FROM fournisseurs WHERE est_actif = 1 ORDER BY nom_fournisseur";
                    if (isset($pdo)) {
                        $stmt_fourn = $pdo->query($sql_fournisseurs);
                        while ($fourn = $stmt_fourn->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='{$fourn['id_fournisseur']}'>{$fourn['nom_fournisseur']}</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="form-group" style="margin-right: 10px;">
                <select class="form-control" name="statut" style="width: 150px;">
                    <option value="">Tous les statuts</option>
                    <option value="payee">Payée</option>
                    <option value="impayee">Impayée</option>
                    <option value="partiel">Partiellement payée</option>
                </select>
            </div>
            <button type="submit" class="btn btn-default">
                <span class="glyphicon glyphicon-search"></span> Rechercher
            </button>
        </form>
    </div>

    <h3>10 dernières factures fournisseurs</h3>
    
    <?php if (!empty($factures)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>N° Facture</th>
                        <th>Date</th>
                        <th>Fournisseur</th>
                        <th>N° Commande</th>
                        <th>Journal</th>
                        <th>Commentaire</th>
                        <th class="text-right">Montant TTC</th>
                        <th style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($factures as $facture): 
                        // Déterminer le statut de paiement (à adapter selon votre logique)
                        $statut = 'en_attente'; // Par défaut
                        $badge_class = 'badge-en-attente';
                        $statut_text = 'En attente';
                        
                        // Ici, vous devriez vérifier le statut réel de la facture
                        // Exemple: vérifier si elle est payée via une table paiements_factures
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($facture['ID_Facture']) ?></td>
                            <td><?= date('d/m/Y', strtotime($facture['Date_Emission'])) ?></td>
                            <td><?= htmlspecialchars($facture['nom_fournisseur'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($facture['Numero_Bon_Commande'] ?? '') ?></td>
                            <td><?= htmlspecialchars($facture['ID_Journal'] ?? '') ?></td>
                            <td><?= htmlspecialchars(substr($facture['Commentaire'] ?? '', 0, 50)) . (strlen($facture['Commentaire'] ?? '') > 50 ? '...' : '') ?></td>
                            <td class="text-right" style="font-weight: bold;">
                                <?= number_format($facture['Montant_TTC'], 2, ',', ' ') ?> €
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="detail_facture.php?id=<?= $facture['ID_Facture'] ?>" class="btn btn-info" title="Détails">
                                        <span class="glyphicon glyphicon-eye-open"></span>
                                    </a>
                                    <a href="modifier_facture.php?id=<?= $facture['ID_Facture'] ?>" class="btn btn-warning" title="Modifier">
                                        <span class="glyphicon glyphicon-pencil"></span>
                                    </a>
                                    <?php if ($_SESSION['role'] == 'patron' || $_SESSION['role'] == 'gestionnaire_stock'): ?>
                                    <a href="supprimer_facture.php?id=<?= $facture['ID_Facture'] ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer la facture N° <?= $facture['ID_Facture'] ?> ?')"
                                       title="Supprimer">
                                        <span class="glyphicon glyphicon-trash"></span>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-right" style="font-weight: bold;">Total des 10 dernières factures :</td>
                        <td class="text-right" style="font-weight: bold; color: #e74c3c;">
                            <?php 
                                $total = array_sum(array_column($factures, 'Montant_TTC'));
                                echo number_format($total, 2, ',', ' ') . ' €';
                            ?>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Statistiques rapides</h3>
                    </div>
                    <div class="panel-body">
                        <p>Nombre de factures : <strong><?= count($factures) ?></strong></p>
                        <p>Montant total : <strong><?= number_format($total, 2, ',', ' ') ?> €</strong></p>
                        <p>Moyenne par facture : <strong>
                            <?= count($factures) > 0 ? number_format($total/count($factures), 2, ',', ' ') : '0,00' ?> €
                        </strong></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Actions rapides</h3>
                    </div>
                    <div class="panel-body">
                        <a href="integration.php?type=produit" class="btn btn-default btn-block" style="margin-bottom: 5px;">
                            Facture produits nettoyage
                        </a>
                        <a href="integration.php?type=equipement" class="btn btn-default btn-block" style="margin-bottom: 5px;">
                            Facture équipement
                        </a>
                        <a href="integration.php?type=emballage" class="btn btn-default btn-block">
                            Facture emballage
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <h4>Aucune facture fournisseur trouvée</h4>
            <p>Vous pouvez créer votre première facture en cliquant sur le bouton "Nouvelle facture fournisseur".</p>
            <p>Les factures permettent de gérer les achats de produits de nettoyage, équipements et autres fournitures.</p>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/i18n/fr.js"></script>
<script>
$(document).ready(function() {
    // Initialiser Select2
    $('select').select2({
        theme: "bootstrap",
        language: "fr"
    });
    
    // Gestion de la suppression
    $('.btn-danger').on('click', function(e) {
        if (!confirm('Cette action est irréversible. Confirmer la suppression ?')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Auto-refresh toutes les 30 secondes pour les nouvelles factures
    setTimeout(function() {
        location.reload();
    }, 30000);
});
</script>

<?php
// Finir la temporisation et afficher
ob_end_flush();
require_once('../../templates/footer.php');
?>