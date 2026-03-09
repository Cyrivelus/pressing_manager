<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';


$titre = "Calendrier de Maintenance Préventive";

// --- TRAITEMENT DES FORMULAIRES ---
$message_action = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_panne'])) {
        // Logique pour signaler une panne
        $stmt = $pdo->prepare("INSERT INTO pannes (id_equipement, description, degre_urgence, date_signalement) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$_POST['id_equipement'], $_POST['description'], $_POST['urgence']]);
        $message_action = "Panne signalée avec succès.";
    } elseif (isset($_POST['action_ajout'])) {
        // Logique pour ajouter un équipement
        $stmt = $pdo->prepare("INSERT INTO equipements (nom_equipement, numero_serie, type_entretien, frequence_jours, derniere_maintenance, est_actif) VALUES (?, ?, ?, ?, ?, TRUE)");
        $stmt->execute([$_POST['nom'], $_POST['sn'], $_POST['type'], $_POST['frequence'], $_POST['derniere']]);
        $message_action = "Équipement ajouté au parc.";
    }
}

try {
    $sql = "SELECT e.*, 
            DATE_ADD(e.derniere_maintenance, INTERVAL e.frequence_jours DAY) as prochaine_date,
            DATEDIFF(DATE_ADD(e.derniere_maintenance, INTERVAL e.frequence_jours DAY), CURDATE()) as jours_restants
            FROM equipements e
            WHERE e.est_actif = TRUE
            ORDER BY prochaine_date ASC";
    $equipements = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    $equipements = [];
    $erreur_db = $e->getMessage();
}

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<style>
    .bg-success-soft { background-color: #e8f5e9; color: #2e7d32; }
    .bg-warning-soft { background-color: #fff3e0; color: #ef6c00; }
    .bg-danger-soft { background-color: #ffebee; color: #c62828; }
    .action-panel { display: none; animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="container-fluid py-5">
    <?php if ($message_action): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4"><?= $message_action ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><?= $titre ?></h2>
            <p class="text-muted">Gestion du parc machines et interventions</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="showPanel('panelPanne')" class="btn btn-outline-danger shadow-sm fw-bold">
                 Signaler Panne
            </button>
            <button onclick="showPanel('panelAjout')" class="btn btn-dark shadow-sm fw-bold">
                 Nouvel Équipement
            </button>
        </div>
    </div>

    

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small text-uppercase text-muted">
                            <tr>
                                <th class="ps-4">Machine</th>
                                <th>Maintenance</th>
                                <th class="text-center">Prochaine Échéance</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($equipements as $e): 
                                $jours = $e['jours_restants'];
                                $couleur = ($jours < 0) ? 'danger' : (($jours < 7) ? 'warning' : 'success');
                            ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold"><?= htmlspecialchars($e['nom_equipement']) ?></div>
                                    <code class="small text-muted"><?= $e['numero_serie'] ?></code>
                                </td>
                                <td><span class="small"><?= htmlspecialchars($e['type_entretien']) ?></span></td>
                                <td class="text-center">
                                    <div class="fw-bold"><?= date('d/m', strtotime($e['prochaine_date'])) ?></div>
                                    <span class="badge bg-<?= $couleur ?> rounded-pill">
                                        <?= ($jours < 0) ? abs($jours).'j de retard' : 'dans '.$jours.'j' ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light border" onclick="alert('Maintenance validée')"></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div id="panelPanne" class="action-panel card border-0 shadow-lg border-top border-4 border-danger p-4 mb-4" style="border-radius: 15px;">
                <h5 class="fw-bold mb-3">Signaler un incident</h5>
                <form method="POST">
                    <input type="hidden" name="action_panne" value="1">
                    <div class="mb-3">
                        <label class="small fw-bold">Machine concernée</label>
                        <select class="form-select" name="id_equipement" required>
                            <?php foreach($equipements as $e): ?>
                                <option value="<?= $e['id_equipement'] ?>"><?= $e['nom_equipement'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Urgence</label>
                        <select class="form-select text-danger fw-bold" name="urgence">
                            <option value="critique">Critique (Arrêt total)</option>
                            <option value="majeur">Majeur (Fonctionnement dégradé)</option>
                            <option value="mineur">Mineur</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Description du problème</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Symptômes constatés..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger w-100 fw-bold">ENVOYER L'ALERTE</button>
                    <button type="button" onclick="hidePanels()" class="btn btn-link w-100 text-muted small">Annuler</button>
                </form>
            </div>

            <div id="panelAjout" class="action-panel card border-0 shadow-lg border-top border-4 border-dark p-4 mb-4" style="border-radius: 15px;">
                <h5 class="fw-bold mb-3">Nouvelle Machine</h5>
                <form method="POST">
                    <input type="hidden" name="action_ajout" value="1">
                    <div class="mb-2">
                        <label class="small fw-bold">Nom</label>
                        <input type="text" name="nom" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="small fw-bold">S/N</label>
                        <input type="text" name="sn" class="form-control form-control-sm" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="small fw-bold">Fréq. (jours)</label>
                            <input type="number" name="frequence" class="form-control form-control-sm" value="30">
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold">Dernière Maintenance</label>
                            <input type="date" name="derniere" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Type d'entretien</label>
                        <input type="text" name="type" class="form-control form-control-sm" placeholder="ex: Vidange, Détartrage">
                    </div>
                    <button type="submit" class="btn btn-dark w-100 fw-bold">ENREGISTRER</button>
                    <button type="button" onclick="hidePanels()" class="btn btn-link w-100 text-muted small">Annuler</button>
                </form>
            </div>

            <div id="panelDefault" class="card border-0 shadow-sm p-4">
                <div class="text-center mb-4">
                    <div class="p-3 bg-light rounded-circle d-inline-block mb-3">
                       
                    </div>
                    <h6 class="fw-bold">Assistance Technique</h6>
                    <p class="small text-muted">Sélectionnez une action en haut pour interagir avec le parc.</p>
                </div>
                <hr>
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary-soft p-2 rounded me-3"></div>
                    <div><span class="fw-bold d-block small">Maintenance Interne</span><small>Ext. 402</small></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showPanel(id) {
    hidePanels();
    document.getElementById('panelDefault').style.display = 'none';
    document.getElementById(id).style.display = 'block';
}

function hidePanels() {
    document.querySelectorAll('.action-panel').forEach(p => p.style.display = 'none');
    document.getElementById('panelDefault').style.display = 'block';
}
</script>

<?php require_once  '../../templates/footer.php'; ?>