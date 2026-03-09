<?php
// pages/Fournisseurs/modifier.php

// 1. Démarrer la session et vérifier les permissions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Vérifier les permissions
$allowed_roles = ['patron', 'gestionnaire_stock'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header('Location: ../index.php?error=Permission non autorisée');
    exit;
}

// 2. Gestion des chemins
$root = dirname(__DIR__, 2);
require_once $root . '/fonctions/database.php';
require_once $root . '/fonctions/gestion_fournisseurs.php';
require_once $root . '/fonctions/validation.php';

// 3. Récupération de l'ID du fournisseur
$fournisseur_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$fournisseur_id) {
    header('Location: index.php?error=fournisseur_manquant');
    exit;
}

// 4. Récupérer le fournisseur existant
$fournisseur = getFournisseurById($pdo, $fournisseur_id);

if (!$fournisseur) {
    header('Location: index.php?error=fournisseur_introuvable');
    exit;
}

// 5. Initialiser les variables
$message = '';
$erreurs = [];
$formData = $fournisseur; // Utiliser les données existantes par défaut

// 6. Traitement du formulaire si la méthode est POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et assainir les données du formulaire
    $formData = [
        'nom_fournisseur' => trim($_POST['nom_fournisseur'] ?? ''),
        'contact' => trim($_POST['contact'] ?? ''),
        'telephone' => trim($_POST['telephone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'adresse' => trim($_POST['adresse'] ?? ''),
        'categorie' => $_POST['categorie'] ?? 'produit_nettoyage',
        'solde_du' => trim($_POST['solde_du'] ?? '0.00'),
        'est_actif' => isset($_POST['est_actif']) ? 1 : 0
    ];

    // Valider les données
    if (empty($formData['nom_fournisseur'])) {
        $erreurs[] = 'Le nom du fournisseur est obligatoire.';
    }
    
    if (!empty($formData['email']) && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'L\'adresse email n\'est pas valide.';
    }
    
    if (!empty($formData['solde_du']) && !is_numeric($formData['solde_du'])) {
        $erreurs[] = 'Le solde doit être un nombre valide.';
    }

    // Si aucune erreur de validation, procéder à la mise à jour
    if (empty($erreurs)) {
        try {
            if (modifierFournisseur($pdo, $fournisseur_id, $formData)) {
                $_SESSION['success_message'] = "Le fournisseur a été mis à jour avec succès.";
                header('Location: index.php?success=modifie');
                exit;
            } else {
                $erreurs[] = "Une erreur est survenue lors de la mise à jour du fournisseur.";
            }
        } catch (Exception $e) {
            $erreurs[] = "Erreur : " . $e->getMessage();
        }
    }
}

// 7. Titre de la page
$titre = 'Modifier le fournisseur : ' . htmlspecialchars($fournisseur['nom_fournisseur']);

// 8. Inclusion des templates
require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<style>
    .content-container {
        margin-top: 80px;
        padding: 20px;
    }
    
    .card {
        border: none;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        border-radius: 10px;
        overflow: hidden;
    }
    
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        border-bottom: none;
    }
    
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
    }
    
    .form-control, .form-select {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 10px 15px;
        transition: all 0.3s;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #764ba2;
        box-shadow: 0 0 0 3px rgba(118, 75, 162, 0.1);
    }
    
    .btn-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 10px 25px;
        border-radius: 8px;
        font-weight: 600;
        transition: transform 0.2s;
    }
    
    .btn-custom:hover {
        transform: translateY(-2px);
        color: white;
        box-shadow: 0 5px 15px rgba(118, 75, 162, 0.3);
    }
    
    .btn-outline-custom {
        border: 2px solid #667eea;
        color: #667eea;
        background: transparent;
        padding: 10px 25px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-outline-custom:hover {
        background: #667eea;
        color: white;
    }
    
    .required-asterisk {
        color: #dc3545;
    }
    
    .category-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        margin: 2px;
    }
    
    .category-badge.produit_nettoyage { background: #e3f2fd; color: #1976d2; }
    .category-badge.equipement { background: #e8f5e9; color: #388e3c; }
    .category-badge.emballage { background: #fff3e0; color: #f57c00; }
    .category-badge.autre { background: #f3e5f5; color: #7b1fa2; }
    
    @media (max-width: 768px) {
        .content-container {
            margin-top: 60px;
            padding: 15px;
        }
        
        .card-header h3 {
            font-size: 1.3rem;
        }
        
        .btn-custom, .btn-outline-custom {
            width: 100%;
            margin-bottom: 10px;
        }
    }
</style>

<div class="container-fluid content-container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <!-- Fil d'Ariane -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb bg-light p-3 rounded">
                    <li class="breadcrumb-item"><a href="<?= '../../pages/dashboard.php' ?>">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Fournisseurs</a></li>
                    <li class="breadcrumb-item"><a href="voir.php?id=<?= $fournisseur_id ?>"><?= htmlspecialchars(substr($fournisseur['nom_fournisseur'], 0, 20)) ?>...</a></li>
                    <li class="breadcrumb-item active">Modification</li>
                </ol>
            </nav>

            <!-- En-tête -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1 text-dark">Modifier le fournisseur</h1>
                    <p class="text-muted mb-0">ID: #<?= $fournisseur_id ?> | Mise à jour des informations</p>
                </div>
                <div>
                    <a href="voir.php?id=<?= $fournisseur_id ?>" class="btn btn-outline-primary">
                         Voir
                    </a>
                </div>
            </div>

            <!-- Messages d'alerte -->
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                  
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($erreurs)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h5 class="alert-heading">Erreurs de validation</h5>
                    <ul class="mb-0">
                        <?php foreach ($erreurs as $erreur): ?>
                            <li><?= htmlspecialchars($erreur) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Formulaire -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="mb-0">Informations du fournisseur</h3>
                </div>
                <div class="card-body">
                    <form method="post" action="" id="formModifierFournisseur">
                        <div class="row g-4">
                            <!-- Nom du fournisseur -->
                            <div class="col-12 col-md-6">
                                <label for="nom_fournisseur" class="form-label">
                                    Nom du fournisseur <span class="required-asterisk">*</span>
                                </label>
                                <input type="text" class="form-control" id="nom_fournisseur" name="nom_fournisseur" 
                                       required maxlength="100" 
                                       value="<?= htmlspecialchars($formData['nom_fournisseur'] ?? '') ?>"
                                       placeholder="Entrez le nom complet">
                                <div class="form-text">Nom de l'entreprise ou du fournisseur</div>
                            </div>

                            <!-- Contact -->
                            <div class="col-12 col-md-6">
                                <label for="contact" class="form-label">Personne de contact</label>
                                <input type="text" class="form-control" id="contact" name="contact" 
                                       maxlength="100"
                                       value="<?= htmlspecialchars($formData['contact'] ?? '') ?>"
                                       placeholder="Nom de la personne à contacter">
                                <div class="form-text">Responsable commercial ou interlocuteur principal</div>
                            </div>

                            <!-- Téléphone -->
                            <div class="col-12 col-md-6">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <div class="input-group">
                                    <span class="input-group-text"></span>
                                    <input type="tel" class="form-control" id="telephone" name="telephone" 
                                           maxlength="20"
                                           value="<?= htmlspecialchars($formData['telephone'] ?? '') ?>"
                                           placeholder="Ex: +33 1 23 45 67 89">
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           maxlength="100"
                                           value="<?= htmlspecialchars($formData['email'] ?? '') ?>"
                                           placeholder="cyrillestevetamboug@gmail.com">
                                </div>
                            </div>

                            <!-- Catégorie -->
                            <div class="col-12 col-md-6">
                                <label for="categorie" class="form-label">Catégorie</label>
                                <select class="form-select" id="categorie" name="categorie" required>
                                    <option value="">Sélectionnez une catégorie</option>
                                    <option value="produit_nettoyage" <?= ($formData['categorie'] ?? '') == 'produit_nettoyage' ? 'selected' : '' ?>>
                                        Produits de nettoyage
                                    </option>
                                    <option value="equipement" <?= ($formData['categorie'] ?? '') == 'equipement' ? 'selected' : '' ?>>
                                        Équipement
                                    </option>
                                    <option value="emballage" <?= ($formData['categorie'] ?? '') == 'emballage' ? 'selected' : '' ?>>
                                        Emballage
                                    </option>
                                    <option value="autre" <?= ($formData['categorie'] ?? '') == 'autre' ? 'selected' : '' ?>>
                                        Autre
                                    </option>
                                </select>
                                <div class="mt-2">
                                    <small>Actuel: 
                                        <span class="category-badge <?= $formData['categorie'] ?? '' ?>">
                                            <?php
                                            $categories = [
                                                'produit_nettoyage' => 'Produits nettoyage',
                                                'equipement' => 'Équipement',
                                                'emballage' => 'Emballage',
                                                'autre' => 'Autre'
                                            ];
                                            echo $categories[$formData['categorie'] ?? ''] ?? 'Non défini';
                                            ?>
                                        </span>
                                    </small>
                                </div>
                            </div>

                            <!-- Solde dû -->
                            <div class="col-12 col-md-6">
                                <label for="solde_du" class="form-label">Solde dû (FCFA)</label>
                                <div class="input-group">
                                    <span class="input-group-text">FCFA</span>
                                    <input type="number" class="form-control" id="solde_du" name="solde_du" 
                                           step="0.01" min="0"
                                           value="<?= htmlspecialchars($formData['solde_du'] ?? '0.00') ?>"
                                           placeholder="0.00">
                                </div>
                                <div class="form-text">Montant actuellement dû au fournisseur</div>
                            </div>

                            <!-- Adresse -->
                            <div class="col-12">
                                <label for="adresse" class="form-label">Adresse complète</label>
                                <textarea class="form-control" id="adresse" name="adresse" 
                                          rows="3" maxlength="500"
                                          placeholder="Adresse, code postal, ville, pays"><?= htmlspecialchars($formData['adresse'] ?? '') ?></textarea>
                                <div class="form-text">&nbsp;&nbsp;&nbsp;&nbsp;Adresse postale du fournisseur</div>
                            </div>

                            <!-- Statut -->
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           id="est_actif" name="est_actif" 
                                           <?= ($formData['est_actif'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="est_actif">
                                        <br>
                                       &nbsp; Fournisseur actif
                                    </label>
                                    <div class="form-text">
                                     &nbsp;&nbsp;&nbsp;   Désactivez cette option pour archiver le fournisseur sans le supprimer
                                    </div>
                                </div>
                            </div>

                            <!-- Boutons d'action -->
                            <div class="col-12">
                                <hr class="my-4">
                                <div class="d-flex flex-wrap gap-3 justify-content-between">
                                    <div>
                                        <button type="submit" class="btn btn-custom">
                                             Enregistrer les modifications
                                        </button>
                                        <button type="reset" class="btn btn-outline-secondary">
                                            Réinitialiser
                                        </button>
                                    </div>
                                    <div>
                                        <a href="index.php" class="btn btn-outline-custom">
                                             Annuler
                                        </a>
                                        <a href="supprimer.php?id=<?= $fournisseur_id ?>" 
                                           class="btn btn-outline-danger"
                                           onclick="return confirm('Êtes-vous sûr de vouloir désactiver ce fournisseur ?')">
                                             Désactiver
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Informations supplémentaires -->
            <div class="row">
                <div class="col-12 col-md-6 mb-4">
                    <div class="card h-100 border-start border-primary border-4">
                        <div class="card-body">
                            <h5 class="card-title text-primary">
                               Informations système
                            </h5>
                            <div class="small">
                                <p class="mb-1"><strong>ID:</strong> #<?= $fournisseur_id ?></p>
                                <p class="mb-1"><strong>Statut:</strong> 
                                    <span class="badge bg-<?= ($fournisseur['est_actif'] ?? 0) ? 'success' : 'secondary' ?>">
                                        <?= ($fournisseur['est_actif'] ?? 0) ? 'Actif' : 'Inactif' ?>
                                    </span>
                                </p>
                                <p class="mb-1"><strong>Créé le:</strong> 
                                    <?= date('d/m/Y H:i', strtotime($fournisseur['created_at'] ?? 'now')) ?>
                                </p>
                                <?php if (!empty($fournisseur['updated_at'])): ?>
                                <p class="mb-0"><strong>Dernière modification:</strong> 
                                    <?= date('d/m/Y H:i', strtotime($fournisseur['updated_at'])) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 mb-4">
                    <div class="card h-100 border-start border-success border-4">
                        <div class="card-body">
                            <h5 class="card-title text-success">
                               Produits associés
                            </h5>
                            <?php
                            // Récupérer le nombre de produits associés
                            try {
                                $stmt = $pdo->prepare("SELECT COUNT(*) as nb_produits FROM produits WHERE id_fournisseur = ?");
                                $stmt->execute([$fournisseur_id]);
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                $nb_produits = $result['nb_produits'] ?? 0;
                            } catch (Exception $e) {
                                $nb_produits = 0;
                            }
                            ?>
                            <div class="text-center py-3">
                                <div class="display-4 text-success mb-2"><?= $nb_produits ?></div>
                                <p class="text-muted mb-0">produit(s) fourni(s)</p>
                            </div>
                            <?php if ($nb_produits > 0): ?>
                            <div class="text-center">
                                <a href="../stock/index.php?fournisseur=<?= $fournisseur_id ?>" 
                                   class="btn btn-sm btn-outline-success">
                                    Voir les produits
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validation côté client
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formModifierFournisseur');
    const nomInput = document.getElementById('nom_fournisseur');
    const emailInput = document.getElementById('email');
    const soldeInput = document.getElementById('solde_du');
    
    // Validation du nom
    nomInput.addEventListener('blur', function() {
        validateField(this, 'Le nom du fournisseur est obligatoire');
    });
    
    // Validation de l'email
    emailInput.addEventListener('blur', function() {
        if (this.value.trim() !== '') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(this.value)) {
                showError(this, 'L\'adresse email n\'est pas valide');
            } else {
                clearError(this);
            }
        }
    });
    
    // Validation du solde
    soldeInput.addEventListener('blur', function() {
        if (this.value.trim() !== '') {
            const value = parseFloat(this.value);
            if (isNaN(value) || value < 0) {
                showError(this, 'Le solde doit être un nombre positif');
            } else {
                clearError(this);
            }
        }
    });
    
    // Validation avant soumission
    form.addEventListener('submit', function(event) {
        let isValid = true;
        
        // Vérifier le nom
        if (nomInput.value.trim() === '') {
            showError(nomInput, 'Le nom du fournisseur est obligatoire');
            isValid = false;
        }
        
        // Vérifier l'email
        if (emailInput.value.trim() !== '') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailInput.value)) {
                showError(emailInput, 'L\'adresse email n\'est pas valide');
                isValid = false;
            }
        }
        
        // Vérifier le solde
        if (soldeInput.value.trim() !== '') {
            const value = parseFloat(soldeInput.value);
            if (isNaN(value) || value < 0) {
                showError(soldeInput, 'Le solde doit être un nombre positif');
                isValid = false;
            }
        }
        
        if (!isValid) {
            event.preventDefault();
            // Scroll vers la première erreur
            const firstError = form.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
    
    // Fonctions utilitaires
    function validateField(field, errorMessage) {
        if (field.value.trim() === '') {
            showError(field, errorMessage);
        } else {
            clearError(field);
        }
    }
    
    function showError(field, message) {
        field.classList.add('is-invalid');
        
        let feedback = field.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentNode.insertBefore(feedback, field.nextSibling);
        }
        
        feedback.textContent = message;
        feedback.style.display = 'block';
    }
    
    function clearError(field) {
        field.classList.remove('is-invalid');
        
        const feedback = field.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.style.display = 'none';
        }
    }
    
    // Formatage du téléphone
    const phoneInput = document.getElementById('telephone');
    phoneInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 10) {
            value = value.substring(0, 10);
        }
        
        if (value.length > 6) {
            value = value.replace(/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/, '$1 $2 $3 $4 $5');
        } else if (value.length > 4) {
            value = value.replace(/(\d{2})(\d{2})(\d{2})/, '$1 $2 $3');
        } else if (value.length > 2) {
            value = value.replace(/(\d{2})(\d{2})/, '$1 $2');
        }
        
        e.target.value = value;
    });
});
</script>

<?php 
require_once  '../../templates/footer.php';
?>