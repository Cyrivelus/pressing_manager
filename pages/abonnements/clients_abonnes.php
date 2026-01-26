<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité et Permissions
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

// 2. Gestion des chemins et inclusions
$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = 'Liste des Abonnés';

// 3. Récupération des abonnés avec calcul de l'urgence (jours restants)
// Note : On joint la table clients pour avoir les infos de contact
$sql = "SELECT a.*, c.nom_client, c.prenom_client, c.telephone,
        DATEDIFF(a.date_fin, CURDATE()) as jours_restants
        FROM abonnements a
        JOIN clients c ON a.id_client = c.id_client
        WHERE a.statut = 'actif'
        ORDER BY jours_restants ASC";
$abonnes = $pdo->query($sql)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<style>
    .card-abonne { border-left: 5px solid #2ecc71; transition: 0.3s; }
    .expire-soon { border-left: 5px solid #e74c3c !important; background-color: #fdf2f2; }
    .search-bar { background: #fff; border-radius: 50px; padding: 10px 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .btn-action { border-radius: 20px; font-size: 0.85rem; }
    .table-container { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
</style>

<div class="container-fluid py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold text-dark m-0"><?= $titre ?></h2>
            <p class="text-muted small">Suivi des forfaits actifs et renouvellements</p>
        </div>
        <div class="d-flex gap-2">
            <a href="ajouter.php" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus"></i> Nouvel Abonnement
            </a>
            <a href="statistiques.php" class="btn btn-outline-secondary shadow-sm">
                <i class="fas fa-chart-line"></i> Voir Stats
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mx-auto">
            <div class="search-bar d-flex align-items-center">
                <i class="fas fa-search text-muted me-2"></i>
                <input type="text" id="filterInput" class="form-control border-0 shadow-none" placeholder="Rechercher un abonné par nom ou téléphone...">
            </div>
        </div>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="abonnesTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Abonné</th>
                        <th>Type de Forfait</th>
                        <th>Validité</th>
                        <th class="text-center">Jours Restants</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($abonnes)): ?>
                        <?php foreach ($abonnes as $a): 
                            $is_urgent = ($a['jours_restants'] <= 5);
                        ?>
                        <tr class="<?= $is_urgent ? 'table-danger' : '' ?>">
                            <td class="ps-4">
                                <div class="fw-bold"><?= htmlspecialchars($a['nom_client'].' '.$a['prenom_client']) ?></div>
                                <div class="small text-muted"><i class="fas fa-phone-alt fa-xs"></i> <?= $a['telephone'] ?></div>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark"><?= $a['type_abonnement'] ?></span>
                                <div class="small text-muted"><?= number_format($a['forfait_mensuel'], 0, ',', ' ') ?> FCFA</div>
                            </td>
                            <td>
                                <div class="small">Du : <b><?= date('d/m/Y', strtotime($a['date_debut'])) ?></b></div>
                                <div class="small">Au : <b><?= date('d/m/Y', strtotime($a['date_fin'])) ?></b></div>
                            </td>
                            <td class="text-center">
                                <?php if($a['jours_restants'] > 0): ?>
                                    <span class="badge <?= $is_urgent ? 'bg-danger' : 'bg-success' ?> rounded-pill">
                                        <?= $a['jours_restants'] ?> jours
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-dark rounded-pill">Expiré</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <a href="modifier.php?id=<?= $a['id_abonnement'] ?>" class="btn btn-outline-primary" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="sendReminder('<?= $a['telephone'] ?>', '<?= $a['nom_client'] ?>')" class="btn btn-outline-success" title="Relancer via SMS/WhatsApp">
                                        <i class="fab fa-whatsapp"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">Aucun abonnement actif trouvé.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<script>
// Filtrage dynamique du tableau
document.getElementById('filterInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#abonnesTable tbody tr');
    
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});

// Simulation d'envoi de relance
function sendReminder(phone, name) {
    const message = `Bonjour ${name}, votre abonnement chez Pressing BailCompta360 arrive à expiration bientôt. Pensez à le renouveler !`;
    const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
}
</script>

<?php require_once $root . '/templates/footer.php'; ?>