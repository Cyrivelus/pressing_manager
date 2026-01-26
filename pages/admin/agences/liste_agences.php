<?php
// views/agences/liste_agences.php
session_start();

/**
 * Correction du chemin : 
 * Si vous êtes dans /views/agences/liste_agences.php
 * ../../ remonte à la racine /
 * Puis on entre dans config/database.php
 */
require_once '../../../fonctions/database.php';

// Initialiser les messages
$message = '';
$message_type = '';

try {
    // 1. Récupérer les agences (Directement via PDO pour cet exemple)
    $stmt = $pdo->query("SELECT * FROM agences ORDER BY nom_agence ASC");
    $agences = $stmt->fetchAll();
    
    // 2. Gérer la suppression
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'supprimer') {
        $id_a_supprimer = $_POST['id_agence'];
        
        $deleteStmt = $pdo->prepare("DELETE FROM agences WHERE id_agence = ?");
        if ($deleteStmt->execute([$id_a_supprimer])) {
            $_SESSION['message'] = "L'agence a été supprimée avec succès.";
            $_SESSION['message_type'] = 'success';
            header("Location: liste_agences.php"); // Redirection pour éviter de renvoyer le formulaire
            exit;
        }
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
    $message_type = 'danger';
    $agences = [];
}

// Récupérer les messages flash de la session
if(isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

include '../../../templates/header.php';
include '../../../templates/navigation.php';
?>
<br> <br> <br>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-white"> Gestion des Agences</h2>
        <a href="ajouter_agence.php" class="btn btn-light">
           + Ajouter une agence
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type; ?> alert-dismissible fade show">
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nom de l'Agence</th>
                        <th>Téléphone</th>
                        <th>Email</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($agences)): ?>
                        <?php foreach ($agences as $agence): ?>
                            <tr>
                                <td><?= htmlspecialchars($agence['id_agence']); ?></td>
                                <td><strong><?= htmlspecialchars($agence['nom_agence']); ?></strong></td>
                                <td><?= htmlspecialchars($agence['telephone'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($agence['email'] ?? 'N/A'); ?></td>
                                <td class="text-center">
                                    <a href="modifier_agence.php?id=<?= $agence['id_agence']; ?>" class="btn btn-sm btn-outline-primary">
                                     Modifier
                                    </a>
                                    <form action="" method="post" class="d-inline" onsubmit="return confirm('Supprimer cette agence ?');">
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="id_agence" value="<?= $agence['id_agence']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            Supprimer
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4">Aucune agence trouvée.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../../templates/footer.php'; ?>