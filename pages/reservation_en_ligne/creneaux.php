<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';


$titre = "Configuration des Créneaux Horaires";

// --- TRAITEMENT PHP : ACTIONS ---
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Ajout de plage
    if (isset($_POST['action_ajouter_plage'])) {
        $nouvelle_plage = $_POST['heure_debut'] . " - " . $_POST['heure_fin'];
        $message = "<div class='alert alert-success border-0 shadow-sm fw-bold'>Créneau $nouvelle_plage configuré.</div>";
    }
    // 2. Mise à jour (Editer)
    if (isset($_POST['action_modifier_plage'])) {
        $message = "<div class='alert alert-info border-0 shadow-sm fw-bold'>Créneau mis à jour avec succès.</div>";
    }
    // 3. Blocage
    if (isset($_POST['action_bloquer_plage'])) {
        $message = "<div class='alert alert-warning border-0 shadow-sm fw-bold'>Le créneau a été marqué comme indisponible.</div>";
    }
}

// Simulation capacité et données
$capacite_max = 5;
$sql = "SELECT creneau_horaire, COUNT(*) as total FROM reservations_online WHERE DATE(date_reservation) = CURDATE() GROUP BY creneau_horaire";
$occupations = $pdo->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    .bg-success-soft { background-color: #e8f5e9; color: #2e7d32; }
    .bg-danger-soft { background-color: #ffebee; color: #c62828; }
    #panelAjout, .edit-row { display: none; animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
    .table-hover tbody tr:hover { background-color: rgba(0,0,0,.02); }
</style>

<div class="container-fluid py-5">
    
    <?= $message ?>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
        <div>
            <h2 class="fw-bold m-0 text-dark"><?= $titre ?></h2>
            <p class="text-muted">Gérez les flux logistiques en temps réel</p>
        </div>
        <button class="btn btn-primary fw-bold shadow-sm px-4" onclick="togglePanel('panelAjout')">
            [+] NOUVELLE PLAGE
        </button>
    </div>

    <div id="panelAjout" class="card border-0 shadow-sm mb-4 bg-primary text-white">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Ajouter un nouveau créneau</h5>
            <form method="POST" class="row g-3 align-items-end">
                <input type="hidden" name="action_ajouter_plage" value="1">
                <div class="col-md-4">
                    <label class="small fw-bold">Heure de début</label>
                    <input type="time" name="heure_debut" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold">Heure de fin</label>
                    <input type="time" name="heure_fin" class="form-control" required>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" onclick="togglePanel('panelAjout')" class="btn btn-outline-light fw-bold me-2">ANNULER</button>
                    <button type="submit" class="btn btn-dark fw-bold px-4">VALIDER</button>
                </div>
            </form>
        </div>
    </div>

    

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th class="ps-4 py-3">PLAGE HORAIRE</th>
                                <th class="text-center">OCCUPATION</th>
                                <th class="text-center">STATUT</th>
                                <th class="text-end pe-4">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $creneaux = ["08:00 - 10:00", "10:00 - 12:00", "14:00 - 16:00", "16:00 - 18:00"];
                            foreach($creneaux as $index => $c): 
                                $occupe = $occupations[$c] ?? 0;
                                $pourcentage = min(($occupe / $capacite_max) * 100, 100);
                                $row_id = "row-" . $index;
                            ?>
                            <tr class="border-bottom">
                                <td class="ps-4 fw-bold text-primary"><?= $c ?></td>
                                <td class="text-center" style="width: 200px;">
                                    <div class="progress mb-1" style="height: 6px;">
                                        <div class="progress-bar <?= $pourcentage >= 80 ? 'bg-danger' : 'bg-success' ?>" style="width: <?= $pourcentage ?>%"></div>
                                    </div>
                                    <small class="fw-bold text-muted"><?= $occupe ?> / <?= $capacite_max ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $occupe >= $capacite_max ? 'bg-danger-soft' : 'bg-success-soft' ?> px-3">
                                        <?= $occupe >= $capacite_max ? 'COMPLET' : 'OUVERT' ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group shadow-sm">
                                        <button class="btn btn-sm btn-outline-danger fw-bold" onclick="toggleEdit('block-<?= $row_id ?>')">BLOQUER</button>
                                        <button class="btn btn-sm btn-light border fw-bold" onclick="toggleEdit('edit-<?= $row_id ?>')">EDITER</button>
                                    </div>
                                </td>
                            </tr>

                            <tr id="edit-<?= $row_id ?>" class="edit-row bg-light">
                                <td colspan="4" class="p-3">
                                    <form method="POST" class="row g-2 align-items-end">
                                        <input type="hidden" name="action_modifier_plage" value="1">
                                        <div class="col-md-4 small fw-bold">Modifier l'horaire :</div>
                                        <div class="col-md-3">
                                            <input type="text" class="form-control form-control-sm" value="<?= $c ?>">
                                        </div>
                                        <div class="col-md-5 text-end">
                                            <button type="submit" class="btn btn-sm btn-success fw-bold">SAUVEGARDER</button>
                                            <button type="button" class="btn btn-sm btn-secondary fw-bold" onclick="toggleEdit('edit-<?= $row_id ?>')">FERMER</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>

                            <tr id="block-<?= $row_id ?>" class="edit-row bg-light">
                                <td colspan="4" class="p-3 text-center border-start border-danger">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action_bloquer_plage" value="1">
                                        <span class="fw-bold text-danger me-3">Voulez-vous suspendre les réservations pour <?= $c ?> ?</span>
                                        <button type="submit" class="btn btn-sm btn-danger fw-bold">OUI, BLOQUER</button>
                                        <button type="button" class="btn btn-sm btn-secondary fw-bold" onclick="toggleEdit('block-<?= $row_id ?>')">ANNULER</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Paramètres Généraux</h6>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Capacité par créneau</label>
                        <input type="number" class="form-control fw-bold text-center" value="<?= $capacite_max ?>">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="checkDim" checked>
                        <br><label class="form-check-label small fw-bold" for="checkDim">Activer le Dimanche</label>
                    </div>
                    <button class="btn btn-dark w-100 fw-bold">METTRE À JOUR TOUT</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Pour le panneau d'ajout principal
    function togglePanel(id) {
        const el = document.getElementById(id);
        el.style.display = (el.style.display === 'block') ? 'none' : 'block';
    }

    // Pour les lignes d'édition/blocage spécifiques
    function toggleEdit(id) {
        const row = document.getElementById(id);
        const allEditRows = document.querySelectorAll('.edit-row');
        
        // Fermer les autres formulaires ouverts pour plus de clarté
        allEditRows.forEach(r => {
            if(r.id !== id) r.style.display = 'none';
        });

        row.style.display = (row.style.display === 'table-row') ? 'none' : 'table-row';
    }
</script>

<?php require_once '../../templates/footer.php'; ?>