<?php
session_start();
require_once __DIR__ . '/../../../fonctions/database.php';


// 1. Récupération de l'utilisateur
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: liste.php?error=ID manquant');
    exit;
}

$query = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = ?");
$query->execute([$id]);
$user = $query->fetch();

if (!$user) {
    header('Location: liste.php?error=Utilisateur introuvable');
    exit;
}

// 2. Récupération des rôles pour le menu déroulant
$roles = $pdo->query("SELECT id_role, nom_role FROM roles ORDER BY nom_role ASC")->fetchAll();

// 3. Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom_complet'];
    $login = $_POST['login_utilisateur'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $id_role = $_POST['id_role'];
    $est_actif = isset($_POST['est_actif']) ? 1 : 0;
    
    // Si le mot de passe est rempli, on le met à jour, sinon on garde l'ancien
    if (!empty($_POST['nouveau_password'])) {
        $password = password_hash($_POST['nouveau_password'], PASSWORD_DEFAULT);
        $sql = "UPDATE utilisateurs SET nom_complet=?, login_utilisateur=?, email=?, telephone=?, id_role=?, est_actif=?, mot_de_passe=? WHERE id_utilisateur=?";
        $params = [$nom, $login, $email, $telephone, $id_role, $est_actif, $password, $id];
    } else {
        $sql = "UPDATE utilisateurs SET nom_complet=?, login_utilisateur=?, email=?, telephone=?, id_role=?, est_actif=? WHERE id_utilisateur=?";
        $params = [$nom, $login, $email, $telephone, $id_role, $est_actif, $id];
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        header('Location: liste.php?success=Utilisateur mis à jour');
        exit;
    } catch (PDOException $e) {
        $error = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}
require_once('../../../templates/header.php');
require_once('../../../templates/navigation.php');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Utilisateur - Pressing Manager</title>
    <link rel="stylesheet" href="../../../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../../assets/css/style.css">
</head>
<body class="bg-light">

<br> <br> <br>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between">
                    <h4 class="mb-0">Modifier l'utilisateur : <?= htmlspecialchars($user['nom_complet']) ?></h4>
                    <a href="liste.php" class="btn btn-sm btn-light">Retour</a>
                </div>
                <div class="card-body">
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom Complet</label>
                                <input type="text" name="nom_complet" class="form-control" value="<?= htmlspecialchars($user['nom_complet']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Identifiant (Login)</label>
                                <input type="text" name="login_utilisateur" class="form-control" value="<?= htmlspecialchars($user['login_utilisateur']) ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Téléphone</label>
                                <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($user['telephone']) ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rôle</label>
                                <select name="id_role" class="form-select" required>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['id_role'] ?>" <?= $user['id_role'] == $role['id_role'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($role['nom_role']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Statut du compte</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="est_actif" id="est_actif" <?= $user['est_actif'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="est_actif"> <br> Compte actif</label>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="mb-3">
                            <label class="form-label text-danger">Nouveau mot de passe (laisser vide pour ne pas modifier)</label>
                            <input type="password" name="nouveau_password" class="form-control" placeholder="********">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<?php 
require_once('../../../templates/footer.php');
?>