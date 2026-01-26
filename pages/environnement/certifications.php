<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Certifications & Labels Qualité";

// 2. Récupération sécurisée
try {
    $query = "SELECT * FROM certifications ORDER BY date_expiration ASC";
    $certifs = $pdo->query($query)->fetchAll();
} catch (PDOException $e) {
    $db_error = "Table absente. Veuillez exécuter le script SQL.";
    $certifs = [];
}

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
        <div>
            <h2 class="fw-bold m-0 text-primary"><i class="fas fa-award me-2"></i><?= $titre ?></h2>
            <p class="text-muted small">Gestion des accréditations et normes environnementales</p>
        </div>
        <button class="btn btn-primary shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalCertif">
            <i class="fas fa-plus me-1"></i> Nouveau Label
        </button>
    </div>

    <?php if (isset($db_error)): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= $db_error ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm p-4 mb-5 rounded-4 bg-white">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h6 class="fw-bold mb-3 text-uppercase small text-muted">Progression vers le Label "Or"</h6>
                <div class="progress mb-2" style="height: 12px; border-radius: 6px;">
                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" style="width: 75%">75%</div>
                </div>
                <small class="text-muted italic">Objectif : Réduction de 20% de la consommation d'eau d'ici fin 2026.</small>
            </div>
            <div class="col-md-4 text-end">
                <div class="p-3 bg-light rounded-4 d-inline-block text-center border">
                    <small class="d-block text-muted fw-bold">PROCHAIN AUDIT</small>
                    <span class="fw-bold text-primary">14 MARS 2026</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach($certifs as $c): 
            $diff = strtotime($c['date_expiration']) - time();
            $jours_restants = round($diff / 86400);
            $status_color = ($jours_restants < 60) ? 'danger' : 'success';
        ?>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm rounded-4 transition-hover">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div class="bg-<?= $status_color ?> bg-opacity-10 p-3 rounded-4">
                            <i class="fas fa-certificate fa-2x text-<?= $status_color ?>"></i>
                        </div>
                        <span class="badge rounded-pill bg-<?= $status_color ?>-soft text-<?= $status_color ?> px-3">
                            <?= $jours_restants > 0 ? $jours_restants . ' jours' : 'Expiré' ?>
                        </span>
                    </div>
                    
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($c['nom_label']) ?></h5>
                    <p class="text-muted small mb-4"><i class="fas fa-building me-1"></i> <?= htmlspecialchars($c['organisme']) ?></p>
                    
                    <div class="bg-light p-3 rounded-3 mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Obtenu le :</span>
                            <span class="fw-bold"><?= date('d/m/Y', strtotime($c['date_obtention'])) ?></span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Expire le :</span>
                            <span class="fw-bold text-<?= $status_color ?>"><?= date('d/m/Y', strtotime($c['date_expiration'])) ?></span>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 p-4 pt-0">
                    <div class="row g-2">
                        <div class="col-8">
                            <a href="../../uploads/certificats/<?= $c['fichier_pdf'] ?>" class="btn btn-sm btn-outline-dark w-100 rounded-pill">
                                <i class="fas fa-download me-1"></i> Document
                            </a>
                        </div>
                        <div class="col-4 text-end">
                            <button class="btn btn-sm btn-light border rounded-circle"><i class="fas fa-sync-alt"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="col">
            <div class="card h-100 border-2 border-dashed border-muted bg-transparent text-center p-5 rounded-4 d-flex align-items-center justify-content-center" 
                 style="cursor: pointer; min-height: 250px;" data-bs-toggle="modal" data-bs-target="#modalCertif">
                <div class="text-muted">
                    <i class="fas fa-plus-circle fa-3x mb-3 opacity-50"></i>
                    <h6 class="fw-bold">Ajouter un Label</h6>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
    .transition-hover { transition: transform 0.3s ease; }
    .transition-hover:hover { transform: translateY(-5px); }
</style>

<?php require_once '../../templates/footer.php'; ?>