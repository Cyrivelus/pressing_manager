<?php
// pages/admin/profils/assigner_utilisateurs.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once('../../../fonctions/database.php');

// 1. Vérification des permissions
$roleUtilisateur = $_SESSION['role'] ?? 'Réceptionniste';
$allowedRoles = ['patron', 'Responsable', 'Administrateur'];
if (!in_array($roleUtilisateur, $allowedRoles)) {
    header('Location: ../../../pages/dashboard.php');
    exit();
}

// 2. Récupération du rôle cible
$id_role = $_GET['id'] ?? null;
if (!$id_role) {
    header('Location: index.php');
    exit();
}

// 3. Récupérer les infos du rôle et la liste des utilisateurs
try {
    // Infos du rôle
    $stmtRole = $pdo->prepare("SELECT nom_role FROM roles WHERE id_role = ?");
    $stmtRole->execute([$id_role]);
    $roleInfo = $stmtRole->fetch();

    // Liste de tous les utilisateurs actifs
    // On récupère aussi leur rôle actuel pour information
    $stmtUsers = $pdo->query("
        SELECT u.id_utilisateur, u.nom_complet, u.login_utilisateur, r.nom_role as role_actuel 
        FROM utilisateurs u 
        LEFT JOIN roles r ON u.id_role = r.id_role 
        WHERE u.est_actif = 1 
        ORDER BY u.nom_complet ASC
    ");
    $users = $stmtUsers->fetchAll();
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

$title = "Assigner des membres";
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
        :root { --primary-color: #2c3e50; }
        body { background-color: #f4f7f6; }
        .main-content { margin-left: 230px; padding: 30px; transition: all 0.3s; }
        .user-card { border-radius: 10px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .user-list-item { cursor: pointer; transition: background 0.2s; }
        .user-list-item:hover { background-color: #f8f9fa; }
        @media (max-width: 992px) { .main-content { margin-left: 0 !important; } }
    </style>
</head>
<body>

<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex align-items-center mb-4">
                    <a href="index.php" class="btn btn-outline-secondary me-3"></a>
                    <h2>Assigner des utilisateurs au rôle : <span class="text-primary"><?= htmlspecialchars($roleInfo['nom_role']) ?></span></h2>
                </div>

                <div class="card user-card">
                    <form action="traitement_assignation.php" method="POST">
                        <input type="hidden" name="id_role" value="<?= $id_role ?>">
                        
                        <div class="card-body">
                            <p class="text-muted mb-4">Cochez les utilisateurs que vous souhaitez basculer vers ce profil.</p>
                            
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="40"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                                            <th>Nom Complet</th>
                                            <th>Identifiant</th>
                                            <th>Rôle Actuel</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr class="user-list-item" onclick="toggleCheckbox(<?= $user['id_utilisateur'] ?>)">
                                            <td>
                                                <input type="checkbox" name="utilisateurs[]" 
                                                       value="<?= $user['id_utilisateur'] ?>" 
                                                       id="check_<?= $user['id_utilisateur'] ?>"
                                                       class="form-check-input user-checkbox"
                                                       onclick="event.stopPropagation();">
                                            </td>
                                            <td><strong><?= htmlspecialchars($user['nom_complet']) ?></strong></td>
                                            <td><code><?= htmlspecialchars($user['login_utilisateur']) ?></code></td>
                                            <td>
                                                <span class="badge <?= ($user['role_actuel'] == $roleInfo['nom_role']) ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= htmlspecialchars($user['role_actuel'] ?? 'Aucun') ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-white py-3">
                            <button type="submit" class="btn btn-primary px-5">
                         Valider l'assignation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Cocher/Décocher tout
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.user-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });

    // Toggle en cliquant sur la ligne
    function toggleCheckbox(id) {
        const cb = document.getElementById('check_' + id);
        cb.checked = !cb.checked;
    }
</script>

</body>
</html>