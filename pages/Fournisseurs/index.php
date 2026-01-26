<?php
// index.php pour la liste des fournisseurs
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Sécurité et Permissions
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$allowed_roles = ['patron', 'gestionnaire_stock', 'receptionniste'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header('Location: ../index.php?error=Permission non autorisée');
    exit;
}

// 2. Gestion des chemins (CORRECTION : Utilisation de chemins système absolus)
// On définit la racine du projet à partir de ce fichier (on remonte de 2 niveaux)
$root = realpath(__DIR__ . '/../../');

// Inclusion des fichiers de fonctions
require_once $root . '/fonctions/database.php';
require_once $root . '/fonctions/gestion_fournisseurs.php';

$titre = 'Gestion des fournisseurs';

// 3. Récupération des données avec filtres
$filtres = [];
if (!empty($_GET['categorie'])) {
    $filtres['categorie'] = $_GET['categorie'];
}
if (!empty($_GET['recherche'])) {
    $filtres['recherche'] = $_GET['recherche'];
}

// Assurez-vous que ces fonctions existent dans fonctions/gestion_fournisseurs.php
$fournisseurs = getAllFournisseurs($pdo, $filtres);
$stats = getStatsFournisseurs($pdo);

// 4. Inclusion des templates (CORRECTION : Chemins locaux sans HTTP)
require_once  '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    .card-stats { transition: transform 0.2s; border: none; }
    .card-stats:hover { transform: translateY(-5px); }
    
    .badge-categorie {
        font-size: 0.85rem;
        padding: 0.4em 0.8em;
        border-radius: 50rem;
        display: inline-block;
        min-width: 100px;
        text-align: center;
    }
    /* Couleurs des badges basées sur vos classes */
    .bg-produit_nettoyage { background-color: #3498db !important; color: #fff; }
    .bg-equipement { background-color: #2ecc71 !important; color: #fff; }
    .bg-emballage { background-color: #e74c3c !important; color: #fff; }
    .bg-autre { background-color: #9b59b6 !important; color: #fff; }
    
    .solde-negatif { color: #d9534f; font-weight: bold; }
    .solde-positif { color: #5cb85c; font-weight: bold; }

    @media (max-width: 768px) {
        .btn-group-sm-mobile { width: 100%; display: flex; flex-direction: column; gap: 5px; }
    }
</style>
<br> <br> <br>
<div class="container-fluid py-5"> <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 mt-4">
        <h2 class="page-header m-0 text-dark fw-bold"><?= htmlspecialchars($titre) ?></h2>
        <div class="mt-3 mt-md-0">
            <a href="ajouter.php" class="btn btn-success shadow-sm">
                 Nouveau fournisseur
            </a>
            <a href="rapport.php" class="btn btn-outline-info shadow-sm">
                 Rapport
            </a>
        </div>
    </div>

    <?php if ($stats): ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card card-stats bg-primary text-white shadow-sm">
                <div class="card-body p-3 text-center">
                    <h5 class="mb-1 fw-bold"><?= $stats['total_fournisseurs'] ?></h5>
                    <p class="small mb-0">Partenaires actifs</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-stats bg-danger text-white shadow-sm">
                <div class="card-body p-3 text-center">
                    <h5 class="mb-1 fw-bold"><?= number_format($stats['total_soldes'], 0, ',', ' ') ?> FCFA</h5>
                    <p class="small mb-0">Total Dettes</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-stats bg-warning text-dark shadow-sm">
                <div class="card-body p-3 text-center">
                    <h5 class="mb-1 fw-bold"><?= $stats['fournisseurs_avec_solde'] ?></h5>
                    <p class="small mb-0">À régler</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-stats bg-success text-white shadow-sm">
                <div class="card-body p-3 text-center">
                    <h5 class="mb-1 fw-bold"><?= number_format($stats['moyenne_solde'] ?? 0, 0, ',', ' ') ?> FCFA</h5>
                    <p class="small mb-0">Moyenne / Fournisseur</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body bg-light">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-bold small text-uppercase">Catégorie</label>
                    <select class="form-select" name="categorie">
                        <option value="">Toutes les catégories</option>
                        <option value="produit_nettoyage" <?= (isset($_GET['categorie']) && $_GET['categorie'] == 'produit_nettoyage') ? 'selected' : '' ?>>Produits de nettoyage</option>
                        <option value="equipement" <?= (isset($_GET['categorie']) && $_GET['categorie'] == 'equipement') ? 'selected' : '' ?>>Équipement</option>
                        <option value="emballage" <?= (isset($_GET['categorie']) && $_GET['categorie'] == 'emballage') ? 'selected' : '' ?>>Emballage</option>
                        <option value="autre" <?= (isset($_GET['categorie']) && $_GET['categorie'] == 'autre') ? 'selected' : '' ?>>Autre</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-bold small text-uppercase">Recherche</label>
                    <input type="text" class="form-control" name="recherche" 
                           value="<?= htmlspecialchars($_GET['recherche'] ?? '') ?>" 
                           placeholder="Nom, contact ou téléphone...">
                </div>
                <div class="col-12 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        Filtrer
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 text-primary fw-bold"> Répertoire Fournisseurs</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3">Fournisseur</th>
                            <th>Contact & Téléphone</th>
                            <th class="text-center">Catégorie</th>
                            <th class="text-end">Solde dû</th>
                            <th class="text-center pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($fournisseurs)): ?>
                            <?php foreach ($fournisseurs as $fournisseur): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($fournisseur['nom_fournisseur']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars(substr($fournisseur['adresse'] ?? 'Pas d\'adresse', 0, 50)) ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold small"><?= htmlspecialchars($fournisseur['contact'] ?? '-') ?></div>
                                    <div class="text-primary small">
                                       <?= htmlspecialchars($fournisseur['telephone'] ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php 
                                        $cat = $fournisseur['categorie'];
                                        $labels = [
                                            'produit_nettoyage' => 'Nettoyage',
                                            'equipement' => 'Équipement',
                                            'emballage' => 'Emballage',
                                            'autre' => 'Autre'
                                        ];
                                    ?>
                                    <span class="badge-categorie bg-<?= htmlspecialchars($cat) ?>">
                                        <?= $labels[$cat] ?? $cat ?>
                                    </span>
                                </td>
                                <td class="text-end fw-bold <?= $fournisseur['solde_du'] > 0 ? 'solde-negatif' : 'solde-positif' ?>">
                                    <?= number_format($fournisseur['solde_du'], 0, ',', ' ') ?> FCFA
                                </td>
                                <td class="text-center pe-3">
                                    <div class="btn-group shadow-sm">
                                        <a href="voir.php?id=<?= $fournisseur['id_fournisseur'] ?>" class="btn btn-sm btn-outline-info">
                                          Voir
                                        </a>
                                        <a href="modifier.php?id=<?= $fournisseur['id_fournisseur'] ?>" class="btn btn-sm btn-outline-warning">
                                            Modifier
                                        </a>
                                        <a href="supprimer.php?id=<?= $fournisseur['id_fournisseur'] ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Attention : Archiver ce partenaire ?')">
                                        Supprimer
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted mb-3">Aucun fournisseur ne correspond à votre recherche.</div>
                                    <a href="ajouter.php" class="btn btn-primary">Ajouter votre premier fournisseur</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php 
// CORRECTION : Toujours utiliser le chemin local pour le footer
require_once  '../../templates/footer.php'; 
?>