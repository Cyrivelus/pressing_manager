<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Centre de Notifications & Alertes";

try {
    // 2. Logique de détection des alertes réelles
    
    // A. Retards de production (Statut 'en_attente' ou 'en_traitement' > 48h)
    // Correction : Utilisation de 'date_depot' au lieu de 'date_reception'
    $sql_retards = "SELECT COUNT(*) FROM tickets 
                    WHERE statut IN ('en_attente', 'en_traitement') 
                    AND date_depot < DATE_SUB(NOW(), INTERVAL 2 DAY)";
    $nb_retards = $pdo->query($sql_retards)->fetchColumn();

    // B. Colis "Oubliés" (Statut 'pret' > 30 jours en rayon)
    // Correction : Utilisation de 'date_depot'
    $sql_oublies = "SELECT COUNT(*) FROM tickets 
                    WHERE statut = 'pret' 
                    AND date_depot < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $nb_oublies = $pdo->query($sql_oublies)->fetchColumn();

    // C. Alertes Stock (Produits en dessous du seuil)
    $sql_stock = "SELECT COUNT(*) FROM produits WHERE quantite_stock <= seuil_alerte AND est_actif = 1";
    $nb_stock = $pdo->query($sql_stock)->fetchColumn();

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0">
                <i class="fas fa-bell text-danger animate__animated animate__swing animate__infinite"></i> 
                <?= $titre ?>
            </h2>
            <p class="text-muted">Anomalies et points de vigilance nécessitant une intervention immédiate</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary shadow-sm"><i class="fas fa-history me-1"></i> Archives</button>
            <button class="btn btn-primary shadow-sm"><i class="fas fa-cog me-1"></i> Seuils</button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-danger text-uppercase small">Alertes Prioritaires</h6>
                </div>
                <div class="list-group list-group-flush">
                    
                    <?php if ($nb_retards > 0): ?>
                    <div class="list-group-item p-4 border-start border-4 border-danger">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex">
                                <div class="icon-box bg-danger-soft text-danger p-3 rounded-circle me-3">
                                    <i class="fas fa-hourglass-half fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1 text-danger">Goulot d'étranglement en atelier</h6>
                                    <p class="small text-muted mb-0"><strong><?= $nb_retards ?> tickets</strong> dépassent le délai standard de 48h.</p>
                                    <div class="mt-2">
                                        <a href="../atelier/liste_tickets.php?filtre=retard" class="btn btn-sm btn-danger px-3 rounded-pill">Traiter en priorité</a>
                                    </div>
                                </div>
                            </div>
                            <span class="badge bg-danger">CRITIQUE</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($nb_stock > 0): ?>
                    <div class="list-group-item p-4 border-start border-4 border-primary">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex">
                                <div class="icon-box bg-primary-soft text-primary p-3 rounded-circle me-3">
                                    <i class="fas fa-box-open fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1 text-primary">Rupture de stock imminente</h6>
                                    <p class="small text-muted mb-0"><strong><?= $nb_stock ?> produits</strong> (lessive, cintres, etc.) sont sous le seuil critique.</p>
                                    <div class="mt-2">
                                        <a href="../stock/inventory.php" class="btn btn-sm btn-primary px-3 rounded-pill">Commander</a>
                                    </div>
                                </div>
                            </div>
                            <span class="badge bg-primary">STOCK</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($nb_oublies > 0): ?>
                    <div class="list-group-item p-4 border-start border-4 border-warning">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex">
                                <div class="icon-box bg-warning-soft text-warning p-3 rounded-circle me-3">
                                    <i class="fas fa-archive fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1 text-warning">Encombrement des rayons (Stocks Morts)</h6>
                                    <p class="small text-muted mb-0"><strong><?= $nb_oublies ?> commandes</strong> prêtes depuis +30 jours.</p>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-warning px-3 rounded-pill text-white"><i class="fas fa-sms me-1"></i> Relancer les clients</button>
                                    </div>
                                </div>
                            </div>
                            <span class="badge bg-warning text-white">VIGILANCE</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($nb_retards == 0 && $nb_oublies == 0 && $nb_stock == 0): ?>
                        <div class="p-5 text-center">
                            <i class="fas fa-check-circle fa-4x text-success opacity-25 mb-3"></i>
                            <h5 class="text-muted">Aucune alerte en cours</h5>
                            <p class="small text-muted">Votre pressing tourne parfaitement !</p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 bg-light" style="border-radius: 15px;">
                <h6 class="fw-bold mb-4 small text-uppercase">Canaux de réception</h6>
                
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" checked id="notifPush">
                    <label class="form-check-label small" for="notifPush">Notifications Push</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" checked id="notifEmail">
                    <label class="form-check-label small" for="notifEmail">Rapport quotidien par Email</label>
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="notifSms">
                    <label class="form-check-label small" for="notifSms">Alertes critiques par SMS</label>
                </div>

                <hr>
                <h6 class="fw-bold mb-3 small text-uppercase text-muted">Responsables alertés</h6>
                <div class="d-flex align-items-center mb-2">
                    <div class="avatar-sm bg-dark text-white rounded-circle me-2 d-flex align-items-center justify-content-center fw-bold" style="width: 30px; height: 30px; font-size: 10px;">AD</div>
                    <span class="small">Administrateur (Vous)</span>
                </div>
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-secondary text-white rounded-circle me-2 d-flex align-items-center justify-content-center fw-bold" style="width: 30px; height: 30px; font-size: 10px;">RA</div>
                    <span class="small">Responsable Atelier</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
</style>

<?php require_once '../../templates/footer.php'; ?>