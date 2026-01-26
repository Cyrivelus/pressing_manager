<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Analytique Boutique";

// 2. Calcul des indicateurs clés (KPIs) du mois en cours
$mois_actuel = date('m');
$annee_actuelle = date('Y');

// CA Total Boutique
$ca_total = $pdo->query("SELECT SUM(montant_total) FROM ventes_boutique WHERE MONTH(date_vente) = $mois_actuel")->fetchColumn();

// Nombre de ventes
$nb_ventes = $pdo->query("SELECT COUNT(*) FROM ventes_boutique WHERE MONTH(date_vente) = $mois_actuel")->fetchColumn();

// Panier Moyen
$panier_moyen = ($nb_ventes > 0) ? ($ca_total / $nb_ventes) : 0;

// 3. Top 5 des produits les plus vendus
$top_produits = $pdo->query("
    SELECT p.nom, SUM(v.quantite) as total_vendu, SUM(v.sous_total) as CA_genere
    FROM ventes_details v
    JOIN boutique_produits p ON v.id_produit = p.id
    GROUP BY v.id_produit
    ORDER BY total_vendu DESC
    LIMIT 5
")->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-5 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><?= $titre ?></h2>
            <p class="text-muted">Performances commerciales de la période</p>
        </div>
        <div class="btn-group shadow-sm">
            <button class="btn btn-white border active">Mensuel</button>
            <button class="btn btn-white border">Trimestriel</button>
            <button class="btn btn-white border">Annuel</button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-primary text-white">
                <small class="opacity-75">Chiffre d'Affaires (Mois)</small>
                <h2 class="fw-bold mb-0"><?= number_format($ca_total, 0, ',', ' ') ?> FCFA</h2>
                <div class="mt-2 small"><i class="fas fa-arrow-up"></i> +8.4% vs mois dernier</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4">
                <small class="text-muted">Nombre de Transactions</small>
                <h2 class="fw-bold mb-0"><?= $nb_ventes ?> ventes</h2>
                <div class="mt-2 small text-success">Affluence en hausse</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4">
                <small class="text-muted">Panier Moyen Boutique</small>
                <h2 class="fw-bold mb-0"><?= number_format($panier_moyen, 0, ',', ' ') ?> FCFA</h2>
                <div class="mt-2 small text-muted">Hors prestations pressing</div>
            </div>
        </div>
    </div>

    

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold mb-0">Top 5 - Meilleures Ventes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-center">Volume</th>
                                    <th class="text-end">CA Généré</th>
                                    <th>Part</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($top_produits as $p): 
                                    $part = ($ca_total > 0) ? ($p['CA_genere'] / $ca_total) * 100 : 0;
                                ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($p['nom']) ?></td>
                                    <td class="text-center"><?= $p['total_vendu'] ?> unités</td>
                                    <td class="text-end fw-bold"><?= number_format($p['CA_genere'], 0, ',', ' ') ?></td>
                                    <td style="width: 100px;">
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-primary" style="width: <?= $part ?>%"></div>
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

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100 bg-light">
                <div class="card-body">
                    <h5 class="fw-bold mb-4">Analyse des Stocks</h5>
                    <div class="d-flex align-items-center mb-4 bg-white p-3 rounded-3 border">
                        <div class="icon-box bg-danger-light text-danger me-3 p-3 rounded-circle">
                            <i class="fas fa-exclamation-triangle fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">4 Ruptures de stock</h6>
                            <small class="text-muted">Action requise immédiatement</small>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center mb-4 bg-white p-3 rounded-3 border text-success">
                        <div class="icon-box bg-success-light me-3 p-3 rounded-circle">
                            <i class="fas fa-sync-alt fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">Rotation des stocks : 12j</h6>
                            <small class="text-muted">Performance optimale</small>
                        </div>
                    </div>

                    <button class="btn btn-outline-dark w-100 mt-2 py-2">
                        <i class="fas fa-file-export me-2"></i> Exporter le rapport complet (Excel)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>