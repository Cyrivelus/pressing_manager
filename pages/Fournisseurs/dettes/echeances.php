<?php
// ... entête similaire ...
$sql = "SELECT d.*, f.nom_fournisseur 
        FROM dettes_fournisseur d
        JOIN fournisseurs f ON d.id_fournisseur = f.id_fournisseur
        WHERE d.statut != 'SOLDE'
        AND d.date_echeance BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
        ORDER BY d.date_echeance ASC";
$echeances = $pdo->query($sql)->fetchAll();
?>

<div class="container py-5">
    <h4 class="fw-bold mb-4">Planning des Décaissements (30 prochains jours)</h4>
    <div class="row g-3">
        <?php foreach($echeances as $e): ?>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-4 border-info">
                <div class="card-body">
                    <small class="text-muted">Échéance : <?= date('d M Y', strtotime($e['date_echeance'])) ?></small>
                    <h5 class="fw-bold mt-1"><?= htmlspecialchars($e['nom_fournisseur']) ?></h5>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="fs-5 fw-bold text-primary"><?= number_format($e['reste_a_payer'], 0) ?> F</span>
                        <span class="badge bg-light text-dark">Facture #<?= $e['num_facture'] ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>