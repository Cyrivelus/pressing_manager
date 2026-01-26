<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Appliquer une Remise";

// Logique simplifiée pour l'exemple : on liste les services pour appliquer une remise globale
require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container py-5">
    <div class="card border-0 shadow-sm p-4 bg-primary text-white mb-4">
        <h4 class="fw-bold m-0"><i class="fas fa-percentage me-2"></i> Simulateur de Remise Client</h4>
        <p class="mb-0 opacity-75">Calculez l'impact d'une remise avant de l'appliquer sur un ticket.</p>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm p-4">
                <label class="fw-bold mb-2">Montant du Ticket</label>
                <input type="number" id="montant" class="form-control mb-3" placeholder="Ex: 5000">
                
                <label class="fw-bold mb-2">Remise (%)</label>
                <input type="range" id="remiseRange" class="form-range" min="0" max="50" step="5" value="10">
                <div class="text-center fw-bold text-primary" id="remiseVal">10%</div>
                
                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Initial:</span>
                    <span id="initial">0 F</span>
                </div>
                <div class="d-flex justify-content-between mb-2 text-danger">
                    <span>Économie Client:</span>
                    <span id="economie">0 F</span>
                </div>
                <div class="d-flex justify-content-between fw-bold fs-5 border-top pt-2">
                    <span>À PAYER:</span>
                    <span id="final" class="text-success">0 F</span>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="alert alert-info">
                <h6 class="fw-bold"><i class="fas fa-info-circle"></i> Politique de remise Kayade</h6>
                <ul class="small mb-0">
                    <li>Les remises au-delà de 30% nécessitent l'accord du gérant.</li>
                    <li>Les points fidélité sont calculés sur le montant après remise.</li>
                    <li>La remise s'applique sur le total hors taxe.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
const input = document.getElementById('montant');
const range = document.getElementById('remiseRange');
const update = () => {
    let m = parseFloat(input.value) || 0;
    let r = range.value;
    document.getElementById('remiseVal').innerText = r + '%';
    document.getElementById('initial').innerText = m + ' F';
    let eco = m * (r/100);
    document.getElementById('economie').innerText = '-' + eco + ' F';
    document.getElementById('final').innerText = (m - eco) + ' FCFA';
};
input.oninput = update;
range.oninput = update;
</script>

<?php require_once $root . '/templates/footer.php'; ?>