<?php
/**
 * pages/boutique/ventes.php
 * Gestion des ventes de la boutique - Interface complète avec création, historique et statistiques
 */

// 1. Initialisation et sécurité
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['utilisateur_id']) || !isset($_SESSION['role'])) {
    header('Location: ../../index.php');
    exit();
}

// Vérification des permissions (rôles autorisés)
$allowed_roles = ['vendeur_boutique', 'caissier_boutique', 'caissier', 'caissière', 'admin', 'directeur', 'gestion_commerce'];
if (!in_array(strtolower($_SESSION['role']), $allowed_roles)) {
    header('Location: ../tableau_bord.php');
    exit();
}

// 2. Configuration
$titre = "Gestion des Ventes Boutique";
$current_page = 'ventes_boutique';
$user_id = $_SESSION['utilisateur_id'];
$user_name = $_SESSION['nom_complet'];
$user_role = $_SESSION['role'];
$agence_code = $_SESSION['code_agence'] ?? 'BOUTIQUE';

// 3. Inclusions
require_once __DIR__ . '/../../fonctions/database.php';
require_once __DIR__ . '/../../fonctions/gestion_utilisateurs.php';

// 4. Gestion des paramètres GET
$action = $_GET['action'] ?? 'liste';
$date_debut = $_GET['debut'] ?? date('Y-m-01');
$date_fin = $_GET['fin'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// 5. Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Création d'une vente
    if (isset($_POST['action']) && $_POST['action'] === 'create_vente') {
        require_once __DIR__ . '/../../fonctions/ventes_boutique.php';
        $result = createVente($_POST, $user_id, $agence_code);
        
        if ($result['success']) {
            $_SESSION['success_message'] = "Vente enregistrée avec succès ! Référence: #V-" . str_pad($result['id_vente'], 5, '0', STR_PAD_LEFT);
            header('Location: ventes.php?action=details&id=' . $result['id_vente']);
            exit();
        } else {
            $error_message = $result['message'];
        }
    }
    
    // Mise à jour du statut de livraison
    if (isset($_POST['action']) && $_POST['action'] === 'update_livraison') {
        require_once __DIR__ . '/../../fonctions/livraisons_boutique.php';
        $result = updateLivraison($_POST['id_livraison'], $_POST['statut'], $user_id);
        
        if ($result['success']) {
            $_SESSION['success_message'] = "Statut de livraison mis à jour";
            header('Location: ventes.php?action=livraisons');
            exit();
        } else {
            $error_message = $result['message'];
        }
    }
}

