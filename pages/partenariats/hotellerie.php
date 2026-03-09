<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion du Secteur Hôtelier";

// Gestion de l'affichage du formulaire (Ajout ou Liste)
$view = $_GET['action'] ?? 'liste';

// 1. Récupération des hôtels partenaires
try {
    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM tickets WHERE id_client = c.id_client AND statut != 'recupere') as encours,
            (SELECT SUM(montant_total) FROM tickets WHERE id_client = c.id_client AND date_depot >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as volume_mensuel
            FROM clients c 
            WHERE c.notes LIKE '%PARTENAIRE_HOTEL%'";
    $hotels = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    $error = "Erreur de base de données : " . $e->getMessage();
}

require_once  '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    .card-dashboard { border: none; border-radius: 15px; transition: transform 0.2s; }
    .card-dashboard:hover { transform: translateY(-5px); }
    .bg-soft-success { background-color: rgba(40, 167, 69, 0.1); color: #28a745; }
    .bg-soft-primary { background-color: rgba(13, 110, 253, 0.1); color: #0d6efd; }
    .form-container { background: #fdfdfd; border: 2px dashed #dee2e6; border-radius: 20px; }
    .btn-rounded { border-radius: 50px; padding: 8px 25px; font-weight: 600; }
    .table-hover tbody tr:hover { background-color: rgba(0,0,0,0.02); }
</style>

<br><br><br>
<div class="container-fluid py-5 px-4">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5 gap-3">
        <div>
            <h2 class="fw-black text-dark m-0" style="letter-spacing: -1px;">🏨 <?= $titre ?></h2>
            <p class="text-muted mb-0">Pilotage de la blanchisserie industrielle et comptes corporatifs</p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($view !== 'nouveau'): ?>
                <a href="?action=nouveau" class="btn btn-primary btn-rounded shadow-sm">✚ Nouveau Partenaire</a>
            <?php else: ?>
                <a href="?action=liste" class="btn btn-outline-secondary btn-rounded">✕ Annuler</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($view === 'nouveau'): ?>
    <div class="form-container p-4 mb-5 shadow-sm">
        <h4 class="fw-bold mb-4 text-primary">Enregistrer un nouvel établissement</h4>
        <form action="traitement_hotel.php" method="POST" class="row g-4">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-uppercase text-muted">Nom de l'Hôtel</label>
                <input type="text" name="nom_hotel" class="form-control form-control-lg border-0 bg-light" placeholder="Ex: Hilton Yaoundé" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-uppercase text-muted">Responsable Blanchisserie</label>
                <input type="text" name="responsable" class="form-control form-control-lg border-0 bg-light" placeholder="Nom du contact" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-uppercase text-muted">Téléphone / Flotte</label>
                <input type="tel" name="telephone" class="form-control form-control-lg border-0 bg-light" placeholder="+237..." required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-uppercase text-muted">Type de Contrat</label>
                <select name="contrat" class="form-select form-select-lg border-0 bg-light">
                    <option value="Gold">Contrat Or (Premium)</option>
                    <option value="Silver">Contrat Argent</option>
                    <option value="Industrial">Blanchisserie Industrielle</option>
                </select>
            </div>
            <div class="col-md-9 d-flex align-items-end justify-content-end">
                <button type="submit" class="btn btn-primary btn-lg btn-rounded px-5">Valider le Partenariat</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card card-dashboard shadow-sm p-4 bg-primary text-white border-0">
                <small class="opacity-75 fw-bold uppercase">Part du CA Hôtels</small>
                <div class="d-flex align-items-baseline">
                    <h2 class="fw-bold m-0">35</h2>
                    <span class="ms-1">%</span>
                </div>
                <div class="mt-2 small opacity-75">Croissance : +5% ce mois</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard shadow-sm p-4 bg-white border-0">
                <small class="text-muted fw-bold uppercase">Linge en Traitement</small>
                <div class="d-flex align-items-baseline">
                    <h2 class="fw-bold m-0 text-dark">1 240</h2>
                    <span class="ms-1 text-muted">KG</span>
                </div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-info" style="width: 60%"></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard shadow-sm p-4 bg-white border-0">
                <small class="text-muted fw-bold uppercase">Encours Client</small>
                <h2 class="fw-bold m-0 text-danger">2 150 000</h2>
                <small class="text-muted">FCFA en attente</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard shadow-sm p-4 bg-dark text-white border-0 text-center">
                <small class="opacity-50 fw-bold uppercase">Indice Satisfaction</small>
                <h2 class="fw-bold m-0 text-warning">4.9<small class="fs-6 opacity-50">/5</small></h2>
                <div class="small text-success">Excellent</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-4 px-4 border-0">
            <h5 class="fw-bold m-0 text-dark">Hôtels sous contrat actif</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4 py-3">Établissement</th>
                        <th>Contact Décisionnel</th>
                        <th class="text-center">Tickets Ouverts</th>
                        <th class="text-center">CA Mensuel</th>
                        <th>Status Flux</th>
                        <th class="text-end pe-4">Gestion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($hotels)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">Aucun partenaire hôtelier enregistré.</td></tr>
                    <?php else: ?>
                        <?php foreach($hotels as $h): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($h['nom_client']) ?></div>
                                <span class="badge bg-soft-primary px-2 py-1 small">Contrat Or</span>
                            </td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($h['prenom_client']) ?></div>
                                <div class="small text-muted">📞 <?= $h['telephone'] ?></div>
                            </td>
                            <td class="text-center">
                                <span class="h6 fw-bold mb-0 text-primary"><?= $h['encours'] ?></span>
                            </td>
                            <td class="text-center fw-bold">
                                <?= number_format($h['volume_mensuel'], 0, ',', ' ') ?> <small>CFA</small>
                            </td>
                            <td>
                                <span class="badge bg-soft-success px-3">Opérationnel</span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group shadow-sm rounded">
                                    <button class="btn btn-sm btn-white border" title="Logistique">🚚</button>
                                    <button class="btn btn-sm btn-white border" title="Factures">🧾</button>
                                    <button class="btn btn-sm btn-white border" title="Tarifs">🏷️</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once  '../../templates/footer.php'; ?>