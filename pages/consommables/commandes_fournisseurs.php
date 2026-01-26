<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Générateur de Commandes Auto";

// 2. Logique de calcul automatique :
// On cherche les articles où stock_actuel < stock_alerte
// La quantité à commander = Stock_Max - Stock_Actuel
$sql = "SELECT *, (stock_confort - stock_actuel) as qte_suggeree 
        FROM consommables 
        WHERE stock_actuel <= stock_alerte 
        ORDER BY fournisseur_habituel";
$suggestions = $pdo->query($sql)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0"><?= $titre ?></h2>
            <p class="text-muted">Calcul automatique des besoins selon vos seuils de confort</p>
        </div>
        <div class="btn-group">
            <button class="btn btn-outline-dark"><i class="fas fa-file-export"></i> Export Excel</button>
            <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Imprimer Bons</button>
        </div>
    </div>

    

    <?php if (empty($suggestions)): ?>
        <div class="card border-0 shadow-sm p-5 text-center">
            <i class="fas fa-check-double fa-3x text-success mb-3"></i>
            <h5>Stocks au complet</h5>
            <p class="text-muted">Aucune commande automatique n'est nécessaire pour le moment.</p>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-calculator me-2"></i>Articles à réapprovisionner</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Fournisseur</th>
                            <th>Article</th>
                            <th class="text-center">En Stock</th>
                            <th class="text-center">Cible (Confort)</th>
                            <th class="text-center bg-light">Qté à Commander</th>
                            <th class="text-end pe-4">Est. Coût</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_total = 0;
                        foreach ($suggestions as $s): 
                            $cout_estime = $s['qte_suggeree'] * $s['dernier_prix_achat'];
                            $grand_total += $cout_estime;
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold text-secondary"><?= htmlspecialchars($s['fournisseur_habituel'] ?? 'DIVERS') ?></td>
                            <td>
                                <strong><?= htmlspecialchars($s['nom_article']) ?></strong><br>
                                <small class="text-muted"><?= $s['conditionnement'] ?></small>
                            </td>
                            <td class="text-center text-danger fw-bold"><?= $s['stock_actuel'] ?></td>
                            <td class="text-center text-muted"><?= $s['stock_confort'] ?></td>
                            <td class="text-center bg-light">
                                <input type="number" class="form-control form-control-sm mx-auto text-center fw-bold text-primary" 
                                       style="width: 80px;" value="<?= $s['qte_suggeree'] ?>">
                            </td>
                            <td class="text-end pe-4 fw-bold">
                                <?= number_format($cout_estime, 0, ',', ' ') ?> FCFA
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <td colspan="5" class="ps-4 text-end">Estimation Totale du Réapprovisionnement :</td>
                            <td class="text-end pe-4 h5 fw-bold"><?= number_format($grand_total, 0, ',', ' ') ?> FCFA</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="mt-4 p-4 bg-white rounded shadow-sm border-start border-4 border-info">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h6 class="fw-bold mb-1">Prêt pour l'envoi ?</h6>
                    <p class="small text-muted mb-0">Les quantités ont été calculées pour maintenir 15 jours d'autonomie de production.</p>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-info text-white fw-bold">
                        <i class="fas fa-paper-plane me-2"></i> Envoyer les Bons par Email
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once $root . '/templates/footer.php'; ?>