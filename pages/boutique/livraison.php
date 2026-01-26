<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Suivi des Livraisons Boutique";

// 2. Récupération des livraisons en attente ou en cours
$sql = "SELECT l.*, v.id_vente, c.nom_client, c.telephone, c.adresse
        FROM livraisons_boutique l
        JOIN ventes_boutique v ON l.id_vente = v.id_vente
        JOIN clients c ON v.id_client = c.id_client
        WHERE l.statut != 'livré'
        ORDER BY l.date_prevue ASC";
$livraisons = $pdo->query($sql)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<style>
    .delivery-card { border-radius: 12px; border: none; border-left: 5px solid #ddd; transition: 0.3s; }
    .status-en-route { border-left-color: #f1c40f !important; background-color: #fef9e7; }
    .status-attente { border-left-color: #3498db !important; background-color: #ebf5fb; }
    .btn-action-round { border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; }
</style>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0"><?= $titre ?></h2>
            <p class="text-muted">Gestion des expéditions de produits boutique</p>
        </div>
        <div class="badge bg-primary px-3 py-2">
            <?= count($livraisons) ?> Livraison(s) active(s)
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <?php if(empty($livraisons)): ?>
                <div class="text-center py-5 bg-light rounded-3">
                    <i class="fas fa-truck-loading fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Aucune livraison en attente pour le moment.</p>
                </div>
            <?php endif; ?>

            <?php foreach($livraisons as $l): ?>
                <?php 
                    $status_class = ($l['statut'] == 'en_route') ? 'status-en-route' : 'status-attente';
                ?>
                <div class="card delivery-card shadow-sm mb-3 <?= $status_class ?>">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center border-end">
                                <span class="d-block fw-bold text-dark"><?= date('d M', strtotime($l['date_prevue'])) ?></span>
                                <small class="text-muted"><?= date('H:i', strtotime($l['date_prevue'])) ?></small>
                            </div>
                            <div class="col-md-5">
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($l['nom_client']) ?></h6>
                                <p class="small text-muted mb-0">
                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($l['adresse']) ?>
                                </p>
                                <p class="small mb-0"><i class="fas fa-phone"></i> <?= $l['telephone'] ?></p>
                            </div>
                            <div class="col-md-3">
                                <div class="small fw-bold">Commande #V-<?= str_pad($l['id_vente'], 5, '0', STR_PAD_LEFT) ?></div>
                                <span class="badge <?= $l['statut'] == 'en_route' ? 'bg-warning text-dark' : 'bg-info' ?>">
                                    <?= strtoupper(str_replace('_', ' ', $l['statut'])) ?>
                                </span>
                            </div>
                            <div class="col-md-2 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-outline-success btn-action-round" title="Valider Livraison" onclick="validerLivraison(<?= $l['id_livraison'] ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-outline-primary btn-action-round" title="Appeler">
                                        <a href="tel:<?= $l['telephone'] ?>" class="text-inherit"><i class="fas fa-phone-alt"></i></a>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm bg-dark text-white p-4 sticky-top" style="top: 100px;">
                <h5 class="fw-bold mb-4">Aujourd'hui</h5>
                <div class="d-flex justify-content-between mb-3">
                    <span>Prévues</span>
                    <span class="badge bg-secondary">12</span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>En cours</span>
                    <span class="badge bg-warning text-dark">3</span>
                </div>
                <hr class="bg-light">
                <button class="btn btn-primary w-100 py-3 fw-bold">
                    <i class="fas fa-map-marked-alt me-2"></i> VOIR LA CARTE DE TOURNÉE
                </button>
                <p class="small text-muted mt-3 text-center">
                    Utilisez l'application mobile pour la signature client.
                </p>
            </div>
        </div>
    </div>
</div>



<script>
function validerLivraison(id) {
    if(confirm('Confirmer que cette commande boutique a été livrée ?')) {
        window.location.href = `action_livraison.php?id=${id}&statut=livre`;
    }
}
</script>

<?php require_once $root . '/templates/footer.php'; ?>