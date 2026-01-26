<?php
// pages/clients/modifier_client.php
session_start();

require_once '../../fonctions/database.php';
require_once '../../fonctions/gestion_clients.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_client = intval($_GET['id']);
$message = '';
$message_type = '';

try {
    // Récupérer les données existantes du client
    $client = trouverClientParId($pdo, $id_client);

    if (!$client) {
        throw new Exception("Client introuvable.");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Nettoyage et préparation des données pour correspondre à la DB
        $donnees_a_jour = [
            'nom_client'      => $_POST['nom_client'] ?? '',
            'prenom_client'   => $_POST['prenom_client'] ?? '',
            'telephone'       => $_POST['telephone'] ?? '',
            'email'           => $_POST['email'] ?? '',
            'adresse'         => $_POST['adresse'] ?? '',
            'notes'           => $_POST['notes'] ?? ''
        ];
        
        // Validation minimale
        if (empty($donnees_a_jour['nom_client']) || empty($donnees_a_jour['telephone'])) {
            throw new Exception("Le nom et le téléphone sont obligatoires.");
        }

        if (mettreAJourClient($pdo, $id_client, $donnees_a_jour)) {
            $message = "Les informations du client ont été mises à jour avec succès.";
            $message_type = 'success';
            $client = trouverClientParId($pdo, $id_client); // Rafraîchir
        } else {
            throw new Exception("Échec de la mise à jour.");
        }
    }

} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
    $message_type = 'danger';
}

include '../../templates/header.php';
include '../../templates/navigation.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Modifier le client : <?= htmlspecialchars($client['nom_client'] ?? 'Inconnu') ?></h2>
        <a href="index.php" class="btn btn-secondary">
            <- Retour
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type; ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($client): ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="modifier_client.php?id=<?= $id_client; ?>" method="post">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom_client" value="<?= htmlspecialchars($client['nom_client']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom</label>
                            <input type="text" class="form-control" name="prenom_client" value="<?= htmlspecialchars($client['prenom_client']) ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" name="telephone" value="<?= htmlspecialchars($client['telephone']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($client['email']) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Adresse</label>
                        <textarea class="form-control" name="adresse" rows="2"><?= htmlspecialchars($client['adresse']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes / Remarques</label>
                        <textarea class="form-control" name="notes" rows="2"><?= htmlspecialchars($client['notes']) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Enregistrer les modifications
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../templates/footer.php'; ?>