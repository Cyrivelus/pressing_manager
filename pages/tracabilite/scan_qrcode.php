<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Station de Scan QR Code";

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>
<br> <br> <br>
<div class="container-fluid py-5">
    <div class="row justify-content-center mt-4">
        <div class="col-lg-6 text-center">
            <div class="mb-4">
                <h2 class="fw-bold mb-1 text-dark">
                    <?= htmlspecialchars($titre) ?>
                </h2>
                <p class="text-muted">Étape actuelle : <span id="current_step_label" class="fw-bold text-primary">LAVAGE</span></p>
            </div>
            
            <div class="card border-0 shadow-lg p-5 mb-4 bg-light overflow-hidden position-relative" style="border-radius: 20px;">
                <div class="scan-overlay"></div>
                <div class="py-4">
                   
                    <h4 class="fw-bold">Prêt à scanner...</h4>
                    
                    <input type="text" id="qr_input" 
                           class="form-control form-control-lg text-center mt-3 border-primary shadow-sm" 
                           placeholder="Scannez l'étiquette ici" 
                           style="font-size: 1.5rem; letter-spacing: 2px;"
                           autofocus>
                    
                    <small class="text-muted d-block mt-3">
                      Mode automatique activé
                    </small>
                </div>
            </div>

            <div class="row g-3 mb-5">
                <div class="col-4">
                    <button class="btn btn-outline-info w-100 py-3 shadow-sm btn-statut active" data-status="LAVAGE">
                        Lavage
                    </button>
                </div>
                <div class="col-4">
                    <button class="btn btn-outline-warning w-100 py-3 shadow-sm btn-statut" data-status="REPASSAGE">
                        Repassage
                    </button>
                </div>
                <div class="col-4">
                    <button class="btn btn-outline-success w-100 py-3 shadow-sm btn-statut" data-status="PRET">
                        Prêt
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm border-top border-4 border-primary">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-uppercase small">Dernières actions</h6>
                    <button class="btn btn-sm btn-light" onclick="loadHistory()"></button>
                </div>
                <div class="list-group list-group-flush" id="scan_history" style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center py-4 text-muted small">Aucune activité récente</div>
                </div>
                <div class="card-footer bg-light text-center py-2">
                    <a href="../journal/flux.php" class="small text-decoration-none fw-bold">Voir tout le journal</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .scan-overlay {
        position: absolute; top: 0; left: 0; width: 100%; height: 4px;
        background: linear-gradient(to bottom, rgba(13, 110, 253, 0), rgba(13, 110, 253, 0.8));
        box-shadow: 0 0 15px rgba(13, 110, 253, 0.5);
        animation: scan-line 2.5s infinite ease-in-out;
        z-index: 10;
    }
    @keyframes scan-line { 0% { top: 0; } 100% { top: 100%; } }
    .btn-statut.active { background-color: var(--bs-info); color: white !important; border-color: var(--bs-info); }
    .btn-outline-warning.active { background-color: var(--bs-warning); color: white !important; }
    .btn-outline-success.active { background-color: var(--bs-success); color: white !important; }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let currentStatus = 'LAVAGE';
    const qrInput = document.getElementById('qr_input');
    const historyContainer = document.getElementById('scan_history');

    // 1. Gestion des boutons de statut
    document.querySelectorAll('.btn-statut').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.btn-statut').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentStatus = this.getAttribute('data-status');
            document.getElementById('current_step_label').innerText = currentStatus;
            qrInput.focus();
        });
    });

    // 2. Maintien du focus
    document.addEventListener('click', () => qrInput.focus());

    // 3. Traitement du Scan
    qrInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            const code = this.value.trim();
            if(code) {
                processScan(code);
                this.value = '';
            }
        }
    });

    function processScan(code) {
        // Animation visuelle de chargement
        qrInput.disabled = true;

        fetch('process_scan.php', {
            method: 'POST',
            headers: { 'Content-Type: application/x-www-form-urlencoded' },
            body: `code=${encodeURIComponent(code)}&status=${currentStatus}`
        })
        .then(response => response.json())
        .then(data => {
            qrInput.disabled = false;
            qrInput.focus();

            if(data.success) {
                // Notification sonore ou visuelle légère
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
                Toast.fire({ icon: 'success', title: `Article ${code} -> ${currentStatus}` });
                loadHistory(); // Rafraîchir l'historique latéral
            } else {
                Swal.fire('Erreur', data.message || 'Code inconnu', 'error');
            }
        })
        .catch(err => {
            qrInput.disabled = false;
            console.error(err);
        });
    }

    function loadHistory() {
        fetch('get_mini_history.php')
            .then(res => res.text())
            .then(html => historyContainer.innerHTML = html);
    }

    // Charger l'historique au démarrage
    loadHistory();
</script>

<?php require_once '../../templates/footer.php'; ?>