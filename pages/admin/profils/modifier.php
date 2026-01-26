<?php
// pages/admin/profils/modifier.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérification des permissions
$roleUtilisateur = $_SESSION['role'] ?? 'Réceptionniste';
$allowedRoles = ['patron', 'Responsable', 'Administrateur'];
if (!in_array($roleUtilisateur, $allowedRoles)) {
    header('Location: ../../../pages/dashboard.php');
    exit();
}

require_once('../../../fonctions/database.php');
require_once('../../../fonctions/gestion_profils.php');

// Récupération de l'ID du rôle à modifier
$id_role = $_GET['id'] ?? null;
$roleData = null;

if ($id_role) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM roles WHERE id_role = ?");
        $stmt->execute([$id_role]);
        $roleData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $_SESSION['admin_message_error'] = "Erreur : " . $e->getMessage();
    }
}

// Redirection si le rôle n'existe pas
if (!$roleData) {
    $_SESSION['admin_message_error'] = "Rôle introuvable.";
    header('Location: index.php');
    exit();
}

$title = "Modifier le Rôle";
include('../../../templates/header.php');
include('../../../templates/navigation.php');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="../../../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../../assets/css/all.min.css">
    <style>
        :root { --primary-color: #2c3e50; --accent-color: #3498db; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .main-content { margin-left: 230px; padding: 30px; transition: all 0.3s; }
        .card-edit { background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: none; }
        .form-label { font-weight: 600; color: var(--primary-color); }
        @media (max-width: 992px) { .main-content { margin-left: 0 !important; } }
    </style>
</head>
<body>
<br><br><br>
<div class="main-content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Rôles</a></li>
                        <li class="breadcrumb-item active">Modifier #<?= $id_role ?></li>
                    </ol>
                </nav>

                <div class="card card-edit">
                    <div class="card-header bg-primary text-white py-3">
                        <h4 class="mb-0">Modifier le Rôle</h4>
                    </div>
                    <div class="card-body p-4">
                        <form action="mettre_a_jour_profil.php" method="POST">
                            <input type="hidden" name="id_role" value="<?= htmlspecialchars($roleData['id_role']) ?>">

                            <div class="mb-3">
                                <label for="nom_role" class="form-label">Nom du Rôle</label>
                                <input type="text" class="form-control" id="nom_role" name="nom_role" 
                                       value="<?= htmlspecialchars($roleData['nom_role']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="niveau_permission" class="form-label">Niveau d'accès</label>
                                <select class="form-select" id="niveau_permission" name="niveau_permission">
                                    <?php for($i=1; $i<=10; $i++): ?>
                                        <option value="<?= $i ?>" <?= ($roleData['niveau_permission'] == $i) ? 'selected' : '' ?>>
                                            Niveau <?= $i ?> <?= ($i == 10) ? '(Administrateur)' : '' ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($roleData['description']) ?></textarea>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <a href="index.php" class="btn btn-outline-secondary">
                                     Annuler
                                </a>
                                <button type="submit" class="btn btn-primary px-4">
                                     Enregistrer les modifications
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="../../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>