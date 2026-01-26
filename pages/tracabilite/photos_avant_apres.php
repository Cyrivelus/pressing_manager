<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Correction du chemin physique pour Windows/XAMPP
$root = realpath(__DIR__ . '/../../');

if (!file_exists($root . '/fonctions/database.php')) {
    die("Fichier database.php introuvable. Vérifiez le chemin : " . $root);
}

require_once  '../../fonctions/database.php';

$titre = "Archives Visuelles & Preuves Qualité";

// 1. Récupération sécurisée des articles avec photos
// Note : Les colonnes doivent exister en DB (voir étape 1)
try {
    $sql = "SELECT l.id_ligne, l.description, l.photo_avant, l.photo_apres, 
                   t.numero_ticket, c.nom_client, t.date_depot
            FROM lignes_ticket l
            JOIN tickets t ON l.id_ticket = t.id_ticket
            JOIN clients c ON t.id_client = c.id_client
            WHERE l.photo_avant IS NOT NULL OR l.photo_apres IS NOT NULL
            ORDER BY t.date_depot DESC LIMIT 12";
    $galerie = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    // Message d'erreur propre si la table n'est pas à jour
    die("<div class='alert alert-danger m-5'>Erreur SQL : Les colonnes photo_avant/apres sont absentes. <br>Exécutez l'ALTER TABLE dans phpMyAdmin.</div>");
}

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-camera-retro text-primary me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Documentation visuelle pour éviter les litiges clients</p>
        </div>
        <button class="btn btn-dark shadow-sm" onclick="location.reload()">
            <i class="fas fa-sync"></i> Actualiser
        </button>
    </div>

    

    <div class="row g-4">
        <?php if (empty($galerie)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-images fa-3x text-light mb-3"></i>
                <p class="text-muted">Aucune photo archivée pour le moment.</p>
            </div>
        <?php else: ?>
            <?php foreach($galerie as $item): ?>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 overflow-hidden" style="border-radius: 15px;">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary">Ticket #<?= $item['numero_ticket'] ?></span>
                            <small class="text-muted"><?= date('d/m/Y', strtotime($item['date_depot'])) ?></small>
                        </div>
                        <h6 class="fw-bold mt-2 mb-0"><?= htmlspecialchars($item['description'] ?? 'Sans description') ?></h6>
                        <small class="text-muted">Client : <?= htmlspecialchars($item['nom_client'] ?? 'Anonyme') ?></small>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="d-flex bg-light" style="height: 250px;">
                            <div class="w-50 border-end position-relative">
                                <img src="<?= $item['photo_avant'] ?: '../../assets/img/no-photo.png' ?>" 
                                     class="img-fluid h-100 w-100 object-fit-cover" alt="Avant">
                                <span class="badge bg-dark position-absolute bottom-0 start-0 m-2 opacity-75">AVANT</span>
                            </div>
                            <div class="w-50 position-relative">
                                <img src="<?= $item['photo_apres'] ?: '../../assets/img/no-photo.png' ?>" 
                                     class="img-fluid h-100 w-100 object-fit-cover" alt="Après">
                                <span class="badge bg-success position-absolute bottom-0 end-0 m-2 opacity-75">APRÈS</span>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between">
                        <button class="btn btn-sm btn-outline-secondary w-100 me-2">
                            <i class="fas fa-search-plus"></i> Comparer
                        </button>
                        <button class="btn btn-sm btn-light border">
                            <i class="fas fa-paper-plane text-primary"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    .object-fit-cover { object-fit: cover; }
    .card:hover { transform: translateY(-5px); transition: 0.3s ease; }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
</style>

<?php require_once  '../../templates/footer.php'; ?>