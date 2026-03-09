<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Traçabilité des Produits Chimiques";
$message_action = "";

// 2. Logique de traitement des formulaires (Nouveau produit ou Signalement)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_type'])) {
        // Ici, vous ajouteriez vos requêtes SQL INSERT
        $message_action = "Opération enregistrée avec succès : " . htmlspecialchars($_POST['nom_produit'] ?? 'Signalement');
    }
}

try {
    $query = "SELECT * FROM produits_chimiques ORDER BY degre_dangerosite DESC";
    $stmt = $pdo->query($query);
    $produits = $stmt->fetchAll();
} catch (PDOException $e) {
    $db_error = "Erreur SQL : " . $e->getMessage();
    $produits = [];
}

require_once  '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
        <div>
            <h2 class="fw-bold m-0 text-success">[LABO] <?= $titre ?></h2>
            <p class="text-muted small">Registre de sécurité conforme aux normes environnementales</p>
        </div>
        <div class="btn-group shadow-sm">
            <a href="#form-signalement" class="btn btn-outline-danger btn-sm">Signaler une anomalie</a>
            <a href="#form-produit" class="btn btn-success btn-sm">+ Ajouter un produit</a>
        </div>
    </div>

    <?php if ($message_action): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4">
            <b>Succès :</b> <?= $message_action ?>
        </div>
    <?php endif; ?>

    <?php if (isset($db_error)): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            <b>Action requise :</b> La table n'a pas été trouvée ou est inaccessible.
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-danger text-white rounded-4">
                <small class="opacity-75 fw-bold">SOLVANTS CRITIQUES</small>
                <h3 class="fw-bold m-0">
                    <?php 
                        $total_solvant = array_sum(array_map(function($p) { return $p['type_usage'] == 'Solvant Machine' ? $p['stock_actuel'] : 0; }, $produits));
                        echo number_format($total_solvant, 0);
                    ?> L
                </h3>
                <small class="mt-2 d-block">Zone de stockage A</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-white border-start border-4 border-warning rounded-4 h-100">
                <small class="text-muted fw-bold">ALERTES FDS</small>
                <h3 class="fw-bold m-0 text-dark">
                    <?= count(array_filter($produits, function($p) { return !$p['fds_valide']; })) ?>
                </h3>
                <small class="text-danger small mt-2 d-block">Mises à jour requises</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-dark text-white rounded-4 h-100">
                <small class="opacity-75 fw-bold">DERNIÈRE COLLECTE BSDD</small>
                <h3 class="fw-bold m-0">12/01</h3>
                <small class="text-info small mt-2 d-block">Eco-Clean Service</small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Substance</th>
                        <th>Usage</th>
                        <th>Quantité</th>
                        <th>Sécurité FDS</th>
                        <th>MàJ</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produits)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">Aucun produit chimique répertorié.</td></tr>
                    <?php else: ?>
                        <?php foreach($produits as $p): ?>
                        <tr>
                            <td class="ps-4 border-start border-4 border-<?= $p['couleur_alerte'] ?>">
                                <div class="fw-bold"><?= htmlspecialchars($p['nom_produit']) ?></div>
                                <span class="badge bg-light text-muted border-0 small p-0"><?= $p['code_interne'] ?></span>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= $p['type_usage'] ?></span></td>
                            <td class="fw-bold"><?= $p['stock_actuel'] ?> <small class="text-muted"><?= $p['unite'] ?></small></td>
                            <td>
                                <?php if($p['fds_valide']): ?>
                                    <span class="text-success small fw-bold">[OUI] Valide</span>
                                <?php else: ?>
                                    <span class="text-danger small fw-bold">[NON] Expirée</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= date('d/m/y', strtotime($p['date_derniere_maj'])) ?></td>
                            <td class="text-end pe-4">
                                <a href="<?= $p['url_fds'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank">PDF</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7" id="form-produit">
            <div class="card border-0 shadow-lg rounded-4 p-4">
                <h4 class="fw-bold text-success mb-3">Enregistrer un Nouveau Produit</h4>
                <form action="" method="POST">
                    <input type="hidden" name="action_type" value="nouveau">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Nom du produit</label>
                            <input type="text" name="nom_produit" class="form-control" required placeholder="Ex: Perchloroéthylène">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Code Interne</label>
                            <input type="text" name="code_interne" class="form-control" required placeholder="Ex: CHIM-001">
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">Type d'usage</label>
                            <select name="type_usage" class="form-select">
                                <option>Solvant Machine</option>
                                <option>Détachant</option>
                                <option>Traitement Eau</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">Stock Initial</label>
                            <input type="number" name="stock_actuel" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">Unité</label>
                            <input type="text" name="unite" class="form-control" value="Litres">
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-success px-4">Ajouter au registre</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-5" id="form-signalement">
            <div class="card border-0 shadow-lg rounded-4 p-4 bg-light">
                <h4 class="fw-bold text-danger mb-3">Signalement d'Anomalie</h4>
                <form action="" method="POST">
                    <input type="hidden" name="action_type" value="signalement">
                    <div class="mb-3">
                        <label class="small fw-bold">Produit concerné</label>
                        <select name="produit_concerne" class="form-select">
                            <?php foreach($produits as $p): ?>
                                <option><?= $p['nom_produit'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Nature du risque</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Ex: Fuite détectée sur le fût de stockage..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger w-100">Envoyer l'alerte de sécurité</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Amélioration de la lisibilité sans icônes */
    .table-hover tbody tr:hover { background-color: #f8f9fa; }
    #form-produit, #form-signalement { scroll-margin-top: 100px; }
    .btn-group a { text-decoration: none; }
    .form-control, .form-select { border-radius: 8px; border: 1px solid #dee2e6; padding: 0.6rem; }
    .form-control:focus { border-color: #198754; box-shadow: 0 0 0 0.25 parm-rgba(25, 135, 84, 0.25); }
</style>

<?php require_once  '../../templates/footer.php'; ?>