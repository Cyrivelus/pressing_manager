<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Campagnes Emailing & Fidélisation";

// 1. Statistiques rapides
$total_clients = $pdo->query("SELECT COUNT(*) FROM clients WHERE email IS NOT NULL AND email != ''")->fetchColumn();
$campagnes_actives = $pdo->query("SELECT COUNT(*) FROM notifications_clients WHERE type_notification = 'Marketing' AND date_envoi >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-primary"><i class="fas fa-paper-plane me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Engagez vos clients et boostez votre chiffre d'affaires</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNouvelleCampagne">
            <i class="fas fa-plus"></i> Créer une campagne
        </button>
    </div>

    

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-center">
                <small class="text-muted fw-bold d-block mb-2">AUDIENCE ÉLIGIBLE</small>
                <h2 class="fw-bold m-0"><?= $total_clients ?></h2>
                <small class="text-success">Emails vérifiés</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-center">
                <small class="text-muted fw-bold d-block mb-2">TAUX D'OUVERTURE MOY.</small>
                <h2 class="fw-bold m-0 text-info">24.8 %</h2>
                <small class="text-muted">Standard secteur : 18%</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-center">
                <small class="text-muted fw-bold d-block mb-2">CONVERSION (DÉPÔTS)</small>
                <h2 class="fw-bold m-0 text-success">8.5 %</h2>
                <small class="text-muted">Post-campagne</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-center">
                <small class="text-muted fw-bold d-block mb-2">DÉSINSCRIPTIONS</small>
                <h2 class="fw-bold m-0 text-danger">0.2 %</h2>
                <small class="text-muted">Fidélité préservée</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0">Campagnes récentes</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th class="ps-4">Objet de l'email</th>
                                <th>Cible</th>
                                <th>Statut</th>
                                <th class="text-center">Ouvertures</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold">Promotion "Spécial Couettes"</span><br>
                                    <small class="text-muted">Envoyé le 15/01/2026</small>
                                </td>
                                <td><span class="badge bg-light text-dark">Tous les clients</span></td>
                                <td><span class="badge bg-success">Terminé</span></td>
                                <td class="text-center fw-bold">142</td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-chart-bar"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold">Vous nous manquez !</span><br>
                                    <small class="text-muted">Automatique (Inactifs > 60j)</small>
                                </td>
                                <td><span class="badge bg-light text-dark">Segment Inactifs</span></td>
                                <td><span class="badge bg-info animate__animated animate__pulse animate__infinite">En cours</span></td>
                                <td class="text-center fw-bold">28</td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-pause"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 h-100 bg-light bg-opacity-50">
                <h6 class="fw-bold mb-4">Segments Suggérés</h6>
                <div class="d-grid gap-3">
                    <div class="p-3 bg-white rounded shadow-sm d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold d-block">Clients VIP</span>
                            <small class="text-muted">Top 10% des dépensiers</small>
                        </div>
                        <span class="badge bg-primary rounded-pill">42</span>
                    </div>
                    <div class="p-3 bg-white rounded shadow-sm d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold d-block">Nouveaux (30j)</span>
                            <small class="text-muted">Offre de bienvenue</small>
                        </div>
                        <span class="badge bg-primary rounded-pill">18</span>
                    </div>
                    <div class="p-3 bg-white rounded shadow-sm d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold d-block">Spécial Mariage</span>
                            <small class="text-muted">Dépôts robes/costumes</small>
                        </div>
                        <span class="badge bg-primary rounded-pill">12</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNouvelleCampagne" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header">
                <h5 class="fw-bold">Concevoir une Campagne</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Objet de l'email</label>
                    <input type="text" class="form-control" placeholder="ex: -20% sur votre prochain dépôt !">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cible (Segment)</label>
                        <select class="form-select">
                            <option>Tous les clients</option>
                            <option>Clients VIP</option>
                            <option>Clients inactifs (> 2 mois)</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Modèle (Template)</label>
                        <select class="form-select">
                            <option>Annonce Promotion</option>
                            <option>Rappel de Retrait</option>
                            <option>Newsletter Mensuelle</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Corps du message (Aperçu)</label>
                    <div class="border rounded p-3 bg-light" style="min-height: 150px;">
                        [L'éditeur visuel (type TinyMCE) se chargerait ici]
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Lancer l'envoi massif</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>