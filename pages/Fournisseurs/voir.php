<?php
// pages/Fournisseurs/voir.php
session_start();

// Inclure d'abord les fonctions nécessaires
require_once(__DIR__ . '/../../fonctions/database.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit();
}

// Vérification des permissions
$allowed_roles = ['patron', 'gestionnaire_stock'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header('Location: ../index.php?error=Permission non autorisée');
    exit();
}

// Récupération de l'ID du fournisseur
$fournisseur_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$fournisseur_id) {
    header('Location: index.php');
    exit();
}

// Initialiser les variables
$fournisseur = null;
$nbProduits = 0;
$historique = [];
$erreur = null;

try {
    // Récupérer les infos du fournisseur
    $stmt = $pdo->prepare("SELECT * FROM fournisseurs WHERE id_fournisseur = ?");
    $stmt->execute([$fournisseur_id]);
    $fournisseur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fournisseur) {
        $_SESSION['error'] = "Fournisseur non trouvé.";
        header('Location: index.php');
        exit();
    }

    // Récupérer le nombre de produits liés
    $stmtProd = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE id_fournisseur = ?");
    $stmtProd->execute([$fournisseur_id]);
    $nbProduits = $stmtProd->fetchColumn();

    // Récupérer l'historique récent des mouvements de stock pour ce fournisseur
    $stmtHistory = $pdo->prepare("
        SELECT 
            m.date_mouvement,
            m.type_mouvement,
            m.quantite,
            p.nom_produit,
            u.nom_complet as utilisateur
        FROM mouvements_stock m
        LEFT JOIN produits p ON m.id_produit = p.id_produit
        LEFT JOIN utilisateurs u ON m.id_utilisateur = u.id_utilisateur
        WHERE p.id_fournisseur = ? 
        ORDER BY m.date_mouvement DESC 
        LIMIT 10
    ");
    $stmtHistory->execute([$fournisseur_id]);
    $historique = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $erreur = "Erreur de base de données : " . $e->getMessage();
}


// Inclure le header avant tout output HTML
require_once('../../templates/header.php');
require_once('../../templates/navigation.php');
?>

