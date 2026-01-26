<?php
// pages/clients/comptes_gestion.php
session_start();

require_once '../../fonctions/database.php';

$message = '';
$message_type = '';

try {
    /**
     * Cette requête calcule dynamiquement le solde par client :
     * - Somme des montants de tous leurs tickets
     * - Somme des paiements effectués
     */
    $sql = "SELECT 
                c.id_client, 
                c.nom_client, 
                c.prenom_client, 
                c.telephone,
                c.points_fidelite,
                IFNULL(SUM(t.montant_total), 0) as total_facture,
                IFNULL(SUM(t.montant_verse), 0) as total_paye,
                (IFNULL(SUM(t.montant_total), 0) - IFNULL(SUM(t.montant_verse), 0)) as solde_du
            FROM clients c
            LEFT JOIN tickets t ON c.id_client = t.id_client
            GROUP BY c.id_client
            ORDER BY solde_du DESC, c.nom_client ASC";

    $stmt = $pdo->query($sql);
    $comptes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $message = "Erreur de calcul des comptes : " . $e->getMessage();
    $message_type = 'danger';
}

include '../../templates/header.php';
include '../../templates/navigation.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-invoice-dollar text-primary"></i> Situation des Comptes Clients</h2>
        <a href="index.php" class="btn btn-secondary">
            <- Retour à la liste
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type; ?> shadow-sm">
            <?= htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php
            $total_dettes = array_sum(array_column($comptes, 'solde_du'));
        ?>
        <div class="col-md-4 mb-4">
            <div class="card bg-danger text-white shadow">
                <div class="card-body">
                    <h6 class="card-title text-uppercase">Total des Créances Client</h6>
                    <h3 class="fw-bold"><?= number_format($total_dettes, 0, ',', ' '); ?> FCFA</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Client</th>
                            <th>Téléphone</th>
                            <th class="text-end">Total Facturé</th>
                            <th class="text-end">Total Payé</th>
                            <th class="text-end">Reste à Payer</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comptes as $compte): ?>
                            <?php 
                                $status_class = ($compte['solde_du'] > 0) ? 'text-danger fw-bold' : 'text-success';
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars(strtoupper($compte['nom_client'])); ?></strong> 
                                    <?= htmlspecialchars($compte['prenom_client']); ?>
                                </td>
                                <td><?= htmlspecialchars($compte['telephone']); ?></td>
                                <td class="text-end"><?= number_format($compte['total_facture'], 0, ',', ' '); ?></td>
                                <td class="text-end text-success"><?= number_format($compte['total_paye'], 0, ',', ' '); ?></td>
                                <td class="text-end <?= $status_class; ?>">
                                    <?= number_format($compte['solde_du'], 0, ',', ' '); ?>
                                </td>
                                <td class="text-center">
                                    <a href="../tickets/nouveau_paiement.php?id_client=<?= $compte['id_client']; ?>" 
                                       class="btn btn-sm btn-outline-primary <?= ($compte['solde_du'] <= 0) ? 'disabled' : ''; ?>">
                                        Encaisser
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>