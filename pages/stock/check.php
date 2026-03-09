<?php
session_start();

// Inclusion des fichiers de configuration et fonctions
require_once "../../fonctions/database.php"; // Votre fichier de connexion PDO
require_once(__DIR__ . '/../../templates/header.php');
require_once(__DIR__ . '/../../templates/navigation.php');

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Vérification des permissions (optionnel)
$allowed_roles = ['admin', 'directeur', 'patron', 'gestionnaire_stock'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo "<div class='container mt-5 alert alert-danger'>Accès non autorisé.</div>";
    require_once(__DIR__ . '/../../templates/footer.php');
    exit();
}

// Variable pour stocker le produit
$produit = null;
$id_produit = null;

// Vérification si un ID est passé en GET
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_produit = (int)$_GET['id'];
    
    try {
        // Connexion à la base de données
        $query = "SELECT p.*, f.nom_fournisseur, f.contact, f.telephone as tel_fournisseur, f.email as email_fournisseur
                  FROM produits p
                  LEFT JOIN fournisseurs f ON p.id_fournisseur = f.id_fournisseur
                  WHERE p.id_produit = :id";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id_produit]);
        $produit = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$produit) {
            $error_message = "Produit introuvable.";
        }
    } catch (PDOException $e) {
        $error_message = "Erreur de base de données : " . $e->getMessage();
    }
} else {
    // Si pas d'ID, on va afficher un formulaire de recherche ou rediriger
    $error_message = "Veuillez sélectionner un produit à consulter.";
}
?>
<br> <br> <br>
<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="inventory.php">Stock</a></li>
            <li class="breadcrumb-item active">
                <?= isset($produit) ? 'Détails du produit' : 'Consulter produit' ?>
            </li>
        </ol>
    </nav>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-warning">
            <h5>⚠️ <?= htmlspecialchars($error_message) ?></h5>
            <hr>
            <p>Vous pouvez :</p>
            <div class="mt-3">
                <a href="inventory.php" class="btn btn-primary">← Retour à la liste des produits</a>
                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'patron'])): ?>
                    <a href="create_product.php" class="btn btn-success">➕ Créer un nouveau produit</a>
                <?php endif; ?>
            </div>
            
            <!-- Formulaire de recherche rapide -->
            <div class="mt-4">
                <h6>Rechercher un produit :</h6>
                <form action="search.php" method="get" class="row g-2">
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control" placeholder="Nom du produit, catégorie..." required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-info w-100">🔍 Rechercher</button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Affichage des détails du produit -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h3 class="mb-0">📦 <?= htmlspecialchars($produit['nom_produit']) ?></h3>
                <span class="badge bg-<?= $produit['quantite_stock'] <= $produit['seuil_alerte'] ? 'danger' : 'success' ?>">
                    Stock : <?= number_format($produit['quantite_stock'], 2) ?> <?= htmlspecialchars($produit['unite_mesure']) ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 border-end">
                        <h5 class="text-muted border-bottom pb-2">📋 Informations Générales</h5>
                        <table class="table table-borderless">
                            <tr>
                                <th>ID Produit:</th>
                                <td><code>#<?= $produit['id_produit'] ?></code></td>
                            </tr>
                            <tr>
                                <th>Catégorie:</th>
                                <td><span class="badge bg-info text-dark"><?= ucfirst($produit['categorie']) ?></span></td>
                            </tr>
                            <tr>
                                <th>Prix Unitaire:</th>
                                <td><strong><?= number_format($produit['prix_unitaire'], 2, ',', ' ') ?> FCFA</strong></td>
                            </tr>
                            <tr>
                                <th>Seuil d'alerte:</th>
                                <td>
                                    <span class="<?= $produit['quantite_stock'] <= $produit['seuil_alerte'] ? 'text-danger fw-bold' : '' ?>">
                                        <?= number_format($produit['seuil_alerte'], 2) ?> <?= $produit['unite_mesure'] ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Valeur en stock:</th>
                                <td>
                                    <strong class="text-success">
                                        <?= number_format($produit['quantite_stock'] * $produit['prix_unitaire'], 2, ',', ' ') ?> FCFA
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <th>Emplacement:</th>
                                <td><?= htmlspecialchars($produit['emplacement'] ?? 'Non défini') ?></td>
                            </tr>
                            <tr>
                                <th>Date de péremption:</th>
                                <td>
                                    <?php if ($produit['date_peremption']): 
                                        $date_peremption = new DateTime($produit['date_peremption']);
                                        $today = new DateTime();
                                        $interval = $today->diff($date_peremption);
                                        $days = (int)$interval->format('%r%a');
                                        
                                        if ($days < 0) {
                                            echo '<span class="text-danger fw-bold">Expiré depuis ' . abs($days) . ' jours</span>';
                                        } elseif ($days < 30) {
                                            echo '<span class="text-warning fw-bold">' . $days . ' jours restants</span>';
                                        } else {
                                            echo $date_peremption->format('d/m/Y');
                                        }
                                    else: ?>
                                        Non définie
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Statut:</th>
                                <td>
                                    <?php if ($produit['est_actif']): ?>
                                        <span class="badge bg-success">✅ Actif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">❌ Inactif</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="col-md-6">
                        <h5 class="text-muted border-bottom pb-2">🏢 Détails Fournisseur</h5>
                        <?php if ($produit['id_fournisseur']): ?>
                            <table class="table table-borderless">
                                <tr>
                                    <th>Nom:</th>
                                    <td><?= htmlspecialchars($produit['nom_fournisseur']) ?></td>
                                </tr>
                                <tr>
                                    <th>Contact:</th>
                                    <td><?= htmlspecialchars($produit['contact'] ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th>Téléphone:</th>
                                    <td>
                                        <?php if ($produit['tel_fournisseur']): ?>
                                            <a href="tel:<?= htmlspecialchars($produit['tel_fournisseur']) ?>" class="text-decoration-none">
                                                📞 <?= htmlspecialchars($produit['tel_fournisseur']) ?>
                                            </a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td>
                                        <?php if ($produit['email_fournisseur']): ?>
                                            <a href="mailto:<?= htmlspecialchars($produit['email_fournisseur']) ?>" class="text-decoration-none">
                                                ✉️ <?= htmlspecialchars($produit['email_fournisseur']) ?>
                                            </a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Actions rapides avec le fournisseur -->
                            <div class="mt-4">
                                <a href="../fournisseurs/check.php?id=<?= $produit['id_fournisseur'] ?>" class="btn btn-outline-info btn-sm">
                                    👁️ Voir ce fournisseur
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <div class="mb-3">📭</div>
                                <p>Aucun fournisseur associé.</p>
                                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'patron'])): ?>
                                    <a href="edit_product.php?id=<?= $produit['id_produit'] ?>" class="btn btn-outline-warning btn-sm">
                                        ✏️ Ajouter un fournisseur
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Section historique/statistiques -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">📊 Statistiques</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <div class="stat-value"><?= number_format($produit['quantite_stock'], 2) ?></div>
                                            <div class="stat-label">Quantité en stock</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <div class="stat-value text-success"><?= number_format($produit['prix_unitaire'], 2, ',', ' ') ?> FCFA</div>
                                            <div class="stat-label">Prix unitaire</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <div class="stat-value text-primary"><?= number_format($produit['quantite_stock'] * $produit['prix_unitaire'], 2, ',', ' ') ?> FCFA</div>
                                            <div class="stat-label">Valeur totale</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light d-flex justify-content-between">
                <div>
                    <a href="inventory.php" class="btn btn-secondary">
                        ← Retour à la liste
                    </a>
                    <a href="check.php" class="btn btn-outline-primary">
                        🔄 Actualiser
                    </a>
                </div>
                <div>
                    <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'patron', 'gestionnaire_stock'])): ?>
                        <a href="edit_product.php?id=<?= $produit['id_produit'] ?>" class="btn btn-warning">✏️ Modifier</a>
                        
                        <?php if ($produit['est_actif']): ?>
                            <a href="disable_product.php?id=<?= $produit['id_produit'] ?>" class="btn btn-outline-danger" 
                               onclick="return confirm('Désactiver ce produit ?')">❌ Désactiver</a>
                        <?php else: ?>
                            <a href="enable_product.php?id=<?= $produit['id_produit'] ?>" class="btn btn-outline-success" 
                               onclick="return confirm('Réactiver ce produit ?')">✅ Réactiver</a>
                        <?php endif; ?>
                        
                        <button onclick="confirmDelete(<?= $produit['id_produit'] ?>)" class="btn btn-danger">🗑️ Supprimer</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.stat-card {
    padding: 15px;
    border-radius: 8px;
    background: #f8f9fa;
    transition: transform 0.2s;
}
.stat-card:hover {
    transform: translateY(-2px);
    background: #e9ecef;
}
.stat-value {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}
.stat-label {
    font-size: 14px;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.badge {
    font-size: 14px;
    padding: 8px 12px;
}
</style>

<script>
function confirmDelete(id) {
    if (confirm("⚠️ Êtes-vous sûr de vouloir supprimer définitivement ce produit ?\n\nCette action est irréversible !")) {
        window.location.href = "delete_product.php?id=" + id;
    }
}

// Navigation avec historique
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du bouton retour
    const backBtn = document.querySelector('a[href="inventory.php"]');
    if (backBtn && document.referrer.includes(window.location.hostname)) {
        backBtn.addEventListener('click', function(e) {
            e.preventDefault();
            history.back();
        });
    }
});
</script>

<?php require_once(__DIR__ . '/../../templates/footer.php'); ?>