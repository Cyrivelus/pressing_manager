<?php
/**
 * pages/admin/factures/modeles.php
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../../index.php');
    exit();
}

// 2. Initialisation
$titre = "Gestion des Modèles";
$modeles = [];
$error_message = null;

// 3. Base de données
require_once realpath(__DIR__ . '/../../../fonctions/database.php');

$action = $_GET['action'] ?? 'liste';
$modele_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // Vérification de l'existence de la table pour éviter le crash
    $checkTable = $pdo->query("SHOW TABLES LIKE 'modeles_facture'")->rowCount();
    
    if ($checkTable > 0) {
        switch ($action) {
            case 'creer':
                // Logique pour formulaire création
                break;
            case 'editer':
                $stmt = $pdo->prepare("SELECT * FROM modeles_facture WHERE id_modele = ?");
                $stmt->execute([$modele_id]);
                $modele = $stmt->fetch();
                break;
            default:
                $modeles = $pdo->query("SELECT * FROM modeles_facture ORDER BY est_par_defaut DESC")->fetchAll(PDO::FETCH_ASSOC);
                break;
        }
    } else {
        $error_message = "La table 'modeles_facture' est introuvable. Veuillez l'importer en base de données.";
    }
} catch (Exception $e) {
    $error_message = "Erreur : " . $e->getMessage();
}

require_once realpath(__DIR__ . '/../../../templates/header.php');
require_once realpath(__DIR__ . '/../../../templates/navigation.php');
?>

<style>
    .dashboard-wrapper { margin-left: 250px; padding: 2rem; background: #f4f7f6; min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
    .page-header { background: #fff; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
    .btn-pro { padding: 0.6rem 1.2rem; border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; border: none; transition: 0.3s; cursor: pointer; }
    .btn-blue { background: #2563eb; color: white; }
    .btn-blue:hover { background: #1e40af; }
    .btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
    .card-body-pro { background: #fff; border-radius: 10px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .alert-pro { background: #fee2e2; border-left: 4px solid #ef4444; color: #b91c1c; padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 1rem; border-bottom: 2px solid #e5e7eb; color: #4b5563; font-size: 0.85rem; text-transform: uppercase; }
    td { padding: 1rem; border-bottom: 1px solid #f3f4f6; color: #1f2937; }
    .badge { padding: 0.25rem 0.75rem; border-radius: 99px; font-size: 0.75rem; font-weight: 600; }
    .badge-default { background: #dcfce7; color: #166534; }
    @media (max-width: 992px) { .dashboard-wrapper { margin-left: 0; } }
</style>

<div class="dashboard-wrapper">
    <div class="page-header">
        <div>
            <h2 style="margin:0; color:#111827;"><?= $titre ?></h2>
            <p style="margin:5px 0 0; color:#6b7280; font-size:0.9rem;">Gestion et personnalisation des factures</p>
        </div>
        <div class="actions">
            <?php if ($action === 'liste'): ?>
                <a href="?action=creer" class="btn-pro btn-blue">Nouveau Modèle</a>
            <?php else: ?>
                <a href="modeles.php" class="btn-pro btn-outline">Retour</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert-pro"><?= $error_message ?></div>
    <?php endif; ?>

    <div class="card-body-pro">
        <?php if ($action === 'liste'): ?>
            <?php if (empty($modeles)): ?>
                <p style="text-align:center; color:#9ca3af; padding: 2rem;">Aucun modèle trouvé.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nom du modèle</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modeles as $m): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($m['nom']) ?></strong>
                                <?php if($m['est_par_defaut']): ?> <span class="badge badge-default">Défaut</span> <?php endif; ?>
                            </td>
                            <td><?= $m['est_actif'] ? 'Actif' : 'Inactif' ?></td>
                            <td>
                                <a href="?action=editer&id=<?= $m['id_modele'] ?>" style="color:#2563eb; text-decoration:none; font-weight:500;">Modifier</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php else: ?>
            <?php
            $base = __DIR__ . '/includes/modeles/';
            $file = ($action === 'creer') ? 'form_creation.php' : (($action === 'editer') ? 'form_edition.php' : 'liste_modeles.php');
            
            if (file_exists($base . $file)) {
                include $base . $file;
            } else {
                echo "<p style='color:#ef4444;'>Composant manquant : includes/modeles/$file</p>";
            }
            ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once realpath(__DIR__ . '/../../../templates/footer.php'); ?>