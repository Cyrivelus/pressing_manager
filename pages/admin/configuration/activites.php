<?php
// pages/admin/configuration/activites.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../fonctions/database.php';

// Vérifier les permissions
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['patron', 'admin', 'gestionnaire_hotel', 'gestionnaire_commerce'])) {
    header('Location: ../../../pages/acces_refuse.php');
    exit();
}

$message = '';
$message_type = '';

// Initialiser les variables pour éviter les erreurs
$activites = [];
$utilisateurs = [];
$assignations = [];
$statistiques = [];

// Fonction pour créer la table si elle n'existe pas avec gestion d'erreurs
function createActivitesTable($pdo) {
    // D'abord, vérifier si la table existe
    $tableExists = false;
    try {
        $result = $pdo->query("SHOW TABLES LIKE 'activites_config'");
        $tableExists = $result->rowCount() > 0;
    } catch (Exception $e) {
        // Table n'existe pas encore
    }
    
    if (!$tableExists) {
        try {
            // Créer la table avec les champs de base
            $sql = "CREATE TABLE IF NOT EXISTS activites_config (
                id_activite INT PRIMARY KEY AUTO_INCREMENT,
                type_activite VARCHAR(20) NOT NULL UNIQUE,
                nom_activite VARCHAR(100) NOT NULL,
                description TEXT,
                actif BOOLEAN DEFAULT TRUE,
                couleur_theme VARCHAR(7) DEFAULT '#3498db',
                date_activation DATETIME,
                date_desactivation DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $pdo->exec($sql);
            
            // Ajouter la colonne ordre_affichage si elle n'existe pas
            try {
                $pdo->exec("ALTER TABLE activites_config ADD COLUMN ordre_affichage INT DEFAULT 0 AFTER couleur_theme");
            } catch (Exception $e) {
                // La colonne existe déjà, ignorer l'erreur
            }
            
            // Ajouter la colonne updated_at si elle n'existe pas
            try {
                $pdo->exec("ALTER TABLE activites_config ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            } catch (Exception $e) {
                // La colonne existe déjà, ignorer l'erreur
            }
            
        } catch (Exception $e) {
            error_log("Erreur création table activites_config: " . $e->getMessage());
        }
    }
    
    // Créer la table utilisateur_activites si elle n'existe pas
    try {
        $sql2 = "CREATE TABLE IF NOT EXISTS utilisateur_activites (
            id INT PRIMARY KEY AUTO_INCREMENT,
            id_utilisateur INT NOT NULL,
            type_activite VARCHAR(20) NOT NULL,
            FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
            UNIQUE KEY unique_user_activity (id_utilisateur, type_activite)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql2);
        
    } catch (Exception $e) {
        error_log("Erreur création table utilisateur_activites: " . $e->getMessage());
    }
    
    // Insérer les activités par défaut si nécessaire
    try {
        $check = $pdo->query("SELECT COUNT(*) FROM activites_config")->fetchColumn();
        if ($check == 0) {
            $default_activities = [
                ['type_activite' => 'pressing', 'nom_activite' => 'Pressing & Blanchisserie', 
                 'description' => 'Service de nettoyage et pressing professionnel', 'couleur_theme' => '#e74c3c', 'ordre_affichage' => 1],
                ['type_activite' => 'commerce', 'nom_activite' => 'Boutique & Commerce', 
                 'description' => 'Vente de produits textiles et accessoires', 'couleur_theme' => '#27ae60', 'ordre_affichage' => 2],
                ['type_activite' => 'hotel', 'nom_activite' => 'Hôtel & Services', 
                 'description' => 'Services hôteliers et réservations', 'couleur_theme' => '#f39c12', 'ordre_affichage' => 3],
                ['type_activite' => 'all', 'nom_activite' => 'Toutes activités', 
                 'description' => 'Accès à toutes les activités du groupe', 'couleur_theme' => '#3498db', 'ordre_affichage' => 0]
            ];
            
            $stmt = $pdo->prepare("INSERT INTO activites_config (type_activite, nom_activite, description, couleur_theme, ordre_affichage, date_activation) 
                                  VALUES (?, ?, ?, ?, ?, NOW())");
            
            foreach ($default_activities as $activity) {
                $stmt->execute([
                    $activity['type_activite'],
                    $activity['nom_activite'],
                    $activity['description'],
                    $activity['couleur_theme'],
                    $activity['ordre_affichage']
                ]);
            }
        }
    } catch (Exception $e) {
        error_log("Erreur insertion activités par défaut: " . $e->getMessage());
    }
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=pressing_manager;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer les tables si nécessaire
    createActivitesTable($pdo);
    
    // Traitement des actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'ajouter':
                    // Ajouter une nouvelle activité
                    $sql = "INSERT INTO activites_config (type_activite, nom_activite, description, couleur_theme, ordre_affichage, actif, date_activation) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW())";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        trim($_POST['type_activite']),
                        trim($_POST['nom_activite']),
                        trim($_POST['description']),
                        trim($_POST['couleur_theme']),
                        intval($_POST['ordre_affichage']),
                        isset($_POST['actif']) ? 1 : 0
                    ]);
                    
                    $message = "Activité ajoutée avec succès !";
                    $message_type = 'success';
                    break;
                    
                case 'modifier':
                    // Modifier une activité existante
                    $sql = "UPDATE activites_config 
                            SET nom_activite = ?, description = ?, couleur_theme = ?, 
                                ordre_affichage = ?, actif = ?, updated_at = NOW() 
                            WHERE type_activite = ?";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        trim($_POST['nom_activite']),
                        trim($_POST['description']),
                        trim($_POST['couleur_theme']),
                        intval($_POST['ordre_affichage']),
                        isset($_POST['actif']) ? 1 : 0,
                        trim($_POST['type_activite'])
                    ]);
                    
                    $message = "Activité mise à jour avec succès !";
                    $message_type = 'success';
                    break;
                    
                case 'supprimer':
                    // Vérifier si l'activité est utilisée
                    $checkSql = "SELECT COUNT(*) FROM utilisateur_activites WHERE type_activite = ?";
                    $checkStmt = $pdo->prepare($checkSql);
                    $checkStmt->execute([trim($_POST['type_activite'])]);
                    $count = $checkStmt->fetchColumn();
                    
                    if ($count > 0) {
                        $message = "Impossible de supprimer cette activité car elle est utilisée par des utilisateurs.";
                        $message_type = 'danger';
                    } else {
                        $sql = "DELETE FROM activites_config WHERE type_activite = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([trim($_POST['type_activite'])]);
                        
                        $message = "Activité supprimée avec succès !";
                        $message_type = 'success';
                    }
                    break;
                    
                case 'activer_desactiver':
                    // Activer/désactiver une activité
                    $sql = "UPDATE activites_config 
                            SET actif = ?, 
                                date_desactivation = IF(? = 0, NOW(), NULL),
                                updated_at = NOW()
                            WHERE type_activite = ?";
                    
                    $newStatus = isset($_POST['actif']) ? 1 : 0;
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$newStatus, $newStatus, trim($_POST['type_activite'])]);
                    
                    $statusText = $newStatus ? 'activée' : 'désactivée';
                    $message = "Activité $statusText avec succès !";
                    $message_type = 'success';
                    break;
                    
                case 'assigner_utilisateur':
                    // Assigner une activité à un utilisateur
                    $userId = intval($_POST['id_utilisateur']);
                    $activite = trim($_POST['type_activite']);
                    
                    // Vérifier si l'assignation existe déjà
                    $checkSql = "SELECT COUNT(*) FROM utilisateur_activites WHERE id_utilisateur = ? AND type_activite = ?";
                    $checkStmt = $pdo->prepare($checkSql);
                    $checkStmt->execute([$userId, $activite]);
                    
                    if ($checkStmt->fetchColumn() > 0) {
                        $message = "Cet utilisateur a déjà cette activité.";
                        $message_type = 'warning';
                    } else {
                        $sql = "INSERT INTO utilisateur_activites (id_utilisateur, type_activite) VALUES (?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$userId, $activite]);
                        
                        $message = "Activité assignée avec succès !";
                        $message_type = 'success';
                    }
                    break;
                    
                case 'retirer_utilisateur':
                    // Retirer une activité d'un utilisateur
                    $sql = "DELETE FROM utilisateur_activites WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([intval($_POST['id_assignation'])]);
                    
                    $message = "Activité retirée avec succès !";
                    $message_type = 'success';
                    break;
                    
                case 'recreer_tables':
                    // Recréer les tables
                    $pdo->exec("DROP TABLE IF EXISTS utilisateur_activites");
                    $pdo->exec("DROP TABLE IF EXISTS activites_config");
                    createActivitesTable($pdo);
                    $message = "Tables recréées avec succès !";
                    $message_type = 'success';
                    break;
            }
        }
    }
    
    // Récupérer toutes les activités - sans ordre_affichage si la colonne n'existe pas
    try {
        // Vérifier si la colonne ordre_affichage existe
        $checkOrder = $pdo->query("SHOW COLUMNS FROM activites_config LIKE 'ordre_affichage'");
        $hasOrderColumn = $checkOrder->rowCount() > 0;
        
        if ($hasOrderColumn) {
            $sqlActivities = "SELECT * FROM activites_config ORDER BY ordre_affichage, type_activite";
        } else {
            $sqlActivities = "SELECT *, 0 as ordre_affichage FROM activites_config ORDER BY type_activite";
        }
        
        $stmtActivities = $pdo->query($sqlActivities);
        $activites = $stmtActivities->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        // En cas d'erreur, récupérer sans ordre_affichage
        $sqlActivities = "SELECT * FROM activites_config ORDER BY type_activite";
        $stmtActivities = $pdo->query($sqlActivities);
        $activites = $stmtActivities->fetchAll(PDO::FETCH_ASSOC);
        foreach ($activites as &$activite) {
            $activite['ordre_affichage'] = $activite['ordre_affichage'] ?? 0;
        }
    }
    
    // Récupérer la liste des utilisateurs pour assignation
    try {
        $sqlUsers = "SELECT u.id_utilisateur, u.nom_complet, u.login_utilisateur, r.nom_role
                     FROM utilisateurs u
                     LEFT JOIN roles r ON u.id_role = r.id_role
                     WHERE u.est_actif = 1
                     ORDER BY u.nom_complet";
        $stmtUsers = $pdo->query($sqlUsers);
        $utilisateurs = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur récupération utilisateurs: " . $e->getMessage());
        $utilisateurs = [];
    }
    
    // Récupérer les assignations actuelles
    try {
        $sqlAssignations = "SELECT ua.id, ua.id_utilisateur, ua.type_activite, 
                                   u.nom_complet, u.login_utilisateur, r.nom_role,
                                   ac.nom_activite, ac.couleur_theme
                            FROM utilisateur_activites ua
                            JOIN utilisateurs u ON ua.id_utilisateur = u.id_utilisateur
                            LEFT JOIN roles r ON u.id_role = r.id_role
                            JOIN activites_config ac ON ua.type_activite = ac.type_activite
                            ORDER BY u.nom_complet, ua.type_activite";
        $stmtAssignations = $pdo->query($sqlAssignations);
        $assignations = $stmtAssignations->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur récupération assignations: " . $e->getMessage());
        $assignations = [];
    }
    
    // Statistiques
    try {
        // Vérifier si la colonne ordre_affichage existe pour les statistiques
        $checkOrder = $pdo->query("SHOW COLUMNS FROM activites_config LIKE 'ordre_affichage'");
        $hasOrderColumn = $checkOrder->rowCount() > 0;
        
        if ($hasOrderColumn) {
            $sqlStats = "SELECT 
                            ac.type_activite,
                            ac.nom_activite,
                            COUNT(DISTINCT ua.id_utilisateur) as nb_utilisateurs,
                            ac.actif,
                            ac.date_activation
                         FROM activites_config ac
                         LEFT JOIN utilisateur_activites ua ON ac.type_activite = ua.type_activite
                         GROUP BY ac.type_activite
                         ORDER BY ac.ordre_affichage";
        } else {
            $sqlStats = "SELECT 
                            ac.type_activite,
                            ac.nom_activite,
                            COUNT(DISTINCT ua.id_utilisateur) as nb_utilisateurs,
                            ac.actif,
                            ac.date_activation
                         FROM activites_config ac
                         LEFT JOIN utilisateur_activites ua ON ac.type_activite = ua.type_activite
                         GROUP BY ac.type_activite
                         ORDER BY ac.type_activite";
        }
        
        $stmtStats = $pdo->query($sqlStats);
        $statistiques = $stmtStats->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur récupération statistiques: " . $e->getMessage());
        $statistiques = [];
    }
    
} catch (Exception $e) {
    $message = "Erreur de connexion à la base de données : " . $e->getMessage();
    $message_type = 'danger';
}

