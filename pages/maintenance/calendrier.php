<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Calendrier de Maintenance Préventive";

try {
    // 1. Récupération des équipements avec calcul de la date d'échéance en SQL
    $sql = "SELECT e.*, 
            DATE_ADD(e.derniere_maintenance, INTERVAL e.frequence_jours DAY) as prochaine_date,
            DATEDIFF(DATE_ADD(e.derniere_maintenance, INTERVAL e.frequence_jours DAY), CURDATE()) as jours_restants
            FROM equipements e
            WHERE e.est_actif = TRUE
            ORDER BY prochaine_date ASC";
    $equipements = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    $equipements = [];
    $erreur_db = "Erreur de base de données : " . $e->getMessage();
}

require_once  '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    .bg-success-soft { background-color: #e8f5e9; color: #2e7d32; }
    .bg-warning-soft { background-color: #fff3e0; color: #ef6c00; }
    .bg-danger-soft { background-color: #ffebee; color: #c62828; }
    .bg-primary-soft { background-color: #e3f2fd; color: #1565c0; }
    .bg-info-soft { background-color: #e0f7fa; color: #00838f; }
</style>

<div class="container-fluid py-5">
    <?php if (isset($erreur_db)): ?>
        <div class="alert alert-danger shadow-sm border-0"><?= $erreur_db ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-danger"><i class="fas fa-calendar-check me-2 text-dark"></i><?= $titre ?></h2>
            <p class="text-muted">Anticipez les révisions pour éviter les arrêts de production</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-danger shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUrgence">
                <i class="fas fa-exclamation-triangle"></i> Signaler une Panne
            </button>
            <button class="btn btn-dark shadow-sm">
                <i class="fas fa-plus"></i> Ajouter un Équipement
            </button>
        </div>
    </div>

    

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light small text-uppercase">
                                <tr>
                                    <th class="ps-4">Équipement</th>
                                    <th>Intervention</th>
                                    <th class="text-center">Échéance</th>
                                    <th>Statut</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($equipements)): ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted">Aucun équipement enregistré.</td></tr>
                                <?php endif; ?>

                                <?php foreach($equipements as $e): 
                                    $jours = $e['jours_restants'];
                                    $couleur = ($jours < 0) ? 'danger' : (($jours < 7) ? 'warning' : 'success');
                                ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($e['nom_equipement']) ?></div>
                                        <small class="text-muted">S/N: <?= $e['numero_serie'] ?></small>
                                    </td>
                                    <td><span class="small text-muted"><?= htmlspecialchars($e['type_entretien']) ?></span></td>
                                    <td class="text-center">
                                        <span class="fw-bold d-block"><?= date('d/m/Y', strtotime($e['prochaine_date'])) ?></span>
                                        <span class="badge bg-<?= $couleur ?> shadow-sm">
                                            <?= ($jours < 0) ? 'Retard : '.abs($jours).'j' : 'Dans '.$jours.'j' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $couleur ?>-soft p-2 rounded-pill">
                                            <i class="fas fa-circle me-1 small"></i> <?= ($jours < 0) ? 'Action Requise' : 'Ok' ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-success" title="Valider l'entretien">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" title="Historique">
                                                <i class="fas fa-history"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm bg-dark text-white p-4 mb-4">
                <h6 class="text-warning text-uppercase small fw-bold">Rappel Sécurité</h6>
                <p class="small mb-0">Toute maintenance sur la chaudière doit être effectuée machine à froid et alimentation électrique coupée.</p>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0"><i class="fas fa-user-cog me-2"></i>Techniciens Partenaires</h6>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex align-items-center p-3 border-0">
                        <div class="bg-primary-soft p-2 rounded me-3 text-primary"><i class="fas fa-tools"></i></div>
                        <div class="flex-grow-1">
                            <span class="fw-bold d-block">Electro-Service SARL</span>
                            <small class="text-muted">+237 600 000 000</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once  '../../templates/footer.php'; ?>