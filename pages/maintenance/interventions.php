<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Suivi des Interventions Techniques";

// 1. Récupération des interventions (En cours et Terminées)
$sql = "SELECT i.*, e.nom_equipement, u.nom_complet as declare_par
        FROM interventions i
        JOIN equipements e ON i.id_equipement = e.id_equipement
        JOIN utilisateurs u ON i.id_utilisateur = u.id_utilisateur
        ORDER BY i.date_intervention DESC";
$interventions = $pdo->query($sql)->fetchAll();

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-tools text-secondary me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Historique des réparations et suivi des coûts de maintenance</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNouvelleIntervention">
            <i class="fas fa-plus"></i> Enregistrer une intervention
        </button>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <small class="text-muted fw-bold text-uppercase">Dépenses Maintenance (Mois)</small>
                <h3 class="fw-bold m-0 text-primary">145 000 <small>FCFA</small></h3>
                <div class="progress mt-2" style="height: 5px;">
                    <div class="progress-bar bg-primary" style="width: 70%"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <small class="text-muted fw-bold text-uppercase">Temps d'Arrêt (Uptime)</small>
                <h3 class="fw-bold m-0 text-success">98.2 %</h3>
                <small class="text-muted">Disponibilité des machines</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white">
                <small class="opacity-75 fw-bold text-uppercase">Pannes non résolues</small>
                <h3 class="fw-bold m-0">02</h3>
                <small class="text-warning">Interventions en attente</small>
            </div>
        </div>
    </div>

    

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4">Date & Réf</th>
                        <th>Équipement</th>
                        <th>Type / Problème</th>
                        <th>Technicien / Prestataire</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end pe-4">Coût (FCFA)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($interventions as $i): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold"><?= date('d/m/Y', strtotime($i['date_intervention'])) ?></span><br>
                            <small class="text-muted">#INT-<?= $i['id_intervention'] ?></small>
                        </td>
                        <td class="fw-bold text-primary"><?= htmlspecialchars($i['nom_equipement']) ?></td>
                        <td>
                            <span class="d-block small"><?= htmlspecialchars($i['description_panne']) ?></span>
                            <?php if($i['piece_remplacee']): ?>
                                <span class="badge bg-light text-dark border small mt-1">Pièce : <?= $i['piece_remplacee'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($i['technicien_nom'] ?? 'Interne') ?></td>
                        <td class="text-center">
                            <?php if($i['statut'] == 'termine'): ?>
                                <span class="badge bg-success-soft text-success"><i class="fas fa-check me-1"></i> Résolu</span>
                            <?php else: ?>
                                <span class="badge bg-warning-soft text-warning animate__animated animate__flash animate__infinite"><i class="fas fa-wrench me-1"></i> En cours</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4 fw-bold">
                            <?= number_format($i['cout_total'], 0, ',', ' ') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNouvelleIntervention" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header">
                <h5 class="fw-bold">Rapport d'intervention</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Équipement concerné</label>
                        <select class="form-select">
                            <option>Machine à Sec Union L800</option>
                            <option>Presse vapeur Hoffman</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Type de panne</label>
                        <select class="form-select">
                            <option>Électrique</option>
                            <option>Mécanique</option>
                            <option>Fuite (Eau/Vapeur)</option>
                            <option>Logiciel / Système</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description détaillée des travaux</label>
                    <textarea class="form-control" rows="3" placeholder="Qu'est-ce qui a été réparé ?"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Pièces remplacées</label>
                        <input type="text" class="form-control" placeholder="ex: Courroie, Filtre">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Prestataire</label>
                        <input type="text" class="form-control" placeholder="Nom du technicien">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Montant Facture (FCFA)</label>
                        <input type="number" class="form-control" value="0">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Enregistrer l'intervention</button>
            </form>
        </div>
    </div>
</div>

<?php require_once  '../../templates/footer.php'; ?>