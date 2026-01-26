<?php
$repartition = $pdo->query("SELECT categorie, SUM(montant) as total FROM depenses GROUP BY categorie ORDER BY total DESC")->fetchAll();
?>
<div class="container py-5">
    <h4 class="fw-bold mb-4">Où va votre argent ?</h4>
    <div class="row">
        <?php foreach($repartition as $r): ?>
        <div class="col-md-12 mb-3">
            <div class="d-flex justify-content-between mb-1">
                <span class="fw-bold"><?= $r['categorie'] ?></span>
                <span><?= number_format($r['total'], 0) ?> F</span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-info" style="width: <?= ($charges > 0) ? ($r['total']/$charges)*100 : 0 ?>%"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>