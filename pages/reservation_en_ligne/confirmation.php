<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Validation des Réservations Web";

// 1. Récupération des demandes en attente de confirmation
$sql = "SELECT r.*, c.nom_client, c.email, c.telephone
        FROM reservations_online r
        JOIN clients c ON r.id_client = c.id_client
        WHERE r.statut = 'en_attente'
        ORDER BY r.date_reservation ASC";
$demandes = $pdo->query($sql)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-primary"><i class="fas fa-check-double me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Modération et validation des demandes de collecte en ligne</p>
        </div>
        <div class="badge bg-warning text-dark p-2 px-3 rounded-pill shadow-sm">
            <i class="fas fa-clock me-1"></i> <?= count($demandes) ?> demande(s) à traiter
        </div>
    </div>

    

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th class="ps-4">Client</th>
                                <th>Date & Créneau</th>
                                <th>Contact</th>
                                <th class="text-end pe-4">Décision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($demandes)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted italic">
                                        <i class="fas fa-coffee fa-2x mb-3 d-block"></i>
                                        Aucune nouvelle demande pour le moment.
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach($demandes as $d): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?= htmlspecialchars($d['nom_client']) ?></div>
                                    <small class="text-muted">ID Web: <?= $d['token_confirmation'] ?></small>
                                </td>
                                <td>
                                    <div class="small fw-bold text-primary"><?= date('d/m/Y', strtotime($d['date_reservation'])) ?></div>
                                    <div class="small"><?= $d['creneau_horaire'] ?></div>
                                </td>
                                <td>
                                    <div class="small"><i class="fas fa-phone fa-xs me-1"></i> <?= $d['telephone'] ?></div>
                                    <div class="small text-muted text-truncate" style="max-width: 150px;"><?= $d['email'] ?></div>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group shadow-sm">
                                        <button class="btn btn-sm btn-success" title="Confirmer et informer le client">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" title="Refuser / Créneau complet">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light border" title="Appeler le client">
                                            <i class="fas fa-phone-alt"></i>
                                        </button>
                                    </div>
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
                    <h6 class="fw-bold mb-3"><i class="fas fa-cog me-2"></i>Automatisation</h6>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="autoSms" checked>
                        <label class="form-check-label small" for="autoSms">Envoi SMS auto à la validation</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="autoEmail" checked>
                        <label class="form-check-label small" for="autoEmail">Envoi Email auto à la validation</label>
                    </div>
                    <hr>
                    <label class="form-label small fw-bold">Modèle de message rapide</label>
                    <textarea class="form-control form-control-sm mb-3" rows="3">Bonjour [NOM], votre demande de collecte pour le [DATE] à [HEURE] est confirmée. Notre livreur passera à l'adresse indiquée. Merci !</textarea>
                    <button class="btn btn-dark w-100 btn-sm">Enregistrer le modèle</button>
                </div>
            </div>

            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body text-center p-4">
                    <h3 class="fw-bold mb-1">98%</h3>
                    <small class="opacity-75">Taux de confirmation client</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>