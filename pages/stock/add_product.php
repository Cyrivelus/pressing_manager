<?php
// 1. LOGIQUE PHP
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../fonctions/database.php');

$message = "";

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id_fournisseur = $_POST['id_fournisseur'];

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
        $message = "<div class='alert alert-danger'><strong>Erreur système :</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Données pour la vue
$stmtF = $pdo->query("SELECT id_fournisseur, nom_fournisseur FROM fournisseurs WHERE est_actif = TRUE ORDER BY nom_fournisseur");
$fournisseurs = $stmtF->fetchAll();

include '../../templates/header.php';
include '../../templates/navigation.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($TITRE_PAGE) ?> | Gestion Stock</title>
    <link rel="stylesheet" href="../../css/select2.min.css">
    <link rel="stylesheet" href="../../css/select2-bootstrap.min.css">
    <style>
        /* Palette Professionnelle : Anthracite, Gris, Blanc */
        :root {
            --primary-dark: #2c3e50;
            --border-color: #dee2e6;
            --bg-body: #f8f9fa;
        }

        body { 
            background-color: var(--bg-body); 
            color: #333;
            font-family: 'Segoe UI', Roboto, sans-serif;
        }

        .card { 
            border: 1px solid var(--border-color);
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            background: #fff;
        }

        .card-header { 
            background-color: #fff; 
            border-bottom: 2px solid var(--primary-dark);
            padding: 1.25rem;
        }

        .card-header h5 {
            color: var(--primary-dark);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .btn-primary { 
            background-color: var(--primary-dark); 
            border: none;
            border-radius: 2px;
            font-weight: 600;
            padding: 10px 20px;
        }

        .btn-primary:hover {
            background-color: #1a252f;
        }

        .btn-light {
            background: #fff;
            border: 1px solid var(--border-color);
            color: var(--primary-dark);
            font-weight: 600;
        }

        .form-label {
            font-size: 0.85rem;
            color: #555;
            margin-bottom: 0.4rem;
        }

        .form-control {
            border-radius: 2px;
            border: 1px solid var(--border-color);
            padding: 0.6rem;
        }

        .form-control:focus {
            border-color: var(--primary-dark);
            box-shadow: none;
        }

        .input-group-text {
            background-color: #f1f3f5;
            border: 1px solid var(--border-color);
            color: var(--primary-dark);
            font-weight: bold;
            font-size: 0.8rem;
        }

        .required::after { content: " *"; color: #d9534f; }
        
        hr { opacity: 0.1; }

        .container-form { margin-top: 50px; }
    </style>
</head>
<body>

<div class="container container-form pb-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Fiche Nouveau Produit</h5>
                    <a href="javascript:history.back()" class="btn btn-sm btn-light">
                        &larr; Annuler et retour
                    </a>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <?= $message ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold required">Désignation du produit</label>
                                <input type="text" name="nom_produit" class="form-control" required placeholder="Ex: Lessive Professionnelle 5L">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold required">Catégorie d'inventaire</label>
                                <select name="categorie" id="categorieSelect" class="form-control select2-enable" required>
                                    <option value="">-- Sélectionner --</option>
                                    <option value="lessive">Lessive</option>
                                    <option value="detachant">Détachant</option>
                                    <option value="cintre">Cintre</option>
                                    <option value="sac">Sac</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Fournisseur attitré</label>
                                <select name="id_fournisseur" id="fournisseurSelect" class="form-control select2-enable" required <?= empty($fournisseurs) ? 'disabled' : '' ?>>
                                    <option value="">-- Rechercher --</option>
                                    <?php foreach($fournisseurs as $f): ?>
                                        <option value="<?= htmlspecialchars($f['id_fournisseur']) ?>">
                                            <?= htmlspecialchars($f['nom_fournisseur']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold required">Prix d'achat unitaire</label>
                                <div class="input-group">
                                    <input type="number" name="prix_unitaire" class="form-control" step="0.01" required placeholder="0.00">
                                    <span class="input-group-text">FCFA</span>
                                </div>
                            </div>
                        </div>

                        <hr class="my-5">

                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Quantité initiale</label>
                                <input type="number" name="quantite_initiale" class="form-control" value="0" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Seuil d'alerte mini</label>
                                <input type="number" name="seuil_alerte" class="form-control" value="10">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Unité de mesure</label>
                                <input type="text" name="unite_mesure" class="form-control" placeholder="Ex: Litre, Kg, Unité">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Localisation / Emplacement</label>
                                <input type="text" name="emplacement" class="form-control" placeholder="Ex: Rayon B, Étagère 4">
                            </div>
                        </div>

                        <div class="mt-5">
                            <button type="submit" class="btn btn-primary w-100">
                                Valider la création du produit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../js/jquery.min.js"></script>
<script src="../../js/bootstrap.min.js"></script>
<script src="../../js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Validation Bootstrap
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

    // Initialisation Select2
    $('.select2-enable').select2({
        theme: "bootstrap",
        placeholder: "-- Sélectionner --",
        allowClear: true,
        width: '100%'
    });
});
</script>
<?php include '../../templates/footer.php'; ?>