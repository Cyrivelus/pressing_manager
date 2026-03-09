<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Logistique & Collectes";

// 1. Traitement du formulaire (Planification)
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['planifier'])) {
    try {
        // CORRECTION : On utilise les colonnes réelles de votre table structure
        // Nous allons utiliser 'zone_geographique' pour stocker le nom de l'hôtel ou le type de mission
        // et 'date_tournee' au lieu de 'date_prevue'
        $sql = "INSERT INTO tournees (id_livreur, date_tournee, heure_depart, zone_geographique, statut) 
                VALUES (:id_livreur, :date_t, :heure_d, :mission, :statut)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id_livreur' => 1, // Par défaut, ou un ID de livreur existant
            'date_t'     => $_POST['date_prevue'],
            'heure_d'    => $_POST['heure_prevue'],
            'mission'    => $_POST['type'] . " - " . $_POST['nom_hotel_cache'], // On combine Type + Hôtel
            'statut'     => 'preparation' // Statut conforme à votre ENUM
        ]);
        $message = "<div class='alert alert-success border-0 shadow-sm'>✅ Mission enregistrée dans le journal des tournées !</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger border-0 shadow-sm'>❌ Erreur SQL : " . $e->getMessage() . "</div>";
    }
}

// 2. Récupération des hôtels pour le formulaire
$hotels = $pdo->query("SELECT id_client, nom_client FROM clients WHERE notes LIKE '%PARTENAIRE_HOTEL%'")->fetchAll();

// 3. Récupération du planning du jour
$aujourdhui = date('Y-m-d');
try {
    // On récupère les tournées prévues pour aujourd'hui
    $sql = "SELECT * FROM tournees 
            WHERE date_tournee = :aujourdhui 
            ORDER BY heure_depart ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['aujourdhui' => $aujourdhui]);
    $planning = $stmt->fetchAll();
} catch (PDOException $e) {
    $planning = [];
}

require_once  '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    .fw-black { font-weight: 900; }
    .bg-soft-success { background-color: #e8f5e9; color: #2e7d32; }
    .bg-soft-warning { background-color: #fff3e0; color: #ef6c00; }
    .bg-soft-secondary { background-color: #f1f3f5; color: #495057; }
    .card { border: none; transition: transform 0.2s; }
    .form-control, .form-select { border: 1px solid #e0e0e0 !important; padding: 12px; }
    .btn-mission { transition: all 0.3s; }
    .btn-mission:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
</style>

<br><br><br>
<div class="container-fluid py-5 px-4">
    
    <div class="mb-5 mt-4">
        <h2 class="fw-black text-dark m-0" style="letter-spacing: -1.5px;"><?= $titre ?></h2>
        <p class="text-muted">Tableau de bord logistique | Flux hôtelier du <?= date('d/m/Y') ?></p>
    </div>

    <?= $message ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold mb-4">Planifier un passage</h5>
                <form method="POST" onsubmit="updateHiddenFields()">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-2 text-uppercase">Hôtel Partenaire</label>
                        <select name="id_client" id="select_hotel" class="form-select rounded-3" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach($hotels as $h): ?>
                                <option value="<?= $h['id_client'] ?>" data-nom="<?= htmlspecialchars($h['nom_client']) ?>">
                                    <?= htmlspecialchars($h['nom_client']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="nom_hotel_cache" id="nom_hotel_cache">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-7">
                            <label class="small fw-bold text-muted mb-2 text-uppercase">Date</label>
                            <input type="date" name="date_prevue" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="small fw-bold text-muted mb-2 text-uppercase">Heure</label>
                            <input type="time" name="heure_prevue" class="form-control rounded-3" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="small fw-bold text-muted mb-2 text-uppercase">Type de Mission</label>
                        <div class="d-flex gap-2">
                            <input type="radio" class="btn-check" name="type" id="t1" value="COLLECTE" checked>
                            <label class="btn btn-outline-primary flex-fill rounded-pill py-2" for="t1">Ramassage</label>
                            
                            <input type="radio" class="btn-check" name="type" id="t2" value="LIVRAISON">
                            <label class="btn btn-outline-success flex-fill rounded-pill py-2" for="t2">Livraison</label>
                        </div>
                    </div>

                    <button type="submit" name="planifier" class="btn btn-dark w-100 rounded-pill py-3 fw-bold btn-mission">
                        Enregistrer la tournée
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold m-0">Journal des tournées du jour</h5>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="small text-muted text-uppercase">
                                <th class="ps-4">Heure Départ</th>
                                <th>Mission / Zone</th>
                                <th>Véhicule</th>
                                <th class="text-end pe-4">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($planning)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div class="opacity-25 mb-2" style="font-size: 2rem;">🚚</div>
                                        <p class="text-muted mb-0">Aucun départ de véhicule prévu aujourd'hui.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($planning as $p): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-primary">
                                        <?= date('H:i', strtotime($p['heure_depart'])) ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($p['zone_geographique']) ?></div>
                                        <div class="small text-muted"><?= $p['zone_livraison'] ?? 'Zone non définie' ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= $p['vehicule_immatriculation'] ?? 'Non assigné' ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php 
                                            $badgeClass = 'bg-soft-secondary';
                                            if($p['statut'] == 'en_cours') $badgeClass = 'bg-soft-warning';
                                            if($p['statut'] == 'terminee') $badgeClass = 'bg-soft-success';
                                        ?>
                                        <span class="badge rounded-pill <?= $badgeClass ?> px-3 py-2 fw-bold text-uppercase" style="font-size: 0.7rem;">
                                            <?= $p['statut'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-md-6">
                    <div class="p-4 bg-white rounded-4 shadow-sm border-start border-success border-4 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold d-block">VÉHICULE LOGISTIQUE A</small>
                            <span class="fw-bold">Pickup Toyota - 4423-CE</span>
                        </div>
                        <span class="badge bg-soft-success">DISPONIBLE</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-4 bg-white rounded-4 shadow-sm border-start border-warning border-4 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold d-block">VÉHICULE LOGISTIQUE B</small>
                            <span class="fw-bold">Fourgon Hiace - 1092-LT</span>
                        </div>
                        <span class="badge bg-soft-warning">EN TOURNÉE</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Petite fonction pour récupérer le nom de l'hôtel sélectionné et l'envoyer au serveur
function updateHiddenFields() {
    var select = document.getElementById('select_hotel');
    var nom = select.options[select.selectedIndex].getAttribute('data-nom');
    document.getElementById('nom_hotel_cache').value = nom;
}
</script>

<?php require_once '../../templates/footer.php'; ?>