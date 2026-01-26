<?php
// pages/admin/utilisateurs/modifier.php
session_start();
ob_start(); // Pour éviter les erreurs headers already sent

// Vérifier l'authentification et les permissions
if (!isset($_SESSION['utilisateur_id']) || ($_SESSION['role'] !== 'patron' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../../../index.php?error=Accès non autorisé");
    exit();
}

require_once '../../fonctions/database.php';
require_once '../../fonctions/gestion_utilisateurs.php';
require_once '../../fonctions/gestion_profils.php';

// Vérifier si l'ID de l'utilisateur est passé en GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=ID utilisateur manquant");
    exit();
}

$utilisateurId = intval($_GET['id']);

// Récupérer l'utilisateur à modifier
$utilisateur = getUtilisateurParId($pdo, $utilisateurId);

if (!$utilisateur) {
    $_SESSION['error_message'] = "Utilisateur non trouvé.";
    header("Location: index.php");
    exit();
}

// Récupérer tous les rôles disponibles
$roles = getRolesDisponibles($pdo);

// Variables pour les messages
$message = '';
$message_type = '';

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $nom_complet = trim($_POST['nom_complet'] ?? '');
    $login_utilisateur = trim($_POST['login_utilisateur'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $id_role = intval($_POST['id_role'] ?? 0);
    $code_agence = trim($_POST['code_agence'] ?? '');
    $est_actif = isset($_POST['est_actif']) ? 1 : 0;
    $changer_mdp = isset($_POST['changer_mdp']) ? 1 : 0;
    $nouveau_mot_de_passe = $_POST['nouveau_mot_de_passe'] ?? '';
    $confirmation_mot_de_passe = $_POST['confirmation_mot_de_passe'] ?? '';
    
    // Validation des données
    $erreurs = [];
    
    if (empty($nom_complet)) {
        $erreurs[] = "Le nom complet est obligatoire.";
    }
    
    if (empty($login_utilisateur)) {
        $erreurs[] = "Le nom d'utilisateur est obligatoire.";
    } else {
        // Vérifier si le login existe déjà pour un autre utilisateur
        $sqlCheck = "SELECT COUNT(*) FROM utilisateurs WHERE login_utilisateur = :login AND id_utilisateur != :id";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([':login' => $login_utilisateur, ':id' => $utilisateurId]);
        
        if ($stmtCheck->fetchColumn() > 0) {
            $erreurs[] = "Ce nom d'utilisateur est déjà utilisé par un autre utilisateur.";
        }
    }
    
    if (empty($email)) {
        $erreurs[] = "L'adresse email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = "L'adresse email n'est pas valide.";
    }
    
    if ($id_role <= 0) {
        $erreurs[] = "Le rôle est obligatoire.";
    }
    
    if ($changer_mdp) {
        if (empty($nouveau_mot_de_passe)) {
            $erreurs[] = "Le nouveau mot de passe est obligatoire.";
        } elseif (strlen($nouveau_mot_de_passe) < 6) {
            $erreurs[] = "Le mot de passe doit contenir au moins 6 caractères.";
        } elseif ($nouveau_mot_de_passe !== $confirmation_mot_de_passe) {
            $erreurs[] = "Les mots de passe ne correspondent pas.";
        }
    }
    
    // Si aucune erreur, procéder à la modification
    if (empty($erreurs)) {
        try {
            // Préparer les données pour la modification
            $data = [
                'nom_complet' => $nom_complet,
                'login_utilisateur' => $login_utilisateur,
                'email' => $email,
                'telephone' => $telephone,
                'id_role' => $id_role,
                'code_agence' => $code_agence,
                'est_actif' => $est_actif
            ];
            
            // Modifier l'utilisateur
            $result = modifierUtilisateur($pdo, $utilisateurId, $data);
            
            if ($result['success']) {
                // Si changement de mot de passe demandé
                if ($changer_mdp) {
                    $result_mdp = modifierMotDePasse($pdo, $utilisateurId, $nouveau_mot_de_passe);
                    
                    if ($result_mdp['success']) {
                        $message = "Utilisateur et mot de passe modifiés avec succès!";
                    } else {
                        $message = "Utilisateur modifié mais erreur lors du changement de mot de passe: " . $result_mdp['message'];
                        $message_type = 'warning';
                    }
                } else {
                    $message = "Utilisateur modifié avec succès!";
                }
                
                $message_type = 'success';
                
                // Recharger les données de l'utilisateur
                $utilisateur = getUtilisateurParId($pdo, $utilisateurId);
            } else {
                $message = "Erreur lors de la modification: " . $result['message'];
                $message_type = 'danger';
            }
            
        } catch (Exception $e) {
            $message = "Erreur technique: " . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        $message = "Erreurs dans le formulaire:<br>" . implode("<br>", $erreurs);
        $message_type = 'danger';
    }
}

// Titre de la page
$title = "Modifier l'utilisateur";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pressing Manager | <?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="../../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/all.min.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .password-field {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
        }
        .required::after {
            content: " *";
            color: #dc3545;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
    </style>
</head>
<body>
<?php 
// Maintenant on vide le buffer et on inclut le header
ob_end_flush();
include('../../templates/header.php'); 
include('../../templates/navigation.php'); 
?>

<br><br><br>

<div class="container-fluid py-4">
    <div class="form-container">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    
                    Modifier l'utilisateur #<?= $utilisateur['id_utilisateur'] ?>
                </h4>
            </div>
            
            <div class="card-body">
                <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" id="formModifierUtilisateur">
                    <input type="hidden" name="utilisateur_id" value="<?= $utilisateur['id_utilisateur'] ?>">
                    
                    <h5 class="mb-3 text-primary">
                    
                        Informations personnelles
                    </h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nom_complet" class="form-label required">Nom complet</label>
                            <input type="text" class="form-control" id="nom_complet" name="nom_complet" 
                                   value="<?= htmlspecialchars($utilisateur['nom_complet']) ?>" 
                                   required maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label for="login_utilisateur" class="form-label required">Nom d'utilisateur</label>
                            <input type="text" class="form-control" id="login_utilisateur" name="login_utilisateur" 
                                   value="<?= htmlspecialchars($utilisateur['login_utilisateur']) ?>" 
                                   required maxlength="50">
                            <div class="form-text">Utilisé pour se connecter</div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Adresse email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($utilisateur['email'] ?? '') ?>" 
                                    maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" 
                                   value="<?= htmlspecialchars($utilisateur['telephone'] ?? '') ?>" 
                                   maxlength="20">
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="mb-3 text-primary">
                       
                        Rôle et permissions
                    </h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="id_role" class="form-label required">Rôle</label>
                            <select class="form-select" id="id_role" name="id_role" required>
                                <option value="">Sélectionner un rôle...</option>
                                <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['id_role'] ?>" 
                                    <?= ($utilisateur['id_role'] == $role['id_role']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role['nom_role']) ?> 
                                    (Niveau: <?= $role['niveau_permission'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Détermine les permissions de l'utilisateur</div>
                        </div>
                        <div class="col-md-6">
                            <label for="code_agence" class="form-label">Code agence (optionnel)</label>
                            <input type="text" class="form-control" id="code_agence" name="code_agence" 
                                   value="<?= htmlspecialchars($utilisateur['code_agence'] ?? '') ?>" 
                                   maxlength="20">
                            <div class="form-text">Code d'agence assignée (si applicable)</div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="est_actif" 
                                       id="est_actif" value="1" 
                                       <?= $utilisateur['est_actif'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="est_actif">
                                   <br> Compte actif
                                </label>
                                <div class="form-text">Décocher pour désactiver le compte</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="changer_mdp" 
                                       id="changer_mdp" value="1">
                                <label class="form-check-label" for="changer_mdp">
                                   <br> Changer le mot de passe
                                </label>
                                <div class="form-text"> Cocher pour modifier le mot de passe</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="password-fields" id="passwordFields" style="display: none;">
                        <hr class="my-4">
                        
                        <h5 class="mb-3 text-primary">
                           
                            Nouveau mot de passe
                        </h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6 password-field">
                                <label for="nouveau_mot_de_passe" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="nouveau_mot_de_passe" 
                                       name="nouveau_mot_de_passe" maxlength="255">
                                <button type="button" class="password-toggle" onclick="togglePassword('nouveau_mot_de_passe')">
                                 
                                </button>
                                <div class="form-text">Minimum 6 caractères</div>
                            </div>
                            <div class="col-md-6 password-field">
                                <label for="confirmation_mot_de_passe" class="form-label">Confirmation</label>
                                <input type="password" class="form-control" id="confirmation_mot_de_passe" 
                                       name="confirmation_mot_de_passe" maxlength="255">
                                <button type="button" class="password-toggle" onclick="togglePassword('confirmation_mot_de_passe')">
                                  
                                <div class="form-text">Ressaisir le mot de passe</div>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex justify-content-between">
                        <div>
                            <a href="index.php" class="btn btn-secondary">
                                 Annuler
                            </a>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                 Enregistrer les modifications
                            </button>
                        </div>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title text-muted">
                         
                            Informations supplémentaires
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <strong>Date de création:</strong> 
                                    <?= !empty($utilisateur['date_creation']) ? date('d/m/Y H:i', strtotime($utilisateur['date_creation'])) : 'Non définie' ?>
                                </p>
                                <p class="mb-1">
                                    <strong>Dernière connexion:</strong> 
                                    <?= !empty($utilisateur['derniere_connexion']) ? date('d/m/Y H:i', strtotime($utilisateur['derniere_connexion'])) : 'Jamais' ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <strong>Rôle actuel:</strong> 
                                    <span class="badge bg-primary">
                                        <?= htmlspecialchars($utilisateur['nom_role'] ?? 'Non défini') ?>
                                    </span>
                                </p>
                                <p class="mb-1">
                                    <strong>Statut:</strong> 
                                    <span class="badge bg-<?= $utilisateur['est_actif'] ? 'success' : 'danger' ?>">
                                        <?= $utilisateur['est_actif'] ? 'Actif' : 'Inactif' ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../../js/jquery.min.js"></script>
<script src="../../../js/bootstrap.bundle.min.js"></script>

<script>
    // Afficher/masquer les champs de mot de passe
    document.getElementById('changer_mdp').addEventListener('change', function() {
        const passwordFields = document.getElementById('passwordFields');
        if (this.checked) {
            passwordFields.style.display = 'block';
            // Rendre les champs requis
            document.getElementById('nouveau_mot_de_passe').required = true;
            document.getElementById('confirmation_mot_de_passe').required = true;
        } else {
            passwordFields.style.display = 'none';
            // Enlever l'attribut requis
            document.getElementById('nouveau_mot_de_passe').required = false;
            document.getElementById('confirmation_mot_de_passe').required = false;
            // Vider les champs
            document.getElementById('nouveau_mot_de_passe').value = '';
            document.getElementById('confirmation_mot_de_passe').value = '';
        }
    });
    
    // Fonction pour basculer la visibilité du mot de passe
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const toggleBtn = field.nextElementSibling;
        const icon = toggleBtn.querySelector('i');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            field.type = 'password';
            icon.className = 'fas fa-eye';
        }
    }
    
    // Validation du formulaire
    document.getElementById('formModifierUtilisateur').addEventListener('submit', function(e) {
        const changerMdp = document.getElementById('changer_mdp').checked;
        const nouveauMdp = document.getElementById('nouveau_mot_de_passe').value;
        const confirmationMdp = document.getElementById('confirmation_mot_de_passe').value;
        
        if (changerMdp) {
            if (nouveauMdp.length < 6) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 6 caractères.');
                document.getElementById('nouveau_mot_de_passe').focus();
                return false;
            }
            
            if (nouveauMdp !== confirmationMdp) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                document.getElementById('confirmation_mot_de_passe').focus();
                return false;
            }
        }
        
        // Validation supplémentaire
        const login = document.getElementById('login_utilisateur').value.trim();
        const email = document.getElementById('email').value.trim();
        const role = document.getElementById('id_role').value;
        
        if (login === '') {
            e.preventDefault();
            alert('Le nom d\'utilisateur est obligatoire.');
            return false;
        }
        
        if (email === '') {
            e.preventDefault();
            alert('L\'adresse email est obligatoire.');
            return false;
        }
        
        // Validation email simple
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Veuillez entrer une adresse email valide.');
            return false;
        }
        
        if (role === '') {
            e.preventDefault();
            alert('Le rôle est obligatoire.');
            return false;
        }
    });
    
    // Auto-fermer les alertes après 5 secondes
    $(document).ready(function() {
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    });
</script>

<?php include('../../templates/footer.php'); ?>
</body>
</html>