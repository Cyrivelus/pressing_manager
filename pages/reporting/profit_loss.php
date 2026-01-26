<?php
session_start();

// Check user authentication and authorization
if (!isset($_SESSION['utilisateur_id']) || !isset($_SESSION['role'])) {
    header('Location: ../../login.php');
    exit;
}

require_once '../../fonctions/database.php';

// Vérifier la connexion à la base de données
if (!isset($pdo) || !$pdo instanceof PDO) {
    // Tentative de reconnexion
    try {
        require_once '../../fonctions/database.php';
        if (!isset($pdo)) {
            throw new Exception("La connexion à la base de données n'est pas initialisée.");
        }
    } catch (Exception $e) {
        die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
    }
}

$titre = 'Compte de Résultat (Profit & Loss)';

$message = '';
$messageType = '';

$startDate = $_POST['start_date'] ?? date('Y-01-01'); // Default to start of current year
$endDate = $_POST['end_date'] ?? date('Y-12-31');     // Default to end of current year

$revenues = [];
$expenses = [];
$netProfitLoss = 0;
$currentTotalRevenues = 0;
$currentTotalExpenses = 0;

if (isset($_POST['generate_report'])) {
    // Validate dates
    if (!DateTime::createFromFormat('Y-m-d', $startDate) || !DateTime::createFromFormat('Y-m-d', $endDate)) {
        $message = "Veuillez saisir des dates valides (AAAA-MM-JJ).";
        $messageType = 'danger';
    } elseif ($startDate > $endDate) {
        $message = "La date de début ne peut pas être postérieure à la date de fin.";
        $messageType = 'danger';
    } else {
        try {
            // --- 1. Fetch Revenues (Produits) from tickets table ---
            // Using the tickets table structure from your document
            $stmtRevenues = $pdo->prepare("
                SELECT 
                    DATE(t.date_depot) as date_periode,
                    COUNT(*) as nb_tickets,
                    SUM(t.montant_total) as Total_Amount,
                    'Ventes pressing' as Nom_Compte
                FROM tickets t
                WHERE DATE(t.date_depot) BETWEEN :start_date AND :end_date
                AND t.statut != 'annule'
                GROUP BY DATE(t.date_depot)
                ORDER BY DATE(t.date_depot)
            ");
            $stmtRevenues->bindParam(':start_date', $startDate);
            $stmtRevenues->bindParam(':end_date', $endDate);
            $stmtRevenues->execute();
            $revenues = $stmtRevenues->fetchAll(PDO::FETCH_ASSOC);

            // --- 2. Fetch Expenses (Charges) from depenses table ---
            // Using the depenses table structure from your document
            $stmtExpenses = $pdo->prepare("
                SELECT 
                    d.categorie as Nom_Compte,
                    SUM(d.montant) as Total_Amount,
                    COUNT(*) as nb_depenses,
                    GROUP_CONCAT(DISTINCT d.description SEPARATOR ', ') as descriptions
                FROM depenses d
                WHERE DATE(d.date_depense) BETWEEN :start_date AND :end_date
                GROUP BY d.categorie
                ORDER BY d.categorie
            ");
            $stmtExpenses->bindParam(':start_date', $startDate);
            $stmtExpenses->bindParam(':end_date', $endDate);
            $stmtExpenses->execute();
            $expenses = $stmtExpenses->fetchAll(PDO::FETCH_ASSOC);

            // Calculate totals
            $totalRevenues = array_sum(array_column($revenues, 'Total_Amount'));
            $totalExpenses = array_sum(array_column($expenses, 'Total_Amount'));
            $netProfitLoss = $totalRevenues - $totalExpenses;
            
            $currentTotalRevenues = $totalRevenues;
            $currentTotalExpenses = $totalExpenses;

            // Log the activity if logging function exists
            if (function_exists('logUserActivity')) {
                logUserActivity("Génération du Compte de Résultat par l'utilisateur ID: " . $_SESSION['utilisateur_id'] . " pour la période du {$startDate} au {$endDate}.");
            }

        } catch (PDOException $e) {
            error_log("Erreur PDO lors de la génération du Compte de Résultat: " . $e->getMessage());
            $message = "Erreur lors de la récupération des données pour le rapport: " . htmlspecialchars($e->getMessage());
            $messageType = 'danger';
        } catch (Exception $e) {
            error_log("Erreur générale lors de la génération du Compte de Résultat: " . $e->getMessage());
            $message = "Une erreur inattendue est survenue: " . htmlspecialchars($e->getMessage());
            $messageType = 'danger';
        }
    }
}

// Now include the templates AFTER processing all PHP logic
require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BailCompta 360 | <?= htmlspecialchars($titre) ?></title>
    <link rel="stylesheet" href="../../css/bootstrap-3.4.1.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/tableau.css">
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <style>
        .report-section h4 { 
            border-bottom: 2px solid #2c3e50; 
            padding-bottom: 10px; 
            margin-top: 30px; 
            color: #2c3e50;
        }
        .report-total { 
            font-weight: bold; 
            font-size: 1.1em; 
            background-color: #f8f9fa;
        }
        .net-profit { color: #27ae60; }
        .net-loss { color: #e74c3c; }
        .container{
            width: 80%;
            min-height: 100vh;
            padding-top: 20px;
            margin-top: 60px;
        }
        .panel-heading {
            background-color: #2c3e50;
            color: white;
        }
        .period-info {
            background-color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        .stat-card.revenue {
            border-left-color: #27ae60;
        }
        .stat-card.expense {
            border-left-color: #e74c3c;
        }
        .stat-card.net {
            border-left-color: #9b59b6;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="page-header"><?= htmlspecialchars($titre) ?></h2>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Sélectionner la Période</h3>
            </div>
            <div class="panel-body">
                <form action="" method="POST" class="form-inline">
                    <div class="form-group mr-2">
                        <label for="start_date">Date de Début :</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?= htmlspecialchars($startDate) ?>" required>
                    </div>
                    <div class="form-group mr-2">
                        <label for="end_date">Date de Fin :</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?= htmlspecialchars($endDate) ?>" required>
                    </div>
                    <button type="submit" name="generate_report" class="btn btn-primary">
                        <span class="glyphicon glyphicon-calendar"></span> Générer le Rapport
                    </button>
                </form>
            </div>
        </div>

        <?php if (isset($_POST['generate_report']) && empty($message)): ?>
            <!-- Summary Statistics -->
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card revenue">
                        <h4>Total Revenus</h4>
                        <div class="stat-number text-success">
                            <?= number_format($currentTotalRevenues, 2, ',', ' ') ?> €
                        </div>
                        <small><?= count($revenues) ?> période(s)</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card expense">
                        <h4>Total Dépenses</h4>
                        <div class="stat-number text-danger">
                            <?= number_format($currentTotalExpenses, 2, ',', ' ') ?> €
                        </div>
                        <small><?= count($expenses) ?> catégorie(s)</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card net">
                        <h4>Résultat Net</h4>
                        <div class="stat-number <?= $netProfitLoss >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= number_format($netProfitLoss, 2, ',', ' ') ?> €
                        </div>
                        <small><?= $netProfitLoss >= 0 ? 'Bénéfice' : 'Perte' ?></small>
                    </div>
                </div>
            </div>

            <div class="period-info">
                <h4><i class="glyphicon glyphicon-calendar"></i> Période analysée</h4>
                <p>Du <?= htmlspecialchars(date('d/m/Y', strtotime($startDate))) ?> au <?= htmlspecialchars(date('d/m/Y', strtotime($endDate))) ?></p>
            </div>

            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title">Détails du Compte de Résultat</h3>
                </div>
                <div class="panel-body">
                    <div class="report-section">
                        <h4><i class="glyphicon glyphicon-euro"></i> Revenus (Ventes)</h4>
                        <?php if (empty($revenues)): ?>
                            <div class="alert alert-info">
                                <i class="glyphicon glyphicon-info-sign"></i> Aucun revenu enregistré pour cette période.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Période</th>
                                            <th>Nombre de tickets</th>
                                            <th class="text-right">Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($revenues as $revenue): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($revenue['date_periode']))) ?></td>
                                                <td><?= htmlspecialchars($revenue['nb_tickets']) ?></td>
                                                <td class="text-right">
                                                    <?= htmlspecialchars(number_format($revenue['Total_Amount'], 2, ',', ' ')) ?> €
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="report-total">
                                            <td colspan="2"><strong>Total Revenus</strong></td>
                                            <td class="text-right">
                                                <strong><?= htmlspecialchars(number_format($currentTotalRevenues, 2, ',', ' ')) ?> €</strong>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="report-section">
                        <h4><i class="glyphicon glyphicon-shopping-cart"></i> Dépenses (Charges)</h4>
                        <?php if (empty($expenses)): ?>
                            <div class="alert alert-info">
                                <i class="glyphicon glyphicon-info-sign"></i> Aucune dépense enregistrée pour cette période.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Catégorie</th>
                                            <th>Nombre de dépenses</th>
                                            <th>Descriptions</th>
                                            <th class="text-right">Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($expenses as $expense): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($expense['Nom_Compte']) ?></td>
                                                <td><?= htmlspecialchars($expense['nb_depenses']) ?></td>
                                                <td><?= htmlspecialchars($expense['descriptions']) ?></td>
                                                <td class="text-right">
                                                    <?= htmlspecialchars(number_format($expense['Total_Amount'], 2, ',', ' ')) ?> €
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="report-total">
                                            <td colspan="3"><strong>Total Dépenses</strong></td>
                                            <td class="text-right">
                                                <strong><?= htmlspecialchars(number_format($currentTotalExpenses, 2, ',', ' ')) ?> €</strong>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <hr>
                    <div class="row">
                        <div class="col-md-8"></div>
                        <div class="col-md-4">
                            <div class="well well-lg <?= $netProfitLoss >= 0 ? 'bg-success' : 'bg-danger' ?>" 
                                 style="color: white; text-align: center;">
                                <h3 style="margin: 0; font-weight: bold;">
                                    Résultat Net : 
                                    <?= htmlspecialchars(number_format($netProfitLoss, 2, ',', ' ')) ?> €
                                </h3>
                                <p style="margin: 5px 0 0 0;">
                                    <?= $netProfitLoss >= 0 ? 'Bénéfice' : 'Perte' ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export Button -->
            <div class="panel panel-default">
                <div class="panel-footer text-right">
                    <form action="../exports/export_reports.php" method="POST" style="display: inline;">
                        <input type="hidden" name="report_type" value="profit_loss">
                        <input type="hidden" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
                        <input type="hidden" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
                        <button type="submit" class="btn btn-success">
                            <span class="glyphicon glyphicon-download-alt"></span> Exporter en CSV
                        </button>
                    </form>
                    <button onclick="window.print()" class="btn btn-info">
                        <span class="glyphicon glyphicon-print"></span> Imprimer
                    </button>
                </div>
            </div>

        <?php elseif (isset($_POST['generate_report']) && !empty($message)): ?>
            <!-- Error state already handled above -->
        <?php else: ?>
            <div class="alert alert-info">
                <i class="glyphicon glyphicon-info-sign"></i> 
                Sélectionnez une période et cliquez sur "Générer le Rapport" pour afficher le Compte de Résultat.
            </div>
        <?php endif; ?>

    </div>
    
    <?php require_once '../../templates/footer.php'; ?>

    <script src="../../js/jquery-1.12.4.min.js"></script>
    <script src="../../js/bootstrap-3.4.1.min.js"></script>
    <script>
        // Auto-focus on start date field
        $(document).ready(function() {
            $('#start_date').focus();
            
            // Form validation
            $('form').submit(function() {
                var startDate = new Date($('#start_date').val());
                var endDate = new Date($('#end_date').val());
                
                if (startDate > endDate) {
                    alert('La date de début ne peut pas être postérieure à la date de fin.');
                    $('#start_date').focus();
                    return false;
                }
                
                // Show loading
                $('button[name="generate_report"]').html('<span class="glyphicon glyphicon-refresh glyphicon-spin"></span> Génération...');
                $('button[name="generate_report"]').prop('disabled', true);
                
                return true;
            });
        });
    </script>
</body>
</html>