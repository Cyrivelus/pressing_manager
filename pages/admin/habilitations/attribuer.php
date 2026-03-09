<?php
// attribuer.php - Gestion des habilitations par rôle (inspiré de Django)
// Page d'administration pour attribuer les permissions aux rôles

// 1. Sécurité et initialisation
ob_start();
session_start();

// Vérifier l'accès admin
if (!isset($_SESSION['utilisateur_id']) || ($_SESSION['role'] ?? '') !== 'patron') {
    header('Location: /pressing_manager/index.php');
    exit();
}

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/admin_errors.log');

// 2. Inclusion des fonctions nécessaires
require_once '../../../fonctions/database.php';
require_once '../../../fonctions/gestion_utilisateurs.php';



/**
 * Récupère tous les rôles existants
 */
function getAllRoles($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM roles ORDER BY nom_role");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère un rôle spécifique par ID
 */
function getRoleById($pdo, $roleId) {
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE id_role = ?");
    $stmt->execute([$roleId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Récupère toutes les permissions disponibles
 */
function getAllPermissions() {
    return [
        // Navigation Principale
        'dashboard' => 'Tableau de Bord',
        'intelligent_dashboard' => 'Dashboard Intelligent',
        
        // Opérations
        'gestion_tickets' => 'Gestion des Tickets',
        'tracabilite' => 'Traçabilité',
        'atelier' => 'Atelier',
        'maintenance' => 'Maintenance',
        'qualite' => 'Qualité',
        
        // Clients & Ventes
        'gestion_clients' => 'Gestion des Clients',
        'abonnements' => 'Abonnements',
        'fidelite' => 'Fidélité',
        'caisse' => 'Caisse',
        'gestion_factures' => 'Factures',
        
        // Stock & Inventaire
        'gestion_stock' => 'Gestion du Stock',
        'consommables' => 'Consommables',
        'gestion_fournisseurs' => 'Fournisseurs',
        
        // Services & Digital
        'reservation_online' => 'Réservation en Ligne',
        'client_portal' => 'Portail Client',
        'paiements_online' => 'Paiements Online',
        'livraison' => 'Livraison',
        'urgences' => 'Services Urgences',
        'boutique' => 'Boutique en Ligne',
        
        // Marketing & Communication
        'marketing' => 'Marketing',
        'notifications' => 'Notifications',
        
        // Administration
        'administration' => 'Administration',
        'rapports' => 'Rapports',
        'gestion_comptes' => 'Comptes',
        'gestion_agences' => 'Gestion des Agences',
        'audit' => 'Audit & Logs',
        'partenariats' => 'Partenariats',
        'environnement' => 'Environnement'
    ];
}

/**
 * Récupère les permissions actuelles d'un rôle
 */
function getRolePermissions($pdo, $roleId) {
    $stmt = $pdo->prepare("
        SELECT p.code_permission 
        FROM role_permissions rp
        JOIN permissions p ON rp.id_permission = p.id_permission
        WHERE rp.id_role = ?
    ");
    $stmt->execute([$roleId]);
    $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    return array_fill_keys($permissions, true);
}

/**
 * Met à jour les permissions d'un rôle
 */
function updateRolePermissions($pdo, $roleId, $permissions) {
    try {
        $pdo->beginTransaction();
        
        // Supprimer toutes les permissions existantes
        $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE id_role = ?");
        $stmt->execute([$roleId]);
        
        // Ajouter les nouvelles permissions
        $allPermissions = getAllPermissions();
        $permissionIds = [];
        
        // Récupérer les IDs des permissions sélectionnées
        foreach ($permissions as $permissionCode) {
            if (array_key_exists($permissionCode, $allPermissions)) {
                $stmt = $pdo->prepare("SELECT id_permission FROM permissions WHERE code_permission = ?");
                $stmt->execute([$permissionCode]);
                $permissionId = $stmt->fetchColumn();
                
                if ($permissionId) {
                    $permissionIds[] = $permissionId;
                }
            }
        }
        
        // Insérer les nouvelles permissions
        $insertStmt = $pdo->prepare("INSERT INTO role_permissions (id_role, id_permission) VALUES (?, ?)");
        foreach ($permissionIds as $permissionId) {
            $insertStmt->execute([$roleId, $permissionId]);
        }
        
        // Journaliser l'action
        logAuditAction($pdo, $_SESSION['utilisateur_id'], 'UPDATE_ROLE_PERMISSIONS', 
            "Mise à jour des permissions pour le rôle ID: $roleId");
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erreur lors de la mise à jour des permissions: " . $e->getMessage());
        return false;
    }
}

/**
 * Journalise les actions d'audit
 */
function logAuditAction($db, $userId, $action, $details) {
    $stmt = $db->prepare("
        INSERT INTO audit_log (user_id, action, details, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $userId,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
}

// 4. Traitement du formulaire

$message = '';
$messageType = '';
$selectedRoleId = $_GET['id'] ?? null;

// Récupérer tous les rôles
$roles = getAllRoles($pdo);

// Si un rôle est sélectionné
$currentRole = null;
$rolePermissions = [];
$allPermissions = getAllPermissions();

if ($selectedRoleId) {
    $currentRole = getRoleById($pdo, $selectedRoleId);
    if ($currentRole) {
        $rolePermissions = getRolePermissions($pdo, $selectedRoleId);
    }
}

// Traitement de la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && 
    hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    
    $roleId = $_POST['role_id'] ?? null;
    $selectedPermissions = $_POST['permissions'] ?? [];
    
    if ($roleId && updateRolePermissions($pdo, $roleId, $selectedPermissions)) {
        $message = "Permissions mises à jour avec succès!";
        $messageType = "success";
        $rolePermissions = array_fill_keys($selectedPermissions, true);
    } else {
        $message = "Erreur lors de la mise à jour des permissions.";
        $messageType = "error";
    }
}

// Générer un nouveau token CSRF
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// 5. Interface utilisateur
require_once  '../../../templates/header.php';
require_once  '../../../templates/navigation.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Habilitations - Pressing Manager</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/pressing_manager/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/pressing_manager/css/font-awesome.min.css">
    
    <!-- CSS personnalisé -->
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-bg: #f8f9fa;
            --border-color: #dee2e6;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 20px;
        }
        
        .container-fluid {
            max-width: 1400px;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), #1a252f);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .page-header .subtitle {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .role-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .role-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .role-card.active {
            border-left: 4px solid var(--secondary-color);
            background-color: #f8fafc;
        }
        
        .role-name {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .role-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .permissions-section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            min-height: 500px;
        }
        
        .permission-group {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .permission-group:last-child {
            border-bottom: none;
        }
        
        .group-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--secondary-color);
            font-size: 1.2rem;
        }
        
        .permission-item {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 6px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            transition: all 0.2s;
        }
        
        .permission-item:hover {
            background: #e9ecef;
            border-color: #dee2e6;
        }
        
        .permission-item label {
            display: flex;
            align-items: center;
            cursor: pointer;
            margin-bottom: 0;
            font-weight: 500;
        }
        
        .permission-item input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .permission-description {
            font-size: 0.85rem;
            color: #6c757d;
            margin-left: 28px;
            margin-top: 5px;
        }
        
        .btn-save {
            background: linear-gradient(135deg, var(--success-color), #229954);
            border: none;
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 6px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
            background: linear-gradient(135deg, #229954, #1e8449);
        }
        
        .btn-back {
            background: #6c757d;
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            background: #5a6268;
            color: white;
        }
        
        .alert {
            border-radius: 6px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid var(--success-color);
        }
        
        .alert-error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid var(--danger-color);
        }
        
        .role-info {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-left: 4px solid var(--secondary-color);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .role-info h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .role-info p {
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .badge-role {
            background: var(--secondary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .permission-count {
            display: inline-block;
            background: var(--secondary-color);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            text-align: center;
            line-height: 24px;
            font-size: 0.8rem;
            margin-left: 10px;
        }
        
        @media (max-width: 768px) {
            .container-fluid {
                padding: 10px;
            }
            
            .page-header {
                padding: 20px;
            }
            
            .permissions-section {
                padding: 20px;
            }
            
            .permission-item {
                padding: 8px;
            }
        }
        
        .permission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .select-all-checkbox {
            display: flex;
            align-items: center;
            font-weight: 600;
            color: var(--primary-color);
            cursor: pointer;
        }
        
        .select-all-checkbox input {
            margin-right: 8px;
        }
    </style>
</head>
<body>

    <div class="container-fluid">
    <br>
        <!-- En-tête -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>Gestion des Habilitations</h1>
                    <p class="subtitle">Attribuez les permissions d'accès aux différents rôles (Inspiré de Django)</p>
                </div>
                <div>
                   <a href="javascript:history.back()" class="btn btn-back">
    <- Retour
</a>
                </div>
            </div>
        </div>
        
        <!-- Message d'alerte -->
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'error' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Colonne des rôles -->
            <div class="col-lg-4 col-md-5">
                <div class="role-card">
                    <h4 class="mb-4"> Rôles Disponibles</h4>
                    
                    <?php foreach ($roles as $role): ?>
                    <div class="role-card mb-3 <?= $selectedRoleId == $role['id_role'] ? 'active' : '' ?>">
                        <a href="?id=<?= $role['id_role'] ?>" class="text-decoration-none">
                            <div class="role-name">
                                <?= htmlspecialchars($role['nom_role']) ?>
                                <?php if ($role['nom_role'] === 'patron'): ?>
                                <span class="badge-role ml-2">Super Admin</span>
                                <?php endif; ?>
                            </div>
                            <div class="role-description">
                                <?= htmlspecialchars($role['description'] ?? 'Aucune description') ?>
                            </div>
                            <div class="text-muted small">
                              ID: <?= $role['id_role'] ?>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($roles)): ?>
                    <div class="empty-state">
                       
                        <h5>Aucun rôle disponible</h5>
                        <p>Créez d'abord des rôles dans la section administration.</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Informations -->
                <div class="role-info">
                    <h3> Comment ça marche ?</h3>
                    <p>1. Sélectionnez un rôle à gauche</p>
                    <p>2. Cochez les permissions souhaitées</p>
                    <p>3. Cliquez sur "Enregistrer les permissions"</p>
                    <p><small class="text-muted">Le rôle "Patron" a automatiquement toutes les permissions.</small></p>
                </div>
            </div>
            
            <!-- Colonne des permissions -->
            <div class="col-lg-8 col-md-7">
                <?php if ($currentRole): ?>
                <form method="POST" action="" id="permissionsForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="role_id" value="<?= $currentRole['id_role'] ?>">
                    
                    <div class="permissions-section">
                        <!-- En-tête du rôle -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="mb-0">
                                    
                                    <?= htmlspecialchars($currentRole['nom_role']) ?>
                                    <?php if ($currentRole['nom_role'] === 'patron'): ?>
                                    <span class="badge-role ml-2">Super Admin</span>
                                    <?php endif; ?>
                                </h2>
                                <p class="text-muted mb-0">ID: <?= $currentRole['id_role'] ?></p>
                            </div>
                            <div class="permission-header">
                                <div class="select-all-checkbox">
                                    <input type="checkbox" id="selectAllPermissions">
                                    <label for="selectAllPermissions">Tout sélectionner</label>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($currentRole['nom_role'] === 'patron'): ?>
                        <div class="alert alert-info">
                             Le rôle <strong>Patron</strong> dispose automatiquement de toutes les permissions.
                            Aucune modification n'est nécessaire.
                        </div>
                        <?php endif; ?>
                        
                        <!-- Groupes de permissions -->
                        <?php
                        // Organiser les permissions par catégorie
                        $permissionCategories = [
                            'Navigation Principale' => ['dashboard', 'intelligent_dashboard'],
                            'Opérations' => ['gestion_tickets', 'tracabilite', 'atelier', 'maintenance', 'qualite'],
                            'Clients & Ventes' => ['gestion_clients', 'abonnements', 'fidelite', 'caisse', 'gestion_factures'],
                            'Stock & Inventaire' => ['gestion_stock', 'consommables', 'gestion_fournisseurs'],
                            'Services & Digital' => ['reservation_online', 'client_portal', 'paiements_online', 'livraison', 'urgences', 'boutique'],
                            'Marketing & Communication' => ['marketing', 'notifications'],
                            'Administration' => ['administration', 'rapports', 'gestion_comptes', 'gestion_agences', 'audit', 'partenariats', 'environnement']
                        ];
                        
                        foreach ($permissionCategories as $category => $categoryPermissions):
                            $hasPermissionsInCategory = false;
                            foreach ($categoryPermissions as $perm) {
                                if (isset($allPermissions[$perm])) {
                                    $hasPermissionsInCategory = true;
                                    break;
                                }
                            }
                            
                            if ($hasPermissionsInCategory):
                        ?>
                        <div class="permission-group">
                            <h4 class="group-title">
                                <?= htmlspecialchars($category) ?>
                                <span class="permission-count">
                                    <?= count(array_intersect_key($allPermissions, array_flip($categoryPermissions))) ?>
                                </span>
                            </h4>
                            
                            <?php foreach ($categoryPermissions as $permissionCode): 
                                if (isset($allPermissions[$permissionCode])):
                            ?>
                            <div class="permission-item">
                                <label>
                                    <input type="checkbox" 
                                           name="permissions[]" 
                                           value="<?= htmlspecialchars($permissionCode) ?>"
                                           <?= isset($rolePermissions[$permissionCode]) ? 'checked' : '' ?>
                                           <?= $currentRole['nom_role'] === 'patron' ? 'disabled checked' : '' ?>>
                                    <?= htmlspecialchars($allPermissions[$permissionCode]) ?>
                                </label>
                                <div class="permission-description">
                                    <?php
                                    $descriptions = [
                                        'dashboard' => 'Accès au tableau de bord principal',
                                        'intelligent_dashboard' => 'Accès au dashboard intelligent avec analyses avancées',
                                        'gestion_tickets' => 'Créer, modifier et gérer les tickets',
                                        'tracabilite' => 'Suivi RFID et QR code des articles',
                                        'atelier' => 'Gestion de la production et planning machines',
                                        'maintenance' => 'Planning et suivi des interventions',
                                        'qualite' => 'Gestion des réclamations et satisfaction',
                                        'gestion_clients' => 'Créer et gérer les fiches clients',
                                        'abonnements' => 'Gérer les abonnements clients',
                                        'fidelite' => 'Programme de fidélité et promotions',
                                        'caisse' => 'Accès à la caisse et encaissements',
                                        'gestion_factures' => 'Création et gestion des factures',
                                        'gestion_stock' => 'Gestion des produits et inventaire',
                                        'consommables' => 'Gestion des consommables et alertes',
                                        'gestion_fournisseurs' => 'Gestion des fournisseurs',
                                        'reservation_online' => 'Gestion des réservations en ligne',
                                        'client_portal' => 'Accès au portail client',
                                        'paiements_online' => 'Gestion des paiements en ligne',
                                        'livraison' => 'Planning des tournées et suivi GPS',
                                        'urgences' => 'Service express et urgences',
                                        'boutique' => 'Boutique en ligne et commandes',
                                        'marketing' => 'Campagnes email et avis clients',
                                        'notifications' => 'Envoi de SMS et emails automatiques',
                                        'administration' => 'Accès à l\'administration générale',
                                        'rapports' => 'Génération et consultation des rapports',
                                        'gestion_comptes' => 'Gestion des comptes clients',
                                        'gestion_agences' => 'Gestion des agences',
                                        'audit' => 'Consultation des logs d\'activité',
                                        'partenariats' => 'Gestion des partenariats',
                                        'environnement' => 'Suivi environnemental'
                                    ];
                                    
                                    echo htmlspecialchars($descriptions[$permissionCode] ?? 'Permission système');
                                    ?>
                                </div>
                            </div>
                            <?php endif; endforeach; ?>
                        </div>
                        <?php endif; endforeach; ?>
                        
                        <!-- Bouton de sauvegarde -->
                        <div class="text-center mt-5">
                            <button type="submit" class="btn btn-save btn-lg" 
                                    <?= $currentRole['nom_role'] === 'patron' ? 'disabled' : '' ?>>
                                Enregistrer les permissions
                            </button>
                            <a href="attribuer.php" class="btn btn-back ml-3">
                                 Annuler
                            </a>
                        </div>
                    </div>
                </form>
                
                <?php else: ?>
                <div class="permissions-section">
                    <div class="empty-state">
                       
                        <h3>Sélectionnez un rôle</h3>
                        <p>Cliquez sur un rôle à gauche pour commencer à attribuer des permissions.</p>
                        <p class="text-muted small">Le système de permissions est inspiré de Django, permettant un contrôle granulaire des accès.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="mt-5 pt-4 border-top text-center text-muted">
            <p class="small">
             
                Système de permissions Django-like • Pressing Manager v2.0.0
            </p>
        </div>
    </div>
    
    <!-- jQuery et Bootstrap JS -->
    <script src="/pressing_manager/js/jquery-3.7.1.min.js"></script>
    <script src="/pressing_manager/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Gestion du "Tout sélectionner"
        $('#selectAllPermissions').change(function() {
            var isChecked = $(this).prop('checked');
            $('input[name="permissions[]"]:not(:disabled)').prop('checked', isChecked);
        });
        
        // Mettre à jour l'état de "Tout sélectionner"
        $('input[name="permissions[]"]').change(function() {
            var allChecked = $('input[name="permissions[]"]:not(:disabled)').length === 
                            $('input[name="permissions[]"]:not(:disabled):checked').length;
            $('#selectAllPermissions').prop('checked', allChecked);
        });
        
        // Vérifier l'état initial
        var allChecked = $('input[name="permissions[]"]:not(:disabled)').length === 
                        $('input[name="permissions[]"]:not(:disabled):checked').length;
        $('#selectAllPermissions').prop('checked', allChecked);
        
        // Animation des cartes de rôle
        $('.role-card').hover(
            function() {
                $(this).css('transform', 'translateY(-2px)');
            },
            function() {
                $(this).css('transform', 'translateY(0)');
            }
        );
        
        // Confirmation avant enregistrement
        $('#permissionsForm').submit(function(e) {
            var checkedCount = $('input[name="permissions[]"]:checked').length;
            
            if (checkedCount === 0) {
                if (!confirm('Vous êtes sur le point d\'enregistrer AUCUNE permission pour ce rôle. Voulez-vous continuer ?')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });
        
        // Alertes auto-dismiss
        $('.alert').delay(5000).fadeOut(400);
        
        // Scroll vers le haut lors de la sélection d'un nouveau rôle
        $('.role-card a').click(function() {
            $('html, body').animate({
                scrollTop: $('.container-fluid').offset().top
            }, 300);
        });
    });
    </script>
</body>
</html>
<?php require_once  '../../../templates/footer.php'; ?>
<?php
ob_end_flush();
?>