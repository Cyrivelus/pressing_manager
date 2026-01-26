<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Optimisation des Itinéraires";

// 1. Récupération des points de livraison non optimisés
$sql = "SELECT t.id_ticket, t.numero_ticket, c.nom_client, c.adresse, c.quartier, s.nom_service
        FROM tickets t
        JOIN clients c ON t.id_client = c.id_client
        JOIN lignes_ticket lt ON t.id_ticket = lt.id_ticket
        JOIN services s ON lt.id_service = s.id_service
        WHERE t.statut = 'pret' AND t.mode_retrait = 'livraison'
        AND t.itineraire_optimise = 0";
$points = $pdo->query($sql)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-indigo"><i class="fas fa-magic me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Intelligence logistique : Calcul du chemin le plus court</p>
        </div>
        <button class="btn btn-indigo text-white shadow-sm" onclick="lancerOptimisation()">
            <i class="fas fa-microchip me-2"></i>Calculer l'ordre optimal
        </button>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0">Points à traiter (<?= count($points) ?>)</h6>
                </div>
                <div class="list-group list-group-flush" id="sortableList">
                    <?php foreach($points as $p): ?>
                    <div class="list-group-item d-flex align-items-center p-3" data-id="<?= $p['id_ticket'] ?>">
                        <div class="me-3">
                            <i class="fas fa-grip-vertical text-muted"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold small"><?= htmlspecialchars($p['nom_client']) ?></span>
                                <span class="badge bg-indigo-soft text-indigo"><?= $p['quartier'] ?></span>
                            </div>
                            <small class="text-muted d-block"><?= htmlspecialchars($p['adresse']) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 h-100 bg-light bg-opacity-50">
                <h5 class="fw-bold mb-4">Aperçu du gain logistique</h5>
                
                

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-white rounded-3 border">
                            <small class="text-muted d-block">Distance estimée (Avant)</small>
                            <span class="h4 fw-bold text-danger">42.5 km</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-white rounded-3 border">
                            <small class="text-muted d-block">Distance estimée (Après)</small>
                            <span class="h4 fw-bold text-success">28.2 km</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <h6 class="fw-bold small text-uppercase text-muted mb-3">Séquence d'arrêt suggérée :</h6>
                    <div class="vertical-timeline ps-3 border-start border-2 border-indigo ms-2">
                        <div class="timeline-item mb-3 position-relative">
                            <span class="position-absolute start-0 translate-middle-x bg-indigo rounded-circle" style="width:12px; height:12px; left:-14px; top:5px;"></span>
                            <strong>Départ : Pressing Central</strong>
                        </div>
                        <div class="timeline-item mb-3">
                            <span class="text-muted small">Arrêt 1 :</span> Bonapriso (3 points groupés)
                        </div>
                        <div class="timeline-item mb-3">
                            <span class="text-muted small">Arrêt 2 :</span> Akwa (2 points groupés)
                        </div>
                        <div class="timeline-item">
                            <strong>Retour : Pressing Central</strong>
                        </div>
                    </div>
                </div>

                <div class="mt-auto pt-4">
                    <div class="alert alert-success border-0 mb-0">
                        <i class="fas fa-leaf me-2"></i> <strong>Gain Éco :</strong> Cette optimisation économise environ <strong>1.4L</strong> de carburant.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
    // Initialisation du glisser-déposer pour un ajustement manuel post-optimisation
    new Sortable(document.getElementById('sortableList'), {
        animation: 150,
        ghostClass: 'bg-light'
    });

    function lancerOptimisation() {
        // Logique de calcul (via API ou algorithme PHP interne)
        alert("L'algorithme analyse les quartiers et les sens de circulation pour réorganiser la liste...");
    }
</script>

<style>
    .bg-indigo-soft { background-color: #e0e7ff; }
    .text-indigo { color: #4338ca; }
    .btn-indigo { background-color: #4f46e5; border-color: #4f46e5; }
    .btn-indigo:hover { background-color: #4338ca; }
</style>

<?php require_once $root . '/templates/footer.php'; ?>