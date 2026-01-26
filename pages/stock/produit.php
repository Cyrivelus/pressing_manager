<?php
// pages/stock/produit.php
ob_start();

// Démarrer la session si non démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la navigation
require_once(__DIR__ . '/../../templates/navigation.php');
require_once(__DIR__ . '/../../templates/header.php');
// 1. Vérification des permissions
if (!isset($_SESSION['role']) || !hasPermission($_SESSION['role'], 'gestion_stock')) {
    header('Location: ' . generateUrl('pages/dashboard.php'));
    exit();
}

// 2. Récupération de l'ID du produit
$id_produit = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_produit) {
    header('Location: ' . generateUrl('pages/stock/index.php'));
    exit();
}

$produit = null;
$mouvements = [];
$stats_mouvements = [];

try {
    // 3. Récupération des informations du produit et son fournisseur
    $stmt = $pdo->prepare("
        SELECT p.*, f.nom_fournisseur, f.contact as fournisseur_contact, 
               f.telephone as fournisseur_tel, f.email as fournisseur_email
        FROM produits p 
        LEFT JOIN fournisseurs f ON p.id_fournisseur = f.id_fournisseur 
        WHERE p.id_produit = ?
    ");
    $stmt->execute([$id_produit]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produit) {
        $_SESSION['error'] = "Produit introuvable.";
        header('Location: ' . generateUrl('pages/stock/index.php'));
        exit();
    }

    // 4. Récupération des 10 derniers mouvements de stock pour ce produit
    $stmtMvt = $pdo->prepare("
        SELECT m.*, u.nom_complet as utilisateur_nom
        FROM mouvements_stock m
        LEFT JOIN utilisateurs u ON m.id_utilisateur = u.id_utilisateur
        WHERE m.id_produit = ?
        ORDER BY m.date_mouvement DESC
        LIMIT 10
    ");
    $stmtMvt->execute([$id_produit]);
    $mouvements = $stmtMvt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Statistiques des mouvements
    $stmtStats = $pdo->prepare("
        SELECT 
            COUNT(*) as total_mouvements,
            SUM(CASE WHEN type_mouvement = 'entree' THEN quantite ELSE 0 END) as total_entrees,
            SUM(CASE WHEN type_mouvement = 'sortie' THEN quantite ELSE 0 END) as total_sorties
        FROM mouvements_stock 
        WHERE id_produit = ?
    ");
    $stmtStats->execute([$id_produit]);
    $stats_mouvements = $stmtStats->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $erreur = "Erreur : " . $e->getMessage();
}

// Inclure le header après avoir traité les données
require_once(__DIR__ . '/../../templates/header.php');
?>

<style>
    /* Structure principale alignée avec la navigation */
    .content-wrapper {
        margin-left: 250px; /* Même largeur que la sidebar */
        padding: 20px;
        transition: all 0.3s;
        background-color: #f8f9fc;
        min-height: 100vh;
    }
    
    /* Quand la sidebar est repliée */
    body.sidebar-collapsed .content-wrapper {
        margin-left: 90px;
    }
    
    /* Responsive pour mobile */
    @media (max-width: 768px) {
        .content-wrapper {
            margin-left: 0 !important;
            padding: 15px;
            margin-top: 70px; /* Pour la navbar fixe */
        }
    }
    
    /* Cartes stylisées */
    .product-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        transition: transform 0.3s;
    }
    
    .product-card:hover {
        transform: translateY(-2px);
    }
    
    .card-header-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px 12px 0 0 !important;
        padding: 1rem 1.5rem;
    }
    
    /* Badges personnalisés */
    .badge-stock {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
    }
    
    .badge-stock-high {
        background-color: #d4edda;
        color: #155724;
    }
    
    .badge-stock-low {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .badge-stock-medium {
        background-color: #fff3cd;
        color: #856404;
    }
    
    /* Tableaux */
    .table-custom th {
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background-color: #f8f9fc;
    }
    
    .table-custom td {
        vertical-align: middle;
        border-top: 1px solid #f0f3f7;
    }
    
    /* Indicateur de stock */
    .stock-indicator {
        height: 6px;
        border-radius: 3px;
        background-color: #e9ecef;
        overflow: hidden;
    }
    
    .stock-fill {
        height: 100%;
        transition: width 0.5s ease;
    }
    
    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate-fadeInUp {
        animation: fadeInUp 0.5s ease-out;
    }
    
    /* Layout des cartes */
    .info-item {
        padding: 12px 0;
        border-bottom: 1px solid #f0f3f7;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    /* Responsive spécifique */
    @media (max-width: 992px) {
        .mobile-stack {
            flex-direction: column;
        }
        
        .mobile-mb-3 {
            margin-bottom: 1.5rem !important;
        }
    }
</style>

<div class="content-wrapper animate-fadeInUp">
    <!-- Fil d'Ariane -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb bg-white shadow-sm p-3 rounded">
            <li class="breadcrumb-item">
                <a href="<?= generateUrl('pages/dashboard.php') ?>">
                   
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= generateUrl('pages/stock/index.php') ?>">Stock</a>
            </li>
            <li class="breadcrumb-item active">
                <?= htmlspecialchars(substr($produit['nom_produit'], 0, 30)) ?>
                <?php if (strlen($produit['nom_produit']) > 30): ?>...<?php endif; ?>
            </li>
        </ol>
    </nav>

    <?php if (isset($erreur)): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
       
        <?= htmlspecialchars($erreur) ?>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- En-tête -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h1 class="h3 font-weight-bold text-dark mb-1">
         
                <?= htmlspecialchars($produit['nom_produit']) ?>
            </h1>
            
        </div>
        
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= generateUrl('pages/stock/modifier.php?id='.$id_produit) ?>" 
               class="btn btn-primary btn-sm shadow-sm">
                 Modifier
            </a>
            <a href="<?= generateUrl('pages/stock/stock_entry.php?id='.$id_produit) ?>" 
               class="btn btn-success btn-sm shadow-sm">
                 Ajuster stock
            </a>
            <a href="javascript:history.back()" 
   class="btn btn-outline-secondary btn-sm shadow-sm">
     <- Retour
</a>
        </div>
    </div>

    <div class="row">
        <!-- Colonne gauche - Informations produit -->
        <div class="col-lg-4 mb-4">
            <!-- Carte statut stock -->
            <div class="card product-card mb-4">
                <div class="card-header card-header-custom">
                    <h5 class="mb-0">
                     Statut du stock
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <?php
                        $stock_niveau = $produit['quantite_stock'];
                        $seuil_alerte = $produit['seuil_alerte'];
                        $isLow = $stock_niveau <= $seuil_alerte;
                        $isCritical = $stock_niveau <= ($seuil_alerte / 2);
                        $stock_percent = min(100, ($stock_niveau / ($seuil_alerte * 3)) * 100);
                        
                        // Déterminer la classe de badge
                        if ($isCritical) {
                          
                            $status_text = 'CRITIQUE';
                        } elseif ($isLow) {
                            
                            $status_text = 'FAIBLE';
                        } else {
                           
                            $status_text = 'OPTIMAL';
                        }
                        ?>
                        
                        <div class="display-2 font-weight-bold mb-3 <?= $isCritical ? 'text-danger' : ($isLow ? 'text-warning' : 'text-success') ?>">
                            <?= number_format($stock_niveau, 0) ?>
                        </div>
                        
                        <div class="mb-3">
                            <span class="badge <?= $badge_class ?> badge-stock">
                                <i class="fas fa-<?= $isCritical ? 'exclamation-circle' : ($isLow ? 'exclamation-triangle' : 'check-circle') ?> mr-1"></i>
                                <?= $status_text ?>
                            </span>
                        </div>
                        
                        <div class="stock-indicator mb-2">
                            <div class="stock-fill <?= $isCritical ? 'bg-danger' : ($isLow ? 'bg-warning' : 'bg-success') ?>" 
                                 style="width: <?= $stock_percent ?>%">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between small text-muted">
                            <span>0</span>
                            <span>Seuil: <?= $seuil_alerte ?></span>
                            <span><?= $seuil_alerte * 3 ?>+</span>
                        </div>
                    </div>
                    
                    <div class="row text-center">
                        <div class="col-6 border-right">
                            <div class="h5 mb-1 text-primary">
                                <?= number_format($stats_mouvements['total_entrees'] ?? 0, 0) ?>
                            </div>
                            <small class="text-muted">Total entrées</small>
                        </div>
                        <div class="col-6">
                            <div class="h5 mb-1 text-danger">
                                <?= number_format($stats_mouvements['total_sorties'] ?? 0, 0) ?>
                            </div>
                            <small class="text-muted">Total sorties</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carte informations produit -->
            <div class="card product-card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 text-dark">
                       Détails du produit
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <div class="small text-muted mb-1">Catégorie</div>
                        <div class="font-weight-bold">
                            <span class="badge badge-light border px-3 py-1">
                                <?= htmlspecialchars($produit['categorie']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="small text-muted mb-1">Prix unitaire</div>
                        <div class="font-weight-bold text-primary h5 mb-0">
                            <?= number_format($produit['prix_unitaire'], 0) ?> FCFA
                        </div>
                        <small class="text-muted"><?= $produit['unite_mesure'] ?></small>
                    </div>
                    
                    <div class="info-item">
                        <div class="small text-muted mb-1">Unité de mesure</div>
                        <div class="font-weight-bold">
                            <?= htmlspecialchars($produit['unite_mesure']) ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="small text-muted mb-1">Emplacement</div>
                        <div class="font-weight-bold">
                            <?php if ($produit['emplacement']): ?>
                                <span class="badge badge-info px-3 py-1">
                                  
                                    <?= htmlspecialchars($produit['emplacement']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">Non spécifié</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($produit['date_peremption']): ?>
                    <div class="info-item">
                        <div class="small text-muted mb-1">Date de péremption</div>
                        <div class="font-weight-bold <?= strtotime($produit['date_peremption']) < strtotime('+30 days') ? 'text-danger' : '' ?>">
                          
                            <?= date('d/m/Y', strtotime($produit['date_peremption'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <div class="small text-muted mb-1">Statut</div>
                        <div class="font-weight-bold">
                            <span class="badge badge-<?= $produit['est_actif'] ? 'success' : 'secondary' ?> px-3 py-1">
                                <?= $produit['est_actif'] ? 'Actif' : 'Inactif' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne droite - Fournisseur et historique -->
        <div class="col-lg-8">
            <!-- Carte fournisseur -->
            <div class="card product-card mb-4">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-dark">
                          Fournisseur
                        </h5>
                        <?php if ($produit['nom_fournisseur']): ?>
                        <a href="<?= generateUrl('pages/fournisseurs/voir.php?id='.$produit['id_fournisseur']) ?>" 
                           class="btn btn-sm btn-outline-primary">
                           Voir fiche
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($produit['nom_fournisseur']): ?>
                        <div class="d-flex align-items-start mb-4">
                            <div class="mr-3">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" 
                                     style="width: 60px; height: 60px;">
                                   
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1 text-primary">
                                    <?= htmlspecialchars($produit['nom_fournisseur']) ?>
                                </h5>
                                <div class="row mt-3">
                                    <?php if ($produit['fournisseur_contact']): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="small text-muted">Contact</div>
                                        <div class="font-weight-bold">
                                           
                                            <?= htmlspecialchars($produit['fournisseur_contact']) ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($produit['fournisseur_tel']): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="small text-muted">Téléphone</div>
                                        <div class="font-weight-bold">
                                           
                                            <a href="tel:<?= htmlspecialchars($produit['fournisseur_tel']) ?>" 
                                               class="text-decoration-none">
                                                <?= htmlspecialchars($produit['fournisseur_tel']) ?>
                                            </a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($produit['fournisseur_email']): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="small text-muted">Email</div>
                                        <div class="font-weight-bold">
                                           
                                            <a href="mailto:<?= htmlspecialchars($produit['fournisseur_email']) ?>" 
                                               class="text-decoration-none">
                                                <?= htmlspecialchars($produit['fournisseur_email']) ?>
                                            </a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                    
                            <p class="text-muted mb-0">Aucun fournisseur associé</p>
                            <a href="<?= generateUrl('pages/stock/modifier.php?id='.$id_produit) ?>" 
                               class="btn btn-sm btn-outline-primary mt-3">
                                 Associer un fournisseur
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Carte historique des mouvements -->
            <div class="card product-card">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-dark">
                           Historique des mouvements
                            <span class="badge badge-light ml-2">
                                <?= $stats_mouvements['total_mouvements'] ?? 0 ?> mouvements
                            </span>
                        </h5>
                        <div class="d-flex gap-2">
                            <a href="<?= generateUrl('pages/stock/stock_history.php?produit='.$id_produit) ?>" 
                               class="btn btn-sm btn-outline-secondary">
                                 Tout voir
                            </a>
                            <a href="<?= generateUrl('pages/stock/stock_entry.php?id='.$id_produit) ?>" 
                               class="btn btn-sm btn-success">
                                 Nouveau mouvement
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="pl-4">Date</th>
                                    <th>Type</th>
                                    <th class="text-center">Quantité</th>
                                    <th>Utilisateur</th>
                                    <th class="pr-4">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($mouvements)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="py-3">
                                               
                                                <p class="text-muted mb-2">Aucun mouvement enregistré</p>
                                                <a href="<?= generateUrl('pages/stock/stock_entry.php?id='.$id_produit) ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    Créer le premier mouvement
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($mouvements as $m): ?>
                                        <tr>
                                            <td class="pl-4">
                                                <div class="font-weight-medium">
                                                    <?= date('d/m/Y', strtotime($m['date_mouvement'])) ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?= date('H:i', strtotime($m['date_mouvement'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($m['type_mouvement'] == 'entree'): ?>
                                                    <span class="badge badge-success-light text-success px-3 py-1">
                                                        Entrée
                                                    </span>
                                                <?php elseif ($m['type_mouvement'] == 'sortie'): ?>
                                                    <span class="badge badge-danger-light text-danger px-3 py-1">
                                                         Sortie
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-info-light text-info px-3 py-1">
                                                     Ajustement
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="font-weight-bold <?= $m['type_mouvement'] == 'sortie' ? 'text-danger' : 'text-success' ?>">
                                                    <?= $m['type_mouvement'] == 'sortie' ? '-' : '+' ?>
                                                    <?= number_format($m['quantite'], 0) ?>
                                                </div>
                                                <small class="text-muted">
                                                    Solde: <?= number_format($m['quantite_apres'], 0) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="mr-2">
                                                        
                                                    </div>
                                                    <div>
                                                        <?= htmlspecialchars($m['utilisateur_nom']) ?>
                                                        <?php if ($m['reference']): ?>
                                                            <small class="d-block text-muted">
                                                                Réf: <?= htmlspecialchars($m['reference']) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="pr-4">
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($m['notes'] ? substr($m['notes'], 0, 50) . (strlen($m['notes']) > 50 ? '...' : '') : '-') ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (!empty($mouvements)): ?>
                    <div class="card-footer bg-white border-top py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Affichage des 10 derniers mouvements
                            </small>
                            <a href="<?= generateUrl('pages/stock/stock_history.php?produit='.$id_produit) ?>" 
                               class="btn btn-sm btn-outline-primary">
                               Voir l'historique complet
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction pour formater les dates
document.addEventListener('DOMContentLoaded', function() {
    // Mise en évidence du statut du stock
    const stockLevel = <?= $stock_niveau ?>;
    const alertThreshold = <?= $seuil_alerte ?>;
    
    if (stockLevel <= alertThreshold) {
        // Animation pour le statut critique
        const stockBadge = document.querySelector('.badge-stock');
        if (stockLevel <= (alertThreshold / 2)) {
            setInterval(() => {
                stockBadge.classList.toggle('badge-danger');
            }, 1000);
        }
    }
    
    // Affichage dynamique des détails du produit
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach((card, index) => {
        card.style.animationDelay = (index * 0.1) + 's';
    });
});

// Fonction d'impression
function printProductSheet() {
    const printContent = document.querySelector('.content-wrapper').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div class="container mt-4">
            <div class="text-center mb-4">
                <h3>Fiche produit: <?= htmlspecialchars($produit['nom_produit']) ?></h3>
                <p>Généré le: ${new Date().toLocaleDateString('fr-FR')}</p>
            </div>
            ${printContent}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContent;
    window.location.reload();
}
</script>

<?php
require_once(__DIR__ . '/../../templates/footer.php');
ob_end_flush();
?>