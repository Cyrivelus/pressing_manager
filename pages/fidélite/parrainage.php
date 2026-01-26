<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Programme de Parrainage";

// 2. Statistiques du programme
// On compte les parrainages réussis (filleuls ayant déjà fait une commande)
$stats = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM parrainages) as total_invitations,
        (SELECT COUNT(*) FROM parrainages WHERE statut = 'converti') as total_reussis,
        (SELECT SUM(gain_parrain) FROM parrainages WHERE statut = 'converti') as total_recompenses
")->fetch();

// 3. Liste des parrainages récents
$query = "SELECT p.*, 
          c1.nom_client as parrain_nom, c1.prenom_client as parrain_prenom,
          c2.nom_client as filleul_nom, c2.prenom_client as filleul_prenom
          FROM parrainages p
          JOIN clients c1 ON p.id_parrain = c1.id_client
          JOIN clients c2 ON p.id_filleul = c2.id_client
          ORDER BY p.date_invitation DESC LIMIT 20";
$parrainages = $pdo->query($query)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-primary"><i class="fas fa-users-cog me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Gérez les invitations et les récompenses d'apport d'affaires</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalConfig">
            <i class="fas fa-cog"></i> Configurer l'offre
        </button>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 text-center border-bottom border-4 border-primary">
                <small class="text-muted text-uppercase fw-bold">Invitations Envoyées</small>
                <h2 class="fw-bold m-0"><?= $stats['total_invitations'] ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 text-center border-bottom border-4 border-success">
                <small class="text-muted text-uppercase fw-bold">Nouveaux Clients (ROI)</small>
                <h2 class="fw-bold m-0"><?= $stats['total_reussis'] ?></h2>
                <small class="text-success fw-bold">Taux de conversion : <?= round(($stats['total_reussis']/$stats['total_invitations'])*100, 1) ?>%</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 text-center border-bottom border-4 border-warning">
                <small class="text-muted text-uppercase fw-bold">Total Récompenses Offertes</small>
                <h2 class="fw-bold m-0 text-warning"><?= number_format($stats['total_recompenses'], 0, ',', ' ') ?> <small>FCFA</small></h2>
            </div>
        </div>
    </div>

    

    <div class="card border-0 shadow-sm mb-5">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold mb-0">Suivi des parrainages</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Parrain</th>
                        <th>Filleul</th>
                        <th>Date Invitation</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end pe-4">Récompense</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($parrainages as $p): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold"><?= htmlspecialchars($p['parrain_prenom'].' '.$p['parrain_nom']) ?></span>
                        </td>
                        <td>
                            <span class="text-dark"><?= htmlspecialchars($p['filleul_prenom'].' '.$p['filleul_nom']) ?></span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($p['date_invitation'])) ?></td>
                        <td class="text-center">
                            <?php if($p['statut'] == 'converti'): ?>
                                <span class="badge bg-success-soft text-success"><i class="fas fa-check-circle me-1"></i> Converti</span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted">En attente</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <span class="fw-bold text-primary"><?= number_format($p['gain_parrain'], 0) ?> pts</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfig" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="fw-bold">Paramètres de l'offre parrainage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Offre pour le Parrain</label>
                    <div class="input-group">
                        <input type="number" class="form-control" placeholder="Ex: 1000">
                        <select class="form-select">
                            <option>Points</option>
                            <option>FCFA (Solde)</option>
                            <option>% de remise</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Cadeau de Bienvenue Filleul</label>
                    <input type="text" class="form-control" placeholder="Ex: -15% sur la première commande">
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" checked>
                    <label class="form-check-label">Envoyer un SMS auto au parrain lors du gain</label>
                </div>
                <button type="submit" class="btn btn-primary w-100">Mettre à jour l'offre</button>
            </form>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>