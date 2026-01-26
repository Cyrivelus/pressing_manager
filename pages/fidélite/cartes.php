<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité : Accès Boutique / Administration
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion des Cartes Fidélité";

// 2. Recherche et Filtrage
$search = $_GET['search'] ?? '';
$sql = "SELECT f.*, c.nom_client, c.prenom_client, c.telephone 
        FROM fidelite_cartes f
        JOIN clients c ON f.id_client = c.id_client
        WHERE f.numero_carte LIKE ? OR c.nom_client LIKE ? OR c.telephone LIKE ?
        ORDER BY f.date_emission DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%", "%$search%"]);
$cartes = $stmt->fetchAll();

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';

?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0"><i class="fas fa-id-card text-primary me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Émission, rechargement et suivi des comptes fidélité</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNouvelleCarte">
            <i class="fas fa-plus-circle"></i> Émettre une carte
        </button>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control" placeholder="Scanner une carte ou saisir un nom/téléphone..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100">Rechercher</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">N° Carte</th>
                                <th>Client</th>
                                <th>Type de Programme</th>
                                <th class="text-center">Solde Points</th>
                                <th class="text-center">Solde Prépayé</th>
                                <th>Statut</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cartes as $card): 
                                $status_badge = ($card['statut'] == 'active') ? 'bg-success' : 'bg-danger';
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary">
                                    <i class="fas fa-barcode me-2"></i><?= $card['numero_carte'] ?>
                                </td>
                                <td>
                                    <span class="fw-bold d-block"><?= htmlspecialchars($card['prenom_client'].' '.$card['nom_client']) ?></span>
                                    <small class="text-muted"><?= $card['telephone'] ?></small>
                                </td>
                                <td>
                                    <span class="badge border text-dark bg-light"><?= strtoupper($card['type_programme']) ?></span>
                                </td>
                                <td class="text-center fw-bold text-info"><?= number_format($card['points'], 0) ?> pts</td>
                                <td class="text-center fw-bold text-success"><?= number_format($card['solde_monetaire'], 0, ',', ' ') ?> FCFA</td>
                                <td><span class="badge <?= $status_badge ?>"><?= ucfirst($card['statut']) ?></span></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" title="Recharger" data-bs-toggle="modal" data-bs-target="#modalRecharge<?= $card['id_carte'] ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-dark" title="Historique"><i class="fas fa-history"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" title="Bloquer"><i class="fas fa-ban"></i></button>
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
</div>

<div class="modal fade" id="modalNouvelleCarte" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="fw-bold">Nouvelle Carte Fidélité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Scanner / Saisir N° de Carte</label>
                    <input type="text" class="form-control" placeholder="Ex: CARD-882910">
                </div>
                <div class="mb-3">
                    <label class="form-label">Sélectionner le Client</label>
                    <select class="form-select select2">
                        <option>Rechercher un client...</option>
                        </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type de Fidélité</label>
                    <select class="form-select">
                        <option value="points">Points (1000 FCFA = 1 pt)</option>
                        <option value="prepaye">Porte-monnaie (Rechargeable)</option>
                        <option value="mixte">Mixte (Points + Argent)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">Activer la carte</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>