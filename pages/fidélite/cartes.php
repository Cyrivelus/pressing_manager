<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';


$titre = "Gestion des Cartes Fidélité";

// --- TRAITEMENT DES ACTIONS (PHP) ---
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Émission
    if (isset($_POST['action_emettre'])) {
        try {
            $ins = $pdo->prepare("INSERT INTO fidelite_cartes (numero_carte, id_client, type_programme, date_emission, statut, points, solde_monetaire) VALUES (?, ?, ?, NOW(), 'active', 0, 0)");
            $ins->execute([$_POST['numero_carte'], $_POST['id_client'], $_POST['type_programme']]);
            $message = "<div class='alert alert-success border-0 shadow-sm'>Carte émise avec succès !</div>";
        } catch (Exception $e) {
            $message = "<div class='alert alert-danger border-0 shadow-sm'>Erreur : Doublon ou données invalides.</div>";
        }
    }
    // 2. Rechargement Solde
    if (isset($_POST['action_recharger'])) {
        $pdo->prepare("UPDATE fidelite_cartes SET solde_monetaire = solde_monetaire + ? WHERE id_carte = ?")
            ->execute([$_POST['montant'], $_POST['id_carte']]);
        $message = "<div class='alert alert-info border-0 shadow-sm'>Porte-monnaie mis à jour.</div>";
    }
}

// --- RECHERCHE ET LISTING ---
$search = $_GET['search'] ?? '';
$sql = "SELECT f.*, c.nom_client, c.prenom_client, c.telephone 
        FROM fidelite_cartes f
        JOIN clients c ON f.id_client = c.id_client
        WHERE f.numero_carte LIKE ? OR c.nom_client LIKE ? OR c.telephone LIKE ?
        ORDER BY f.date_emission DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%", "%$search%"]);
$cartes = $stmt->fetchAll();

