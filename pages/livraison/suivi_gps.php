
<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Tracking & Monitoring GPS";

try {
    // 1. Récupération des livreurs en mission
    // Correction du nom de la colonne : statut_livraison au lieu de statut
    $sql_livreurs = "SELECT u.id_utilisateur, u.nom_complet, t.id_tournee, t.vehicule_immatriculation,
                    (SELECT COUNT(*) FROM tournees_details td WHERE td.id_tournee = t.id_tournee AND td.statut_livraison = 'livre') as livraisons_faites,
                    (SELECT COUNT(*) FROM tournees_details td WHERE td.id_tournee = t.id_tournee) as total_livraisons
                    FROM utilisateurs u
                    JOIN tournees t ON u.id_utilisateur = t.id_livreur
                    WHERE t.statut = 'en_cours' AND t.date_tournee = CURDATE()";
    
    $livreurs_actifs = $pdo->query($sql_livreurs)->fetchAll();

} catch (PDOException $e) {
    $error_db = "Erreur de suivi : " . $e->getMessage();
    $livreurs_actifs = [];
}

require_once '../../templates/header.php';
require_once   '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <?php if (isset($error_db)): ?>
        <div class="alert alert-danger mt-5"><?= $error_db ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-success"><i class="fas fa-map-marked-alt me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Suivi en direct de la flotte et performance des tournées</p>
        </div>
        <div class="badge bg-success-light text-success p-2 border border-success">
            <i class="fas fa-satellite-dish fa-spin me-2"></i> Connexion Satellite Active
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-uppercase small">Livreurs en mouvement</h6>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach($livreurs_actifs as $l): 
                        $progression = ($l['total_livraisons'] > 0) ? ($l['livraisons_faites'] / $l['total_livraisons']) * 100 : 0;
                    ?>
                    <div class="list-group-item p-3 border-0 border-bottom">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-bold text-dark"><?= htmlspecialchars($l['nom_complet']) ?></span>
                            <span class="badge bg-light text-dark"><?= htmlspecialchars($l['vehicule_immatriculation']) ?></span>
                        </div>
                        <div class="progress mb-2" style="height: 8px; border-radius: 10px; background-color: #f0f0f0;">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" 
                                 style="width: <?= $progression ?>%"></div>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted"><?= $l['livraisons_faites'] ?> / <?= $l['total_livraisons'] ?> livraisons</span>
                            <span class="text-success fw-bold"><?= round($progression) ?>%</span>
                        </div>
                        <div class="mt-3 d-grid">
                            <button class="btn btn-sm btn-success shadow-sm" onclick="focusLivreur(<?= $l['id_utilisateur'] ?>)">
                                <i class="fas fa-crosshairs me-1"></i> Localiser le véhicule
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if(empty($livreurs_actifs)): ?>
                        <div class="p-5 text-center">
                            <i class="fas fa-truck-loading fa-3x text-light mb-3"></i>
                            <p class="text-muted small">Aucun livreur n'a de tournée active pour le moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-4 bg-dark text-white shadow-lg" style="border-radius: 15px;">
                <h6 class="small text-uppercase opacity-75 fw-bold mb-3">Performance Logistique</h6>
                <div class="d-flex align-items-center mb-3">
                    <h2 class="fw-bold m-0">14 min</h2>
                    <span class="ms-2 badge bg-success">+2%</span>
                </div>
                <small class="d-block opacity-50 mb-3">Temps moyen par arrêt aujourd'hui</small>
                <hr class="opacity-25">
                <div class="row text-center">
                    <div class="col-6 border-end border-secondary">
                        <small class="d-block opacity-75">Distance</small>
                        <span class="fw-bold">128 km</span>
                    </div>
                    <div class="col-6">
                        <small class="d-block opacity-75">Alertes</small>
                        <span class="fw-bold text-warning">0</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100 overflow-hidden position-relative" style="min-height: 600px; border-radius: 15px;">
                <div class="position-absolute top-0 end-0 m-3 p-2 bg-white shadow-sm rounded border fw-bold small" style="z-index: 10;">
                    <i class="fas fa-circle text-success me-1"></i> Live : Douala, Cameroun
                </div>

                <div id="map" style="height: 100%; min-height: 600px; background: #f8f9fa;" class="d-flex align-items-center justify-content-center">
                    <div class="text-center p-5">
                        <div class="spinner-grow text-success mb-3" role="status"></div>
                        <h5 class="fw-bold text-dark">Initialisation de la carte...</h5>
                        <p class="text-muted small">Synchronisation avec les coordonnées GPS des terminaux livreurs.</p>
                    </div>
                </div>

                <div class="position-absolute bottom-0 start-0 m-3 p-3 bg-white shadow-lg rounded-4 border-start border-success border-5" style="max-width: 300px; z-index: 1000;">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-success-light p-2 rounded me-2">
                            <i class="fas fa-route text-success"></i>
                        </div>
                        <h6 class="fw-bold mb-0 small">Prochaine étape</h6>
                    </div>
                    <p class="mb-1 fw-bold small">Mme. TCHAMENI (Bonapriso)</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-light text-dark border">Ticket #4529</span>
                        <span class="text-success small fw-bold">~ 8 min</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-success-light { background-color: rgba(25, 135, 84, 0.1); }
    .animate__pulse { animation-duration: 2s; }
</style>

<script>
    function focusLivreur(id) {
        // Logique pour centrer la carte sur le marqueur du livreur spécifique
        console.log("Tracking activé pour livreur ID: " + id);
    }
</script>

<?php require_once  '../../templates/footer.php'; ?>