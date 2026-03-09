<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';


$titre = "Gestion des Réclamations & Litiges";

// --- TRAITEMENT DU FORMULAIRE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_incident'])) {
    $id_ticket_input = $_POST['id_ticket'];
    $incident_type = $_POST['incident_type'];
    $description = $_POST['description'];

    $check = $pdo->prepare("SELECT id_ticket FROM tickets WHERE numero_ticket = ?");
    $check->execute([$id_ticket_input]);
    $ticket = $check->fetch();

    if ($ticket) {
        $sqlInsert = "INSERT INTO tracabilite_tickets (id_ticket, action, note_qualite, date_heure) 
                      VALUES (?, 'RECLAMATION', ?, NOW())";
        $pdo->prepare($sqlInsert)->execute([$ticket['id_ticket'], "[$incident_type] $description"]);
        $success = "L'incident pour le ticket #$id_ticket_input a été enregistré.";
    } else {
        $error = "Numéro de ticket '$id_ticket_input' introuvable.";
    }
}

// --- RÉCUPÉRATION DES DONNÉES ---
$sql = "SELECT t.id_ticket, t.numero_ticket, c.nom_client, 
               tr.note_qualite as incident, tr.date_heure as date_incident
        FROM tickets t
        JOIN clients c ON t.id_client = c.id_client
        JOIN tracabilite_tickets tr ON t.id_ticket = tr.id_ticket
        WHERE tr.action = 'RECLAMATION' AND t.statut != 'annule'
        ORDER BY tr.date_heure DESC";
$reclamations = $pdo->query($sql)->fetchAll();

require_once '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<style>
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
    #incidentFormContainer { display: none; animation: fadeInDown 0.4s ease; }
    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .card { border-radius: 12px; }
    .btn-action { transition: all 0.2s; }
    .btn-action:hover { transform: translateY(-2px); }
</style>
<br> <br> <br>
<div class="container-fluid py-5">
    <?php if(isset($success)): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4 animate__animated animate__fadeIn">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4 animate__animated animate__shakeX">
             <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-danger"><?= $titre ?></h2>
            <p class="text-muted mb-0">Interface de résolution des litiges clients</p>
        </div>
        <button id="btnToggleForm" class="btn btn-danger shadow-sm px-4 py-2 fw-bold">
            Signaler un Incident
        </button>
    </div>

    <div id="incidentFormContainer" class="mb-5">
        <div class="card border-0 shadow-lg border-start border-4 border-danger">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0">Nouveau dossier de litige</h5>
                    <button type="button" class="btn-close" id="btnCloseForm"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action_incident" value="1">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">N° Ticket de caisse</label>
                            <input type="text" name="id_ticket" class="form-control" placeholder="ex: 4502" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nature du litige</label>
                            <select name="incident_type" class="form-select">
                                <option>Détérioration textile</option>
                                <option>Perte d'article</option>
                                <option>Retard de livraison</option>
                                <option>Qualité de nettoyage insuffisante</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Niveau d'urgence</label>
                            <select class="form-select text-danger fw-bold">
                                <option>CRITIQUE (Remboursement)</option>
                                <option>MOYEN (Retouche)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Description des faits</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Détaillez l'incident..." required></textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-danger px-5 fw-bold shadow">ENREGISTRER LE LITIGE</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    

    <div class="row g-4 mb-5">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 h-100">
                <h6 class="fw-bold mb-4 text-uppercase small text-muted">Statistiques Qualité</h6>
                <div class="row text-center">
                    <div class="col-6 border-end">
                        <h3 class="fw-bold text-danger"><?= count($reclamations) ?></h3>
                        <small class="text-muted">Réclamations totales</small>
                    </div>
                    <div class="col-6">
                        <h3 class="fw-bold text-success">92%</h3>
                        <small class="text-muted">Satisfaction après litige</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white h-100">
                <h6 class="text-warning fw-bold mb-3 small">RISQUE FINANCIER ESTIMÉ</h6>
                <h2 class="fw-bold m-0">145 000 <small class="fs-6">FCFA</small></h2>
                <hr class="opacity-25">
                <small class="text-muted italic">Basé sur la valeur vénale des articles en litige</small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="fw-bold mb-0">Historique des litiges récents</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4">Ticket / Client</th>
                        <th>Détails de la réclamation</th>
                        <th class="text-center">Date d'ouverture</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reclamations)): ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted">Aucun incident enregistré.</td></tr>
                    <?php endif; ?>
                    <?php foreach($reclamations as $r): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-primary">#<?= htmlspecialchars($r['numero_ticket']) ?></div>
                            <small class="fw-bold"><?= htmlspecialchars($r['nom_client']) ?></small>
                        </td>
                        <td>
                            <div class="small text-dark p-2 rounded bg-light border-start border-3 border-danger">
                                <?= htmlspecialchars($r['incident']) ?>
                            </div>
                        </td>
                        <td class="text-center small">
                            <?= date('d/m/Y', strtotime($r['date_incident'])) ?><br>
                            <span class="text-muted"><?= date('H:i', strtotime($r['date_incident'])) ?></span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-dark btn-action" title="Détails"></button>
                                <button class="btn btn-sm btn-success btn-action" title="Marquer comme résolu"></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Logique d'affichage du formulaire sans Modal
    const btnToggle = document.getElementById('btnToggleForm');
    const btnClose = document.getElementById('btnCloseForm');
    const formContainer = document.getElementById('incidentFormContainer');

    btnToggle.addEventListener('click', () => {
        formContainer.style.display = 'block';
        window.scrollTo({ top: formContainer.offsetTop - 100, behavior: 'smooth' });
    });

    btnClose.addEventListener('click', () => {
        formContainer.style.display = 'none';
    });
</script>

<?php require_once '../../templates/footer.php'; ?>