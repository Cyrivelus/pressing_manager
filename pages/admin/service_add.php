<?php
session_start();
require_once('../../fonctions/database.php');
require_once('../../fonctions/gestion_services.php');

// 1. Protection d'accès
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'patron') {
    header("Location: ../../../index.php?error=Accès réservé aux administrateurs");
    exit();
}

// 2. Récupération des catégories pour le menu déroulant
$categories = getAllCategories($pdo);

include('../../templates/header.php'); 
include('../../templates/navigation.php'); 
?>


<br> <br> <br>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="services.php">Services</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Nouveau Service</li>
                </ol>
            </nav>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white p-3">
                    <h4 class="mb-0"> Ajouter un Nouveau Service</h4>
                </div>
                <div class="card-body p-4">
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($_GET['error']) ?>
                        </div>
                    <?php endif; ?>

                    <form action="../../../controllers/ServiceController.php?action=add" method="POST">
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="nom_service" class="form-label fw-bold">Désignation du Service</label>
                                <input type="text" name="nom_service" id="nom_service" 
                                       class="form-control form-control-lg" 
                                       placeholder="Ex: Lavage complet Costume 2 pièces" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="id_categorie" class="form-label fw-bold">Catégorie</label>
                                <select name="id_categorie" id="id_categorie" class="form-select" required>
                                    <option value="" selected disabled>Choisir une catégorie...</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?= $cat['id_categorie'] ?>">
                                            <?= htmlspecialchars($cat['nom_categorie']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                         <div class="col-md-6 mb-3">
    <label for="unite_mesure" class="form-label fw-bold">Unité de mesure / Conditionnement</label>
    <select name="unite_mesure" id="unite_mesure" class="form-select">
        <optgroup label="Pressing & Blanchisserie">
            <option value="pièce" selected>Pièce (vêtement unique)</option>
            <option value="paire">Paire (chaussures, gants)</option>
            <option value="kg">Kilogramme (Linge au poids)</option>
            <option value="mètre">Mètre (rideaux, tapis)</option>
            <option value="machine">Machine (forfait lavage)</option>
        </optgroup>

        <optgroup label="Commerce & Restauration">
            <option value="bouteille">Bouteille</option>
            <option value="canette">Canette</option>
            <option value="casier">Casier / Carton</option>
            <option value="plat">Plat / Portion</option>
            <option value="litre">Litre (L)</option>
            <option value="gramme">Gramme (g)</option>
        </optgroup>

        <optgroup label="Autres">
            <option value="forfait">Forfait global</option>
            <option value="unité">Unité diverse</option>
        </optgroup>
    </select>
</div>

                            <div class="col-md-6 mb-3">
                                <label for="prix_unitaire" class="form-label fw-bold">Prix de vente (FCFA)</label>
                                <div class="input-group">
                                    <input type="number" name="prix_unitaire" id="prix_unitaire" 
                                           class="form-control" min="0" step="50" required>
                                    <span class="input-group-text">FCFA</span>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="duree_estimee" class="form-label fw-bold">Durée estimée (minutes)</label>
                                <div class="input-group">
                                    <input type="number" name="duree_estimee" id="duree_estimee" 
                                           class="form-control" value="60" min="0">
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                </div>
                                <small class="text-muted">Temps moyen de traitement</small>
                            </div>

                            <div class="col-md-12 mb-4">
                                <label for="description" class="form-label fw-bold">Description / Notes (Optionnel)</label>
                                <textarea name="description" id="description" class="form-control" rows="3" 
                                          placeholder="Précisions sur le service..."></textarea>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="services.php" class="btn btn-outline-secondary">
                                 Annuler
                            </a>
                            <button type="submit" class="btn btn-primary px-5">
                               Créer le service
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="mt-4 p-3 bg-light rounded border">
                <small class="text-muted">
               <strong>Astuce :</strong> Assurez-vous d'avoir créé la catégorie correspondante avant d'ajouter un service spécifique. Les services créés ici seront immédiatement visibles par les réceptionnistes lors de la création d'un ticket.
                </small>
            </div>
        </div>
    </div>
</div>

<?php include('../../templates/footer.php'); ?>