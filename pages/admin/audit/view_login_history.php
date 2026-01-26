<?php
// Vérifier si les headers ont déjà été envoyés
if (headers_sent()) {
    die("Erreur: Les en-têtes HTTP ont déjà été envoyés. Vérifiez l'espace blanc dans vos fichiers.");
}

session_start();
// Check user authentication and authorization (only admins should view login history)
if (!isset($_SESSION['utilisateur_id']) || ($_SESSION['role'] !== 'patron' && $_SESSION['role'] !== 'super_admin')) {
    header('Location: ../../login.php'); // Redirect to login or unauthorized page
    exit;
}

// Nettoyer le buffer de sortie si nécessaire
if (ob_get_length()) {
    ob_end_clean();
}

// Définir le titre AVANT d'inclure les templates
$titre = 'Historique des Connexions';

// Inclure les fonctions
require_once '../../../fonctions/database.php';
require_once '../../../fonctions/gestion_logs.php'; // Include the logging functions (where recordLoginAttempt resides)

// --- Pagination and Filtering ---
$attemptsPerPage = 50;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $attemptsPerPage;

$filterUserId = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int)$_GET['user_id'] : null;
$filterStatus = $_GET['status'] ?? null; // 'success', 'failed', 'all'
$filterIsSuccessful = null;
if ($filterStatus === 'success') {
    $filterIsSuccessful = true;
} elseif ($filterStatus === 'failed') {
    $filterIsSuccessful = false;
}

$filterStartDate = $_GET['start_date'] ?? null;
$filterEndDate = $_GET['end_date'] ?? null;

// Ensure date formats are correct for DB
if ($filterStartDate && !DateTime::createFromFormat('Y-m-d', $filterStartDate)) {
    $filterStartDate = null;
}
if ($filterEndDate && !DateTime::createFromFormat('Y-m-d', $filterEndDate)) {
    $filterEndDate = null;
}

// Fetch total count for pagination
$totalAttempts = countLoginHistory($pdo, $filterUserId, $filterIsSuccessful, $filterStartDate, $filterEndDate);
$totalPages = ceil($totalAttempts / $attemptsPerPage);

// Fetch login attempts with filters and pagination
$loginHistory = getLoginHistory(
    $pdo,
    $filterUserId,
    $filterIsSuccessful,
    $filterStartDate,
    $filterEndDate,
    $attemptsPerPage,
    $offset
);