include '../../../templates/header.php';
include '../../../templates/navigation.php';
?>

<style>
    .activity-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 600;
        color: white;
        text-transform: uppercase;
        margin-right: 5px;
    }
    
    .badge-pressing { background-color: #e74c3c; }
    .badge-commerce { background-color: #27ae60; }
    .badge-hotel { background-color: #f39c12; }
    .badge-all { background-color: #3498db; }
    
    .status-active { color: #27ae60; font-weight: bold; }
    .status-inactive { color: #e74c3c; font-weight: bold; }
    
    .color-preview {
        width: 20px;
        height: 20px;
        border-radius: 3px;
        display: inline-block;
        margin-right: 5px;
        border: 1px solid #ddd;
    }
    
    .activity-card {
        border-left: 4px solid #3498db;
        transition: all 0.3s ease;
    }
    
    .activity-card:hover {
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .stats-card {
        background: #2c3e50;
        color: white;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 15px;
        border-left: 4px solid #3498db;
    }
    
    .tab-content {
        padding: 20px;
        background: white;
        border: 1px solid #ddd;
        border-top: none;
    }
    
    .nav-tabs {
        margin-bottom: 0;
    }
    
    .panel-heading h3 {
        margin: 0;
        padding: 0;
        font-size: 1.2em;
    }
    
    .btn-group-sm .btn {
        padding: 2px 8px;
        font-size: 0.85em;
    }
    
    .form-control[type="color"] {
        padding: 3px;
        height: 34px;
    }
</style>

<script>
function toggleTab(tabId) {
    // Cacher tous les contenus d'onglets
    var tabContents = document.querySelectorAll('.tab-content > div');
    tabContents.forEach(function(content) {
        content.style.display = 'none';
    });
    
    // Désactiver tous les onglets
    var tabLinks = document.querySelectorAll('.tab-link');
    tabLinks.forEach(function(link) {
        link.classList.remove('active');
    });
    
    // Afficher le contenu sélectionné et activer l'onglet
    document.getElementById(tabId).style.display = 'block';
    event.currentTarget.classList.add('active');
}

function editActivity(type, nom, description, couleur, ordre, actif) {
    document.getElementById('edit_type').value = type;
    document.getElementById('edit_nom').value = nom;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_couleur').value = couleur;
    document.getElementById('edit_ordre').value = ordre;
    document.getElementById('edit_actif').checked = actif == '1';
    
    // Afficher le formulaire d'édition
    toggleTab('ajouter');
    
    // Pré-remplir avec les valeurs d'édition
    document.getElementById('form_action').value = 'modifier';
    document.querySelector('input[name="type_activite"]').value = type;
    document.querySelector('input[name="type_activite"]').readOnly = true;
    document.querySelector('input[name="nom_activite"]').value = nom;
    document.querySelector('textarea[name="description"]').value = description;
    document.querySelector('input[name="couleur_theme"]').value = couleur;
    document.querySelector('input[name="ordre_affichage"]').value = ordre;
    document.querySelector('input[name="actif"]').checked = actif == '1';
    document.querySelector('button[type="submit"]').innerHTML = 'Mettre à jour';
}

function resetAddForm() {
    document.getElementById('form_action').value = 'ajouter';
    document.querySelector('input[name="type_activite"]').value = '';
    document.querySelector('input[name="type_activite"]').readOnly = false;
    document.querySelector('input[name="nom_activite"]').value = '';
    document.querySelector('textarea[name="description"]').value = '';
    document.querySelector('input[name="couleur_theme"]').value = '#9b59b6';
    document.querySelector('input[name="ordre_affichage"]').value = '0';
    document.querySelector('input[name="actif"]').checked = true;
    document.querySelector('button[type="submit"]').innerHTML = 'Créer l\'activité';
}

function updateColorPreview() {
    var color = document.querySelector('input[name="couleur_theme"]').value;
    document.querySelector('.color-preview').style.backgroundColor = color;
}

function confirmAction(action, message) {
    return confirm(message);
}
</script>

</br></br>
<br> <br> <br>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading" style="background: #2c3e50; color: white; padding: 15px;">
                    <h3 style="margin: 0; font-size: 1.3em;">
                        Gestion des Activités Multi-Sites
                        <small style="opacity: 0.8; font-size: 0.9em;"> - Pressing, Commerce & Hôtel</small>
                    </h3>
                </div>
                <div class="panel-body" style="padding: 20px;">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?>" style="margin-bottom: 20px; padding: 10px 15px;">
                            <?php if ($message_type == 'success'): ?>
                                <span style="color: green; font-weight: bold;">✓</span>
                            <?php else: ?>
                                <span style="color: red; font-weight: bold;">⚠</span>
                            <?php endif; ?>
                            <?= $message ?>
                            <button type="button" onclick="this.parentElement.style.display='none'" 
                                    style="float: right; background: none; border: none; color: #666; cursor: pointer;">×</button>
                        </div>
                    <?php endif; ?>

                    <!-- Statistiques générales -->
                    <div class="row">
                        <?php if (!empty($statistiques)): ?>
                            <?php foreach ($statistiques as $stat): ?>
                            <div class="col-md-3 col-sm-6">
                                <div class="stats-card">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h4 style="margin: 0 0 5px 0; font-size: 1.1em; font-weight: bold;"><?= htmlspecialchars($stat['nom_activite']) ?></h4>
                                            <p style="margin: 0; opacity: 0.9; font-size: 0.9em;">
                                                <?= $stat['nb_utilisateurs'] ?> utilisateurs
                                            </p>
                                        </div>
                                        <div>
                                            <span class="activity-badge" style="background-color: <?= 
                                                $stat['type_activite'] == 'pressing' ? '#e74c3c' : 
                                                ($stat['type_activite'] == 'commerce' ? '#27ae60' : 
                                                ($stat['type_activite'] == 'hotel' ? '#f39c12' : '#3498db')) ?>">
                                                <?= strtoupper(substr($stat['type_activite'], 0, 1)) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                                        <span style="background: <?= $stat['actif'] ? '#27ae60' : '#e74c3c' ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 0.8em;">
                                            <?= $stat['actif'] ? 'Activée' : 'Désactivée' ?>
                                        </span>
                                        <small style="opacity: 0.8; font-size: 0.8em;">
                                            <?= date('d/m/Y', strtotime($stat['date_activation'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-md-12">
                                <div class="alert alert-info" style="text-align: center;">
                                    Aucune activité configurée. Utilisez le formulaire ci-dessous pour en créer.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Onglets simplifiés -->
                    <div style="margin-top: 20px; border-bottom: 1px solid #ddd;">
                        <button class="tab-link active" onclick="toggleTab('liste')" 
                                style="background: none; border: none; padding: 10px 15px; margin-right: 5px; border-bottom: 3px solid #3498db; cursor: pointer;">
                            Liste des Activités
                        </button>
                        <button class="tab-link" onclick="toggleTab('ajouter'); resetAddForm();" 
                                style="background: none; border: none; padding: 10px 15px; margin-right: 5px; cursor: pointer;">
                            Ajouter une Activité
                        </button>
                        <button class="tab-link" onclick="toggleTab('assignations')" 
                                style="background: none; border: none; padding: 10px 15px; margin-right: 5px; cursor: pointer;">
                            Assignations
                        </button>
                        <button class="tab-link" onclick="toggleTab('parametres')" 
                                style="background: none; border: none; padding: 10px 15px; cursor: pointer;">
                            Paramètres
                        </button>
                    </div>

                    <!-- Contenu des onglets -->
                    <div class="tab-content">
                        
                        <!-- Onglet 1: Liste des activités -->
                        <div id="liste" style="display: block;">
                            <h4 style="margin-top: 0; color: #2c3e50;">Activités Configurées</h4>
                            <p style="color: #666; margin-bottom: 20px;">Gérez les différentes activités de votre entreprise</p>
                            
                            <?php if (!empty($activites)): ?>
                            <div style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead>
                                        <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                            <th style="padding: 10px; text-align: left; width: 60px;">#</th>
                                            <th style="padding: 10px; text-align: left;">Code</th>
                                            <th style="padding: 10px; text-align: left;">Nom</th>
                                            <th style="padding: 10px; text-align: left;">Description</th>
                                            <th style="padding: 10px; text-align: left;">Couleur</th>
                                            <th style="padding: 10px; text-align: left;">Ordre</th>
                                            <th style="padding: 10px; text-align: left;">Statut</th>
                                            <th style="padding: 10px; text-align: left;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                     <?php foreach ($activites as $activite): 
    // Sécurisation des données pour éviter les erreurs "Undefined index"
    $couleur = $activite['couleur_theme'] ?? '#6c757d'; // Gris par défaut si vide
    $est_actif = (bool)($activite['actif'] ?? false);
    $type = $activite['type_activite'] ?? 'N/A';
?>
<tr class="activity-card" style="border-bottom: 1px solid #eee;">
    <td style="padding: 10px;"><?= $activite['id_activite'] ?? '' ?></td>
    <td style="padding: 10px;">
        <span class="activity-badge" style="background-color: <?= $couleur ?>; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.85em;">
            <?= strtoupper(htmlspecialchars($type)) ?>
        </span>
    </td>
    <td style="padding: 10px;"><strong><?= htmlspecialchars($activite['nom_activite'] ?? '') ?></strong></td>
    <td style="padding: 10px;"><?= htmlspecialchars($activite['description'] ?? '') ?></td>
    <td style="padding: 10px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <span style="display: inline-block; width: 15px; height: 15px; border-radius: 50%; background-color: <?= $couleur ?>; border: 1px solid #ddd;"></span>
            <code><?= $couleur ?></code>
        </div>
    </td>
    <td style="padding: 10px;"><?= $activite['ordre_affichage'] ?? 0 ?></td>
    <td style="padding: 10px;">
        <?php if ($est_actif): ?>
            <span style="color: #27ae60; font-weight: bold;">✓ Activée</span>
        <?php else: ?>
            <span style="color: #e74c3c; font-weight: bold;">✗ Désactivée</span>
        <?php endif; ?>
    </td>
    <td style="padding: 10px;">
        <div style="display: flex; gap: 5px;">
            <button onclick="editActivity('<?= addslashes($type) ?>', '<?= addslashes($activite['nom_activite'] ?? '') ?>', '<?= addslashes($activite['description'] ?? '') ?>', '<?= $couleur ?>', '<?= $activite['ordre_affichage'] ?? 0 ?>', '<?= $est_actif ? 1 : 0 ?>')" 
                    style="background: #f39c12; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">
                Modifier
            </button>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="activer_desactiver">
                <input type="hidden" name="type_activite" value="<?= htmlspecialchars($type) ?>">
                <input type="hidden" name="actif" value="<?= $est_actif ? '0' : '1' ?>">
                <button type="submit" 
                        style="background: <?= $est_actif ? '#e74c3c' : '#27ae60' ?>; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">
                    <?= $est_actif ? 'Désactiver' : 'Activer' ?>
                </button>
            </form>
            
            <?php if (!in_array(strtolower($type), ['pressing', 'commerce', 'hotel', 'all'])): ?>
            <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer cette activité ?');">
                <input type="hidden" name="action" value="supprimer">
                <input type="hidden" name="type_activite" value="<?= htmlspecialchars($type) ?>">
                <button type="submit" style="background: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">
                    Supprimer
                </button>
            </form>
            <?php endif; ?>
        </div>
    </td>
</tr>
<?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                                <div style="text-align: center; padding: 40px; color: #666;">
                                    Aucune activité trouvée. Utilisez le bouton "Ajouter une Activité" pour commencer.
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Onglet 2: Ajouter/Modifier une activité -->
                        <div id="ajouter" style="display: none;">
                            <h4 style="margin-top: 0; color: #2c3e50;" id="form_title">Nouvelle Activité</h4>
                            <p style="color: #666; margin-bottom: 20px;" id="form_description">Créez une nouvelle activité pour votre entreprise</p>
                            
                            <form method="POST" style="max-width: 800px;">
                                <input type="hidden" name="action" id="form_action" value="ajouter">
                                
                                <div style="display: flex; margin-bottom: 15px; align-items: center;">
                                    <label style="width: 200px; font-weight: bold;">Code Activité *</label>
                                    <div style="flex: 1;">
                                        <input type="text" name="type_activite" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;" 
                                               placeholder="Ex: restaurant, spa, coworking" required
                                               pattern="[a-z_]{3,20}"
                                               title="3-20 caractères minuscules, underscores autorisés">
                                        <small style="color: #666; font-size: 0.9em;">Identifiant unique en minuscules (sans espaces)</small>
                                    </div>
                                </div>
                                
                                <div style="display: flex; margin-bottom: 15px; align-items: center;">
                                    <label style="width: 200px; font-weight: bold;">Nom d'affichage *</label>
                                    <div style="flex: 1;">
                                        <input type="text" name="nom_activite" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;" 
                                               placeholder="Ex: Restaurant & Catering" required>
                                    </div>
                                </div>
                                
                                <div style="display: flex; margin-bottom: 15px; align-items: flex-start;">
                                    <label style="width: 200px; font-weight: bold;">Description</label>
                                    <div style="flex: 1;">
                                        <textarea name="description" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; height: 100px;" 
                                                  placeholder="Description détaillée de l'activité..."></textarea>
                                    </div>
                                </div>
                                
                                <div style="display: flex; margin-bottom: 15px; align-items: center;">
                                    <label style="width: 200px; font-weight: bold;">Couleur du thème</label>
                                    <div style="flex: 1; display: flex; gap: 10px; align-items: center;">
                                        <input type="color" name="couleur_theme" value="#9b59b6" 
                                               style="width: 50px; height: 40px; border: 1px solid #ddd; border-radius: 3px;"
                                               onchange="updateColorPreview()">
                                        <input type="text" value="#9b59b6" pattern="^#[0-9A-Fa-f]{6}$"
                                               style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 3px;"
                                               onchange="document.querySelector('input[name=\"couleur_theme\"]').value=this.value; updateColorPreview()">
                                        <span class="color-preview" style="background-color: #9b59b6"></span>
                                    </div>
                                </div>
                                
                                <div style="display: flex; margin-bottom: 15px; align-items: center;">
                                    <label style="width: 200px; font-weight: bold;">Ordre d'affichage</label>
                                    <div style="flex: 1;">
                                        <input type="number" name="ordre_affichage" style="width: 100px; padding: 8px; border: 1px solid #ddd; border-radius: 3px;" 
                                               value="0" min="0" max="100">
                                        <small style="color: #666; font-size: 0.9em;">0 = premier, 100 = dernier</small>
                                    </div>
                                </div>
                                
                                <div style="display: flex; margin-bottom: 20px; align-items: center;">
                                    <label style="width: 200px; font-weight: bold;"></label>
                                    <div style="flex: 1;">
                                        <label style="display: flex; align-items: center; gap: 10px;">
                                            <input type="checkbox" name="actif" checked> 
                                            <span>Activité active</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 10px;">
                                    <button type="submit" style="background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 3px; cursor: pointer;">
                                        Créer l'activité
                                    </button>
                                    <button type="button" onclick="resetAddForm()" style="background: #95a5a6; color: white; border: none; padding: 10px 20px; border-radius: 3px; cursor: pointer;">
                                        Réinitialiser
                                    </button>
                                    <button type="button" onclick="toggleTab('liste')" style="background: #7f8c8d; color: white; border: none; padding: 10px 20px; border-radius: 3px; cursor: pointer;">
                                        Annuler
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Onglet 3: Assignations utilisateurs -->
                        <div id="assignations" style="display: none;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div>
                                    <h4 style="margin-top: 0; color: #2c3e50;">Assigner une activité</h4>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="assigner_utilisateur">
                                        
                                        <div style="margin-bottom: 15px;">
                                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Utilisateur *</label>
                                            <select name="id_utilisateur" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;" required>
                                                <option value="">Sélectionner un utilisateur...</option>
                                                <?php foreach ($utilisateurs as $user): ?>
                                                <option value="<?= $user['id_utilisateur'] ?>">
                                                    <?= htmlspecialchars($user['nom_complet']) ?> 
                                                    (<?= $user['login_utilisateur'] ?>)
                                                    - <?= $user['nom_role'] ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div style="margin-bottom: 15px;">
                                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Activité *</label>
                                            <select name="type_activite" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;" required>
                                                <option value="">Sélectionner une activité...</option>
                                                <?php foreach ($activites as $activite): 
                                                    if ($activite['actif']): ?>
                                              <option value="<?= htmlspecialchars($activite['type_activite'] ?? '') ?>" 
        data-color="<?= htmlspecialchars($activite['couleur_theme'] ?? '#000000') ?>">
    <?= htmlspecialchars($activite['nom_activite'] ?? 'Sans nom') ?>
    (<?= strtoupper(htmlspecialchars($activite['type_activite'] ?? 'N/A')) ?>)
</option>
                                                <?php endif;
                                                endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <button type="submit" style="background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 3px; cursor: pointer;">
                                                Assigner
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <div>
                                    <h4 style="margin-top: 0; color: #2c3e50;">Assignations Actuelles</h4>
                                    <?php if (!empty($assignations)): ?>
                                    <div style="overflow-y: auto; max-height: 400px;">
                                        <table style="width: 100%; border-collapse: collapse;">
                                            <thead>
                                                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                                    <th style="padding: 8px; text-align: left;">Utilisateur</th>
                                                    <th style="padding: 8px; text-align: left;">Activité</th>
                                                    <th style="padding: 8px; text-align: left;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($assignations as $assign): ?>
                                                <tr style="border-bottom: 1px solid #eee;">
                                                    <td style="padding: 8px;">
                                                        <?= htmlspecialchars($assign['nom_complet']) ?><br>
                                                        <small style="color: #666;"><?= $assign['nom_role'] ?></small>
                                                    </td>
                                                    <td style="padding: 8px;">
                                                        <span class="activity-badge" style="background-color: <?= $assign['couleur_theme'] ?>">
                                                            <?= strtoupper($assign['type_activite']) ?>
                                                        </span><br>
                                                        <small><?= $assign['nom_activite'] ?></small>
                                                    </td>
                                                    <td style="padding: 8px;">
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirmAction('retirer', 'Retirer cette activité à l\\'utilisateur ?');">
                                                            <input type="hidden" name="action" value="retirer_utilisateur">
                                                            <input type="hidden" name="id_assignation" value="<?= $assign['id'] ?>">
                                                            <button type="submit" style="background: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 0.9em;">
                                                                Retirer
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                        <div style="text-align: center; padding: 20px; color: #666;">
                                            Aucune assignation trouvée.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Onglet 4: Paramètres globaux -->
                        <div id="parametres" style="display: none;">
                            <h4 style="margin-top: 0; color: #2c3e50;">Paramètres Globaux</h4>
                            <p style="color: #666; margin-bottom: 20px;">Configuration générale du système multi-activités</p>
                            
                            <form method="POST" style="max-width: 600px;">
                                <input type="hidden" name="action" value="parametres_globaux">
                                
                                <div style="margin-bottom: 20px;">
                                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">Mode multi-activités</label>
                                    <div style="display: flex; flex-direction: column; gap: 10px;">
                                        <label style="display: flex; align-items: center; gap: 10px;">
                                            <input type="radio" name="mode_multi" value="1" checked> 
                                            Activé - Affichage séparé par activité
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 10px;">
                                            <input type="radio" name="mode_multi" value="0"> 
                                            Désactivé - Vue unifiée
                                        </label>
                                    </div>
                                </div>
                                
                                <div style="margin-bottom: 20px;">
                                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">Catégorisation clients</label>
                                    <div style="display: flex; flex-direction: column; gap: 10px;">
                                        <label style="display: flex; align-items: center; gap: 10px;">
                                            <input type="checkbox" name="categorisation_auto" checked> 
                                            Catégorisation automatique selon l'activité
                                        </label>
                                        <small style="color: #666; font-size: 0.9em;">Les clients sont automatiquement associés à l'activité de création</small>
                                    </div>
                                </div>
                                
                                <div style="margin-bottom: 20px;">
                                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">Facturation</label>
                                    <div style="display: flex; flex-direction: column; gap: 10px;">
                                        <label style="display: flex; align-items: center; gap: 10px;">
                                            <input type="radio" name="facturation" value="separee" checked> 
                                            Factures séparées par activité
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 10px;">
                                            <input type="radio" name="facturation" value="consolidee"> 
                                            Facture consolidée
                                        </label>
                                    </div>
                                </div>
                                
                                <div style="margin-bottom: 30px;">
                                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">Reporting</label>
                                    <div style="display: flex; flex-direction: column; gap: 10px;">
                                        <label style="display: flex; align-items: center; gap: 10px;">
                                            <input type="checkbox" name="rapport_separe" checked> 
                                            Rapports séparés par activité
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 10px;">
                                            <input type="checkbox" name="rapport_global" checked> 
                                            Rapport global consolidé
                                        </label>
                                    </div>
                                </div>
                                
                                <div>
                                    <button type="submit" style="background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 3px; cursor: pointer;">
                                        Enregistrer les paramètres
                                    </button>
                                </div>
                            </form>
                            
                            <hr style="margin: 30px 0;">
                            
                            <h5 style="color: #2c3e50; margin-bottom: 15px;">Maintenance</h5>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                                <form method="POST" onsubmit="return confirmAction('recreer', 'Recréer les tables d\\'activités ? Les données existantes seront conservées.');">
                                    <input type="hidden" name="action" value="recreer_tables">
                                    <button type="submit" style="width: 100%; background: #3498db; color: white; border: none; padding: 10px; border-radius: 3px; cursor: pointer;">
                                        Re-créer les tables
                                    </button>
                                </form>
                                <form method="POST" onsubmit="return confirmAction('reset', 'Réinitialiser les activités par défaut ?');">
                                    <input type="hidden" name="action" value="reset_default">
                                    <button type="submit" style="width: 100%; background: #f39c12; color: white; border: none; padding: 10px; border-radius: 3px; cursor: pointer;">
                                        Valeurs par défaut
                                    </button>
                                </form>
                                <button type="button" style="width: 100%; background: #27ae60; color: white; border: none; padding: 10px; border-radius: 3px; cursor: pointer;">
                                    Exporter configuration
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../../templates/footer.php'; ?>