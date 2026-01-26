<?php
// Inclure les fichiers de fonctions
require_once '../../fonctions/database.php';
require_once '../../fonctions/validation.php';

// Vérifier si l'utilisateur est connecté et a les permissions
session_start();
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Vérifier les permissions (seulement gestionnaire_stock, patron, etc.)
$allowed_roles = ['patron', 'gestionnaire_stock', 'receptionniste'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header('Location: ../index.php?error=Permission non autorisée');
    exit;
}

$titre = 'Ajouter un nouveau fournisseur';
$message = '';
$erreurs = [];

// Données par défaut pour le formulaire
$formData = [
    'nom_fournisseur' => '',
    'contact' => '',
    'telephone' => '',
    'email' => '',
    'adresse' => '',
    'categorie' => 'produit_nettoyage',
    'solde_du' => '0.00'
];

// Traitement du formulaire si la méthode est POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et assainir les données du formulaire
    $formData = [
        'nom_fournisseur' => trim($_POST['nom_fournisseur'] ?? ''),
        'contact' => trim($_POST['contact'] ?? ''),
        'telephone' => trim($_POST['telephone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'adresse' => trim($_POST['adresse'] ?? ''),
        'categorie' => $_POST['categorie'] ?? 'produit_nettoyage',
        'solde_du' => trim($_POST['solde_du'] ?? '0.00')
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

    // Si aucune erreur de validation, procéder à l'insertion
    if (empty($erreurs)) {
        try {
            $sql = "INSERT INTO fournisseurs 
                    (nom_fournisseur, contact, telephone, email, adresse, categorie, solde_du, est_actif) 
                    VALUES (:nom_fournisseur, :contact, :telephone, :email, :adresse, :categorie, :solde_du, 1)";
            
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':nom_fournisseur' => $formData['nom_fournisseur'],
                ':contact' => $formData['contact'] ?: null,
                ':telephone' => $formData['telephone'] ?: null,
                ':email' => $formData['email'] ?: null,
                ':adresse' => $formData['adresse'] ?: null,
                ':categorie' => $formData['categorie'],
                ':solde_du' => $formData['solde_du']
            ]);
            
            $message = "Le fournisseur a été ajouté avec succès.";
            
            // Optionnel : Redirection vers la page d'index après l'ajout
            // header('Location: index.php?success=1');
            // exit();
            
        } catch (PDOException $e) {
            $erreurs[] = "Erreur lors de l'ajout du fournisseur: " . $e->getMessage();
        }
    }
}

// Inclusion des templates
require_once('../../templates/header.php');
require_once('../../templates/navigation.php');
?>
<br> <br> <br>
<div class="container-fluid content-container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Ajouter un nouveau fournisseur</h3>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($erreurs)): ?>
                        <div class="alert alert-danger">
                            <h5>Erreurs:</h5>
                            <ul class="mb-0">
                                <?php foreach ($erreurs as $erreur): ?>
                                    <li><?= htmlspecialchars($erreur) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="" method="post">
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="mb-3 border-bottom pb-2">Informations principales</h5>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="nom_fournisseur">Nom du fournisseur *</label>
                                <input type="text" class="form-control" id="nom_fournisseur" name="nom_fournisseur" 
                                       required value="<?= htmlspecialchars($formData['nom_fournisseur']) ?>">
                                <small class="form-text text-muted">Nom complet de l'entreprise ou du fournisseur</small>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="contact">Personne de contact</label>
                                <input type="text" class="form-control" id="contact" name="contact" 
                                       value="<?= htmlspecialchars($formData['contact']) ?>">
                                <small class="form-text text-muted">Nom de la personne à contacter</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="telephone">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" 
                                       value="<?= htmlspecialchars($formData['telephone']) ?>">
                            </div>

                            <div class="form-group col-md-6">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($formData['email']) ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="categorie">Catégorie</label>
                                <select class="form-control" id="categorie" name="categorie" style="height: auto; padding: 10px 15px;">
    <option value="produit_nettoyage" <?= $formData['categorie'] == 'produit_nettoyage' ? 'selected' : '' ?>>Produits de nettoyage</option>
    <option value="equipement" <?= $formData['categorie'] == 'equipement' ? 'selected' : '' ?>>Équipement</option>
    <option value="emballage" <?= $formData['categorie'] == 'emballage' ? 'selected' : '' ?>>Emballage</option>
    <option value="autre" <?= $formData['categorie'] == 'autre' ? 'selected' : '' ?>>Autre</option>
</select>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="solde_du">Solde dû (FCFA)</label>
                                <input type="number" class="form-control" id="solde_du" name="solde_du" 
                                       step="0.01" min="0" value="<?= htmlspecialchars($formData['solde_du']) ?>">
                                <small class="form-text text-muted">Montant actuellement dû au fournisseur</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="adresse">Adresse</label>
                                <textarea class="form-control" id="adresse" name="adresse" 
                                          rows="3"><?= htmlspecialchars($formData['adresse']) ?></textarea>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-success">
                                    Enregistrer le fournisseur
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    Annuler
                                </a>
                                <button type="reset" class="btn btn-outline-secondary">
                                    Réinitialiser
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validation côté client
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const nomInput = document.getElementById('nom_fournisseur');
    
    form.addEventListener('submit', function(event) {
        let isValid = true;
        let errorMessage = '';
        
        // Validation du nom
        if (nomInput.value.trim() === '') {
            isValid = false;
            errorMessage += 'Le nom du fournisseur est obligatoire.\n';
            nomInput.classList.add('is-invalid');
        } else {
            nomInput.classList.remove('is-invalid');
        }
        
        // Validation de l'email si fourni
        const emailInput = document.getElementById('email');
        if (emailInput.value.trim() !== '') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailInput.value)) {
                isValid = false;
                errorMessage += 'L\'adresse email n\'est pas valide.\n';
                emailInput.classList.add('is-invalid');
            } else {
                emailInput.classList.remove('is-invalid');
            }
        }
        
        // Validation du solde
        const soldeInput = document.getElementById('solde_du');
        if (soldeInput.value.trim() !== '') {
            const soldeValue = parseFloat(soldeInput.value);
            if (isNaN(soldeValue) || soldeValue < 0) {
                isValid = false;
                errorMessage += 'Le solde doit être un nombre positif.\n';
                soldeInput.classList.add('is-invalid');
            } else {
                soldeInput.classList.remove('is-invalid');
            }
        }
        
        if (!isValid) {
            event.preventDefault();
            alert('Veuillez corriger les erreurs suivantes:\n\n' + errorMessage);
        }
    });
    
    // Validation en temps réel
    nomInput.addEventListener('blur', function() {
        if (this.value.trim() === '') {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });
});
</script>

<?php 
require_once('../../templates/footer.php');
?>