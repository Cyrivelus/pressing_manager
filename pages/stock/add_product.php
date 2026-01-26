<?php
// 1. LOGIQUE PHP (Toujours en premier)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../fonctions/database.php');

$message = "";

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id_fournisseur = $_POST['id_fournisseur'];

        // Gestion du fournisseur par défaut
        if (empty($id_fournisseur)) {
            $checkF = $pdo->query("SELECT id_fournisseur FROM fournisseurs WHERE nom_fournisseur = 'FOURNISSEUR GENERAL' LIMIT 1");
            $defaultF = $checkF->fetch();

            if ($defaultF) {
                $id_fournisseur = $defaultF['id_fournisseur'];
            } else {
                $insF = $pdo->prepare("INSERT INTO fournisseurs (nom_fournisseur, categorie, est_actif) VALUES ('FOURNISSEUR GENERAL', 'autre', TRUE)");
                $insF->execute();
                $id_fournisseur = $pdo->lastInsertId();
            }
        }

        $sql = "INSERT INTO produits (nom_produit, id_fournisseur, categorie, unite_mesure, quantite_stock, seuil_alerte, prix_unitaire, emplacement) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['nom_produit'],
            $id_fournisseur,
            $_POST['categorie'],
            $_POST['unite_mesure'],
            $_POST['quantite_initiale'] ?: 0,
            $_POST['seuil_alerte'] ?: 10,
            $_POST['prix_unitaire'],
            $_POST['emplacement']
        ]);
        
        header("Location: inventory.php?success=added");
        exit();
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger shadow-sm'><strong>Erreur :</strong> " . $e->getMessage() . "</div>";
    }
}

// Données pour la vue
$stmtF = $pdo->query("SELECT id_fournisseur, nom_fournisseur FROM fournisseurs WHERE est_actif = TRUE ORDER BY nom_fournisseur");
$fournisseurs = $stmtF->fetchAll();

// 2. INCLUSIONS DES TEMPLATES (Après la logique, avant le contenu)
// Note : Si votre header.php contient déjà le <html><head> et la navbar,
// n'écrivez pas manuellement les balises head ici.
include '../../templates/header.php';
include '../../templates/navigation.php';
?>

<style>
    /* On garde uniquement les styles spécifiques à cette page */
    body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
    .card { border-radius: 15px; animation: fadeIn 0.5s ease-out; }
    .card-header { background: linear-gradient(135deg, #ffffff 0%, #764ba2 50%); border: none; }
    .btn-primary { background: linear-gradient(135deg, #ffffff 0%, #764ba2 50%); border: none; }
    .required::after { content: " *"; color: #dc3545; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>
<br><br><br>
<div class="container mt-4 pb-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="card shadow-lg border-0">
                <div class="card-header text-white d-flex justify-content-between align-items-center py-3">
    <h5 class="mb-0">Ajouter un produit</h5>
    <a href="javascript:history.back()" class="btn btn-sm btn-light border shadow-sm">
        <- Retour
    </a>
</div>
                
                <div class="card-body p-4 p-md-5">
                    <?= $message ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold required">Nom du produit</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-box text-primary"></i></span>
                                    <input type="text" name="nom_produit" class="form-control" required placeholder="Ex: Lessive 5L">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold required">Catégorie</label>
                                <div class="input-group">
                                    <span class="input-group-text"></span>
                                    <select name="categorie" class="form-select" required>
                                        <option value="">Sélectionner...</option>
                                        <option value="lessive">Lessive</option>
                                        <option value="detachant">Détachant</option>
                                        <option value="cintre">Cintre</option>
                                        <option value="sac">Sac</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <br>
                                <label class="form-label fw-bold">Fournisseur</label>
                                <select name="id_fournisseur" class="form-select">
                                    <option value="">-- Fournisseur par défaut --</option>
                                    <?php foreach($fournisseurs as $f): ?>
                                        <option value="<?= $f['id_fournisseur'] ?>"><?= htmlspecialchars($f['nom_fournisseur']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold required">Prix Unitaire (Achat)</label>
                                <div class="input-group">
                                    <input type="number" name="prix_unitaire" class="form-control" step="0.01" required>
                                    <span class="input-group-text">FCFA</span>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Stock Initial</label>
                                <input type="number" name="quantite_initiale" class="form-control" value="0" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Seuil d'alerte</label>
                                <input type="number" name="seuil_alerte" class="form-control" value="10">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Unité</label>
                                <input type="text" name="unite_mesure" class="form-control" placeholder="Ex: Litre">
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-bold">Emplacement</label>
                            <input type="text" name="emplacement" class="form-control" placeholder="Ex: Étagère A-1">
                        </div>

                        <div class="d-grid gap-2 mt-4">
                        <br>
                            <button type="submit" class="btn btn-primary btn-lg">
                                
                                Enregistrer le produit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Scripts de validation
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php include '../../templates/footer.php'; ?>