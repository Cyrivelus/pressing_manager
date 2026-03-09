<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Certifications & Labels Qualité";
$success_msg = "";

// 2. Traitement dynamique du "Nouveau Label"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_label'])) {
    try {
        $fichier_nom = $_FILES['certif_file']['name'] ?? 'default.pdf';
        // Simulation d'upload (à adapter selon votre dossier d'upload)
        
        $stmt = $pdo->prepare("
            INSERT INTO certifications (nom_label, organisme, date_obtention, date_expiration, fichier_pdf) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['nom_label'],
            $_POST['organisme'],
            $_POST['date_obtention'],
            $_POST['date_expiration'],
            $fichier_nom
        ]);
        $success_msg = "Le nouveau label a été enregistré avec succès.";
    } catch (PDOException $e) {
        $db_error = "Erreur lors de l'enregistrement : " . $e->getMessage();
    }
}

// 3. Récupération des données
try {
    $query = "SELECT * FROM certifications ORDER BY date_expiration ASC";
    $certifs = $pdo->query($query)->fetchAll();
} catch (PDOException $e) {
    $db_error = "Table absente ou inaccessible.";
    $certifs = [];
}

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
        <div>
            <h2 class="fw-bold m-0 text-primary">[QUALITÉ] <?= $titre ?></h2>
            <p class="text-muted small">Suivi des accréditations et conformité</p>
        </div>
        <a href="#nouveau-label-form" class="btn btn-primary px-4 rounded-pill shadow-sm">
            + Ajouter un Label
        </a>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4"><b>[SUCCÈS]</b> <?= $success_msg ?></div>
    <?php endif; ?>

    <?php if (isset($db_error)): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4"><b>[ALERTE]</b> <?= $db_error ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-lg rounded-4 mb-5" id="nouveau-label-form">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-4 text-primary text-uppercase small border-bottom pb-2">Enregistrer une nouvelle certification</h5>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action_label" value="1">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="small fw-bold">Nom du Label / Certification</label>
                        <input type="text" name="nom_label" class="form-control" required placeholder="Ex: ISO 14001, Eco-Label">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">Organisme certificateur</label>
                        <input type="text" name="organisme" class="form-control" required placeholder="Ex: AFNOR, Ecocert">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Obtention</label>
                        <input type="date" name="date_obtention" id="date_obt" class="form-control" required onchange="setExpiry()">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Expiration</label>
                        <input type="date" name="date_expiration" id="date_exp" class="form-control" required>
                    </div>
                    <div class="col-md-8">
                        <label class="small fw-bold">Fichier justificatif (PDF)</label>
                        <input type="file" name="certif_file" class="form-control">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100 fw-bold">Valider la certification</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm p-4 mb-5 rounded-4 bg-white border-start border-5 border-success">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h6 class="fw-bold mb-3 text-uppercase small text-muted">Progression vers le Label "Or"</h6>
                <div class="progress mb-2" style="height: 12px; border-radius: 6px;">
                    <div class="progress-bar bg-success" style="width: 75%">75%</div>
                </div>
                <small class="text-muted"><i>Objectif : Réduction de 20% de la consommation d'eau d'ici 2026.</i></small>
            </div>
            <div class="col-md-4 text-end">
                <div class="p-3 bg-light rounded-4 d-inline-block text-center border">
                    <small class="d-block text-muted fw-bold small text-uppercase">Prochain Audit</small>
                    <span class="fw-bold text-primary">14 MARS 2026</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach($certifs as $c): 
            $diff = strtotime($c['date_expiration']) - time();
            $jours = round($diff / 86400);
            $is_warning = ($jours < 60);
            $color = $is_warning ? 'danger' : 'success';
        ?>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-<?= $color ?> bg-opacity-10 p-2 px-3 border border-<?= $color ?> rounded-3">
                            <span class="fw-bold text-<?= $color ?>">[LABEL]</span>
                        </div>
                        <span class="badge rounded-pill <?= $is_warning ? 'bg-danger' : 'bg-success-soft text-success' ?>">
                            <?= $jours > 0 ? $jours . ' j.' : '[EXPIRÉ]' ?>
                        </span>
                    </div>
                    
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($c['nom_label']) ?></h5>
                    <p class="text-muted small mb-3">Organisme : <?= htmlspecialchars($c['organisme']) ?></p>
                    
                    <div class="bg-light p-3 rounded-3 mb-0">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Obtenu :</span>
                            <span class="fw-bold"><?= date('d/m/Y', strtotime($c['date_obtention'])) ?></span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Expire :</span>
                            <span class="fw-bold text-<?= $color ?>"><?= date('d/m/Y', strtotime($c['date_expiration'])) ?></span>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 p-4 pt-0">
                    <div class="row g-2">
                        <div class="col-12">
                            <a href="../../uploads/certificats/<?= $c['fichier_pdf'] ?>" class="btn btn-sm btn-outline-dark w-100 rounded-pill" target="_blank">
                                Ouvrir le PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Intelligence : calcul auto de la date d'expiration (N+1 an)
function setExpiry() {
    const obtDate = document.getElementById('date_obt').value;
    if(obtDate) {
        let date = new Date(obtDate);
        date.setFullYear(date.getFullYear() + 1);
        document.getElementById('date_exp').value = date.toISOString().split('T')[0];
    }
}
</script>

<style>
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .form-control { border-radius: 8px; border: 1px solid #ced4da; padding: 0.6rem; }
    .form-control:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15); }
    #nouveau-label-form { scroll-margin-top: 100px; }
    .card { transition: transform 0.2s; }
    .card:hover { transform: translateY(-3px); }
</style>

<?php require_once '../../templates/footer.php'; ?>