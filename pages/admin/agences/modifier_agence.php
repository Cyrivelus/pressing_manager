<?php
// pages/admin/agences/modifier_agence.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Utilisation de chemins absolus pour éviter les erreurs "No such file"
require_once __DIR__ . '/../../../fonctions/database.php';
require_once __DIR__ . '/../../../fonctions/gestion_agences.php';

// Vérification Sécurité
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'patron') {
    header("Location: ../../../index.php");
    exit();
}

$message = '';
$message_type = '';
$agence_data = null;

// 1. TRAITEMENT DU FORMULAIRE (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_agence'])) {
    $id_agence = $_POST['id_agence'];
    $nom_agence = htmlspecialchars($_POST['nom_agence']);
    $adresse = htmlspecialchars($_POST['adresse']);

    if (modifierAgence($pdo, $id_agence, $nom_agence, $adresse)) {
        $_SESSION['success'] = "Agence mise à jour !";
        header("Location: liste_agences.php");
        exit();
    } else {
        $message = "Erreur lors de la mise à jour.";
        $message_type = "danger";
    }
}

// 2. CHARGEMENT DES DONNÉES (GET)
if (isset($_GET['id'])) {
    $agence_data = getAgenceById($pdo, $_GET['id']);
}

if (!$agence_data) {
    die("Agence introuvable.");
}

// Inclusion Header
include __DIR__ . '/../../../templates/header.php';
include __DIR__ . '/../../../templates/navigation.php';
?>
&nbsp;&nbsp;&nbsp;
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3>Modifier l'agence : <?= htmlspecialchars($agence_data['nom_agence']) ?></h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="id_agence" value="<?= $agence_data['id_agence'] ?>">
                
                <div class="mb-3">
                    <label>Nom de l'agence</label>
                    <input type="text" name="nom_agence" class="form-control" 
                           value="<?= htmlspecialchars($agence_data['nom_agence']) ?>" required>
                </div>

                <div class="mb-3">
                    <label>Adresse</label>
                    <textarea name="adresse" class="form-control"><?= htmlspecialchars($agence_data['adresse']) ?></textarea>
                </div>

                <button type="submit" name="modifier_agence" class="btn btn-success">Enregistrer les modifications</button>
                <a href="liste_agences.php" class="btn btn-secondary">Retour</a>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../templates/footer.php'; ?>