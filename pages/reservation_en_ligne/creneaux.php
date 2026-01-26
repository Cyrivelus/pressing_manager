<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Configuration des Créneaux Horaires";

// Simulation de la capacité par créneau
$capacite_max = 5; // Nombre maximum de collectes par heure/livreur

// Récupération des réservations par créneau pour aujourd'hui
$sql = "SELECT creneau_horaire, COUNT(*) as total 
        FROM reservations_online 
        WHERE DATE(date_reservation) = CURDATE() 
        GROUP BY creneau_horaire";
$occupations = $pdo->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);

require_once  '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-calendar-alt text-primary me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Définissez vos heures d'ouverture et gérez la charge de vos livreurs</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalConfig">
            <i class="fas fa-plus"></i> Ajouter une Plage
        </button>
    </div>

    

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between">
                    <h6 class="fw-bold mb-0">Disponibilités du jour : <?= date('d/m/Y') ?></h6>
                    <span class="badge bg-light text-dark">Zone : Centre-Ville</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light small text-uppercase">
                                <tr>
                                    <th class="ps-4">Créneau</th>
                                    <th class="text-center">Charge</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $creneaux = ["08:00 - 10:00", "10:00 - 12:00", "14:00 - 16:00", "16:00 - 18:00"];
                                foreach($creneaux as $c): 
                                    $occupe = $occupations[$c] ?? 0;
                                    $pourcentage = ($occupe / $capacite_max) * 100;
                                ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?= $c ?></td>
                                    <td class="text-center" style="width: 300px;">
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar <?= $pourcentage >= 100 ? 'bg-danger' : 'bg-primary' ?>" 
                                                 style="width: <?= $pourcentage ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= $occupe ?> / <?= $capacite_max ?> réservations</small>
                                    </td>
                                    <td>
                                        <?php if($occupe >= $capacite_max): ?>
                                            <span class="badge bg-danger-soft text-danger">Complet</span>
                                        <?php else: ?>
                                            <span class="badge bg-success-soft text-success">Disponible</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-light border" title="Désactiver pour aujourd'hui">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Paramètres de Réservation</h6>
                    <div class="mb-3">
                        <label class="form-label small">Capacité par créneau (Clients)</label>
                        <input type="number" class="form-control" value="<?= $capacite_max ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Délai minimum avant réservation</label>
                        <select class="form-select">
                            <option>2 Heures</option>
                            <option selected>4 Heures</option>
                            <option>24 Heures</option>
                        </select>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" checked>
                        <br>
                        <label class="form-check-label small">Autoriser les collectes le dimanche</label>
                    </div>
                    <button class="btn btn-dark w-100">Appliquer les réglages</button>
                </div>
            </div>

            <div class="alert alert-info border-0">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>Astuce :</strong> Réduisez la capacité lors des jours de forte pluie pour compenser les ralentissements logistiques.
            </div>
        </div>
    </div>
</div>

<?php require_once  '../../templates/footer.php'; ?>