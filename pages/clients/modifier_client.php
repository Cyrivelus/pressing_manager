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
        // Préparation des données (on garde les anciennes valeurs pour les champs non présents dans le form)
        $donnees_a_jour = [
            'nom_client'      => $_POST['nom_client'] ?? '',
            'prenom_client'   => $_POST['prenom_client'] ?? '',
            'telephone'       => $_POST['telephone'] ?? '',
            'email'           => $_POST['email'] ?? '',
            'adresse'         => $_POST['adresse'] ?? '',
            'notes'           => $_POST['notes'] ?? '',
            'remise_speciale' => $client['remise_speciale'], // On conserve l'existant
            'est_actif'       => $client['est_actif']      // On conserve l'existant
        ];
        
        if (empty($donnees_a_jour['nom_client']) || empty($donnees_a_jour['telephone'])) {
            throw new Exception("Le nom et le téléphone sont obligatoires.");
        }

        // L'appel à la fonction est maintenant cohérent avec gestion_clients.php
        if (mettreAJourClient($pdo, $id_client, $donnees_a_jour)) {
            $message = "Les informations du client ont été mises à jour avec succès.";
            $message_type = 'success';
            $client = trouverClientParId($pdo, $id_client); // Rafraîchir les données affichées
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
<br><br><br>
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Modifier le client : <?= htmlspecialchars($client['nom_client'] ?? 'Inconnu') ?></h2>
        <a href="javascript:history.back()" class="btn btn-outline-secondary">
    &larr; Retour à la liste
</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type; ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($client): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form action="modifier_client.php?id=<?= $id_client; ?>" method="post">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-uppercase">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom_client" value="<?= htmlspecialchars($client['nom_client']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-uppercase">Prénom</label>
                            <input type="text" class="form-control" name="prenom_client" value="<?= htmlspecialchars($client['prenom_client']) ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-uppercase">Téléphone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" name="telephone" value="<?= htmlspecialchars($client['telephone']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-uppercase">Email</label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($client['email']) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Adresse postale</label>
                        <textarea class="form-control" name="adresse" rows="2"><?= htmlspecialchars($client['adresse']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Notes internes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Informations complémentaires..."><?= htmlspecialchars($client['notes']) ?></textarea>
                    </div>

                    <hr>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary px-5 fw-bold">
                            Mettre à jour le profil
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../templates/footer.php'; ?>