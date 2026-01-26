<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Automatisation des Emails Transactionnels";

// 1. Liste des déclencheurs disponibles (Triggers)
$triggers = [
    ['id' => 1, 'evenement' => 'Nouveau Client', 'objet' => 'Bienvenue chez [Nom Pressing]', 'statut' => 'Actif'],
    ['id' => 2, 'evenement' => 'Commande Prête', 'objet' => 'Bonne nouvelle ! Votre linge est prêt', 'statut' => 'Actif'],
    ['id' => 3, 'evenement' => 'Anniversaire Client', 'objet' => 'Un cadeau pour votre anniversaire 🎂', 'statut' => 'Inactif'],
    ['id' => 4, 'evenement' => 'Retrait effectué', 'objet' => 'Votre avis nous intéresse', 'statut' => 'Actif']
];

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-indigo"><i class="fas fa-robot me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Configurez vos messages automatiques basés sur le cycle de vie client</p>
        </div>
        <div class="badge bg-indigo-soft text-indigo p-2">
            <i class="fas fa-bolt"></i> Moteur d'envoi opérationnel
        </div>
    </div>

    

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Scénarios configurés</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small text-uppercase">
                            <tr>
                                <th class="ps-4">Événement déclencheur</th>
                                <th>Objet de l'email</th>
                                <th class="text-center">Délai</th>
                                <th>Statut</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($triggers as $t): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?= $t['evenement'] ?></div>
                                    <small class="text-muted">Système automatique</small>
                                </td>
                                <td><span class="text-primary italic">"<?= $t['objet'] ?>"</span></td>
                                <td class="text-center">Immédiat</td>
                                <td>
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input" type="checkbox" <?= $t['statut'] == 'Actif' ? 'checked' : '' ?>>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-indigo" title="Éditer le template">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm bg-indigo text-white p-4 mb-4">
                <h6 class="fw-bold small text-uppercase mb-3">Variables dynamiques</h6>
                <p class="small opacity-75">Utilisez ces codes dans vos textes pour personnaliser l'email :</p>
                <div class="d-flex flex-wrap gap-2">
                    <code class="bg-white bg-opacity-25 text-white px-2 py-1 rounded">{NOM_CLIENT}</code>
                    <code class="bg-white bg-opacity-25 text-white px-2 py-1 rounded">{NUM_TICKET}</code>
                    <code class="bg-white bg-opacity-25 text-white px-2 py-1 rounded">{TOTAL_A_PAYER}</code>
                    <code class="bg-white bg-opacity-25 text-white px-2 py-1 rounded">{LIVREUR_NOM}</code>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-4">
                <h6 class="fw-bold mb-3">Aperçu rapide</h6>
                <div class="border rounded p-3 bg-light small" style="min-height: 200px;">
                    <strong>De :</strong> Pressing Pro <br>
                    <strong>À :</strong> {NOM_CLIENT} <br><br>
                    Bonjour {NOM_CLIENT}, <br><br>
                    Bonne nouvelle ! Votre commande <strong>#{NUM_TICKET}</strong> est terminée et prête à être récupérée dans votre agence. <br><br>
                    À très bientôt !
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .text-indigo { color: #4e73df; }
    .bg-indigo { background-color: #4e73df; }
    .bg-indigo-soft { background-color: rgba(78, 115, 223, 0.1); }
    .btn-outline-indigo { color: #4e73df; border-color: #4e73df; }
    .btn-outline-indigo:hover { background-color: #4e73df; color: white; }
</style>

<?php require_once  '../../templates/footer.php'; ?>