<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Prise de Rendez-vous / Collecte";

// 1. Récupération des rendez-vous à venir (Aujourd'hui et futur)
$sql = "SELECT r.*, c.nom_client, c.telephone, c.adresse
        FROM reservations_online r
        JOIN clients c ON r.id_client = c.id_client
        WHERE r.date_reservation >= CURDATE()
        ORDER BY r.date_reservation ASC, r.creneau_horaire ASC";
$rdv_futurs = $pdo->query($sql)->fetchAll();

require_once  '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-indigo"><i class="fas fa-calendar-check me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Planification des collectes à domicile et dépôts prioritaires</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-indigo" data-bs-toggle="modal" data-bs-target="#modalCalendrier">
                <i class="fas fa-th-large"></i> Vue Calendrier
            </button>
            <button class="btn btn-indigo text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNouveauRDV">
                <i class="fas fa-plus"></i> Nouveau RDV
            </button>
        </div>
    </div>

    

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <small class="text-muted fw-bold">RDV AUJOURD'HUI</small>
                <h3 class="fw-bold m-0 text-indigo">12</h3>
                <small class="text-success"><i class="fas fa-truck"></i> 8 collectes, 4 livraisons</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <small class="text-muted fw-bold">TEMPS DE RÉPONSE MOYEN</small>
                <h3 class="fw-bold m-0">14 <small class="fs-6">min</small></h3>
                <small class="text-muted">Validation des demandes</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-1">PROCHAINE COLLECTE</h6>
                        <p class="small opacity-75 mb-0">Zone : Bastos | Client : M. Atangana</p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-warning text-dark px-3 py-2 h5 mb-0">14:30</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h6 class="fw-bold mb-0">Agenda des réservations confirmées</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4">Date & Créneau</th>
                        <th>Client / Adresse</th>
                        <th class="text-center">Statut</th>
                        <th>Contact</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rdv_futurs as $r): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold"><?= date('d/m/Y', strtotime($r['date_reservation'])) ?></div>
                            <span class="badge bg-indigo-soft text-indigo fw-normal"><?= $r['creneau_horaire'] ?></span>
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($r['nom_client']) ?></div>
                            <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;">
                                <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($r['adresse']) ?>
                            </small>
                        </td>
                        <td class="text-center">
                            <?php 
                            $status_class = ($r['statut'] == 'confirme') ? 'bg-success' : 'bg-warning text-dark';
                            ?>
                            <span class="badge <?= $status_class ?> rounded-pill px-3"><?= ucfirst($r['statut']) ?></span>
                        </td>
                        <td>
                            <a href="tel:<?= $r['telephone'] ?>" class="btn btn-sm btn-light border">
                                <i class="fas fa-phone-alt text-success"></i>
                            </a>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-danger" title="Annuler le RDV"><i class="fas fa-times"></i></button>
                            <button class="btn btn-sm btn-dark" title="Convertir en Ticket"><i class="fas fa-arrow-right"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNouveauRDV" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <div class="modal-header bg-indigo text-white">
                <h5 class="fw-bold m-0">Planifier une Collecte</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Rechercher un Client</label>
                    <input type="text" class="form-control" placeholder="Nom ou Téléphone...">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Créneau</label>
                        <select class="form-select">
                            <option>08:00 - 10:00</option>
                            <option>10:00 - 12:00</option>
                            <option>14:00 - 16:00</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Note / Consignes de collecte</label>
                    <textarea class="form-control" rows="2" placeholder="ex: Sonner chez le gardien..."></textarea>
                </div>
                <button type="submit" class="btn btn-indigo text-white w-100 fw-bold">Confirmer le Rendez-vous</button>
            </form>
        </div>
    </div>
</div>

<style>
    .text-indigo { color: #4e73df; }
    .bg-indigo { background-color: #4e73df; }
    .btn-indigo { background-color: #4e73df; border-color: #4e73df; }
    .bg-indigo-soft { background-color: rgba(78, 115, 223, 0.1); }
</style>

<?php require_once '../../templates/footer.php'; ?>