<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';


// Traitement AJAX pour l'association
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'associer_tag') {
    header('Content-Type: application/json');
    try {
        if(empty($_POST['uid']) || empty($_POST['id_ligne'])) {
            throw new Exception("Données incomplètes (UID ou Article manquant)");
        }
        $stmt = $pdo->prepare("UPDATE lignes_ticket SET rfid_tag = ? WHERE id_ligne = ?");
        $stmt->execute([$_POST['uid'], $_POST['id_ligne']]);
        echo json_encode(['status' => 'success', 'message' => 'Tag RFID associé avec succès !']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

$titre = "Système de Traçabilité RFID";

try {
    // Récupération des articles tagués
    $sql = "SELECT l.*, t.numero_ticket, c.nom_client 
            FROM lignes_ticket l
            JOIN tickets t ON l.id_ticket = t.id_ticket
            JOIN clients c ON t.id_client = c.id_client
            WHERE l.rfid_tag IS NOT NULL
            ORDER BY l.id_ligne DESC LIMIT 15";
    $articles_rfid = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // Articles sans tag pour le formulaire
    $sql_dispo = "SELECT l.id_ligne, l.description, t.numero_ticket 
                  FROM lignes_ticket l 
                  JOIN tickets t ON l.id_ticket = t.id_ticket 
                  WHERE l.rfid_tag IS NULL ORDER BY t.date_depot DESC LIMIT 50";
    $articles_dispos = $pdo->query($sql_dispo)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { $db_error = $e->getMessage(); }

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($TITRE_PAGE) ?> | BailCompta 360</title>
   
    <link rel="stylesheet" href="../../css/select2.min.css">
    <link rel="stylesheet" href="../../css/select2-bootstrap.min.css">

</head>
<body>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-primary"><i class="fas fa-microchip me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Identification radio-fréquence et suivi de flux en temps réel</p>
        </div>
        <div class="d-flex gap-2">
            <button id="btnSync" class="btn btn-outline-primary border-2 fw-bold px-4">
                Synchroniser Scanner
            </button>
            <button id="toggleAssignBox" class="btn btn-primary shadow-sm fw-bold px-4">
                 Nouvelle Association
            </button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4 d-none" id="assignBox">
            <div class="card border-0 shadow-lg sticky-top" style="top: 100px; border-radius: 20px;">
                <div class="card-header bg-primary text-white py-3 border-0" style="border-radius: 20px 20px 0 0;">
                    <h5 class="fw-bold mb-0">Scanner l'Article</h5>
                </div>
                <div class="card-body p-4">
                    <form id="formAssocier">
                        <input type="hidden" name="action" value="associer_tag">
                        
                        <div class="text-center mb-4">
                            <div id="scannerAnimation" class="scanner-ring mx-auto mb-3">
                              
                            </div>
                            <p class="small text-muted" id="statusText">Lecteur en attente d'un tag...</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">UID du Tag (Auto-detect)</label>
                            <input type="text" class="form-control form-control-lg font-monospace text-center bg-light" 
                                   name="uid" id="inputUID" readonly placeholder="---- ---- ---- ----">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase">Article à lier</label>
                           <div class="form-group">
    <label for="selectArticle" class="fw-bold">Article concerné</label>
    <select class="form-control select2-enable form-select-lg shadow-sm" 
            name="id_ligne" 
            id="selectArticle" 
            required 
            <?= empty($articles_dispos) ? 'disabled' : '' ?>>
        
        <option value="">-- Rechercher un Ticket ou une Description --</option>
        
        <?php foreach($articles_dispos as $ad): ?>
            <option value="<?= htmlspecialchars($ad['id_ligne']) ?>">
                Ticket #<?= htmlspecialchars($ad['numero_ticket']) ?> - <?= htmlspecialchars($ad['description']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-pill shadow">
                            ENREGISTRER L'ASSOCIATION
                        </button>
                        <button type="button" class="btn btn-link w-100 mt-2 text-muted" id="btnCancel">Annuler</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8" id="tableBox">
            <div class="card border-0 shadow-sm" style="border-radius: 20px;">
                <div class="card-header bg-white py-4 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-uppercase tracking-wider">
                        <span class="p-2 bg-success rounded-circle d-inline-block me-2 pulse-green"></span>
                        Flux RFID Actifs
                    </h6>
                    <div class="input-group w-auto">
                        <span class="input-group-text bg-light border-0"></span>
                        <input type="text" class="form-control bg-light border-0 small" placeholder="Rechercher un tag...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light-primary text-primary small fw-bold">
                            <tr>
                                <th class="ps-4">TAG ID</th>
                                <th>ARTICLE / TICKET</th>
                                <th>CLIENT</th>
                                <th class="text-center">DERN. SCAN</th>
                                <th class="text-end pe-4">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($articles_rfid)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">Aucune détection enregistrée.</td></tr>
                            <?php else: foreach($articles_rfid as $a): ?>
                                <tr>
                                    <td class="ps-4">
                                        <code class="text-primary fw-bold"><?= htmlspecialchars($a['rfid_tag']) ?></code>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($a['description'] ?? 'Linge') ?></div>
                                        <div class="badge bg-light text-dark fw-normal">Ticket #<?= $a['numero_ticket'] ?></div>
                                    </td>
                                    <td><span class="small"><?= htmlspecialchars($a['nom_client']) ?></span></td>
                                    <td class="text-center">
                                        <span class="badge bg-info-soft text-info">Zone Séchage</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-light rounded-circle"></button>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-light-primary { background-color: #f8faff; }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
    .pulse-green { width: 10px; height: 10px; box-shadow: 0 0 0 rgba(40, 167, 69, 0.4); animation: pulse 2s infinite; }
    
    .scanner-ring {
        width: 80px; height: 80px; border: 4px solid #e9ecef; border-top: 4px solid #0d6efd;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        animation: spin 2s linear infinite;
    }

    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); } 100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); } }
    
    .font-monospace { font-family: 'Monaco', 'Consolas', monospace; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const assignBox = document.getElementById('assignBox');
    const tableBox = document.getElementById('tableBox');
    const btnToggle = document.getElementById('toggleAssignBox');
    const btnCancel = document.getElementById('btnCancel');
    const inputUID = document.getElementById('inputUID');
    const statusText = document.getElementById('statusText');

    // Toggle formulaire
    btnToggle.addEventListener('click', () => {
        assignBox.classList.remove('d-none');
        tableBox.classList.replace('col-lg-12', 'col-lg-8');
        simulateRFIDScan(); // Démarrer la simulation de scan
    });

    btnCancel.addEventListener('click', () => {
        assignBox.classList.add('d-none');
        tableBox.classList.replace('col-lg-8', 'col-lg-12');
    });

    // Simulation de lecture de puce
    function simulateRFIDScan() {
        inputUID.value = "";
        statusText.innerHTML = '<span class="text-primary"> Approchez un tag...</span>';
        
        setTimeout(() => {
            const mockUID = "RF-" + Math.random().toString(16).slice(2, 10).toUpperCase();
            inputUID.value = mockUID;
            statusText.innerHTML = '<span class="text-success fw-bold"> Tag détecté !</span>';
            inputUID.classList.add('is-valid');
        }, 2000);
    }

    // Traitement formulaire
    const form = document.getElementById('formAssocier');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Enregistré', timer: 1500, showConfirmButton: false });
                setTimeout(() => location.reload(), 1000);
            } else {
                Swal.fire('Erreur', data.message, 'error');
            }
        });
    });

    // Synchronisation matérielle
    document.getElementById('btnSync').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Recherche...';
        
        setTimeout(() => {
            Swal.fire('Scanner Connecté', 'Lecteur USB RFID Port COM4 opérationnel', 'success');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Synchroniser Scanner';
        }, 1500);
    });
});
</script>
<script src="../../js/jquery.min.js"></script>
<script src="../../js/bootstrap.min.js"></script>
<script src="../../js/select2.min.js"></script>

<script>
    $(document).ready(function() {
    // Initialisation de Select2 pour les articles
    $('#selectArticle').select2({
        theme: "bootstrap",
        placeholder: "-- Tapez le numéro de ticket ou la description --",
        allowClear: true,
        width: '100%',
        // On s'assure que le menu déroulant ne soit pas trop petit
        dropdownParent: $('body') 
    });
});
</script>
<?php require_once '../../templates/footer.php'; ?>