// Get list of users for filter dropdown
$users = [];
try {
    $stmtUsers = $pdo->query("SELECT ID_Utilisateur, Nom FROM Utilisateurs ORDER BY Nom ASC");
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la récupération des utilisateurs pour le filtre (historique de connexion): " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BailCompta 360 | <?= htmlspecialchars($titre) ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/tableau.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* ------------------------------------------------ */
        /* 💡 STYLES CORRIGÉS POUR LA RÉACTIVITÉ (EN %) */
        /* ------------------------------------------------ */
        
        :root {
            --sidebar-width: 20%;
        }

        /* Conteneur principal du contenu, décalé vers la droite */
        .main-content-wrapper {
            margin-left: var(--sidebar-width); 
            width: calc(100% - var(--sidebar-width)); 
            min-height: 100vh;
            padding: 2%; /* Rembourrage en % */
            box-sizing: border-box; 
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        /* Conteneur pour le contenu du formulaire */
        .page-container {
            width: 98%; /* Légèrement moins que 100% pour la marge intérieure */
            margin: 0 auto; 
            padding: 1% 0; /* Marge interne en % */
        }
        
        .page-header { margin-bottom: 2%; }
        .alert { margin-bottom: 2%; }

        .login-history-table th, .login-history-table td {
            vertical-align: middle;
            font-size: 0.9em;
            padding: 1%; /* Padding en % */
        }
        .status-success { color: #5cb85c; font-weight: bold; } /* Green */
        .status-failed { color: #d9534f; font-weight: bold; } /* Red */
        
        /* Ajustement de la colonne User Agent pour qu'elle ne déforme pas le tableau */
        .user-agent-col { 
            max-width: 15%; /* Largeur max en % */
            overflow: hidden; 
            text-overflow: ellipsis; 
            white-space: nowrap; 
        }

        .pagination-container { 
            text-align: center; 
            margin-top: 2%; /* Marge en % */
        }
        
        /* Assurer que le formulaire de filtre est réactif */
        .form-inline .form-group {
            margin-right: 1.5%;
            margin-bottom: 1%; /* Espace sous les groupes de formulaires */
        }

        /* ------------------------------------------------ */
        /* @media queries pour la réactivité */
        /* ------------------------------------------------ */

        @media (max-width: 992px) { 
            :root {
                --sidebar-width: 0; 
            }
            .main-content-wrapper {
                margin-left: 0; 
                width: 100%;
                padding: 1%;
            }
            .page-container {
                width: 100%;
            }
            /* Empiler les filtres sur les petits écrans */
            .form-inline .form-group {
                display: block;
                width: 100%;
                margin-right: 0;
                margin-bottom: 1.5%;
            }
            .form-inline .form-control {
                width: 100%;
            }
            .form-inline button, .form-inline a.btn {
                display: block;
                width: 100%;
                margin-left: 0 !important;
                margin-top: 1%;
            }
            .user-agent-col {
                max-width: none; /* Afficher entièrement sur mobile */
                white-space: normal;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Inclure les templates maintenant, après que les headers soient gérés
    require_once('../../../templates/header.php');
    require_once('../../../templates/navigation.php');
    ?>
    
    <div class="main-content-wrapper">
        <div class="page-container">
            <h2 class="page-header"><?= htmlspecialchars($titre) ?></h2>

            <form action="" method="GET" class="form-inline mb-4 form-filters-responsive">
                <div class="form-group">
                    <label for="user_id">Utilisateur :</label>
                    <select name="user_id" id="user_id" class="form-control">
                        <option value="">Tous les utilisateurs</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= htmlspecialchars($user['ID_Utilisateur']) ?>" <?= $filterUserId === (int)$user['ID_Utilisateur'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['Nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Statut :</label>
                    <select name="status" id="status" class="form-control">
                        <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Tous</option>
                        <option value="success" <?= $filterStatus === 'success' ? 'selected' : '' ?>>Succès</option>
                        <option value="failed" <?= $filterStatus === 'failed' ? 'selected' : '' ?>>Échec</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="start_date">Date début :</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($filterStartDate ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="end_date">Date fin :</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($filterEndDate ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary">Appliquer les filtres</button>
                <a href="view_login_history.php" class="btn btn-default">Réinitialiser les filtres</a>
            </form>

            <?php if (empty($loginHistory)): ?>
                <div class="alert alert-info">Aucun historique de connexion trouvé avec les critères sélectionnés.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped login-history-table">
                        <thead>
                            <tr>
                                <th>Date/Heure</th>
                                <th>Utilisateur Tenté</th>
                                <th>Utilisateur Réel</th>
                                <th>Statut</th>
                                <th>Raison Échec</th>
                                <th>Adresse IP</th>
                                <th>Agent Utilisateur</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($loginHistory as $attempt): ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($attempt['login_timestamp']))) ?></td>
                                    <td><?= htmlspecialchars($attempt['username_attempted']) ?></td>
                                    <td><?= htmlspecialchars($attempt['actual_username'] ?? 'N/A') ?></td>
                                    <td class="<?= $attempt['is_successful'] ? 'status-success' : 'status-failed' ?>">
                                        <?= $attempt['is_successful'] ? 'Succès' : 'Échec' ?>
                                    </td>
                                    <td><?= htmlspecialchars($attempt['failure_reason'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($attempt['ip_address']) ?></td>
                                    <td class="user-agent-col" title="<?= htmlspecialchars($attempt['user_agent']) ?>">
                                        <?= htmlspecialchars($attempt['user_agent']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <nav class="pagination-container">
                    <ul class="pagination">
                        <li class="<?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>" aria-label="Précédent">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="<?= ($i === $currentPage) ? 'active' : '' ?>">
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="<?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>" aria-label="Suivant">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <?php require_once('../../../templates/footer.php'); ?>
</body>
</html>