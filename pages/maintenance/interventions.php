<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Suivi des Interventions Techniques";

// --- TRAITEMENT DU FORMULAIRE (LOGIQUE D'INSERTION) ---
$success_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer_intervention'])) {
    try {
        $sql_ins = "INSERT INTO interventions (id_equipement, description_panne, type_panne, piece_remplacee, technicien_nom, cout_total, date_intervention, statut, id_utilisateur) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), 'termine', ?)";
        $stmt = $pdo->prepare($sql_ins);
        // On suppose que l'ID utilisateur est en session
        $user_id = $_SESSION['user_id'] ?? 1; 
        $stmt->execute([
            $_POST['id_equipement'],
            $_POST['description'],
            $_POST['type_panne'],
            $_POST['piece'],
            $_POST['prestataire'],
            $_POST['montant'],
            $user_id
        ]);
        $success_msg = "L'intervention a été enregistrée avec succès !";
    } catch (Exception $e) {
        $error_db = "Erreur lors de l'enregistrement : " . $e->getMessage();
    }
}

// 1. Récupération des équipements pour le select
$equipements_list = $pdo->query("SELECT id_equipement, nom_equipement FROM equipements WHERE est_actif = 1")->fetchAll();

// 2. Récupération des interventions
$sql = "SELECT i.*, e.nom_equipement 
        FROM interventions i
        JOIN equipements e ON i.id_equipement = e.id_equipement
        ORDER BY i.date_intervention DESC";
$interventions = $pdo->query($sql)->fetchAll();

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<style>
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); color: #997404; }
    #formContainer { display: none; animation: slideDown 0.4s ease-out; }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .card { border-radius: 12px; }
</style>
<br> <br> <br>
<div class="container-fluid py-5">
    
    <?php if ($success_msg): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4"><?= $success_msg ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><?= $titre ?></h2>
            <p class="text-muted small text-uppercase tracking-wider">Maintenance préventive & curative</p>
        </div>
        <button id="toggleFormBtn" class="btn btn-primary shadow-sm fw-bold px-4">
           Nouvelle Intervention
        </button>
    </div>

    <div id="formContainer" class="mb-5">
        <div class="card border-0 shadow-lg border-start border-4 border-primary">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0">Rapport d'Intervention Technique</h5>
                    <button type="button" class="btn-close" id="closeFormBtn"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="enregistrer_intervention" value="1">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">Équipement</label>
                            <select class="form-select shadow-sm" name="id_equipement" required>
                                <option value="">Choisir la machine...</option>
                                <?php foreach($equipements_list as $eq): ?>
                                    <option value="<?= $eq['id_equipement'] ?>"><?= htmlspecialchars($eq['nom_equipement'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">Type de Panne</label>
                            <select class="form-select shadow-sm" name="type_panne">
                                <option>Électrique</option>
                                <option>Mécanique</option>
                                <option>Fuite / Hydraulique</option>
                                <option>Maintenance Routine</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">Prestataire / Technicien</label>
                            <input type="text" class="form-control shadow-sm" name="prestataire" placeholder="Nom du technicien" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Description détaillée</label>
                        <textarea class="form-control shadow-sm" name="description" rows="2" placeholder="Expliquez la panne et la solution..." required></textarea>
                    </div>
                    <div class="row align-items-end">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">Pièces remplacées</label>
                            <input type="text" class="form-control shadow-sm" name="piece" placeholder="ex: Courroie X12, Filtre">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">Coût de l'intervention (FCFA)</label>
                            <div class="input-group shadow-sm">
                                <input type="number" class="form-control" name="montant" value="0" required>
                                <span class="input-group-text bg-light">FCFA</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <button type="submit" class="btn btn-dark w-100 py-2 fw-bold shadow">
                                Enregistrer le Rapport
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white border-start border-4 border-primary">
                <small class="text-muted fw-bold text-uppercase">Dépenses (Mois)</small>
                <h3 class="fw-bold m-0 text-primary">145 000 <small>FCFA</small></h3>
                <div class="progress mt-2" style="height: 5px;">
                    <div class="progress-bar bg-primary" style="width: 70%"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white border-start border-4 border-success">
                <small class="text-muted fw-bold text-uppercase">Taux de Disponibilité</small>
                <h3 class="fw-bold m-0 text-success">98.2 %</h3>
                <small class="text-muted">Uptime total du parc machine</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white">
                <small class="opacity-75 fw-bold text-uppercase">Statut Critique</small>
                <h3 class="fw-bold m-0 text-warning">02</h3>
                <small>Interventions urgentes à prévoir</small>
            </div>
        </div>
    </div>

    

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold mb-0">Historique Récent</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4">Date & Réf</th>
                        <th>Équipement</th>
                        <th>Détails de l'intervention</th>
                        <th>Technicien</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end pe-4">Coût</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($interventions as $i): ?>
                    <tr>
                        <td class="ps-4 py-3">
                            <span class="fw-bold"><?= date('d/m/y', strtotime($i['date_intervention'])) ?></span><br>
                            <small class="text-muted">#INT-<?= $i['id_intervention'] ?></small>
                        </td>
                        <td>
                            <div class="fw-bold text-primary"><?= htmlspecialchars($i['nom_equipement']) ?></div>
                            <span class="badge bg-light text-dark border small"><?= htmlspecialchars($i['type_panne'] ?? 'Inconnu') ?></span>
                        </td>
                        <td>
                            <div class="small text-truncate" style="max-width: 250px;"><?= htmlspecialchars($i['description_panne']) ?></div>
                            <?php if($i['piece_remplacee']): ?>
                                <span class="text-muted x-small italic"><?= $i['piece_remplacee'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="small fw-bold"><?= htmlspecialchars($i['technicien_nom'] ?? 'Interne') ?></td>
                        <td class="text-center">
                            <span class="badge bg-success-soft text-success px-3 py-2 rounded-pill small">
                                 Résolu
                            </span>
                        </td>
                        <td class="text-end pe-4 fw-bold">
                            <?= number_format($i['cout_total'], 0, ',', ' ') ?> <small>FCFA</small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('toggleFormBtn');
    const container = document.getElementById('formContainer');
    const closeBtn = document.getElementById('closeFormBtn');

    btn.addEventListener('click', function() {
        container.style.display = (container.style.display === 'none' || container.style.display === '') ? 'block' : 'none';
        if(container.style.display === 'block') {
            container.scrollIntoView({ behavior: 'smooth' });
        }
    });

    closeBtn.addEventListener('click', function() {
        container.style.display = 'none';
    });
});
</script>

<?php require_once '../../templates/footer.php'; ?>