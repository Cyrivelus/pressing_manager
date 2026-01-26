<?php
// Moyenne des 3 derniers mois pour projection
$projection = $pdo->query("
    SELECT AVG(mensuel) FROM (
        SELECT SUM(montant_total) as mensuel FROM tickets 
        WHERE date_depot >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        GROUP BY MONTH(date_depot)
    ) as subquery
")->fetchColumn();
?>

<div class="container py-5 text-center">
    <div class="p-5 bg-light rounded-5 shadow-sm">
        <div class="display-1 text-primary mb-3"><i class="fas fa-crystal-ball"></i></div>
        <h2 class="fw-bold">Prévisions du Mois Prochain</h2>
        <p class="lead text-muted">Basé sur vos performances passées, votre CA estimé est de :</p>
        <h1 class="display-3 fw-bold"><?= number_format($projection, 0) ?> FCFA</h1>
        <div class="mt-4">
            <span class="badge bg-success-soft text-success p-2">Fiabilité : Moyenne (Basée sur 3 mois)</span>
        </div>
    </div>
</div>