$clients = $pdo->query("SELECT id_client, nom_client, prenom_client FROM clients ORDER BY nom_client ASC")->fetchAll();

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    .card-fid { border-left: 5px solid #0d6efd; }
    .action-row { display: none; background-color: #f8f9fa; border-left: 5px solid #0d6efd; }
    #panelEmission { display: none; animation: fadeIn 0.3s ease; }
    .status-active { color: #198754; background: #e9f7ef; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
    .status-blocked { color: #dc3545; background: #fdf2f2; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>

<div class="container-fluid py-5">
    
    <?= $message ?>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
        <div>
            <h2 class="fw-bold m-0 text-dark"><?= $titre ?></h2>
            <p class="text-muted small mb-0 text-uppercase">Outil de fidélisation et porte-monnaie électronique</p>
        </div>
        <button onclick="togglePanel('panelEmission')" class="btn btn-primary fw-bold shadow-sm px-4">
            [+] NOUVELLE CARTE
        </button>
    </div>

    <div id="panelEmission" class="mb-5 shadow-lg card border-0 card-fid">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Activer une nouvelle carte</h5>
            <form method="POST" class="row g-3">
                <input type="hidden" name="action_emettre" value="1">
                <div class="col-md-4">
                    <label class="small fw-bold">Numéro de Carte</label>
                    <input type="text" name="numero_carte" class="form-control" placeholder="Scanner ici..." required>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold">Client</label>
                    <select name="id_client" class="form-select" required>
                        <?php foreach($clients as $c): ?>
                            <option value="<?= $c['id_client'] ?>"><?= $c['nom_client'] ?> <?= $c['prenom_client'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold">Type de Programme</label>
                    <select name="type_programme" class="form-select">
                        <option value="points">Points uniquement</option>
                        <option value="prepaye">Porte-monnaie uniquement</option>
                        <option value="mixte">Mixte (Points + Cash)</option>
                    </select>
                </div>
                <div class="col-12 text-end">
                    <button type="button" onclick="togglePanel('panelEmission')" class="btn btn-light fw-bold">ANNULER</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow">ENREGISTRER</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body bg-light">
            <form method="GET" class="row g-2">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control border-0" placeholder="Recherche rapide (Nom, Mobile, Carte)..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100 fw-bold">FILTRER</button>
                </div>
            </form>
        </div>
    </div>

    

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-dark text-white">
                    <tr>
                        <th class="ps-4">N° CARTE</th>
                        <th>CLIENT</th>
                        <th class="text-center">SOLDE POINTS</th>
                        <th class="text-center">SOLDE CASH</th>
                        <th class="text-center">STATUT</th>
                        <th class="text-end pe-4">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($cartes as $card): ?>
                    <tr class="border-bottom">
                        <td class="ps-4 py-3 fw-bold text-primary"><?= $card['numero_carte'] ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($card['prenom_client'].' '.$card['nom_client']) ?></div>
                            <div class="text-muted small"><?= $card['telephone'] ?></div>
                        </td>
                        <td class="text-center fw-bold text-info"><?= number_format($card['points'], 0) ?> PTS</td>
                        <td class="text-center fw-bold text-success"><?= number_format($card['solde_monetaire'], 0, ',', ' ') ?> F</td>
                        <td class="text-center">
                            <span class="<?= ($card['statut'] == 'active') ? 'status-active' : 'status-blocked' ?>">
                                <?= strtoupper($card['statut']) ?>
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group shadow-sm">
                                <button onclick="toggleActionRow('recharge-<?= $card['id_carte'] ?>')" class="btn btn-sm btn-outline-primary fw-bold">+ SOLDE</button>
                                <button onclick="toggleActionRow('histo-<?= $card['id_carte'] ?>')" class="btn btn-sm btn-outline-dark fw-bold">HISTO.</button>
                                <button class="btn btn-sm btn-outline-danger fw-bold">X</button>
                            </div>
                        </td>
                    </tr>
                    
                    <tr id="recharge-<?= $card['id_carte'] ?>" class="action-row">
                        <td colspan="6" class="p-3">
                            <form method="POST" class="d-flex align-items-center gap-3">
                                <input type="hidden" name="action_recharger" value="1">
                                <input type="hidden" name="id_carte" value="<?= $card['id_carte'] ?>">
                                <span class="fw-bold text-primary">RECHARGER LE COMPTE :</span>
                                <input type="number" name="montant" class="form-control form-control-sm w-25" placeholder="Montant en FCFA" required>
                                <button type="submit" class="btn btn-sm btn-success fw-bold">VALIDER LA RECHARGE</button>
                                <button type="button" onclick="toggleActionRow('recharge-<?= $card['id_carte'] ?>')" class="btn btn-sm btn-secondary">FERMER</button>
                            </form>
                        </td>
                    </tr>

                    <tr id="histo-<?= $card['id_carte'] ?>" class="action-row">
                        <td colspan="6" class="p-3">
                            <div class="small fw-bold mb-2">3 dernières transactions :</div>
                            <ul class="list-unstyled small mb-0">
                                <li>- 12/01/2026 : Utilisation de 50 points (Remise 5%)</li>
                                <li>- 05/01/2026 : Recharge de 10 000 FCFA</li>
                                <li>- 01/01/2026 : Gain de 12 points (Ticket #4502)</li>
                            </ul>
                        </td>
                    </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Gère l'affichage du panneau d'émission global
    function togglePanel(id) {
        const el = document.getElementById(id);
        el.style.display = (el.style.display === 'block') ? 'none' : 'block';
    }

    // Gère l'affichage des actions spécifiques à chaque ligne
    function toggleActionRow(id) {
        const rows = document.getElementsByClassName('action-row');
        const target = document.getElementById(id);
        
        // Fermer les autres lignes ouvertes pour garder la propreté
        for (let row of rows) {
            if (row.id !== id) row.style.display = 'none';
        }
        
        target.style.display = (target.style.display === 'table-row') ? 'none' : 'table-row';
    }
</script>

<?php require_once '../../templates/footer.php'; ?>