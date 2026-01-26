<?php
// pages/agences/ajouter_agence.php
session_start();

// 1. Inclure les fichiers de configuration et de base de données
require_once '../../../fonctions/database.php'; // Vérifiez que ce fichier crée une variable $pdo

/**
 * Récupère les utilisateurs pour la liste des responsables
 */
function getListeUtilisateurs($pdo) {
    $stmt = $pdo->query("SELECT id_utilisateur, nom_complet FROM utilisateurs WHERE est_actif = 1 ORDER BY nom_complet");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Initialisation des messages
$message = '';
$message_type = '';

// 2. Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_agence = trim(htmlspecialchars($_POST['nom_agence']));
    $adresse = trim(htmlspecialchars($_POST['adresse']));
    $telephone = trim(htmlspecialchars($_POST['telephone']));
    $email = trim(htmlspecialchars($_POST['email']));
    $responsable_id = !empty($_POST['responsable_id']) ? $_POST['responsable_id'] : null;

    if (empty($nom_agence)) {
        $message = "Le nom de l'agence est obligatoire.";
        $message_type = 'warning';
    } else {
        try {
            $sql = "INSERT INTO agences (nom_agence, adresse, telephone, email, responsable_id, date_ouverture) 
                    VALUES (:nom, :adresse, :tel, :email, :resp, CURDATE())";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                ':nom' => $nom_agence,
                ':adresse' => $adresse,
                ':tel' => $telephone,
                ':email' => $email,
                ':resp' => $responsable_id
            ]);

            if ($result) {
                $_SESSION['success'] = "L'agence '$nom_agence' a été créée avec succès.";
                header('Location: liste_agences.php');
                exit();
            }
        } catch (PDOException $e) {
            $message = "Erreur lors de l'ajout : " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

$utilisateurs = getListeUtilisateurs($pdo);

// 3. Inclure les templates (selon votre nouvelle structure)
include '../../../templates/header.php';
include '../../../templates/navigation.php';
?>

<br><br><br>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">🏠 Nouvelle Agence / Point de Vente</h3>
                </div>
                <div class="card-body">
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom de l'agence <span class="text-danger">*</span></label>
                                <input type="text" name="nom_agence" class="form-control" placeholder="ex: Pressing Palace Centre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Téléphone</label>
                                <input type="text" name="telephone" class="form-control" placeholder="ex: +225 01020304">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email de l'agence</label>
                            <input type="email" name="email" class="form-control" placeholder="agence@pressing.com">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Adresse physique</label>
                            <textarea name="adresse" class="form-control" rows="2" placeholder="Quartier, Rue, Immeuble..."></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Responsable d'agence</label>
                            <select name="responsable_id" class="form-select">
                                <option value="">-- Choisir un responsable --</option>
                                <?php foreach ($utilisateurs as $user): ?>
                                    <option value="<?= $user['id_utilisateur'] ?>">
                                        <?= htmlspecialchars($user['nom_complet']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">L'utilisateur sélectionné pourra gérer les rapports de cette agence.</div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="javascript:history.back()" class="btn btn-outline-secondary">
    <- Retour
</a>
                            <button type="submit" class="btn btn-primary">
                                Enregistrer l'agence
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../../templates/footer.php'; ?>