<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion des Réclamations & Litiges";

// --- TRAITEMENT DU FORMULAIRE (LOGIQUE D'ENREGISTREMENT) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_incident'])) {
    $id_ticket_input = $_POST['id_ticket'];
    $incident_type = $_POST['incident_type'];
    $description = $_POST['description'];

    // On cherche l'ID du ticket à partir du numéro saisi
    $check = $pdo->prepare("SELECT id_ticket FROM tickets WHERE numero_ticket = ?");
    $check->execute([$id_ticket_input]);
    $ticket = $check->fetch();

    if ($ticket) {
        $sqlInsert = "INSERT INTO tracabilite_tickets (id_ticket, action, note_qualite, date_heure) 
                      VALUES (?, 'RECLAMATION', ?, NOW())";
        $pdo->prepare($sqlInsert)->execute([$ticket['id_ticket'], "[$incident_type] $description"]);
        $success = "L'incident a été enregistré avec succès.";
    } else {
        $error = "Numéro de ticket introuvable.";
    }
}

// --- RÉCUPÉRATION DES DONNÉES ---
$sql = "SELECT t.id_ticket, t.numero_ticket, c.nom_client, c.telephone,
               tr.note_qualite as incident, tr.date_heure as date_incident,
               t.montant_total
        FROM tickets t
        JOIN clients c ON t.id_client = c.id_client
        JOIN tracabilite_tickets tr ON t.id_ticket = tr.id_ticket
        WHERE tr.action = 'RECLAMATION' AND t.statut != 'annule'
        ORDER BY tr.date_heure DESC";
$reclamations = $pdo->query($sql)->fetchAll();

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <?php if(isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Centralisez et résolvez les incidents pour maintenir l'excellence du service</p>
        </div>
        <button type="button" class="btn btn-danger shadow-sm px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalNewReclamation">
            <i class="fas fa-plus me-2"></i> Enregistrer un Incident
        </button>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 h-100">
                <h6 class="fw-bold mb-4 text-uppercase small text-muted">Analyse de la Qualité</h6>
                <div class="text-center py-4">
                    <i class="fas fa-chart-line fa-3x text-light"></i>
                    <p class="text-muted mt-2">Graphique des tendances disponible après 10 saisies.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white h-100">
                <h6 class="text-warning fw-bold mb-3">IMPACT FINANCIER</h6>
                <h2 class="fw-bold m-0">145 000 <small class="fs-6">FCFA</small></h2>
                <hr class="opacity-25">
                <div class="d-flex justify-content-between small mb-2">
                    <span>Taux de réclamation</span>
                    <span class="badge bg-danger">2.4%</span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span>Temps moyen de résolution</span>
                    <span class="fw-bold text-success">48h</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="fw-bold mb-0">Dossiers de litiges en cours</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4">Ticket / Client</th>
                        <th>Nature du problème</th>
                        <th class="text-center">Date</th>
                        <th class="text-center">Urgence</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reclamations)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Aucun incident enregistré.</td></tr>
                    <?php endif; ?>
                    <?php foreach($reclamations as $r): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">#<?= htmlspecialchars($r['numero_ticket']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($r['nom_client']) ?></small>
                        </td>
                        <td>
                            <div class="small text-wrap" style="max-width: 300px;"><?= htmlspecialchars($r['incident']) ?></div>
                        </td>
                        <td class="text-center small"><?= date('d/m/Y', strtotime($r['date_incident'])) ?></td>
                        <td class="text-center">
                            <span class="badge bg-danger-soft text-danger">Haute</span>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></button>
                            <button class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNewReclamation" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="fw-bold m-0">Ouvrir un Dossier Litige</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action_incident" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Numéro de Ticket</label>
                        <input type="text" name="id_ticket" class="form-control" placeholder="ex: T-2026-001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Type d'incident</label>
                        <select name="incident_type" class="form-select">
                            <option>Détérioration textile</option>
                            <option>Perte d'article</option>
                            <option>Retard de livraison</option>
                            <option>Problème de repassage</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description détaillée</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Preuve (Optionnel)</label>
                        <input type="file" class="form-control">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger px-4">Enregistrer le litige</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
</style>

<?php require_once  '../../templates/footer.php'; ?>