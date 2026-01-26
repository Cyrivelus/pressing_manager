<?php
// pages/clients/index.php
session_start();

require_once '../../fonctions/database.php';
require_once '../../fonctions/gestion_clients.php';

$message = '';
$message_type = '';
$clients = [];

try {
    // 1. Gérer la suppression
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'supprimer') {
        $id_client_a_supprimer = intval($_POST['id_client']);
        
        if (supprimerClient($pdo, $id_client_a_supprimer)) {
            $message = "Le client a été supprimé avec succès.";
            $message_type = 'success';
        } else {
            throw new Exception("Impossible de supprimer ce client. Vérifiez s'il est lié à des tickets ou des paiements.");
        }
    }
    
    // 2. Récupérer la liste des clients
    $clients = listerClients($pdo);

} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
    $message_type = 'danger';
}

include '../../templates/header.php'; 
include '../../templates/navigation.php'; 
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users"></i> Gestion des Clients</h2>
        <div>
            <a href="ajouter_client.php" class="btn btn-primary">
                Nouveau Client
            </a>
            <a href="comptes_gestion.php" class="btn btn-outline-secondary">
                Comptes Clients
            </a>
        </div>
    </div>

    <hr>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type; ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Nom & Prénom</th>
                        <th>Téléphone</th>
                        <th>Email</th>
                        <th>Points</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($clients)): ?>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td class="ps-3">#<?= htmlspecialchars($client['id_client']); ?></td>
                                <td>
                                    <strong><?= htmlspecialchars(strtoupper($client['nom_client'])); ?></strong> 
                                    <?= htmlspecialchars($client['prenom_client']); ?>
                                </td>
                                <td><?= htmlspecialchars($client['telephone']); ?></td>
                                <td><?= htmlspecialchars($client['email'] ?: 'Non renseigné'); ?></td>
                                <td><span class="badge bg-info text-dark"><?= $client['points_fidelite'] ?? 0; ?> pts</span></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="modifier_client.php?id=<?= $client['id_client']; ?>" 
                                           class="btn btn-warning btn-sm" title="Modifier">
                                           Modifier
                                        </a>
                                        
                                        <form action="index.php" method="POST" class="d-inline" 
                                              onsubmit="return confirm('Supprimer définitivement ce client ?');">
                                            <input type="hidden" name="action" value="supprimer">
                                            <input type="hidden" name="id_client" value="<?= $client['id_client']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Supprimer">
                                               Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fas fa-info-circle"></i> Aucun client trouvé dans la base de données.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>