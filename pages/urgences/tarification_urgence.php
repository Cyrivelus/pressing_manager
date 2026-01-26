<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Paramétrage des Tarifs d'Urgence";

// 1. Récupération des types d'urgence configurés (Simulation via table paramètres)
// En production, vous pouvez créer une table 'tarifs_urgences'
$types_urgence = [
    ['code' => 'SUPER_EXPRESS', 'label' => 'Super Express (2h)', 'majoration' => 100, 'color' => 'danger'],
    ['code' => 'EXPRESS_JOUR', 'label' => 'Express Journée (6h)', 'majoration' => 50, 'color' => 'warning'],
    ['code' => 'V_24H', 'label' => 'Veille pour Lendemain', 'majoration' => 25, 'color' => 'primary']
];

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-hand-holding-usd text-danger me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Configurez les majorations automatiques pour les prestations prioritaires</p>
        </div>
        <button class="btn btn-danger shadow-sm">
            <i class="fas fa-plus"></i> Ajouter une règle
        </button>
    </div>

    

    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Grille des Majorations Actives</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small text-uppercase">
                            <tr>
                                <th class="ps-4">Libellé du Service</th>
                                <th class="text-center">Majoration (%)</th>
                                <th class="text-center">Délai Garanti</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($types_urgence as $u): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?= $u['label'] ?></div>
                                    <small class="text-muted">Appliqué sur le montant HT</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $u['color'] ?>-soft text-<?= $u['color'] ?> fs-6">
                                        + <?= $u['majoration'] ?> %
                                    </span>
                                </td>
                                <td class="text-center">
                                    <i class="fas fa-clock text-muted me-1"></i> <?= str_replace(['(', ')'], '', substr($u['label'], -3)) ?>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light border"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm bg-dark text-white p-4">
                <h6 class="fw-bold mb-3 text-warning">SIMULATEUR RAPIDE</h6>
                <div class="mb-3">
                    <label class="small opacity-75">Prix Standard de l'article</label>
                    <div class="input-group input-group-sm">
                        <input type="number" id="base_price" class="form-control bg-secondary text-white border-0" value="5000">
                        <span class="input-group-text bg-secondary text-white border-0">FCFA</span>
                    </div>
                </div>
                <hr class="opacity-25">
                <div class="d-flex justify-content-between mb-2">
                    <span class="small">Prix Super Express (100%) :</span>
                    <span class="fw-bold text-danger">10 000 FCFA</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="small">Prix Express 6h (50%) :</span>
                    <span class="fw-bold text-warning">7 500 FCFA</span>
                </div>
                <p class="extra-small text-muted mt-3 mb-0">
                    <i class="fas fa-info-circle"></i> Ces prix sont calculés automatiquement lors de la création du ticket si l'option urgence est cochée.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .extra-small { font-size: 0.75rem; }
</style>

<?php require_once $root . '/templates/footer.php'; ?>