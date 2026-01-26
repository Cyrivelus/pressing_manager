<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Analyse de la Consommation";

// 2. Récupération des données analytiques
// On calcule le ratio : Consommation totale vs Nombre de tickets produits
$periode = $_GET['periode'] ?? date('Y-m');

// Exemple de requête pour récupérer l'utilisation des produits par mois
$stats_conso = $pdo->prepare("
    SELECT m.id_consommable, c.nom_article, c.unite_mesure, SUM(m.quantite) as total_utilise
    FROM mouvements_stock m
    JOIN consommables c ON m.id_consommable = c.id_consommable
    WHERE m.type_mouvement = 'sortie' AND m.date_mouvement LIKE ?
    GROUP BY m.id_consommable
");
$stats_conso->execute([$periode . '%']);
$consommations = $stats_conso->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0"><?= $titre ?></h2>
            <p class="text-muted">Rapport d'utilisation des ressources techniques - Période: <?= $periode ?></p>
        </div>
        <form class="d-flex gap-2">
            <input type="month" name="periode" class="form-control" value="<?= $periode ?>">
            <button type="submit" class="btn btn-primary">Analyser</button>
        </form>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 text-center">
                <small class="text-muted d-block">Coût Consommables / Ticket</small>
                <h4 class="fw-bold text-primary">850 FCFA</h4>
                <div class="progress mt-2" style="height: 5px;">
                    <div class="progress-bar bg-primary" style="width: 70%"></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 text-center border-start border-4 border-success">
                <small class="text-muted d-block">Efficacité Lessive</small>
                <h4 class="fw-bold">12L / 100 kg</h4>
                <small class="text-success fw-bold">-5% vs mois dernier</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 text-center border-start border-4 border-warning">
                <small class="text-muted d-block">Pertes / Gaspillage</small>
                <h4 class="fw-bold">2.4 %</h4>
                <small class="text-muted">Seuil toléré: 3%</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 text-center bg-dark text-white">
                <small class="opacity-75 d-block">Dépense Totale Technique</small>
                <h4 class="fw-bold">425 000 FCFA</h4>
            </div>
        </div>
    </div>

    

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Détail par article</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Article</th>
                                <th class="text-center">Usage Total</th>
                                <th class="text-center">Coût Moyen</th>
                                <th class="text-end">Impact CA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($consommations as $c): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($c['nom_article']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark"><?= $c['total_utilise'] ?> <?= $c['unite_mesure'] ?></span>
                                </td>
                                <td class="text-center">--</td>
                                <td class="text-end fw-bold">-- FCFA</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 h-100">
                <h6 class="fw-bold mb-4">Répartition des charges</h6>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Produits Chimiques</span>
                        <span>55%</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-info" style="width: 55%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Emballage (Plastique/Cintres)</span>
                        <span>30%</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: 30%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Énergie (Gaz/Élec)</span>
                        <span>15%</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-warning" style="width: 15%"></div>
                    </div>
                </div>
                
                <div class="alert alert-info mt-auto mb-0 border-0">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Conseil :</strong> Votre consommation d'emballage a augmenté. Vérifiez si les employés doublent les cintres inutilement.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>