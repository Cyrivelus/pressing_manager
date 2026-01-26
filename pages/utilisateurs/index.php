<?php
// pages/utilisateurs/index.php
session_start();

// 1. Inclusion des fichiers de configuration et fonctions
require_once('../../fonctions/database.php'); // Contient votre $pdo

// 3. Récupération des utilisateurs avec leur rôle (Jointure SQL)
try {
    $query = "SELECT u.*, r.nom_role 
              FROM utilisateurs u 
              LEFT JOIN roles r ON u.id_role = r.id_role 
              ORDER BY u.nom_complet ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $messageErreur = "Erreur lors de la récupération : " . $e->getMessage();
}

$titre = 'Gestion des Utilisateurs';
require_once('../../templates/header.php');
require_once('../../templates/navigation.php');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BailCompta 360 | <?= htmlspecialchars($titre) ?></title>
    <link rel="stylesheet" href="../../css/bootstrap-3.4.1.min.css">
    <link rel="stylesheet" href="../../css/monstyle.css">
</head>
<body>

<br> <br> <br>
<div class="container">
    <div class="page-header">
        <div class="pull-right">
            <a href="ajouter.php" class="btn btn-success"> Nouvel Utilisateur</a>
        </div>
        <h1> <?= htmlspecialchars($titre) ?></h1>
    </div>

    <?php if (isset($messageErreur)): ?>
        <div class="alert alert-danger"><?= $messageErreur ?></div>
    <?php endif; ?>

    <div class="panel panel-default">
        <div class="panel-heading">Liste des membres du personnel</div>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Nom Complet</th>
                        <th>Login</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Agence</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($utilisateurs)): ?>
                        <tr><td colspan="7" class="text-center">Aucun utilisateur trouvé.</td></tr>
                    <?php else: ?>
                        <?php foreach ($utilisateurs as $user): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($user['nom_complet']) ?></strong></td>
                            <td><?= htmlspecialchars($user['login_utilisateur']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><span class="label label-info"><?= htmlspecialchars($user['nom_role'] ?? 'Aucun') ?></span></td>
                            <td><?= htmlspecialchars($user['code_agence'] ?? 'N/A') ?></td>
                            <td>
                                <?php if ($user['est_actif']): ?>
                                    <span class="text-success"> Actif</span>
                                <?php else: ?>
                                    <span class="text-danger"> Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="modifier.php?id=<?= $user['id_utilisateur'] ?>" class="btn btn-xs btn-primary">modifier</a>
                                <a href="supprimer.php?id=<?= $user['id_utilisateur'] ?>" class="btn btn-xs btn-danger" onclick="return confirm('Supprimer cet utilisateur ?')">Supprimer</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="../../js/jquery-3.6.0.js" defer></script>
<script src="../../js/bootstrap-3.4.1.min.js" defer></script>
</body>
</html>

<?php require_once('../../templates/footer.php'); ?>