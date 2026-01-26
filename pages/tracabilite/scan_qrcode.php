<?php
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * CORRECTION DU CHEMIN : 
 * On utilise __DIR__ pour remonter proprement les niveaux de dossiers.
 * Puisqu'on est dans /pages/tracabilite/, on remonte deux fois (../../)
 */
$root = realpath(__DIR__ . '/../../');

// On définit une constante pour l'inclusion des fichiers si nécessaire
if (!defined('ROOT_PATH')) define('ROOT_PATH', $root);

// Vérification de sécurité pour éviter les erreurs de chemin
if (!file_exists($root . '/fonctions/database.php')) {
    die("Erreur critique : Le fichier database.php est introuvable dans " . $root);
}

require_once $root . '/fonctions/database.php';

$titre = "Station de Scan QR Code";

// Inclusion des templates via le chemin physique (C:\xampp\htdocs...)
require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="row justify-content-center mt-4">
        <div class="col-lg-6 text-center">
            <div class="mb-4">
                <h2 class="fw-bold mb-1 text-dark">
                    <i class="fas fa-qrcode text-primary me-2"></i><?= htmlspecialchars($titre) ?>
                </h2>
                <p class="text-muted">Mise à jour instantanée du flux de production</p>
            </div>
            
            <div class="card border-0 shadow-lg p-5 mb-4 bg-light overflow-hidden position-relative" style="border-radius: 20px;">
                <div class="scan-overlay"></div>
                <div class="py-4">
                    <i class="fas fa-expand fa-5x text-primary opacity-25 mb-4"></i>
                    <h4 class="fw-bold">Prêt à scanner...</h4>
                    
                    <input type="text" id="qr_input" 
                           class="form-control form-control-lg text-center mt-3 border-primary shadow-sm" 
                           placeholder="Scannez l'étiquette ici" 
                           style="font-size: 1.5rem; letter-spacing: 2px;"
                           autofocus>
                    
                    <small class="text-muted d-block mt-3">
                        <i class="fas fa-info-circle me-1"></i> Le focus doit rester sur ce champ pour les douchettes automatiques
                    </small>
                </div>
            </div>

            <div class="row g-3 mb-5">
                <div class="col-4">
                    <button class="btn btn-outline-info w-100 py-3 active shadow-sm btn-statut" data-status="LAVAGE">
                        <i class="fas fa-soap d-block mb-2 fa-lg"></i> Lavage
                    </button>
                </div>
                <div class="col-4">
                    <button class="btn btn-outline-warning w-100 py-3 shadow-sm btn-statut" data-status="REPASSAGE">
                        <i class="fas fa-tshirt d-block mb-2 fa-lg"></i> Repassage
                    </button>
                </div>
                <div class="col-4">
                    <button class="btn btn-outline-success w-100 py-3 shadow-sm btn-statut" data-status="PRET">
                        <i class="fas fa-check-double d-block mb-2 fa-lg"></i> Prêt
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm border-top border-4 border-primary">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-uppercase small"><i class="fas fa-history me-2"></i>Dernières actions</h6>
                </div>
                <div class="list-group list-group-flush" id="scan_history">
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <span class="fw-bold small">#T-8892 - Chemise soie</span><br>
                            <span class="badge bg-success-soft text-success smaller">Statut : Terminé</span>
                        </div>
                        <small class="text-muted italic">Il y a 2m</small>
                    </div>
                </div>
                <div class="card-footer bg-light text-center py-2">
                    <a href="#" class="small text-decoration-none">Voir tout le journal</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Animation de la ligne de scan */
    .scan-overlay {
        position: absolute; top: 0; left: 0; width: 100%; height: 4px;
        background: linear-gradient(to bottom, rgba(13, 110, 253, 0), rgba(13, 110, 253, 0.8));
        box-shadow: 0 0 15px rgba(13, 110, 253, 0.5);
        animation: scan-line 2.5s infinite ease-in-out;
        z-index: 10;
    }

    @keyframes scan-line {
        0% { top: 0; }
        100% { top: 100%; }
    }

    #qr_input:focus { 
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15); 
        border-color: #0d6efd; 
    }

    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .smaller { font-size: 0.75rem; }
    .italic { font-style: italic; }
</style>

<script>
    // Script pour s'assurer que l'input garde toujours le focus
    document.addEventListener('click', function() {
        document.getElementById('qr_input').focus();
    });

    // Gestion de l'input (simulation de scan)
    document.getElementById('qr_input').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            const code = this.value;
            if(code) {
                console.log("Code scanné : " + code);
                // Ici vous feriez un appel AJAX vers /fonctions/tracabilite/update_status.php
                this.value = ''; // Reset du champ
                alert("Article " + code + " mis à jour !");
            }
        }
    });
</script>

<?php  '../../templates/footer.php'; ?>