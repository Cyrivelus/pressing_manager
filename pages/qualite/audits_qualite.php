<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Audits Qualité & Conformité Interne";

// 1. Liste des derniers audits réalisés
$sql = "SELECT tr.*, u.nom_complet as auditeur
        FROM tracabilite_tickets tr
        JOIN utilisateurs u ON tr.id_utilisateur = u.id_utilisateur
        WHERE tr.action = 'AUDIT_INTERNE'
        ORDER BY tr.date_heure DESC LIMIT 10";
$audits = $pdo->query($sql)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-indigo"><i class="fas fa-clipboard-check me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Évaluez la conformité de vos processus et maintenez vos standards d'excellence</p>
        </div>
        <button class="btn btn-indigo text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNewAudit">
            <i class="fas fa-search"></i> Lancer une inspection
        </button>
    </div>

    

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white text-center">
                <small class="text-muted fw-bold">SCORE DE CONFORMITÉ MOYEN</small>
                <h1 class="fw-bold text-indigo m-0">88%</h1>
                <div class="progress mt-3" style="height: 10px;">
                    <div class="progress-bar bg-indigo" style="width: 88%"></div>
                </div>
                <small class="text-success mt-2 d-block"><i class="fas fa-arrow-up"></i> +3% ce mois</small>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white">
                <h6 class="fw-bold mb-3 text-uppercase small opacity-75">Points Critiques de Vigilance</h6>
                <div class="row text-center">
                    <div class="col-4">
                        <div class="h3 fw-bold text-warning">95%</div>
                        <small class="opacity-50">Propreté Atelier</small>
                    </div>
                    <div class="col-4 border-start border-secondary">
                        <div class="h3 fw-bold text-danger">72%</div>
                        <small class="opacity-50">Étiquetage</small>
                    </div>
                    <div class="col-4 border-start border-secondary">
                        <div class="h3 fw-bold text-info">91%</div>
                        <small class="opacity-50">Maintenance Machines</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h6 class="fw-bold mb-0">Historique des inspections et actions correctives</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small">
                    <tr>
                        <th class="ps-4">Date / Heure</th>
                        <th>Inspecteur</th>
                        <th>Observations Clés</th>
                        <th class="text-center">Score</th>
                        <th class="text-end pe-4">Rapport</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($audits as $a): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold"><?= date('d/m/Y', strtotime($a['date_heure'])) ?></span><br>
                            <small class="text-muted"><?= date('H:i', strtotime($a['date_heure'])) ?></small>
                        </td>
                        <td><?= htmlspecialchars($a['auditeur']) ?></td>
                        <td>
                            <div class="small text-truncate" style="max-width: 350px;">
                                <?= htmlspecialchars($a['note_qualite']) ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-indigo-soft text-indigo">Conforme</span>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-light border"><i class="fas fa-file-pdf"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .text-indigo { color: #6610f2; }
    .bg-indigo { background-color: #6610f2; }
    .btn-indigo { background-color: #6610f2; border-color: #6610f2; }
    .bg-indigo-soft { background-color: rgba(102, 16, 242, 0.1); }
</style>

<?php require_once $root . '/templates/footer.php'; ?>