<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité : Vérification de l'utilisateur
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$utilisateur_id = $_SESSION['utilisateur_id'];
$titre = "Réserver un service";

// 2. Traitement du formulaire
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_res = $_POST['date_reservation'];
    $creneau = $_POST['creneau_horaire'];
    $type = $_POST['type_service']; // 'collecte' ou 'depot'

    try {
        $stmt = $pdo->prepare("INSERT INTO reservations_online (id_client, date_reservation, creneau_horaire, statut, token_confirmation) 
                               VALUES (?, ?, ?, 'en_attente', ?)");
        $token = bin2hex(random_bytes(16));
        $stmt->execute([$utilisateur_id, $date_res, $creneau, $token]);
        
        $message = "<div class='alert alert-success'>Votre réservation a été enregistrée ! Vous recevrez un SMS de confirmation.</div>";
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Erreur : " . $e->getMessage() . "</div>";
    }
}

require_once $root . '/templates/header_client.php';
?>

<div class="container py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="index.php" class="btn btn-light rounded-circle me-3"><i class="fas fa-arrow-left"></i></a>
        <h2 class="fw-bold m-0"><?= $titre ?></h2>
    </div>

    <?= $message ?>

    <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 20px;">
        <form method="POST" action="">
            <div class="mb-4">
                <label class="form-label fw-bold">Type de service</label>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="radio" class="btn-check" name="type_service" id="service1" value="depot" checked>
                        <label class="btn btn-outline-primary w-100 py-3" for="service1">
                            <i class="fas fa-store d-block mb-2 fa-lg"></i> Dépôt Boutique
                        </label>
                    </div>
                    <div class="col-6">
                        <input type="radio" class="btn-check" name="type_service" id="service2" value="collecte">
                        <label class="btn btn-outline-primary w-100 py-3" for="service2">
                            <i class="fas fa-truck d-block mb-2 fa-lg"></i> Collecte Domicile
                        </label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Date souhaitée</label>
                    <input type="date" name="date_reservation" class="form-control form-control-lg" 
                           min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Créneau horaire</label>
                    <select name="creneau_horaire" class="form-select form-select-lg" required>
                        <option value="">Choisir une heure...</option>
                        <option>08:00 - 10:00</option>
                        <option>10:00 - 12:00</option>
                        <option>14:00 - 16:00</option>
                        <option>16:00 - 18:00</option>
                    </select>
                </div>
            </div>

            <div class="bg-light p-3 rounded-3 mb-4 small text-muted">
                <i class="fas fa-info-circle me-2"></i> 
                Pour les collectes à domicile, un supplément de 2 000 FCFA sera appliqué sur votre facture finale.
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 py-3 shadow">
                Confirmer ma réservation
            </button>
        </form>
    </div>

    <h5 class="fw-bold mb-3">Mes réservations à venir</h5>
    <?php
    $stmt = $pdo->prepare("SELECT * FROM reservations_online WHERE id_client = ? AND date_reservation >= CURDATE() ORDER BY date_reservation ASC");
    $stmt->execute([$utilisateur_id]);
    $mes_res = $stmt->fetchAll();

    if(empty($mes_res)): ?>
        <p class="text-muted small">Aucune réservation prévue.</p>
    <?php else: foreach($mes_res as $r): ?>
        <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-3 shadow-sm mb-2 border-start border-4 border-warning">
            <div>
                <span class="fw-bold"><?= date('d/m/Y', strtotime($r['date_reservation'])) ?></span><br>
                <small class="text-muted"><?= $r['creneau_horaire'] ?></small>
            </div>
            <span class="badge bg-light text-dark border"><?= ucfirst($r['statut']) ?></span>
        </div>
    <?php endforeach; endif; ?>
</div>



<?php require_once $root . '/templates/footer_client.php'; ?>