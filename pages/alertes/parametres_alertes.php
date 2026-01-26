<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['seuils'] as $id => $valeur) {
        $stmt = $pdo->prepare("UPDATE consommables SET seuil_alerte = ? WHERE id_produit = ?");
        $stmt->execute([$valeur, $id]);
    }
    $message = "Configuration sauvegardée !";
}

$produits = $pdo->query("SELECT * FROM consommables ORDER BY nom_produit")->fetchAll();
require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container py-5">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-4">Réglage des Seuils de Sécurité</h4>
            <?php if(isset($message)): ?> <div class="alert alert-success"><?= $message ?></div> <?php endif; ?>
            
            <form method="POST">
                <table class="table table-borderless align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th>Consommable</th>
                            <th width="200">Seuil d'alerte</th>
                            <th>Unité</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($produits as $p): ?>
                        <tr>
                            <td class="fw-bold"><?= $p['nom_produit'] ?></td>
                            <td>
                                <input type="number" name="seuils[<?= $p['id_produit'] ?>]" 
                                       class="form-control" value="<?= $p['seuil_alerte'] ?>">
                            </td>
                            <td class="text-muted"><?= $p['unite'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="btn btn-primary mt-3">Mettre à jour les paramètres</button>
            </form>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>