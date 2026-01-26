<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion de l'Équipe de Livraison";

// 1. Récupération des livreurs et de leurs véhicules assignés
$query = "SELECT u.*, r.nom_role 
          FROM utilisateurs u
          JOIN roles r ON u.id_role = r.id_role
          WHERE r.nom_role IN ('Livreur', 'Chauffeur')
          ORDER BY u.nom_complet ASC";
$livreurs = $pdo->query($query)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0"><i class="fas fa-user-tie text-dark me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Gestion des profils, véhicules et conformité des chauffeurs</p>
        </div>
        <button class="btn btn-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddLivreur">
            <i class="fas fa-plus-circle"></i> Nouveau Livreur
        </button>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary p-3 rounded-3 me-3">
                        <i class="fas fa-users fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0"><?= count($livreurs) ?></h5>
                        <small class="text-muted">Livreurs Actifs</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-success bg-opacity-10 text-success p-3 rounded-3 me-3">
                        <i class="fas fa-motorcycle fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">5</h5>
                        <small class="text-muted">Véhicules en Service</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-warning bg-opacity-10 text-warning p-3 rounded-3 me-3">
                        <i class="fas fa-star fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">4.8/5</h5>
                        <small class="text-muted">Note Client Moyenne</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 bg-white text-danger">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-danger bg-opacity-10 text-danger p-3 rounded-3 me-3">
                        <i class="fas fa-exclamation-triangle fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">2</h5>
                        <small class="text-muted">Permis à renouveler</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach($livreurs as $l): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-circle me-3">
                            <img src="../../assets/img/avatars/livreur_<?= $l['id_utilisateur'] ?>.jpg" 
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($l['nom_complet']) ?>&background=random'" 
                                 class="rounded-circle" width="60">
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($l['nom_complet']) ?></h5>
                            <span class="badge bg-<?= $l['est_actif'] ? 'success' : 'secondary' ?>-soft text-<?= $l['est_actif'] ? 'success' : 'secondary' ?> small">
                                <?= $l['est_actif'] ? 'Disponible' : 'En pause' ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-3 bg-light rounded-3 mb-3">
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Contact:</span>
                            <span class="fw-bold"><?= $l['telephone'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Véhicule assigné:</span>
                            <span class="fw-bold">Yamaha AG100 (#02)</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Zone habituelle:</span>
                            <span class="fw-bold text-primary">Bastos / Golf</span>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button class="btn btn-sm btn-outline-dark"><i class="fas fa-id-card"></i> Documents</button>
                        <button class="btn btn-sm btn-primary"><i class="fas fa-chart-line"></i> Performance</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="modalAddLivreur" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <div class="modal-header">
                <h5 class="fw-bold">Enregistrer un nouveau livreur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nom Complet</label>
                    <input type="text" class="form-control" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Téléphone Pro</label>
                        <input type="tel" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">N° de Permis</label>
                        <input type="text" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Véhicule Attitré</label>
                    <select class="form-select">
                        <option>Moto N°01 - Yamaha</option>
                        <option>Moto N°02 - Yamaha</option>
                        <option>Van N°01 - Renault</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Photo du profil</label>
                    <input type="file" class="form-control">
                </div>
                <button type="submit" class="btn btn-dark w-100">Activer le profil</button>
            </form>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>