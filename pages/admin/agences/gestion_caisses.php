<?php
// 1. Démarrer la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * 2. GESTION DES CHEMINS
 * Utilisation de chemins relatifs directs pour éviter les erreurs "http wrapper disabled"
 */
require_once '../../../fonctions/database.php';
require_once '../../../fonctions/gestion_agences.php';
require_once '../../../fonctions/gestion_operations_caisse.php';

// 3. VÉRIFICATION DE L'ACCÈS
if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: ../../../index.php");
    exit();
}

$id_utilisateur = $_SESSION['utilisateur_id'];
$id_agence = $_SESSION['id_agence'] ?? null;

// Si l'agence n'est pas en session, on la cherche en DB
if (!$id_agence) {
    try {
        $stmt = $pdo->prepare("SELECT code_agence FROM utilisateurs WHERE id_utilisateur = ? LIMIT 1");
        $stmt->execute([$id_utilisateur]);
        $id_agence_from_db = $stmt->fetchColumn();
        
        $_SESSION['id_agence'] = $id_agence_from_db;
        $id_agence = $id_agence_from_db;
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
    }
}

// 4. TRAITEMENT DES ACTIONS POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $mouvement_en_cours = getCaisseOuverte($pdo, $id_utilisateur);

        switch ($_POST['action']) {
            case 'ouvrir_caisse':
                $solde_initial = floatval($_POST['solde_initial'] ?? 0);
                if ($solde_initial < 0) {
                    $_SESSION['admin_message_error'] = "Le solde ne peut pas être négatif.";
                } elseif ($mouvement_en_cours) {
                    $_SESSION['admin_message_error'] = "Une caisse est déjà ouverte.";
                } else {
                    ouvrirCaisse($pdo, $id_utilisateur, $id_agence, $solde_initial);
                    $_SESSION['admin_message_success'] = "Caisse ouverte avec " . number_format($solde_initial, 0, '.', ' ') . " F CFA.";
                }
                break;

            case 'fermer_caisse':
                $solde_final = floatval($_POST['solde_final'] ?? 0);
                if ($mouvement_en_cours) {
                    $ecart = fermerCaisse($pdo, $mouvement_en_cours['id_mouvement_caisse'], $solde_final, $id_agence);
                    $_SESSION['admin_message_success'] = "Caisse fermée. Écart : " . number_format($ecart, 0, '.', ' ') . " F CFA.";
                }
                break;
        }
    } catch (Exception $e) {
        $_SESSION['admin_message_error'] = "Erreur : " . $e->getMessage();
    }
    header("Location: gestion_caisses.php");
    exit();
}

// 5. PRÉPARATION DE L'AFFICHAGE
$message = $_SESSION['admin_message_success'] ?? $_SESSION['admin_message_error'] ?? '';
$message_type = isset($_SESSION['admin_message_success']) ? 'success' : 'danger';
unset($_SESSION['admin_message_success'], $_SESSION['admin_message_error']);

$mouvement_en_cours = getCaisseOuverte($pdo, $id_utilisateur);
$solde_courant = $mouvement_en_cours ? calculerSoldeCourant($pdo, $mouvement_en_cours['id_mouvement_caisse']) : 0;
$historique_caisses = getHistoriqueCaisses($pdo);

// 6. INCLUSION DES VUES (Correction des chemins ici)
include '../../../templates/header.php';
include '../../../templates/navigation.php';
?>

<br><br><br>
<main class="container mt-4">
    <div class="pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 text-white">Gestion de la Caisse</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <?php if (!$mouvement_en_cours): ?>
                        <h5 class="card-title text-danger">Caisse Fermée</h5>
                        <form method="POST">
                            <input type="hidden" name="action" value="ouvrir_caisse">
                            <div class="mb-3">
                                <label class="form-label">Solde Initial (F CFA)</label>
                                <input type="number" name="solde_initial" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Ouvrir la Caisse</button>
                        </form>
                    <?php else: ?>
                        <h5 class="card-title text-success">Caisse Ouverte</h5>
                        <p class="h3"><?= number_format($solde_courant, 0, '.', ' ') ?> <small>F CFA</small></p>
                        <hr>
                        <form method="POST">
                            <input type="hidden" name="action" value="fermer_caisse">
                            <div class="mb-3">
                                <label class="form-label">Solde Final Compté</label>
                                <input type="number" name="solde_final" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-danger w-100">Fermer la Caisse</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">Historique Récent</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Initial</th>
                                <th>Final</th>
                                <th>Écart</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historique_caisses as $m): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($m['date_ouverture'])) ?></td>
                                <td><?= number_format($m['solde_initial'], 0, '.', ' ') ?></td>
                                <td><?= number_format($m['solde_final'] ?? 0, 0, '.', ' ') ?></td>
                                <td class="<?= ($m['ecart'] < 0) ? 'text-danger' : 'text-success' ?>">
                                    <?= number_format($m['ecart'] ?? 0, 0, '.', ' ') ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $m['date_fermeture'] ? 'secondary' : 'success' ?>">
                                        <?= $m['date_fermeture'] ? 'Fermée' : 'Ouverte' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../../../templates/footer.php'; ?>