<?php
// 1. Initialisation de la session et inclusions
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../fonctions/database.php');
require_once(__DIR__ . '/../../templates/header.php');
require_once(__DIR__ . '/../../templates/navigation.php');

// 2. Traitement du formulaire (POST)
$success_msg = null;
$error_msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        $pdo->beginTransaction();
        
        if (isset($_POST['settings']) && is_array($_POST['settings'])) {
            foreach ($_POST['settings'] as $cle => $valeur) {
                $valeur = trim($valeur);
                $stmt = $pdo->prepare("UPDATE parametres_systeme SET valeur_parametre = ? WHERE cle_parametre = ?");
                $stmt->execute([$valeur, $cle]);
            }
            
            $pdo->commit();
            $success_msg = "Paramètres mis à jour avec succès.";
            
            // Journalisation
            $logStmt = $pdo->prepare("INSERT INTO logs_activite (id_utilisateur, action, table_concernée, ip_adresse, user_agent) VALUES (?, ?, ?, ?, ?)");
            $logStmt->execute([
                $_SESSION['utilisateur_id'] ?? 0,
                'Mise à jour des paramètres système',
                'parametres_systeme',
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error_msg = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}

// 3. Récupération des données pour l'affichage
// On récupère tout et on groupe par catégorie pour faciliter l'affichage dans les onglets
$stmt = $pdo->query("SELECT * FROM parametres_systeme ORDER BY categorie");
$params = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

$services = $pdo->query("SELECT * FROM categories_service ORDER BY nom_categorie")->fetchAll();
$agences = $pdo->query("SELECT * FROM agences WHERE est_actif = 1 ORDER BY nom_agence")->fetchAll();
$users = $pdo->query("SELECT u.*, r.nom_role FROM utilisateurs u LEFT JOIN roles r ON u.id_role = r.id_role WHERE u.est_actif = 1 ORDER BY u.date_creation DESC LIMIT 5")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paramètres - Pressing Manager</title>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">

<link rel="stylesheet" href="../../css/all.min.css">
    <style>
        .settings-container { margin-left: 250px; padding: 30px; transition: all 0.3s; }
        @media (max-width: 768px) { .settings-container { margin-left: 0; } }
        .list-group-item { cursor: pointer; border: none; padding: 12px 20px; }
        .list-group-item.active { background-color: #0d6efd !important; font-weight: bold; }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); margin-bottom: 20px; }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-light">

<div class="settings-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <br><br> <br>
        <h2>Configuration du Système</h2>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check me-2"></i><?= $success_msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
<br><br> <br>
    <div class="row">
        <div class="col-md-3">
            <div class="list-group shadow-sm" id="settingsTabs" role="tablist">
                <button class="list-group-item list-group-item-action active" data-bs-toggle="pill" data-bs-target="#tab-general" type="button">
                   Entreprise
                </button>
                <button class="list-group-item list-group-item-action" data-bs-toggle="pill" data-bs-target="#tab-agences" type="button">
                    Agences
                </button>
                <button class="list-group-item list-group-item-action" data-bs-toggle="pill" data-bs-target="#tab-services" type="button">
                   Services & Tarifs
                </button>
                <button class="list-group-item list-group-item-action" data-bs-toggle="pill" data-bs-target="#tab-system" type="button">
                     Système
                </button>
                <button class="list-group-item list-group-item-action text-danger" data-bs-toggle="pill" data-bs-target="#tab-backup" type="button">
                     Sauvegarde
                </button>
            </div>
        </div>

        <div class="col-md-9">
            <div class="tab-content shadow-sm bg-white p-4 rounded">
                
                <div class="tab-pane active" id="tab-general">
                    <h4 class="mb-4">Informations de l'entreprise</h4>
                    <form method="POST">
                        <div class="row g-3">
                            <?php 
                            $cat = isset($params['entreprise']) ? 'entreprise' : (isset($params['general']) ? 'general' : '');
                            if($cat && isset($params[$cat])):
                                foreach ($params[$cat] as $p): ?>
                                    <div class="col-md-6">
                                        <label class="form-label small text-uppercase fw-bold text-muted"><?= htmlspecialchars($p['description']) ?></label>
                                        <input type="text" name="settings[<?= $p['cle_parametre'] ?>]" class="form-control" value="<?= htmlspecialchars($p['valeur_parametre']) ?>">
                                    </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <button type="submit" name="update_settings" class="btn btn-primary mt-4">Enregistrer</button>
                    </form>
                </div>

                <div class="tab-pane" id="tab-agences">
                    <div class="d-flex justify-content-between mb-3">
                        <h4>Liste des Agences</h4>
                        <a href="../admin/agences/ajouter_agence.php" class="btn btn-sm btn-outline-primary">Ajouter</a>
                    </div>
                    <table class="table align-middle">
                        <thead><tr><th>Nom</th><th>Ville</th><th>Statut</th></tr></thead>
                        <tbody>
                            <?php foreach($agences as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['nom_agence']) ?></td>
                                <td><?= htmlspecialchars($a['adresse']) ?></td>
                                <td><span class="badge bg-success">Active</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane" id="tab-services">
                    <h4 class="mb-4">Services Actifs</h4>
                    <div class="list-group">
                        <?php foreach($services as $s): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center border mb-2 rounded">
                            <div>
                                <strong><?= htmlspecialchars($s['nom_categorie']) ?></strong><br>
                                <small class="text-muted">Prix base: <?= $s['prix_base'] ?> FCFA</small>
                            </div>
                            <span class="badge bg-primary rounded-pill"><?= $s['delai_standard'] ?>h</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="tab-pane" id="tab-system">
                    <h4 class="mb-4">Configuration Technique</h4>
                    <form method="POST">
                        <?php 
                        $catSys = isset($params['systeme']) ? 'systeme' : (isset($params['config']) ? 'config' : '');
                        if($catSys && isset($params[$catSys])):
                            foreach ($params[$catSys] as $p): ?>
                            <div class="mb-3">
                                <label class="form-label"><?= htmlspecialchars($p['description']) ?></label>
                                <select name="settings[<?= $p['cle_parametre'] ?>]" class="form-select">
                                    <option value="1" <?= $p['valeur_parametre'] == '1' ? 'selected' : '' ?>>Activé / Oui</option>
                                    <option value="0" <?= $p['valeur_parametre'] == '0' ? 'selected' : '' ?>>Désactivé / Non</option>
                                </select>
                            </div>
                        <?php endforeach; endif; ?>
                        <button type="submit" name="update_settings" class="btn btn-primary mt-3">Mettre à jour le système</button>
                    </form>
                </div>

                <div class="tab-pane" id="tab-backup">
                    <div class="text-center py-5">
                        <h4>Maintenance des données</h4>
                        <p class="text-muted">Sauvegardez votre base de données ou restaurez un point précédent.</p>
                        <button class="btn btn-danger">Télécharger un Backup SQL</button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="../../js/bootstrap.bundle.min.js"></script></script>

<script>
    // Script pour s'assurer que les onglets fonctionnent même si Bootstrap a un conflit
    document.querySelectorAll('[data-bs-toggle="pill"]').forEach(button => {
        button.addEventListener('click', function() {
            // Retirer 'active' de tous les boutons
            document.querySelectorAll('.list-group-item').forEach(btn => btn.classList.remove('active'));
            // Ajouter 'active' au bouton cliqué
            this.classList.add('active');
            
            // Cacher tous les onglets
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            // Afficher l'onglet ciblé
            const target = this.getAttribute('data-bs-target');
            document.querySelector(target).classList.add('active');
        });
    });
</script>

</body>
</html>