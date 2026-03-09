<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Analytique Boutique";
$mois_actuel = date('m');

try {
    // CA Total - Utilisation de IFNULL pour éviter le retour vide
    $ca_total = $pdo->query("SELECT IFNULL(SUM(montant_total), 0) FROM ventes_boutique WHERE MONTH(date_vente) = '$mois_actuel'")->fetchColumn();

    // Nombre de ventes
    $nb_ventes = $pdo->query("SELECT COUNT(*) FROM ventes_boutique WHERE MONTH(date_vente) = '$mois_actuel'")->fetchColumn();

    // Panier Moyen avec sécurité anti-division par zéro
    $panier_moyen = ($nb_ventes > 0) ? ($ca_total / $nb_ventes) : 0;

    // Top 5 des produits (Jointure sécurisée)
    $sql_top = "SELECT p.nom, SUM(v.quantite) as total_vendu, SUM(v.sous_total) as CA_genere
                FROM ventes_details v
                JOIN boutique_produits p ON v.id_produit = p.id
                GROUP BY p.id, p.nom
                ORDER BY total_vendu DESC
                LIMIT 5";
    $top_produits = $pdo->query($sql_top)->fetchAll();

} catch (PDOException $e) {
    // Si la table n'existe pas encore, on initialise à vide pour ne pas crasher
    $top_produits = [];
    $ca_total = 0; $nb_ventes = 0; $panier_moyen = 0;
    $db_warning = "Note : Les tables de statistiques sont en cours de configuration.";
}

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>
<br> <br> <br>
<div class="container-fluid py-4">
    <div class="row mt-5 mb-4">
        <div class="col">
            <h2 class="fw-bold"> <?= $titre ?></h2>
            <p class="text-muted">Analyse des ventes du mois de <?= date('F Y') ?></p>
        </div>
    </div>

    <?php if(isset($db_warning)): ?>
        <div class="alert alert-info border-0 shadow-sm"> <?= $db_warning ?></div>
    <?php endif; ?>

    <div class="row g-4 mb-5 text-center">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-primary text-white h-100">
                <small class="text-uppercase opacity-75">Chiffre d'Affaires</small>
                <h2 class="fw-bold m-0"><?= number_format($ca_total, 0, ',', ' ') ?> F</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 h-100">
                <small class="text-muted text-uppercase fw-bold">Transactions</small>
                <h2 class="fw-bold m-0"><?= $nb_ventes ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 h-100">
                <small class="text-muted text-uppercase fw-bold">Panier Moyen</small>
                <h2 class="fw-bold m-0"><?= number_format($panier_moyen, 0, ',', ' ') ?> F</h2>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3">
            <h5 class="m-0 fw-bold"> Top des ventes</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small">
                    <tr>
                        <th class="ps-4">PRODUIT</th>
                        <th class="text-center">QUANTITÉ</th>
                        <th class="text-end pe-4">CA GÉNÉRÉ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($top_produits)): ?>
                        <tr><td colspan="3" class="text-center py-5 text-muted">Aucune donnée de vente disponible.</td></tr>
                    <?php else: ?>
                        <?php foreach($top_produits as $p): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($p['nom']) ?></td>
                            <td class="text-center"><span class="badge bg-info"><?= $p['total_vendu'] ?></span></td>
                            <td class="text-end pe-4 fw-bold text-primary"><?= number_format($p['CA_genere'], 0, ',', ' ') ?> F</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .bg-primary { background-color: #4e73df !important; }
    .text-primary { color: #4e73df !important; }
    .card { transition: 0.3s; border-radius: 15px; }
</style>

<?php require_once  '../../templates/footer.php'; ?>