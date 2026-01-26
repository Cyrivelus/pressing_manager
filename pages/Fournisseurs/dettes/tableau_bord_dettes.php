<?php
// Calculs statistiques
$stats = $pdo->query("
    SELECT 
        SUM(reste_a_payer) as total_du,
        COUNT(CASE WHEN date_echeance < NOW() THEN 1 END) as nb_en_retard,
        (SELECT nom_fournisseur FROM fournisseurs f JOIN dettes_fournisseur d ON f.id_fournisseur = d.id_fournisseur GROUP BY f.id_fournisseur ORDER BY SUM(reste_a_payer) DESC LIMIT 1) as principal_creancier
    FROM dettes_fournisseur 
    WHERE statut != 'SOLDE'
")->fetch();
?>

<div class="container-fluid py-5">
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card bg-dark text-white p-4 border-0 shadow">
                <small class="opacity-75">Dette Totale à ce jour</small>
                <h1 class="fw-bold text-warning"><?= number_format($stats['total_du'], 0) ?> <small>F</small></h1>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-white p-4 border-0 shadow-sm">
                <small class="text-muted">Dossiers en retard</small>
                <h1 class="fw-bold text-danger"><?= $stats['nb_en_retard'] ?></h1>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-white p-4 border-0 shadow-sm">
                <small class="text-muted">Plus gros créancier</small>
                <h5 class="fw-bold mt-2 text-uppercase"><?= $stats['principal_creancier'] ?? 'Aucun' ?></h5>
            </div>
        </div>
    </div>
</div>