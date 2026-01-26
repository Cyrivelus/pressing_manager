<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';
require_once $root . '/fonctions/tarifs/configuration_tarifs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_service = $_POST['id_service'];
    $nouveau_prix = $_POST['nouveau_prix'];
    $id_user = $_SESSION['utilisateur_id'];

    if (modifierPrixService($id_service, $nouveau_prix, $id_user, $pdo)) {
        $success = "Prix mis à jour avec succès.";
    } else {
        $error = "Erreur lors de la mise à jour.";
    }
}

$services = $pdo->query("SELECT * FROM services ORDER BY nom_service")->fetchAll();
$titre = "Configuration des Tarifs";
require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-lg">
                <div class="card-body p-5">
                    <h3 class="fw-bold mb-4 text-center">Modifier un Prix</h3>
                    
                    <?php if(isset($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Sélectionner le service</label>
                            <select name="id_service" class="form-select form-select-lg" required>
                                <?php foreach($services as $s): ?>
                                    <option value="<?= $s['id_service'] ?>"><?= $s['nom_service'] ?> (Actuel: <?= $s['prix_unitaire'] ?> F)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Nouveau Prix (FCFA)</label>
                            <input type="number" name="nouveau_prix" class="form-control form-control-lg" placeholder="Ex: 1500" required>
                        </div>
                        <button type="submit" class="btn btn-dark btn-lg w-100 py-3 shadow">
                            Enregistrer le nouveau tarif
                        </button>
                    </form>
                    <div class="text-center mt-4">
                        <a href="index.php" class="text-muted text-decoration-none small"><i class="fas fa-arrow-left"></i> Retour à la liste</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>