// 6. Récupération des données selon l'action
try {
    switch ($action) {
        case 'nouvelle':
            // Récupération des produits disponibles
            $stmt = $pdo->prepare("SELECT p.*, c.nom_categorie 
                                  FROM boutique_produits p
                                  LEFT JOIN categories_boutique c ON p.id_categorie = c.id_categorie
                                  WHERE p.est_actif = 1 AND p.stock > 0
                                  ORDER BY p.nom ASC");
            $stmt->execute();
            $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupération des clients
            $stmt = $pdo->prepare("SELECT id_client, CONCAT(prenom, ' ', nom) as nom_complet, telephone, email 
                                  FROM clients 
                                  WHERE est_actif = 1 
                                  ORDER BY nom, prenom ASC");
            $stmt->execute();
            $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupération des modes de paiement
            $stmt = $pdo->prepare("SELECT * FROM modes_paiement WHERE actif = 1 ORDER BY ordre");
            $stmt->execute();
            $modes_paiement = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'details':
            $vente_id = $_GET['id'] ?? 0;
            
            // Récupération des détails de la vente
            $stmt = $pdo->prepare("SELECT v.*, 
                                  c.nom as client_nom, c.prenom as client_prenom, c.telephone, c.email,
                                  u.nom_complet as vendeur_nom,
                                  mp.nom as mode_paiement_nom
                                  FROM ventes_boutique v
                                  LEFT JOIN clients c ON v.id_client = c.id_client
                                  LEFT JOIN utilisateurs u ON v.id_utilisateur = u.id_utilisateur
                                  LEFT JOIN modes_paiement mp ON v.mode_paiement_id = mp.id
                                  WHERE v.id_vente = :id");
            $stmt->execute([':id' => $vente_id]);
            $vente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$vente) {
                $error_message = "Vente non trouvée";
                break;
            }
            
            // Récupération des articles de la vente
            $stmt = $pdo->prepare("SELECT lv.*, p.nom as produit_nom, p.reference as produit_ref
                                  FROM lignes_vente_boutique lv
                                  JOIN boutique_produits p ON lv.id_produit = p.id_produit
                                  WHERE lv.id_vente = :id_vente");
            $stmt->execute([':id_vente' => $vente_id]);
            $lignes_vente = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupération de l'historique des paiements
            $stmt = $pdo->prepare("SELECT * FROM paiements_vente 
                                  WHERE id_vente = :id_vente 
                                  ORDER BY date_paiement DESC");
            $stmt->execute([':id_vente' => $vente_id]);
            $paiements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupération des informations de livraison
            $stmt = $pdo->prepare("SELECT * FROM livraisons_boutique 
                                  WHERE id_vente = :id_vente");
            $stmt->execute([':id_vente' => $vente_id]);
            $livraison = $stmt->fetch(PDO::FETCH_ASSOC);
            break;
            
        case 'livraisons':
            // Récupération des livraisons en attente
            $stmt = $pdo->prepare("SELECT l.*, 
                                  v.numero_vente, v.montant_total,
                                  CONCAT(c.prenom, ' ', c.nom) as client_nom,
                                  c.telephone, c.adresse,
                                  u.nom_complet as vendeur_nom
                                  FROM livraisons_boutique l
                                  JOIN ventes_boutique v ON l.id_vente = v.id_vente
                                  JOIN clients c ON v.id_client = c.id_client
                                  JOIN utilisateurs u ON v.id_utilisateur = u.id_utilisateur
                                  WHERE l.statut IN ('en_attente', 'en_preparation', 'en_livraison')
                                  ORDER BY 
                                    CASE l.statut 
                                        WHEN 'en_livraison' THEN 1
                                        WHEN 'en_preparation' THEN 2
                                        WHEN 'en_attente' THEN 3
                                        ELSE 4
                                    END,
                                    l.date_prevue ASC");
            $stmt->execute();
            $livraisons = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'statistiques':
            // Statistiques générales
            $stmt = $pdo->prepare("SELECT 
                                  COUNT(*) as total_ventes,
                                  SUM(montant_total) as chiffre_affaires,
                                  AVG(montant_total) as moyenne_vente,
                                  COUNT(DISTINCT id_client) as clients_uniques
                                  FROM ventes_boutique
                                  WHERE DATE(date_vente) BETWEEN :debut AND :fin");
            $stmt->execute([':debut' => $date_debut, ':fin' => $date_fin]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Ventes par jour
            $stmt = $pdo->prepare("SELECT DATE(date_vente) as jour, 
                                  COUNT(*) as nb_ventes,
                                  SUM(montant_total) as ca_jour
                                  FROM ventes_boutique
                                  WHERE DATE(date_vente) BETWEEN :debut AND :fin
                                  GROUP BY DATE(date_vente)
                                  ORDER BY jour DESC
                                  LIMIT 15");
            $stmt->execute([':debut' => $date_debut, ':fin' => $date_fin]);
            $ventes_par_jour = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Produits les plus vendus
            $stmt = $pdo->prepare("SELECT p.nom, p.reference,
                                  COUNT(lv.id_ligne) as nb_vendu,
                                  SUM(lv.quantite) as quantite_totale,
                                  SUM(lv.sous_total) as ca_produit
                                  FROM lignes_vente_boutique lv
                                  JOIN boutique_produits p ON lv.id_produit = p.id_produit
                                  JOIN ventes_boutique v ON lv.id_vente = v.id_vente
                                  WHERE DATE(v.date_vente) BETWEEN :debut AND :fin
                                  GROUP BY lv.id_produit
                                  ORDER BY quantite_totale DESC
                                  LIMIT 10");
            $stmt->execute([':debut' => $date_debut, ':fin' => $date_fin]);
            $top_produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // CA par mode de paiement
            $stmt = $pdo->prepare("SELECT mp.nom as mode_paiement,
                                  COUNT(v.id_vente) as nb_ventes,
                                  SUM(v.montant_total) as ca_mode
                                  FROM ventes_boutique v
                                  JOIN modes_paiement mp ON v.mode_paiement_id = mp.id
                                  WHERE DATE(v.date_vente) BETWEEN :debut AND :fin
                                  GROUP BY v.mode_paiement_id
                                  ORDER BY ca_mode DESC");
            $stmt->execute([':debut' => $date_debut, ':fin' => $date_fin]);
            $ca_par_paiement = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        default: // 'liste'
            // Construction de la requête avec filtres
            $sql = "SELECT v.*, 
                   CONCAT(c.prenom, ' ', c.nom) as client_nom,
                   u.nom_complet as vendeur_nom,
                   mp.nom as mode_paiement_nom
                   FROM ventes_boutique v
                   LEFT JOIN clients c ON v.id_client = c.id_client
                   LEFT JOIN utilisateurs u ON v.id_utilisateur = u.id_utilisateur
                   LEFT JOIN modes_paiement mp ON v.mode_paiement_id = mp.id
                   WHERE 1=1";
            
            $params = [];
            
            // Filtre par date
            if (!empty($date_debut) && !empty($date_fin)) {
                $sql .= " AND DATE(v.date_vente) BETWEEN :debut AND :fin";
                $params[':debut'] = $date_debut;
                $params[':fin'] = $date_fin;
            }
            
            // Filtre par recherche
            if (!empty($search)) {
                $sql .= " AND (v.numero_vente LIKE :search OR 
                             c.nom LIKE :search OR 
                             c.prenom LIKE :search OR 
                             v.id_vente = :id_search)";
                $params[':search'] = "%$search%";
                if (is_numeric($search)) {
                    $params[':id_search'] = $search;
                }
            }
            
            // Comptage total pour la pagination
            $count_sql = "SELECT COUNT(*) FROM ($sql) as total";
            $stmt = $pdo->prepare($count_sql);
            $stmt->execute($params);
            $total_ventes = $stmt->fetchColumn();
            $total_pages = ceil($total_ventes / $limit);
            
            // Récupération des ventes avec pagination
            $sql .= " ORDER BY v.date_vente DESC LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            foreach ($params as $key => $value) {
                if ($key === ':limit' || $key === ':offset') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            $ventes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupération des totaux pour la période
            $stmt = $pdo->prepare("SELECT 
                                  COUNT(*) as nb_ventes,
                                  SUM(montant_total) as total_ca,
                                  AVG(montant_total) as moyenne_vente
                                  FROM ventes_boutique
                                  WHERE DATE(date_vente) BETWEEN :debut AND :fin");
            $stmt->execute([':debut' => $date_debut, ':fin' => $date_fin]);
            $totaux_periode = $stmt->fetch(PDO::FETCH_ASSOC);
            break;
    }
} catch (PDOException $e) {
    error_log("Erreur PDO dans ventes.php: " . $e->getMessage());
    $error_message = "Erreur de base de données. Veuillez réessayer.";
}

// 7. Inclusions des templates
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/navigation.php';
?>

<style>
:root {
    --primary-color: #2c3e50;
    --secondary-color: #34495e;
    --accent-color: #3498db;
    --success-color: #27ae60;
    --warning-color: #f39c12;
    --danger-color: #e74c3c;
    --light-color: #f8f9fa;
    --border-color: #dee2e6;
    --sidebar-width: 250px;
}

.dashboard-wrapper {
    margin-left: var(--sidebar-width);
    padding: 2rem 1.5rem;
    min-height: 100vh;
    background-color: var(--light-color);
    transition: all 0.3s ease;
    width: calc(100% - var(--sidebar-width));
}

.page-header {
    border-bottom: 0.3rem solid var(--primary-color);
    padding-bottom: 1.2rem;
    margin-bottom: 2rem;
    color: var(--primary-color);
    font-weight: 600;
}

.nav-tabs-boutique {
    border-bottom: 2px solid #dee2e6;
    margin-bottom: 2rem;
}

.nav-tabs-boutique .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    color: var(--secondary-color);
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s;
}

.nav-tabs-boutique .nav-link:hover {
    border-color: var(--accent-color);
    color: var(--accent-color);
}

.nav-tabs-boutique .nav-link.active {
    color: var(--primary-color);
    border-color: var(--primary-color);
    background-color: transparent;
}

.card-boutique {
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.3s;
    margin-bottom: 1.5rem;
}

.card-boutique:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
}

.card-header-boutique {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border-radius: 0.75rem 0.75rem 0 0 !important;
    padding: 1rem 1.5rem;
    font-weight: 600;
}

.badge-statut {
    padding: 0.4rem 0.8rem;
    border-radius: 2rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-completed { background-color: #d4edda; color: #155724; }
.badge-pending { background-color: #fff3cd; color: #856404; }
.badge-cancelled { background-color: #f8d7da; color: #721c24; }
.badge-processing { background-color: #cce5ff; color: #004085; }

.btn-boutique {
    border-radius: 0.5rem;
    font-weight: 500;
    padding: 0.5rem 1.5rem;
    transition: all 0.3s;
}

.btn-boutique-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border: none;
}

.btn-boutique-primary:hover {
    background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.product-card {
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.3s;
    cursor: pointer;
}

.product-card:hover {
    border-color: var(--accent-color);
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.product-card.selected {
    border-color: var(--success-color);
    background-color: rgba(39, 174, 96, 0.05);
}

.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-bottom: 1px solid var(--border-color);
}

.cart-item:last-child {
    border-bottom: none;
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-btn {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 1px solid var(--border-color);
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.quantity-btn:hover {
    background: var(--light-color);
    border-color: var(--accent-color);
}

.stats-card {
    text-align: center;
    padding: 1.5rem;
    border-radius: 0.75rem;
    background: white;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.stats-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stats-label {
    color: var(--secondary-color);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05rem;
}

.delivery-card {
    border-left: 4px solid var(--warning-color);
    margin-bottom: 1rem;
}

.delivery-card.en-livraison {
    border-left-color: var(--accent-color);
}

.delivery-card.livre {
    border-left-color: var(--success-color);
}

.delivery-card.annule {
    border-left-color: var(--danger-color);
}

@media (max-width: 992px) {
    .dashboard-wrapper {
        margin-left: 0;
        width: 100%;
        padding: 1rem;
    }
}

@media print {
    .no-print {
        display: none !important;
    }
    
    .print-only {
        display: block !important;
    }
    
    .dashboard-wrapper {
        margin: 0;
        padding: 0;
        width: 100%;
    }
    
    .card-boutique {
        box-shadow: none;
        border: 1px solid #000;
    }
}
@media print {
    /* 1. Supprimer le Header, Footer, Nav, et Boutons */
    header, footer, nav, .navigation, .sidebar, .no-print, .btn-boutique {
        display: none !important;
    }

    /* 2. Supprimer les marges par défaut du navigateur (enlève les liens .php, dates, titres) */
    @page {
        margin: 0; /* Supprime les entêtes/pieds de page du navigateur */
        size: auto;
    }

    /* 3. Réinitialiser le corps du document */
    body {
        background-color: white !important;
        margin: 1cm; /* Marge physique sur le papier */
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* 4. Forcer l'affichage des couleurs (pour les logos ou badges) */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* 5. Supprimer les ombres et bordures inutiles pour économiser l'encre */
    .container, .card {
        width: 100% !important;
        max-width: 100% !important;
        border: none !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* 6. Forcer les tableaux à prendre toute la largeur */
    table {
        width: 100% !important;
        border-collapse: collapse;
    }
}
@media print {
    a[href]:after { content: none !important; }
}
tr {
    page-break-inside: avoid;
}
</style>

<div class="dashboard-wrapper">
    <div class="container-fluid">
        <br><br><br>
        <!-- En-tête de page -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-header mb-2"><?= htmlspecialchars($titre) ?></h2>
                <p class="text-muted mb-0">
                  Gestion des ventes de la boutique <?= htmlspecialchars($agence_code) ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-boutique btn-boutique-primary no-print" onclick="window.print()">
    Imprimer
</button>
                <a href="../tickets/create.php" class="btn btn-boutique btn-boutique-primary">
    Nouvelle Vente
</a>
            </div>
        </div>
        
        <!-- Messages de notification -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Navigation par onglets -->
        <ul class="nav nav-tabs nav-tabs-boutique">
            <li class="nav-item">
                <a class="nav-link <?= ($action === 'liste' || $action === 'details') ? 'active' : '' ?>" 
                   href="ventes.php?action=liste">
                  Historique des Ventes
                </a>
            </li>
            <li class="nav-item">
                <li class="nav-item">
    <a class="nav-link" href="../tickets/create.php">
        Nouvelle Vente
    </a>
</li>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $action === 'livraisons' ? 'active' : '' ?>" 
                   href="ventes.php?action=livraisons">
                   Livraisons
                    <?php if (isset($livraisons) && count($livraisons) > 0): ?>
                        <span class="badge bg-danger ms-1"><?= count($livraisons) ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $action === 'statistiques' ? 'active' : '' ?>" 
                   href="ventes.php?action=statistiques">
                   Statistiques
                </a>
            </li>
        </ul>
        
        <!-- Contenu selon l'action -->
       <?php 
// Définition du dossier de base pour éviter les erreurs de chemin
$base_path = __DIR__ . '/'; 

switch ($action): 
    case 'nouvelle': ?>
        <?php include $base_path . 'nouvelle_vente.php'; ?>
        <?php break; ?>
        
    case 'details': ?>
        <?php include $base_path . 'details_vente.php'; ?>
        <?php break; ?>
        
    case 'livraisons': ?>
        <?php include $base_path . 'livraisons.php'; ?>
        <?php break; ?>
        
    case 'statistiques': ?>
        <?php include $base_path . 'statistiques.php'; ?>
        <?php break; ?>
        
    default: ?>
        <?php 
        // Si votre liste est dans index.php, assurez-vous de ne pas créer une boucle infinie
        // Il est préférable d'avoir un fichier liste_ventes.php
        if (file_exists($base_path . 'liste_ventes.php')) {
            include $base_path . 'liste_ventes.php';
        } else {
            echo "Fichier de liste indisponible.";
        }
        ?>
        <?php break; ?>
<?php endswitch; ?>
    </div>
</div>

<!-- Scripts JavaScript -->
<script src="../../js/jquery-3.7.1.js"></script>
<script src="../../js/bootstrap.bundle.min.js"></script>
<script src="../../js/chart.js"></script>

<script>
$(document).ready(function() {
    // Auto-hide les alertes après 5 secondes
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
    
    // Initialisation des tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Gestion de l'impression
    $('.btn-print').click(function() {
        window.print();
    });
    
    // Confirmation pour les actions critiques
    $('.btn-confirm').click(function(e) {
        if (!confirm('Êtes-vous sûr de vouloir effectuer cette action ?')) {
            e.preventDefault();
        }
    });
});

// Fonction pour générer un reçu
function imprimerRecu(idVente) {
    const url = `imprimer_recu.php?id=${idVente}`;
    const fenetre = window.open(url, 'ImpressionReçu', 'width=800,height=600,scrollbars=yes');
    fenetre.focus();
}

// Fonction pour mettre à jour le statut d'une livraison
function updateLivraisonStatut(idLivraison, nouveauStatut) {
    if (confirm('Confirmer le changement de statut ?')) {
        $.ajax({
            url: 'ajax/update_livraison.php',
            type: 'POST',
            data: {
                id_livraison: idLivraison,
                statut: nouveauStatut
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + response.message);
                }
            },
            error: function() {
                alert('Erreur lors de la mise à jour');
            }
        });
    }
}

// Fonction pour rechercher rapidement
function quickSearch() {
    const searchTerm = $('#quickSearch').val();
    if (searchTerm.length >= 2) {
        window.location.href = `ventes.php?action=liste&search=${encodeURIComponent(searchTerm)}`;
    }
}

// Initialisation des charts pour les statistiques
<?php if ($action === 'statistiques'): ?>
function initCharts() {
    // Chart pour les ventes par jour
    const ctx1 = document.getElementById('chartVentesJour');
    if (ctx1) {
        const labels = <?= json_encode(array_column($ventes_par_jour, 'jour')) ?>;
        const data = <?= json_encode(array_column($ventes_par_jour, 'ca_jour')) ?>;
        
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Chiffre d\'affaires (FCFA)',
                    data: data,
                    backgroundColor: '#3498db',
                    borderColor: '#2980b9',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('fr-FR') + ' F';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Chart pour les produits les plus vendus
    const ctx2 = document.getElementById('chartTopProduits');
    if (ctx2) {
        const labels = <?= json_encode(array_column($top_produits, 'nom')) ?>;
        const data = <?= json_encode(array_column($top_produits, 'quantite_totale')) ?>;
        
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6',
                        '#1abc9c', '#d35400', '#c0392b', '#16a085', '#8e44ad'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }
}

// Initialiser les charts lorsque la page est chargée
initCharts();
<?php endif; ?>
</script>

<?php 
require_once __DIR__ . '/../../templates/footer.php';
?>