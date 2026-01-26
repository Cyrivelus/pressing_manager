<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Registre des Conventions & Accords";

// 1. Récupération des conventions avec alertes sur les dates d'expiration
$sql = "SELECT c.id_client, c.nom_client, c.remise_speciale,
               p.valeur_parametre as date_expiration,
               DATEDIFF(p.valeur_parametre, CURDATE()) as jours_restants
        FROM clients c
        LEFT JOIN parametres_systeme p ON CONCAT('CONV_EXP_', c.id_client) = p.cle_parametre
        WHERE c.notes LIKE '%PARTENAIRE%'
        ORDER BY jours_restants ASC";
$conventions = $pdo->query($sql)->fetchAll();

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-file-contract text-info me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Gestion contractuelle et conformité des comptes grands comptes</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark"><i class="fas fa-archive"></i> Archives</button>
            <button class="btn btn-info text-white shadow-sm"><i class="fas fa-plus"></i> Créer un Accord</button>
        </div>
    </div>

    

    <div class="row g-4 mb-5">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4 h-100">
                <h6 class="fw-bold mb-4 text-uppercase small text-muted">Aperçu du Cycle de Vie des Contrats</h6>
                
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-warning-soft h-100 d-flex flex-column justify-content-center">
                <h6 class="text-warning fw-bold mb-1">RENOUVELLEMENTS PRIORITAIRES</h6>
                <p class="small text-muted mb-4">Contrats expirant dans les 30 prochains jours.</p>
                <div class="list-group list-group-flush bg-transparent">
                    <?php foreach($conventions as $conv): if($conv['jours_restants'] <= 30 && $conv['jours_restants'] > 0): ?>
                        <div class="list-group-item bg-transparent px-0 border-warning border-opacity-25">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold small"><?= $conv['nom_client'] ?></span>
                                <span class="badge bg-warning text-dark"><?= $conv['jours_restants'] ?>j</span>
                            </div>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small">
                    <tr>
                        <th class="ps-4">Partenaire</th>
                        <th>Type d'Accord</th>
                        <th class="text-center">Remise</th>
                        <th class="text-center">Échéance</th>
                        <th class="text-center">Documents</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($conventions as $c): ?>
                    <tr class="<?= $c['jours_restants'] < 0 ? 'table-danger opacity-75' : '' ?>">
                        <td class="ps-4">
                            <div class="fw-bold"><?= htmlspecialchars($c['nom_client']) ?></div>
                            <small class="text-muted">ID: CONV-<?= $c['id_client'] ?></small>
                        </td>
                        <td>
                            <span class="badge bg-info-soft text-info">Prestation de Services</span>
                        </td>
                        <td class="text-center fw-bold text-success"><?= $c['remise_speciale'] ?>%</td>
                        <td class="text-center">
                            <div class="small fw-bold"><?= $c['date_expiration'] ?: 'Indéterminée' ?></div>
                            <small class="<?= $c['jours_restants'] < 15 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                <?= $c['jours_restants'] >= 0 ? 'Reste '.$c['jours_restants'].' jours' : 'Expiré' ?>
                            </small>
                        </td>
                        <td class="text-center">
                            <a href="#" class="text-danger"><i class="fas fa-file-pdf fa-lg"></i></a>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-light border">Réviser les termes</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
</style>

<?php require_once  '../../templates/footer.php'; ?>