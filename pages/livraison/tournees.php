<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Planning des Tournées de Livraison";
$attentes = [];
$tournees = [];

try {
    // 1. Récupération des livraisons en attente
    $sql_attente = "SELECT t.*, c.nom_client, c.adresse, c.telephone 
                    FROM tickets t 
                    JOIN clients c ON t.id_client = c.id_client 
                    WHERE t.statut = 'pret' 
                    AND t.mode_retrait = 'livraison'
                    AND t.id_ticket NOT IN (SELECT id_ticket FROM tournees_details)
                    ORDER BY c.adresse ASC";
    $attentes = $pdo->query($sql_attente)->fetchAll();

    // 2. Récupération des tournées du jour
    $aujourdhui = date('Y-m-d');
    $sql_tournees = "SELECT tr.*, u.nom_complet as livreur_nom 
                     FROM tournees tr
                     JOIN utilisateurs u ON tr.id_livreur = u.id_utilisateur
                     WHERE tr.date_tournee = ?
                     ORDER BY tr.heure_depart ASC";
    $stmt = $pdo->prepare($sql_tournees);
    $stmt->execute([$aujourdhui]);
    $tournees = $stmt->fetchAll();

} catch (PDOException $e) {
    $error_db = "Erreur de base de données : " . $e->getMessage();
}

require_once '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <?php if (isset($error_db)): ?>
        <div class="alert alert-danger mt-5"><?= $error_db ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-primary"><i class="fas fa-truck me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Optimisation des trajets et suivi des chauffeurs</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNouvelleTournee">
            <i class="fas fa-route"></i> Créer une tournée
        </button>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-uppercase small text-muted">Commandes prêtes à livrer</h6>
                </div>
                <form action="assigner_tournee.php" method="POST">
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush" style="max-height: 600px; overflow-y: auto;">
                            <?php foreach($attentes as $a): ?>
                            <div class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="badge bg-light text-dark mb-1">#<?= $a['numero_ticket'] ?></span>
                                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($a['nom_client']) ?></h6>
                                        <small class="text-muted d-block"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($a['adresse'] ?? 'Pas d\'adresse') ?></small>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="selection_livraison[]" value="<?= $a['id_ticket'] ?>">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if(empty($attentes)): ?>
                                <div class="p-4 text-center text-muted small">Aucune livraison en attente</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if(!empty($attentes)): ?>
                    <div class="card-footer bg-light border-0">
                        <button type="submit" class="btn btn-sm btn-dark w-100">Assigner la sélection</button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Tournées programmées (Aujourd'hui)</h6>
                </div>
                <div class="card-body">
                    <?php foreach($tournees as $t): ?>
                    <div class="border rounded-3 p-3 mb-3 bg-light bg-opacity-50">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <span class="d-block small text-muted">Livreur</span>
                                <span class="fw-bold"><?= htmlspecialchars($t['livreur_nom']) ?></span>
                            </div>
                            <div class="col-md-2">
                                <span class="d-block small text-muted">Départ</span>
                                <span class="badge bg-dark"><?= date('H:i', strtotime($t['heure_depart'])) ?></span>
                            </div>
                            <div class="col-md-3">
                                <span class="d-block small text-muted">Zone</span>
                                <span><?= htmlspecialchars($t['zone_livraison'] ?? 'N/A') ?></span>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="badge bg-primary"><?= $t['statut'] ?></span>
                            </div>
                            <div class="col-md-2 text-end">
                                <a href="details_tournee.php?id=<?= $t['id_tournee'] ?>" class="btn btn-sm btn-outline-primary">Détails</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($tournees)): ?>
                        <div class="text-center py-4 text-muted">Aucune tournée créée pour aujourd'hui.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="alert alert-info border-0 shadow-sm d-flex align-items-center">
                <i class="fas fa-lightbulb fa-2x me-3"></i>
                <div>
                    <strong>Conseil Logistique :</strong> Regroupez vos livraisons par quartiers pour économiser du carburant.
                </div>
            </div>
        </div>
    </div>
</div>