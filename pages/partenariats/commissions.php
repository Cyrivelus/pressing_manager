<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion des Apporteurs d'Affaires";
$action = $_GET['action'] ?? 'liste';

try {
    $sql = "SELECT c.id_client, c.nom_client, 
                   COUNT(t.id_ticket) as nb_ventes,
                   SUM(t.montant_total) as ca_genere,
                   SUM(t.montant_total * (IFNULL(c.remise_speciale, 10) / 100)) as commission_due
            FROM clients c
            JOIN clients parraines ON c.id_client = parraines.id_parrain
            JOIN tickets t ON parraines.id_client = t.id_client
            WHERE t.statut = 'recupere' 
              AND t.montant_verse >= t.montant_total
            GROUP BY c.id_client";
            
    $commissions = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    $db_error = "Erreur de configuration : La colonne de parrainage est introuvable.";
    $commissions = [];
}
require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>
<br> <br> <br>
<div class="container-fluid py-4">
    <div class="row align-items-center mb-4 mt-5">
        <div class="col-sm-7">
            <h2 class="fw-bold m-0 text-purple">
                 <?= $titre ?>
            </h2>
            <p class="text-muted">Pilotage des rémunérations et performance apporteurs</p>
        </div>
        <div class="col-sm-5 text-sm-end mt-3 mt-sm-0">
            <?php if ($action !== 'regler'): ?>
                <a href="?action=regler" class="btn btn-purple shadow-sm">
                  Régler les Commissions
                </a>
            <?php else: ?>
                <a href="?" class="btn btn-default border shadow-sm">
                    Retour au suivi
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($action === 'regler'): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-5 bg-light">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-3">Nouveau Règlement</h4>
            <form class="row g-3">
                <div class="col-md-5">
                    <label class="small fw-bold">Choisir le Partenaire</label>
                    <select class="form-control">
                        <?php foreach($commissions as $com): ?>
                            <option value="<?= $com['id_client'] ?>"><?= htmlspecialchars($com['nom_client']) ?> (<?= number_format($com['commission_due'], 0, ',', ' ') ?> F)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold">Mode de paiement</label>
                    <select class="form-control">
                        <option>Espèces</option>
                        <option>Chèque</option>
                        <option>Virement / Mobile Money</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100 fw-bold">Valider le paiement</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($db_error)): ?>
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            <b>Note :</b> <?= $db_error ?> 
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white border-start border-4 border-purple h-100">
                <small class="text-muted fw-bold">À PAYER</small>
                <?php $total_du = array_sum(array_column($commissions, 'commission_due')); ?>
                <h2 class="fw-bold m-0 text-purple"><?= number_format($total_du, 0, ',', ' ') ?> <small class="fs-6 text-muted">F</small></h2>
                <small class="text-muted">Commissions en attente</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white h-100">
                <small class="text-muted fw-bold">VOLUME APPORTÉ</small>
                <?php $total_ca = array_sum(array_column($commissions, 'ca_genere')); ?>
                <h2 class="fw-bold m-0 text-dark"><?= number_format($total_ca, 0, ',', ' ') ?> <small class="fs-6 text-muted">F</small></h2>
                <small class="text-success fw-bold small"> Chiffre d'affaires</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white h-100">
                <small class="opacity-75 fw-bold">EFFECTIF</small>
                <h2 class="fw-bold m-0 text-warning"><?= count($commissions) ?></h2>
                <small class="text-info small">Apporteurs enregistrés</small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4 py-3">Partenaire</th>
                        <th class="text-center py-3">Ventes</th>
                        <th class="text-end py-3">CA Généré</th>
                        <th class="text-center py-3">Taux</th>
                        <th class="text-end py-3">Commission Due</th>
                        <th class="text-end pe-4 py-3">Détails</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($commissions)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">Aucune donnée disponible.</td></tr>
                    <?php else: ?>
                        <?php foreach($commissions as $com): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($com['nom_client']) ?></div>
                                <span class="text-muted small">REF-<?= $com['id_client'] ?></span>
                            </td>
                            <td class="text-center fw-bold text-secondary"><?= $com['nb_ventes'] ?></td>
                            <td class="text-end fw-bold"><?= number_format($com['ca_genere'], 0, ',', ' ') ?> <small>F</small></td>
                            <td class="text-center">
                                <span class="label-taux">10%</span>
                            </td>
                            <td class="text-end fw-bold text-purple"><?= number_format($com['commission_due'], 0, ',', ' ') ?> <small>F</small></td>
                            <td class="text-end pe-4">
                                <a href="?action=details&id=<?= $com['id_client'] ?>" class="btn btn-sm btn-default border">
                                  
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    :root { --purple: #6f42c1; }
    .text-purple { color: var(--purple); }
    .bg-purple { background-color: var(--purple); }
    .btn-purple { background-color: var(--purple); border-color: var(--purple); color: white; transition: 0.3s; }
    .btn-purple:hover { background-color: #59359a; color: white; transform: translateY(-1px); }
    .border-purple { border-color: var(--purple) !important; }
    .label-taux { 
        background-color: rgba(111, 66, 193, 0.1); 
        color: var(--purple); 
        padding: 4px 8px; 
        border-radius: 4px; 
        font-size: 0.85em; 
        font-weight: bold;
    }
    .table thead th { border-top: none; letter-spacing: 0.5px; color: #777; }
    .card { transition: transform 0.2s; }
    .form-control:focus { border-color: var(--purple); box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.15); }
    
    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .container-fluid { padding-top: 20px; }
        h2 { font-size: 1.5rem; }
    }
</style>

<?php require_once '../../templates/footer.php'; ?>