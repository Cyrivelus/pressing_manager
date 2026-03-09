<?php
// pages/admin/profils/ajouter.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Configuration et Includes ---
$title = "Ajouter un Rôle";
$admin_style = true;

// Inclusion de la base de données selon votre nouvelle arborescence
// Note : Ajustez le nombre de ../ selon l'emplacement réel
require_once('../../../fonctions/database.php'); 

// Vérification des permissions (Sécurité)
if (!isset($_SESSION['id_role']) || $_SESSION['id_role'] > 2) { // Exemple: seul admin/patron
     // header('Location: ../../../index.php'); exit(); 
}

// Récupérer les messages flash
$errorMessage = $_SESSION['admin_message_error'] ?? null;
$successMessage = $_SESSION['admin_message_success'] ?? null;
unset($_SESSION['admin_message_error'], $_SESSION['admin_message_success']);

// Liste des profils types pour le pressing
$profilsProposes = [
    'ADMINISTRATEUR' => 'Accès total à la configuration et aux finances',
    'GERANT / PATRON' => 'Supervision complète de l\'agence',
    'RESPONSABLE TECHNIQUE' => 'Gestion de la production et du nettoyage',
    'RECEPTIONNISTE' => 'Accueil client, dépôt et retrait des tickets',
    'CAISSIER' => 'Gestion exclusive des paiements et de la caisse',
    'LIVREUR' => 'Gestion des collectes et livraisons à domicile',
    'COMPTABLE' => 'Accès aux rapports financiers et dépenses'
];

include('../../../templates/header.php');
include('../../../templates/navigation.php');
?>
<br><br><br>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-user-tag me-2"></i>Nouveau Rôle utilisateur</h1>
    </div>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($errorMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="post" action="enregistrer_profil.php">
                        <?php if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } ?>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                        <div class="mb-3">
                            <label for="nom_role" class="form-label fw-bold">Nom du Rôle</label>
                            <input type="text" class="form-control" id="nom_role" name="nom_role" placeholder="ex: Réceptionniste" required>
                        </div>

                        <div class="mb-3">
                            <label for="niveau_permission" class="form-label fw-bold">Niveau d'accès (1 à 10)</label>
                            <input type="number" class="form-control" id="niveau_permission" name="niveau_permission" min="1" max="10" value="1" required>
                            <div class="form-text">1 = Accès limité, 10 = Accès total (Admin)</div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-bold">Description des responsabilités</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Quelles sont les tâches de ce rôle ?"></textarea>
                        </div>

                        <hr>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                 Créer le rôle
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-info shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-lightbulb me-2"></i>Modèles de rôles</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Sélectionnez un modèle pour pré-remplir le formulaire :</p>
                    <div class="list-group">
                        <?php foreach ($profilsProposes as $nom => $desc): ?>
                            <button type="button" 
                                    class="list-group-item list-group-item-action" 
                                    onclick="prefillRole('<?= addslashes($nom) ?>', '<?= addslashes($desc) ?>')">
                                <strong><?= $nom ?></strong><br>
                                <small class="text-muted"><?= $desc ?></small>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function prefillRole(nom, desc) {
    document.getElementById('nom_role').value = nom;
    document.getElementById('description').value = desc;
    
    // Logique de niveau automatique
    let niveau = 1;
    if(nom.includes('ADMIN')) niveau = 10;
    else if(nom.includes('PATRON') || nom.includes('DIRECTEUR')) niveau = 9;
    else if(nom.includes('GERANT') || nom.includes('RESPONSABLE')) niveau = 7;
    
    document.getElementById('niveau_permission').value = niveau;
    
    // Petit effet visuel
    const form = document.getElementById('nom_role');
    form.style.backgroundColor = '#e8f0fe';
    setTimeout(() => form.style.backgroundColor = '', 500);
}
</script>
