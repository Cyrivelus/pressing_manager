<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Automatisation des Emails Transactionnels";

// 1. Liste des déclencheurs (Simulation de données)
$triggers = [
    ['id' => 1, 'evenement' => 'Nouveau Client', 'objet' => 'Bienvenue chez [Nom Pressing]', 'statut' => 'Actif', 'contenu' => 'Bonjour {NOM_CLIENT}, merci de nous avoir fait confiance !'],
    ['id' => 2, 'evenement' => 'Commande Prête', 'objet' => 'Bonne nouvelle ! Votre linge est prêt', 'statut' => 'Actif', 'contenu' => 'Bonjour {NOM_CLIENT}, votre commande #{NUM_TICKET} est prête.'],
    ['id' => 3, 'evenement' => 'Anniversaire Client', 'objet' => 'Un cadeau pour votre anniversaire 🎂', 'statut' => 'Inactif', 'contenu' => 'Joyeux anniversaire {NOM_CLIENT} !'],
    ['id' => 4, 'evenement' => 'Retrait effectué', 'objet' => 'Votre avis nous intéresse', 'statut' => 'Actif', 'contenu' => 'Comment s\'est passé votre retrait ?']
];

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    .text-indigo { color: #4e73df; }
    .bg-indigo { background-color: #4e73df; }
    .bg-indigo-soft { background-color: rgba(78, 115, 223, 0.1); }
    .btn-outline-indigo { color: #4e73df; border-color: #4e73df; border-width: 2px; font-weight: bold; }
    .btn-outline-indigo:hover { background-color: #4e73df; color: white; }
    
    /* Animation du panneau d'édition */
    #editPanel { display: none; transition: all 0.3s ease; }
    .highlight-row { background-color: #f8f9fc !important; border-left: 5px solid #4e73df !important; }
    .variable-badge { cursor: pointer; transition: transform 0.2s; }
    .variable-badge:hover { transform: scale(1.05); }
</style>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-indigo">[AUTOMATE] <?= $titre ?></h2>
            <p class="text-muted">Gérez les messages automatiques sans intervention humaine</p>
        </div>
        <div class="badge bg-indigo-soft text-indigo p-2 border border-indigo">
            ● MOTEUR D'ENVOI OPÉRATIONNEL
        </div>
    </div>

    <div id="editPanel" class="card border-0 shadow-lg mb-4 border-top border-4 border-indigo">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold m-0 text-indigo">Configuration du Template : <span id="display_event" class="text-dark"></span></h5>
                <button type="button" class="btn-close" onclick="closeEditor()"></button>
            </div>
            <form action="save_template.php" method="POST">
                <input type="hidden" name="trigger_id" id="input_id">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-uppercase">Objet de l'email</label>
                        <input type="text" name="objet" id="input_objet" class="form-control border-0 shadow-sm bg-light" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-uppercase">Statut du scénario</label>
                        <select name="statut" id="input_statut" class="form-select border-0 shadow-sm bg-light">
                            <option value="Actif">Actif (Envoi automatique)</option>
                            <option value="Inactif">Inactif (Désactivé)</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-uppercase">Message (HTML accepté)</label>
                        <textarea name="contenu" id="input_contenu" rows="5" class="form-control border-0 shadow-sm bg-light"></textarea>
                    </div>
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-light fw-bold px-4 me-2" onclick="closeEditor()">ANNULER</button>
                        <button type="submit" class="btn btn-indigo text-white fw-bold px-5 shadow">ENREGISTRER LES MODIFICATIONS</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-uppercase small">Scénarios configurés</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="triggerTable">
                        <thead class="bg-light small text-uppercase">
                            <tr>
                                <th class="ps-4">Événement</th>
                                <th>Objet de l'email</th>
                                <th class="text-center">Statut</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($triggers as $t): ?>
                            <tr id="row-<?= $t['id'] ?>">
                                <td class="ps-4">
                                    <div class="fw-bold"><?= $t['evenement'] ?></div>
                                    <small class="text-muted text-uppercase" style="font-size: 0.7rem;">Trigger ID: #<?= $t['id'] ?></small>
                                </td>
                                <td><span class="text-primary italic">"<?= $t['objet'] ?>"</span></td>
                                <td class="text-center">
                                    <span class="badge <?= $t['statut'] == 'Actif' ? 'bg-success' : 'bg-secondary' ?> rounded-pill">
                                        <?= strtoupper($t['statut']) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-indigo px-3" 
                                            onclick='openEditor(<?= json_encode($t) ?>)'>
                                        MODIFIER
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
                <h6 class="fw-bold small text-uppercase mb-3">[Variables de personnalisation]</h6>
                <p class="small opacity-75">Cliquez sur un code pour voir sa description :</p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="variable-badge bg-white bg-opacity-25 text-white px-2 py-1 rounded small fw-bold" title="Prénom et Nom">{NOM_CLIENT}</span>
                    <span class="variable-badge bg-white bg-opacity-25 text-white px-2 py-1 rounded small fw-bold" title="Numéro de commande">{NUM_TICKET}</span>
                    <span class="variable-badge bg-white bg-opacity-25 text-white px-2 py-1 rounded small fw-bold" title="Montant en FCFA">{TOTAL_A_PAYER}</span>
                    <span class="variable-badge bg-white bg-opacity-25 text-white px-2 py-1 rounded small fw-bold" title="Nom du livreur">{LIVREUR_NOM}</span>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-0 overflow-hidden">
                <div class="bg-light p-3 border-bottom fw-bold small text-uppercase text-center">Aperçu du rendu final</div>
                <div class="p-4 small" style="min-height: 200px; background-color: #fff;">
                    <div class="mb-2 text-muted">Objet : <span id="preview_objet" class="fw-bold text-dark">Bonne nouvelle !...</span></div>
                    <hr>
                    <div id="preview_body" class="text-dark opacity-75">
                        Bonjour {NOM_CLIENT}, <br><br>
                        Votre commande est prête à être récupérée.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openEditor(data) {
    // 1. On remplit le formulaire avec les données
    document.getElementById('input_id').value = data.id;
    document.getElementById('display_event').innerText = data.evenement;
    document.getElementById('input_objet').value = data.objet;
    document.getElementById('input_statut').value = data.statut;
    document.getElementById('input_contenu').value = data.contenu;
    
    // 2. Mise à jour de l'aperçu en temps réel
    document.getElementById('preview_objet').innerText = data.objet;
    document.getElementById('preview_body').innerHTML = data.contenu.replace(/\n/g, '<br>');

    // 3. Effets visuels (Highlight sur la ligne)
    document.querySelectorAll('tr').forEach(tr => tr.classList.remove('highlight-row'));
    document.getElementById('row-' + data.id).classList.add('highlight-row');

    // 4. On affiche le panneau
    const panel = document.getElementById('editPanel');
    panel.style.display = 'block';
    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function closeEditor() {
    document.getElementById('editPanel').style.display = 'none';
    document.querySelectorAll('tr').forEach(tr => tr.classList.remove('highlight-row'));
}

// Mise à jour de l'aperçu pendant la saisie
document.getElementById('input_contenu').addEventListener('input', function() {
    document.getElementById('preview_body').innerHTML = this.value.replace(/\n/g, '<br>');
});
document.getElementById('input_objet').addEventListener('input', function() {
    document.getElementById('preview_objet').innerText = this.value;
});
</script>

<?php require_once '../../templates/footer.php'; ?>