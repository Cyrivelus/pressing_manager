<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion des Apporteurs d'Affaires";

try {
    // 1. Récupération des commissions
    // NOTE : On suppose ici que la colonne s'appelle 'id_parrain'. 
    // Si vous utilisez un code parrainage textuel, la jointure devra être adaptée.
    $sql = "SELECT c.id_client, c.nom_client, 
                   COUNT(t.id_ticket) as nb_ventes,
                   SUM(t.montant_total) as ca_genere,
                   -- On utilise une commission par défaut de 10% si remise_speciale est vide
                   SUM(t.montant_total * (IFNULL(c.remise_speciale, 10) / 100)) as commission_due
            FROM clients c
            JOIN clients parraines ON c.id_client = parraines.id_parrain
            JOIN tickets t ON parraines.id_client = t.id_client
            WHERE t.statut = 'recupere' 
              AND t.montant_verse >= t.montant_total
            GROUP BY c.id_client";
            
    $commissions = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    // En cas d'erreur de colonne, on initialise un tableau vide pour ne pas bloquer l'affichage
    $db_error = "Erreur de configuration : La colonne de parrainage est introuvable.";
    $commissions = [];
}

require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
        <div>
            <h2 class="fw-bold m-0 text-purple"><i class="fas fa-percentage me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Suivi des rémunérations pour vos partenaires</p>
        </div>
        <button class="btn btn-purple text-white shadow-sm" onclick="alert('Fonctionnalité de règlement bientôt disponible')">
            <i class="fas fa-hand-holding-usd me-1"></i> Régler les Commissions
        </button>
    </div>

    <?php if (isset($db_error)): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            <i class="fas fa-info-circle me-2"></i> <b>Note technique :</b> <?= $db_error ?> 
            <br><small>Assurez-vous d'ajouter une colonne <code>id_parrain</code> (INT) dans votre table <b>clients</b>.</small>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white border-start border-4 border-purple h-100">
                <small class="text-muted fw-bold">COMMISSIONS À PAYER</small>
                <?php $total_du = array_sum(array_column($commissions, 'commission_due')); ?>
                <h2 class="fw-bold m-0 text-purple"><?= number_format($total_du, 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h2>
                <small class="text-muted small">Sur factures encaissées</small>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white h-100">
                <small class="text-muted fw-bold">APPORT TOTAL (CA)</small>
                <?php $total_ca = array_sum(array_column($commissions, 'ca_genere')); ?>
                <h2 class="fw-bold m-0 text-dark"><?= number_format($total_ca, 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h2>
                <small class="text-success fw-bold small"><i class="fas fa-arrow-up"></i> Volume B2B</small>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white h-100">
                <small class="opacity-75 fw-bold">NOMBRE DE PARTENAIRES</small>
                <h2 class="fw-bold m-0 text-warning"><?= count($commissions) ?></h2>
                <small class="text-info small">Apporteurs d'affaires actifs</small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4">Partenaire</th>
                        <th class="text-center">Ventes</th>
                        <th class="text-end">CA Généré</th>
                        <th class="text-center">Taux</th>
                        <th class="text-end">Commission Due</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($commissions)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">Aucune commission calculée pour le moment.</td></tr>
                    <?php else: ?>
                        <?php foreach($commissions as $com): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($com['nom_client']) ?></div>
                                <span class="badge bg-light text-muted border small">#PART-0<?= $com['id_client'] ?></span>
                            </td>
                            <td class="text-center fw-bold"><?= $com['nb_ventes'] ?></td>
                            <td class="text-end"><?= number_format($com['ca_genere'], 0, ',', ' ') ?> F</td>
                            <td class="text-center">
                                <span class="badge bg-purple-soft text-purple">10%</span>
                            </td>
                            <td class="text-end fw-bold text-purple"><?= number_format($com['commission_due'], 0, ',', ' ') ?> F</td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-purple"><i class="fas fa-list"></i></button>
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
    .text-purple { color: #6f42c1; }
    .bg-purple { background-color: #6f42c1; }
    .btn-purple { background-color: #6f42c1; border-color: #6f42c1; color: white; }
    .btn-purple:hover { background-color: #59359a; color: white; }
    .btn-outline-purple { color: #6f42c1; border-color: #6f42c1; }
    .bg-purple-soft { background-color: rgba(111, 66, 193, 0.1); }
</style>

<?php require_once  '../../templates/footer.php'; ?>