<div class="container-fluid main-content">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="mt-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= generateUrl('pages/dashboard.php') ?>">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Fournisseurs</a></li>
                    <li class="breadcrumb-item active">Détails de <?= htmlspecialchars($fournisseur['nom_fournisseur'] ?? 'Fournisseur') ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if ($erreur): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Colonne gauche - Informations du fournisseur -->
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"> Fiche Fournisseur</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="avatar-circle mb-3">
                            
                        </div>
                        <h4 class="card-title"><?= htmlspecialchars($fournisseur['nom_fournisseur'] ?? 'N/A') ?></h4>
                        <span class="badge badge-info">ID: #<?= $fournisseur['id_fournisseur'] ?? 'N/A' ?></span>
                    </div>
                    
                    <hr>
                    
                    <div class="fournisseur-info">
                        <p>
                            <strong>Contact :</strong><br>
                            <span class="ml-4"><?= htmlspecialchars($fournisseur['contact'] ?? 'N/A') ?></span>
                        </p>
                        
                        <p>
                            <strong>Téléphone :</strong><br>
                            <span class="ml-4">
                                <?php if (!empty($fournisseur['telephone'])): ?>
                                    <a href="tel:<?= htmlspecialchars($fournisseur['telephone']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($fournisseur['telephone']) ?>
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </span>
                        </p>
                        
                        <p>
                            <strong>Email :</strong><br>
                            <span class="ml-4">
                                <?php if (!empty($fournisseur['email'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($fournisseur['email']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($fournisseur['email']) ?>
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </span>
                        </p>
                        
                        <p>
                            <strong>Adresse :</strong><br>
                            <span class="ml-4"><?= nl2br(htmlspecialchars($fournisseur['adresse'] ?? 'N/A')) ?></span>
                        </p>
                        
                        <p>
                            <strong>Catégorie :</strong><br>
                            <span class="ml-4">
                                <?php
                                $categorie = $fournisseur['categorie'] ?? '';
                                $badgeClass = '';
                                switch ($categorie) {
                                    case 'produit_nettoyage':
                                        $badgeClass = 'badge-primary';
                                        $categorieText = 'Produits nettoyage';
                                        break;
                                    case 'equipement':
                                        $badgeClass = 'badge-success';
                                        $categorieText = 'Équipement';
                                        break;
                                    case 'emballage':
                                        $badgeClass = 'badge-warning';
                                        $categorieText = 'Emballage';
                                        break;
                                    case 'autre':
                                        $badgeClass = 'badge-secondary';
                                        $categorieText = 'Autre';
                                        break;
                                    default:
                                        $badgeClass = 'badge-light';
                                        $categorieText = $categorie;
                                }
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $categorieText ?></span>
                            </span>
                        </p>
                        
                        <p>
                            <strong>Solde dû :</strong><br>
                            <span class="ml-4 <?= ($fournisseur['solde_du'] ?? 0) > 0 ? 'text-danger font-weight-bold' : 'text-success' ?>">
                                <?= number_format($fournisseur['solde_du'] ?? 0, 2, ',', ' ') ?> FCFA
                            </span>
                        </p>
                        
                        <p>
                            <strong>Statut :</strong><br>
                            <span class="ml-4">
                                <?php if (($fournisseur['est_actif'] ?? 0) == 1): ?>
                                    <span class="badge badge-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactif</span>
                                <?php endif; ?>
                            </span>
                        </p>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <a href="modifier.php?id=<?= $fournisseur_id ?>" class="btn btn-warning btn-sm flex-fill">
                        Modifier
                        </a>
                        <button class="btn btn-danger btn-sm flex-fill" onclick="confirmDelete(<?= $fournisseur_id ?>)">
                         Supprimer
                        </button>
                        <a href="javascript:history.back()" class="btn btn-secondary btn-sm flex-fill">
   <- Retour
</a>
                    </div>
                </div>
            </div>
            
            <!-- Card statistiques -->
            <div class="card shadow mt-4 border-left-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-uppercase text-success font-weight-bold small">Produits Fournis</div>
                            <div class="h4 mb-0"><?= $nbProduits ?> articles</div>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne droite - Historique des mouvements -->
        <div class="col-lg-8 col-md-6">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary">
                    Derniers mouvements
                    </h5>
                    <a href="../stock/stock_entry.php?fournisseur_id=<?= $fournisseur_id ?>" class="btn btn-success btn-sm">
                        Nouvelle entrée
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Produit</th>
                                    <th class="text-center">Quantité</th>
                                    <th class="text-center">Type</th>
                                    <th>Par</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($historique)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            
                                            Aucun mouvement trouvé
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($historique as $mouvement): ?>
                                    <tr>
                                        <td>
                                            <small><?= date('d/m/Y', strtotime($mouvement['date_mouvement'])) ?></small><br>
                                            <small class="text-muted"><?= date('H:i', strtotime($mouvement['date_mouvement'])) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($mouvement['nom_produit'] ?? 'Produit inconnu') ?></td>
                                        <td class="text-center">
                                            <span class="badge badge-pill <?= $mouvement['type_mouvement'] == 'entree' ? 'badge-success' : 'badge-warning' ?>">
                                                <?= $mouvement['quantite'] ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($mouvement['type_mouvement'] == 'entree'): ?>
                                                <span class="badge badge-success">Entrée</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Sortie</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($mouvement['utilisateur'] ?? 'Système') ?></small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (!empty($historique)): ?>
                    <div class="text-center mt-3">
                        <a href="../stock/stock_history.php?fournisseur_id=<?= $fournisseur_id ?>" class="btn btn-outline-primary btn-sm">
                           Voir tous les mouvements
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Section produits du fournisseur -->
            <div class="card shadow mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Produits de ce fournisseur</h5>
                </div>
                <div class="card-body">
                    <?php 
                    // Récupérer les produits de ce fournisseur
                    try {
                        $stmtProduits = $pdo->prepare("
                            SELECT p.*, 
                                   (SELECT SUM(quantite) FROM mouvements_stock WHERE id_produit = p.id_produit AND type_mouvement = 'entree') as total_entrees,
                                   (SELECT SUM(quantite) FROM mouvements_stock WHERE id_produit = p.id_produit AND type_mouvement = 'sortie') as total_sorties
                            FROM produits p
                            WHERE p.id_fournisseur = ? AND p.est_actif = 1
                            ORDER BY p.nom_produit
                        ");
                        $stmtProduits->execute([$fournisseur_id]);
                        $produits = $stmtProduits->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        $produits = [];
                    }
                    ?>
                    
                    <?php if (empty($produits)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                            Aucun produit associé à ce fournisseur
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($produits as $produit): 
                                $stock = ($produit['total_entrees'] ?? 0) - ($produit['total_sorties'] ?? 0);
                                $stockClass = $stock <= ($produit['seuil_alerte'] ?? 10) ? 'border-left-danger' : 'border-left-success';
                            ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100 border <?= $stockClass ?>">
                                    <div class="card-body">
                                        <h6 class="card-title text-truncate"><?= htmlspecialchars($produit['nom_produit']) ?></h6>
                                        <div class="small text-muted mb-2">
                                            Catégorie: <?= htmlspecialchars($produit['categorie']) ?>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="small">Stock: <strong><?= $stock ?></strong> <?= $produit['unite_mesure'] ?></div>
                                                <?php if ($stock <= ($produit['seuil_alerte'] ?? 10)): ?>
                                                    <span class="badge badge-danger">Stock faible</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-weight-bold"><?= number_format($produit['prix_unitaire'], 2, ',', ' ') ?> FCFA</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent border-top-0 py-2">
                                        <a href="../stock/produit.php?id=<?= $produit['id_produit'] ?>" class="btn btn-sm btn-outline-primary btn-block">
                                            Détails
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Cette action supprimera le fournisseur. Les produits associés seront conservés mais sans fournisseur.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "supprimer.php?id=" + id;
        }
    });
}

// Fonction pour formater les numéros de téléphone
function formatPhoneNumber(phone) {
    return phone.replace(/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/, '$1 $2 $3 $4 $5');
}

// Appliquer le formatage aux numéros de téléphone
document.addEventListener('DOMContentLoaded', function() {
    const phoneElements = document.querySelectorAll('a[href^="tel:"]');
    phoneElements.forEach(el => {
        const phone = el.getAttribute('href').replace('tel:', '');
        el.textContent = formatPhoneNumber(phone);
    });
});
</script>

<style>
.main-content {
    margin-top: 80px; /* Pour compenser la navbar fixe */
    padding: 15px;
}

.avatar-circle {
    width: 80px;
    height: 80px;
    background-color: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    border: 3px solid #e9ecef;
}

.fournisseur-info p {
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #f1f1f1;
}

.fournisseur-info p:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.border-left-success {
    border-left: 4px solid #28a745 !important;
}

.border-left-danger {
    border-left: 4px solid #dc3545 !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .main-content {
        margin-top: 60px;
        padding: 10px;
    }
    
    .avatar-circle {
        width: 60px;
        height: 60px;
    }
    
    .card-title {
        font-size: 1.25rem;
    }
}

@media (max-width: 576px) {
    .d-flex.flex-wrap {
        flex-direction: column;
    }
    
    .btn-sm.flex-fill {
        margin-bottom: 5px;
        width: 100%;
    }
}
</style>

<?php
require_once('../../templates/footer.php');
?>