<?php
// 1. Démarrage de la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * 2. GESTION DES CHEMINS
 * On utilise des chemins relatifs simples comme dans votre liste_agences.php
 */
require_once '../../../fonctions/database.php';

// 3. VÉRIFICATION DE L'ACCÈS
if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: ../../../index.php");
    exit();
}

// Récupération sécurisée des infos de session
$nom_affichage = $_SESSION['nom_complet'] ?? $_SESSION['username'] ?? 'Utilisateur';
$role = $_SESSION['role'] ?? 'Non défini';

// 4. INCLUSION DES TEMPLATES (Chemins physiques relatifs)
include '../../../templates/header.php';
include '../../../templates/navigation.php';
?>

<br><br><br>

<main class="container mt-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 text-white">Gestion des Agences</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <span class="badge bg-info text-dark p-2">Rôle : <?= htmlspecialchars($role) ?></span>
        </div>
    </div>

    <div class="alert alert-light border shadow-sm">
        <p class="mb-0">Bienvenue, <strong><?= htmlspecialchars($nom_affichage) ?></strong>. Voici les outils de gestion pour vos points de vente.</p>
    </div>

    <div class="row row-cols-1 row-cols-md-3 g-4 mt-2">
        <div class="col">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body text-center">
                    <div class="mb-3 text-primary"><i class="bi bi-cash-coin fs-1"></i></div>
                    <h5 class="card-title">Flux de Caisse</h5>
                    <p class="card-text text-muted">Ouverture, fermeture et suivi des recettes journalières.</p>
                    <a href="gestion_caisses.php" class="btn btn-outline-primary w-100">Gérer la caisse</a>
                </div>
            </div>
        </div>

        <?php if ($role === 'Admin' || $role === 'patron'): ?>
            <div class="col">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body text-center">
                        <div class="mb-3 text-success"><i class="bi bi-geo-alt fs-1"></i></div>
                        <h5 class="card-title">Configuration Agences</h5>
                        <p class="card-text text-muted">Modifier les coordonnées des boutiques et les responsables.</p>
                        <a href="liste_agences.php" class="btn btn-outline-success w-100">Liste des agences</a>
                    </div>
                </div>
            </div>
            
            <div class="col">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body text-center">
                        <div class="mb-3 text-warning"><i class="bi bi-graph-up fs-1"></i></div>
                        <h5 class="card-title">Statistiques</h5>
                        <p class="card-text text-muted">Performance globale par point de vente.</p>
                        <a href="../rapports/agence_stats.php" class="btn btn-outline-warning w-100">Voir les rapports</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
// 6. PIED DE PAGE
include '../../../templates/footer.php';
?>