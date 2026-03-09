<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion & Recyclage des Déchets";
$message_status = "";

// 2. Traitement dynamique de l'enregistrement (L'action qui ne fonctionnait pas)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer_collecte'])) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO registre_dechets (date_collecte, type_dechet, poids_volume, unite, prestataire, num_bordereau)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['date_collecte'],
            $_POST['type_dechet'],
            $_POST['quantite'],
            $_POST['unite'],
            $_POST['prestataire'],
            $_POST['num_bordereau']
        ]);
        $message_status = "success";
    } catch (PDOException $e) {
        $message_status = "error";
        $db_error = $e->getMessage();
    }
}

try {
    $annee = date('Y');
    // Statistiques par type
    $stats_dechets = $pdo->prepare("
        SELECT type_dechet, SUM(poids_volume) as total, unite
        FROM registre_dechets 
        WHERE YEAR(date_collecte) = ? 
        GROUP BY type_dechet
    ");
    $stats_dechets->execute([$annee]);
    $bilan = $stats_dechets->fetchAll();

    // Récupération du registre complet
    $registre = $pdo->query("SELECT * FROM registre_dechets ORDER BY date_collecte DESC LIMIT 15")->fetchAll();

} catch (PDOException $e) {
    $db_error = "Erreur : " . $e->getMessage();
    $bilan = [];
    $registre = [];
}

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
        <div>
            <h2 class="fw-bold m-0 text-success">[ECO] <?= $titre ?></h2>
            <p class="text-muted">Conformité environnementale et suivi du tri sélectif</p>
        </div>
        <a href="#form-section" class="btn btn-primary shadow-sm rounded-pill px-4">
            + Enregistrer un enlèvement
        </a>
    </div>

    <?php if ($message_status === 'success'): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4"><b>[OK]</b> Enlèvement enregistré avec succès dans le registre.</div>
    <?php endif; ?>

    <?php if (isset($db_error)): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            <b>[ERREUR BDD] :</b> <?= $db_error ?>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <?php 
        $poids_plastique = 0; $vol_boues = 0;
        foreach($bilan as $b) {
            if(stripos($b['type_dechet'], 'plastique') !== false) $poids_plastique += $b['total'];
            if(stripos($b['type_dechet'], 'solvant') !== false || stripos($b['type_dechet'], 'boues') !== false) $vol_boues += $b['total'];
        }
        ?>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 border-start border-4 border-primary">
                <small class="text-muted fw-bold">PLASTIQUES RECYCLÉS</small>
                <h2 class="fw-bold m-0"><?= number_format($poids_plastique, 1) ?> <small class="fs-6">kg</small></h2>
                <div class="progress mt-2" style="height: 5px;"><div class="progress-bar bg-primary" style="width: 65%"></div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 border-start border-4 border-danger">
                <small class="text-muted fw-bold">BOUES DE SOLVANTS (DANGEREUX)</small>
                <h2 class="fw-bold m-0 text-danger"><?= number_format($vol_boues, 1) ?> <small class="fs-6">L</small></h2>
                <small class="text-danger small fw-bold text-uppercase">[!] Enlèvement pro obligatoire</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-success text-white">
                <small class="opacity-75 fw-bold text-uppercase">Taux de valorisation</small>
                <h2 class="fw-bold m-0">72%</h2>
                <small>[FEUILLE] Performance Éco-responsable</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4 order-lg-2" id="form-section">
            <div class="card border-0 shadow-lg rounded-4 p-4 sticky-top" style="top: 100px;">
                <h5 class="fw-bold text-primary mb-4">[SAISIE] Nouvel Enlèvement</h5>
                <form action="" method="POST">
                    <input type="hidden" name="enregistrer_collecte" value="1">
                    
                    <div class="mb-3">
                        <label class="small fw-bold">Date de collecte</label>
                        <input type="date" name="date_collecte" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Type de déchet</label>
                        <select name="type_dechet" id="type_dechet" class="form-select" required onchange="updateUnit()">
                            <option value="Plastiques / Emballages" data-unit="kg">Plastiques / Emballages</option>
                            <option value="Boues de solvants" data-unit="L">Boues de solvants (Dangereux)</option>
                            <option value="Eaux de lavage" data-unit="L">Eaux de lavage</option>
                            <option value="Papiers / Cartons" data-unit="kg">Papiers / Cartons</option>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-8">
                            <label class="small fw-bold">Quantité</label>
                            <input type="number" step="0.1" name="quantite" class="form-control" required placeholder="0.0">
                        </div>
                        <div class="col-4">
                            <label class="small fw-bold">Unité</label>
                            <input type="text" name="unite" id="unite_field" class="form-control bg-light" value="kg" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Prestataire</label>
                        <input type="text" name="prestataire" class="form-control" required placeholder="Ex: Eco-Clean Service">
                    </div>

                    <div class="mb-4">
                        <label class="small fw-bold">N° Bordereau (BSDD)</label>
                        <input type="text" name="num_bordereau" class="form-control" placeholder="Obligatoire pour solvants">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow">Valider l'enregistrement</button>
                </form>
            </div>
        </div>

        <div class="col-lg-8 order-lg-1">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Registre Chronologique</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Déchet</th>
                                <th>Quantité</th>
                                <th>Prestataire</th>
                                <th class="text-end pe-4">Référence</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($registre)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">Aucun enregistrement trouvé.</td></tr>
                            <?php else: ?>
                                <?php foreach($registre as $r): ?>
                                <tr>
                                    <td class="ps-4 small fw-bold"><?= date('d/m/Y', strtotime($r['date_collecte'])) ?></td>
                                    <td>
                                        <span class="badge <?= stripos($r['type_dechet'], 'boues') !== false ? 'bg-danger-soft text-danger' : 'bg-success-soft text-success' ?> rounded-pill">
                                            <?= htmlspecialchars($r['type_dechet']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold"><?= $r['poids_volume'] ?> <small><?= $r['unite'] ?></small></td>
                                    <td class="small"><?= htmlspecialchars($r['prestataire']) ?></td>
                                    <td class="text-end pe-4">
                                        <span class="text-muted small fw-bold"><?= $r['num_bordereau'] ?: '[SANS NUMÉRO]' ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Intelligence du formulaire : met à jour l'unité selon le type de déchet choisi
function updateUnit() {
    const select = document.getElementById('type_dechet');
    const unitField = document.getElementById('unite_field');
    const selectedOption = select.options[select.selectedIndex];
    unitField.value = selectedOption.getAttribute('data-unit');
}
</script>

<style>
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
    .form-control, .form-select { border-radius: 8px; border: 1px solid #e0e0e0; padding: 10px; }
    .form-control:focus { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15); border-color: #0d6efd; }
    #form-section { scroll-margin-top: 100px; }
</style>

<?php require_once '../../templates/footer.php'; ?>