<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Registre des Conventions & Accords";
$view = $_GET['view'] ?? 'list'; // Détecte l'action : list, archive, ou create

// 1. Récupération des conventions
$sql = "SELECT c.id_client, c.nom_client, c.remise_speciale,
               p.valeur_parametre as date_expiration,
               DATEDIFF(p.valeur_parametre, CURDATE()) as jours_restants
        FROM clients c
        LEFT JOIN parametres_systeme p ON CONCAT('CONV_EXP_', c.id_client) = p.cle_parametre
        WHERE c.notes LIKE '%PARTENAIRE%'
        ORDER BY jours_restants ASC";
$conventions = $pdo->query($sql)->fetchAll();

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>
<br> <br> <br>
<div class="container-fluid py-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h2 class="fw-bold m-0 text-dark">
                 <?= $titre ?>
            </h2>
            <p class="text-muted mb-0">Pilotage contractuel et conformité des comptes stratégiques</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <div class="btn-group shadow-sm">
                <a href="?" class="btn btn-default <?= $view == 'list' ? 'active' : '' ?>">
                     Actifs
                </a>
                <a href="?view=archive" class="btn btn-default <?= $view == 'archive' ? 'active' : '' ?>">
                    Archives
                </a>
                <a href="?view=create" class="btn btn-primary">
                 Créer un Accord
                </a>
            </div>
        </div>
    </div>

    <hr>

    <?php if ($view === 'archive'): ?>
        <div class="alert alert-info border-0 shadow-sm mb-5">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="m-0">Archives Historiques</h4>
                <a href="?" class="btn btn-sm btn-link text-dark">Fermer</a>
            </div>
            <p class="small mt-2">Consultation des accords résiliés ou arrivés à terme.</p>
            <div class="bg-white p-3 rounded text-center text-muted border">
                Aucun archive à afficher pour le moment.
            </div>
        </div>

    <?php elseif ($view === 'create'): ?>
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-header bg-primary text-white d-flex justify-content-between">
                <span class="fw-bold"> Nouvel Accord Partenaire</span>
                <a href="?" class="text-white text-decoration-none">&times;</a>
            </div>
            <div class="card-body bg-light">
                <form class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Partenaire</label>
                        <select class="form-control select2"><option>Sélectionner un client...</option></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Remise (%)</label>
                        <input type="number" class="form-control" placeholder="0.00">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Échéance</label>
                        <input type="date" class="form-control">
                    </div>
                    <div class="col-12 text-end">
                        <button type="reset" class="btn btn-sm btn-default">Annuler</button>
                        <button type="submit" class="btn btn-sm btn-success px-4">Enregistrer le contrat</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4 h-100 bg-white">
                <h6 class="fw-bold mb-3 text-uppercase small text-secondary">
                   Aperçu de la Performance
                </h6>
                <div class="row text-center">
                    <div class="col-4 border-end">
                        <div class="h3 fw-bold m-0"><?= count($conventions) ?></div>
                        <div class="small text-muted text-uppercase">Total Accords</div>
                    </div>
                    <div class="col-4 border-end">
                        <div class="h3 fw-bold m-0 text-success">12%</div>
                        <div class="small text-muted text-uppercase">Moy. Remise</div>
                    </div>
                    <div class="col-4">
                        <div class="h3 fw-bold m-0 text-info">5</div>
                        <div class="small text-muted text-uppercase">Nouveaux (30j)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-warning-soft h-100">
                <h6 class="text-warning fw-bold mb-1">RENOUVELLEMENTS PRIORITAIRES</h6>
                <p class="small text-muted mb-3">Expirations imminentes (< 30j)</p>
                <div class="list-group list-group-flush bg-transparent">
                    <?php 
                    $urgentCount = 0;
                    foreach($conventions as $conv): 
                        if($conv['jours_restants'] <= 30 && $conv['jours_restants'] > 0): 
                            $urgentCount++;
                    ?>
                        <div class="list-group-item bg-transparent px-0 border-warning border-opacity-25 py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold small text-dark"><?= $conv['nom_client'] ?></span>
                                <span class="label label-warning"><?= $conv['jours_restants'] ?>j</span>
                            </div>
                        </div>
                    <?php endif; endforeach; 
                    if($urgentCount === 0) echo '<p class="small text-muted italic">Aucune alerte en cours.</p>';
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="text-muted small">
                        <th class="ps-4 py-3">PARTENAIRE / RÉFÉRENCE</th>
                        <th class="py-3">TYPE D'ACCORD</th>
                        <th class="text-center py-3">REMISE</th>
                        <th class="text-center py-3">ÉCHÉANCE</th>
                        <th class="text-center py-3">DOCUMENTS</th>
                        <th class="text-end pe-4 py-3">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($conventions as $c): ?>
                    <tr class="<?= $c['jours_restants'] < 0 ? 'bg-danger bg-opacity-10' : '' ?>">
                        <td class="ps-4">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($c['nom_client']) ?></div>
                            <small class="text-muted italic"> CONV-<?= $c['id_client'] ?></small>
                        </td>
                        <td>
                            <span class="badge bg-info-soft text-info fw-normal">Accord Cadre</span>
                        </td>
                        <td class="text-center fw-bold text-success">
                            <?= $c['remise_speciale'] ?>%
                        </td>
                        <td class="text-center">
                            <div class="small fw-bold"><?= $c['date_expiration'] ?: 'Contrat Permanent' ?></div>
                            <?php if($c['date_expiration']): ?>
                                <small class="<?= $c['jours_restants'] < 15 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                   
                                    <?= $c['jours_restants'] >= 0 ? $c['jours_restants'].' jours restants' : 'Contrat expiré' ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="#" class="text-danger p-2 d-inline-block" title="Télécharger PDF">
                              
                            </a>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-default shadow-sm border" title="Modifier">
                              
                                </button>
                                <button class="btn btn-default shadow-sm border text-primary" title="Renouveler">
                                   
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    :root {
        --bs-info-soft: rgba(13, 202, 240, 0.1);
        --bs-warning-soft: rgba(255, 193, 7, 0.1);
    }
    .bg-warning-soft { background-color: var(--bs-warning-soft); }
    .bg-info-soft { background-color: var(--bs-info-soft); }
    .table thead th { letter-spacing: 0.5px; text-transform: uppercase; border-bottom: 2px solid #eee; }
    .badge { padding: 5px 10px; border-radius: 4px; font-weight: 500; }
    .label { padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; display: inline-block; }
    .label-warning { background-color: #f0ad4e; color: white; }
    .italic { font-style: italic; }
    tr:hover { background-color: #fcfcfc !important; }
</style>

<?php require_once '../../templates/footer.php'; ?>