<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Contrats & Conventions Entreprises";

// Gestion de l'affichage des sections (Nouvelle convention / Facturation)
$view = $_GET['action'] ?? 'liste';

// 1. Récupération des entreprises partenaires
$sql = "SELECT c.*, 
        c.remise_speciale,
        (SELECT COUNT(*) FROM tickets WHERE id_client = c.id_client AND date_depot >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as nb_tickets_mois,
        (SELECT SUM(montant_total) FROM tickets WHERE id_client = c.id_client AND statut != 'recupere') as solde_en_cours
        FROM clients c 
        WHERE c.notes LIKE '%PARTENAIRE_ENTREPRISE%'";
$entreprises = $pdo->query($sql)->fetchAll();

require_once  '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    .btn-action { border-radius: 8px; font-weight: 600; padding: 10px 20px; transition: all 0.3s; }
    .card-stat { border: none; border-radius: 12px; transition: transform 0.2s; }
    .card-stat:hover { transform: translateY(-5px); }
    .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; }
    .form-section { background: #f8f9fa; border-radius: 15px; border: 1px solid #e9ecef; }
    .initials-avatar { width: 40px; height: 40px; background: #28a745; color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold; }
</style>

<div class="container-fluid py-5 px-4">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 mt-2 gap-3">
        <div>
            <h1 class="fw-black text-dark mb-1" style="letter-spacing: -1.5px;">B2B • <?= mb_convert_case($titre, MB_CASE_UPPER) ?></h1>
            <p class="text-muted mb-0">Gestion centralisée des comptes entreprises, uniformes et EPI</p>
        </div>
        <div class="d-flex gap-2">
            <a href="?action=nouvelle" class="btn btn-outline-success btn-action">✚ Nouvelle Convention</a>
            <a href="?action=facturation" class="btn btn-success btn-action shadow-sm">▤ Facturation Groupée</a>
        </div>
    </div>

    <?php if ($view === 'nouvelle'): ?>
    <div class="form-section p-4 mb-5 shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold m-0 text-success">Nouvelle Convention Partenaire</h4>
            <a href="?action=liste" class="text-muted text-decoration-none fw-bold">✕ Fermer</a>
        </div>
        <form action="traitement_convention.php" method="POST" class="row g-3">
            <div class="col-md-4">
                <label class="form-label small fw-bold">RAISON SOCIALE</label>
                <input type="text" name="raison_sociale" class="form-control border-0 p-3 shadow-sm" placeholder="Ex: SABC, MTN..." required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">REMISE ACCORDÉE (%)</label>
                <input type="number" name="remise" class="form-control border-0 p-3 shadow-sm" placeholder="Ex: 15" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">TYPE DE FACTURATION</label>
                <select name="billing_type" class="form-select border-0 p-3 shadow-sm">
                    <option value="monthly">Mensuelle Récapitulative</option>
                    <option value="prepaid">Compte Prépayé Entreprise</option>
                </select>
            </div>
            <div class="col-12 text-end mt-4">
                <button type="submit" class="btn btn-success px-5 py-3 fw-bold rounded-3">ENREGISTRER LE PARTENARIAT</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($view === 'facturation'): ?>
    <div class="form-section p-4 mb-5 shadow-sm border-primary">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold m-0 text-primary">Générer Facturation Mensuelle</h4>
            <a href="?action=liste" class="text-muted text-decoration-none fw-bold">✕ Fermer</a>
        </div>
        <form action="generer_facture_b2b.php" method="POST" class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold">SÉLECTIONNER L'ENTREPRISE</label>
                <select name="id_client" class="form-select border-0 p-3 shadow-sm">
                    <?php foreach($entreprises as $e): ?>
                        <option value="<?= $e['id_client'] ?>"><?= htmlspecialchars($e['nom_client']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">PÉRIODE DE FACTURATION</label>
                <input type="month" name="periode" class="form-control border-0 p-3 shadow-sm" value="<?= date('Y-m') ?>">
            </div>
            <div class="col-12 text-end mt-4">
                <button type="submit" class="btn btn-primary px-5 py-3 fw-bold rounded-3">GÉNÉRER LE RELEVÉ DÉTAILLÉ</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card card-stat shadow-sm p-4 bg-white border-start border-success border-5">
                <small class="text-muted fw-bold d-block mb-1">QUALITÉ DE SERVICE (SLA)</small>
                <h2 class="fw-bold m-0">24h <span class="fs-6 text-success fw-normal">Maximum</span></h2>
                <div class="progress mt-3" style="height: 6px;">
                    <div class="progress-bar bg-success" style="width: 95%"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat shadow-sm p-4 bg-white border-start border-primary border-5">
                <small class="text-muted fw-bold d-block mb-1">ENCOURS GLOBAL B2B</small>
                <h2 class="fw-bold m-0 text-primary">4 850 000 <small class="fs-6 fw-normal text-muted">FCFA</small></h2>
                <p class="small text-muted mt-2 mb-0">📅 Échéance moyenne : 15 jours</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat shadow-sm p-4 bg-dark text-white border-0">
                <small class="opacity-75 fw-bold d-block mb-1">LOGISTIQUE / RAMASSAGE</small>
                <h4 class="fw-bold mb-1 text-warning">Zone Magzi</h4>
                <p class="small mb-0">🚚 Prochain passage : Demain 10h00</p>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-4 px-4 border-0">
            <h5 class="fw-bold m-0">Portefeuille Entreprises Actif</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase fw-bold text-muted">
                    <tr>
                        <th class="ps-4 py-3">Raison Sociale</th>
                        <th>Statut Contrat</th>
                        <th class="text-center">Volume (30j)</th>
                        <th class="text-center">Remise</th>
                        <th class="text-end">Solde Encours</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($entreprises)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">Aucun contrat entreprise actif.</td></tr>
                    <?php else: ?>
                        <?php foreach($entreprises as $e): ?>
                        <tr>
                            <td class="ps-4 py-4">
                                <div class="d-flex align-items-center">
                                    <div class="initials-avatar me-3">
                                        <?= strtoupper(substr($e['nom_client'], 0, 2)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($e['nom_client']) ?></div>
                                        <div class="small text-muted"><?= $e['telephone'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge bg-light border border-success text-success">CONVENTION ANNUELLE</span>
                            </td>
                            <td class="text-center fw-bold text-dark"><?= $e['nb_tickets_mois'] ?> tickets</td>
                            <td class="text-center">
                                <span class="fw-bold text-danger">-<?= $e['remise_speciale'] ?>%</span>
                            </td>
                            <td class="text-end fw-bold">
                                <?= number_format($e['solde_en_cours'], 0, ',', ' ') ?> <small class="text-muted">CFA</small>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group shadow-sm rounded-3 overflow-hidden">
                                    <button class="btn btn-sm btn-white border-end" title="Employés">👥</button>
                                    <button class="btn btn-sm btn-white border-end" title="Détails">📁</button>
                                    <button class="btn btn-sm btn-dark" title="Relevé">🖨️</button>
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