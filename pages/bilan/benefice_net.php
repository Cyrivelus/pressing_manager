<?php
// ... entête ...
// Calcul du bénéfice net après amortissement ou coûts cachés
$benefice = $recettes - $charges;
$marge = ($recettes > 0) ? ($benefice / $recettes) * 100 : 0;
?>

<div class="container py-5">
    <div class="card border-0 shadow-lg overflow-hidden">
        <div class="row g-0">
            <div class="col-md-8 p-5">
                <h3 class="fw-bold mb-4">Calcul du Bénéfice Net</h3>
                <table class="table table-borderless">
                    <tr class="border-bottom"><td>Total des encaissements</td><td class="text-end fw-bold text-success">+ <?= number_format($recettes,0) ?> F</td></tr>
                    <tr class="border-bottom"><td>Coût des consommables (soustractif)</td><td class="text-end fw-bold text-danger">- <?= number_format($charges,0) ?> F</td></tr>
                    <tr class="bg-light">
                        <td class="fw-bold py-3">BÉNÉFICE NET ESTIMÉ</td>
                        <td class="text-end fw-bold py-3 fs-4"><?= number_format($benefice, 0) ?> FCFA</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-4 bg-dark text-white p-5 d-flex flex-column justify-content-center text-center">
                <h5 class="opacity-75">Taux de Marge</h5>
                <h1 class="display-4 fw-bold"><?= round($marge, 1) ?>%</h1>
                <p class="small">Indice de performance du mois</p>
            </div>
        </div>
    </div>
</div>