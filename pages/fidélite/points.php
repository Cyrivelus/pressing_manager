<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Barème & Conversion des Points";

// 2. Récupération des paliers de récompenses
$query = "SELECT * FROM fidelite_paliers ORDER BY points_requis ASC";
$paliers = $pdo->query($query)->fetchAll();

// 3. Récupération des dernières transactions de points
$logs = $pdo->query("
    SELECT l.*, c.nom_client, c.prenom_client 
    FROM fidelite_log_points l
    JOIN clients c ON l.id_client = c.id_client
    ORDER BY l.date_transaction DESC LIMIT 15
")->fetchAll();
?>

<?php require_once $root . '/templates/header.php'; ?>
<?php require_once $root . '/templates/navigation.php'; ?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-info"><i class="fas fa-star me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Configurez la valeur des points et les cadeaux associés</p>
        </div>
        <div class="bg-light p-2 rounded border d-flex align-items-center">
            <span class="me-3 fw-bold">Taux actuel :</span>
            <span class="badge bg-primary fs-6">1 000 FCFA = 10 Points</span>
            <button class="btn btn-sm btn-link text-decoration-none"><i class="fas fa-edit"></i></button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Catalogue des Récompenses</h5>
                    <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalPalier">
                        <i class="fas fa-plus"></i> Ajouter un palier
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Points Requis</th>
                                <th>Récompense Offerte</th>
                                <th>Valeur Estimée</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($paliers as $p): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge rounded-pill bg-info text-dark fw-bold px-3">
                                        <?= $p['points_requis'] ?> pts
                                    </span>
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($p['libelle_recompense']) ?></td>
                                <td><?= number_format($p['valeur_marchande'], 0, ',', ' ') ?> FCFA</td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light"><i class="fas fa-pen"></i></button>
                                    <button class="btn btn-sm btn-light text-danger"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Activités Récentes</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach($logs as $l): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <span class="fw-bold d-block"><?= htmlspecialchars($l['prenom_client'].' '.$l['nom_client']) ?></span>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($l['date_transaction'])) ?></small>
                            </div>
                            <div class="text-end">
                                <span class="fw-bold <?= $l['quantite'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $l['quantite'] > 0 ? '+' : '' ?><?= $l['quantite'] ?> pts
                                </span>
                                <small class="d-block text-muted" style="font-size: 0.7rem;"><?= $l['motif'] ?></small>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card-footer bg-light text-center">
                    <a href="#" class="small text-decoration-none">Voir tout l'historique</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPalier" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="fw-bold">Créer une récompense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Libellé du cadeau</label>
                    <input type="text" class="form-control" placeholder="ex: Lavage 1 Couette offert">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Points requis</label>
                        <input type="number" class="form-control" placeholder="500">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Coût pour l'entreprise</label>
                        <input type="number" class="form-control" placeholder="FCFA">
                    </div>
                </div>
                <button type="submit" class="btn btn-info text-white w-100 fw-bold">Enregistrer le palier</button>
            </form>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>