<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Registre des Annulations";

// 1. Récupération des réservations annulées récemment
$sql = "SELECT r.*, c.nom_client, c.telephone
        FROM reservations_online r
        JOIN clients c ON r.id_client = c.id_client
        WHERE r.statut = 'annule'
        ORDER BY r.date_reservation DESC LIMIT 15";
$annulations = $pdo->query($sql)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-danger"><i class="fas fa-calendar-times me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Analyse des rendez-vous annulés et gestion des créneaux libérés</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-danger"><i class="fas fa-file-pdf"></i> Rapport d'incidents</button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white border-start border-4 border-danger">
                <small class="text-muted fw-bold">TAUX D'ANNULATION (30j)</small>
                <h2 class="fw-bold m-0">4.2 %</h2>
                <small class="text-muted">Seuil d'alerte : 5%</small>
            </div>
        </div>
        
        

        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <small class="text-muted fw-bold">PRINCIPAL MOTIF</small>
                <h2 class="fw-bold m-0 text-dark">Indisponibilité</h2>
                <small class="text-danger"><i class="fas fa-user-clock"></i> 65% des cas</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white">
                <small class="opacity-75 fw-bold">CRÉNEAUX RÉCUPÉRÉS</small>
                <h2 class="fw-bold m-0 text-success">+18</h2>
                <small>Heures remises en vente ce mois</small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4">Rendez-vous initial</th>
                        <th>Client</th>
                        <th>Motif / Commentaire</th>
                        <th class="text-center">Origine</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($annulations as $a): ?>
                    <tr class="opacity-75">
                        <td class="ps-4">
                            <div class="fw-bold text-decoration-line-through"><?= date('d/m/Y', strtotime($a['date_reservation'])) ?></div>
                            <small class="text-muted"><?= $a['creneau_horaire'] ?></small>
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($a['nom_client']) ?></div>
                            <small><?= $a['telephone'] ?></small>
                        </td>
                        <td>
                            <span class="small italic text-danger">"Client absent lors du passage"</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark">Client (Web)</span>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-primary" title="Proposer un nouveau RDV">
                                <i class="fas fa-redo"></i> Re-planifier
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .italic { font-style: italic; }
</style>

<?php require_once $root . '/templates/footer.php'; ?>