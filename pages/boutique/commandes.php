<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Historique des Ventes Boutique";

// 2. Filtres avec dates sécurisées
$date_debut = $_GET['debut'] ?? date('Y-m-01');
$date_fin = $_GET['fin'] ?? date('Y-m-d');
$ventes = [];
$total_ca = 0;

try {
    // 3. Récupération des ventes (Jointure avec clients et utilisateurs)
    // Correction de u.nom_utilisateur par u.nom_complet si nécessaire selon votre table
    $query = "SELECT v.*, c.nom_client, c.prenom_client, u.nom_complet as nom_utilisateur 
              FROM ventes_boutique v
              LEFT JOIN clients c ON v.id_client = c.id_client
              LEFT JOIN utilisateurs u ON v.id_utilisateur = u.id_utilisateur
              WHERE DATE(v.date_vente) BETWEEN :debut AND :fin
              ORDER BY v.date_vente DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute(['debut' => $date_debut, 'fin' => $date_fin]);
    $ventes = $stmt->fetchAll();

    // 4. Calcul du CA total
    $total_ca = array_sum(array_column($ventes, 'montant_total'));

} catch (PDOException $e) {
    // Si la table n'existe toujours pas après l'exécution du SQL ci-dessus
    $error_db = "Erreur de base de données : " . $e->getMessage();
}

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    .card { border: none; border-radius: 12px; }
    .table thead th { background-color: #f8fafc; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; color: #64748b; border-top: none; }
    .ca-box { background: #1e293b; color: white; border-left: 5px solid #10b981; }
    .badge-cash { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
</style>

<br><br><br>
<div class="container-fluid py-5">
    <?php if(isset($error_db)): ?>
        <div class="alert alert-danger shadow-sm border-start border-4 border-danger">
            <?= $error_db ?>
        </div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold m-0 text-dark"><?= $titre ?></h2>
            <p class="text-muted mb-0">Rapport détaillé des ventes encaissées</p>
        </div>
        <div class="ca-box p-3 rounded-3 shadow-sm text-end">
            <small class="d-block opacity-75 fw-bold">CHIFFRE D'AFFAIRES PÉRIODE</small>
            <h3 class="m-0 fw-bold text-success"><?= number_format($total_ca, 0, '.', ' ') ?> <small class="fs-6">F CFA</small></h3>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Date de début</label>
                    <input type="date" name="debut" class="form-control" value="<?= $date_debut ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Date de fin</label>
                    <input type="date" name="fin" class="form-control" value="<?= $date_fin ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                       Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Réf.</th>
                        <th>Date & Heure</th>
                        <th>Client</th>
                        <th>Quantité</th>
                        <th>Mode Paiement</th>
                        <th>Vendeur</th>
                        <th>Montant Total</th>
                        <th class="text-center pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($ventes as $v): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-primary">#V-<?= str_pad($v['id_vente'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td>
                            <div class="fw-bold"><?= date('d/m/Y', strtotime($v['date_vente'])) ?></div>
                            <div class="text-muted small"><?= date('H:i', strtotime($v['date_vente'])) ?></div>
                        </td>
                        <td>
                            <?php if($v['id_client']): ?>
                                <span class="fw-bold small text-dark"><?= htmlspecialchars($v['prenom_client'].' '.$v['nom_client']) ?></span>
                            <?php else: ?>
                                <span class="text-muted italic small">Client de passage</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge rounded-pill bg-light text-dark border">
                                <?= $v['nb_articles'] ?> article(s)
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-cash px-3 py-2">
                               <?= strtoupper($v['mode_paiement']) ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars($v['nom_utilisateur'] ?? 'Admin') ?></td>
                        <td class="fw-bold"><?= number_format($v['montant_total'], 0, '.', ' ') ?> F</td>
                        <td class="text-center pe-4">
                            <div class="btn-group shadow-sm">
                                <button class="btn btn-sm btn-white border" onclick="printReceipt(<?= $v['id_vente'] ?>)" title="Imprimer">
                                    
                                </button>
                                <button class="btn btn-sm btn-white border" data-bs-toggle="modal" data-bs-target="#modalDetails<?= $v['id_vente'] ?>" title="Détails">
                                  
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if(empty($ventes)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="opacity-25 mb-3"></div>
                            <p class="text-muted fw-bold">Aucune transaction trouvée pour cette période.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function printReceipt(id) {
    const width = 450;
    const height = 650;
    const left = (window.innerWidth / 2) - (width / 2);
    const top = (window.innerHeight / 2) - (height / 2);
    window.open(`imprimer_recu.php?id=${id}`, 'Impression Ticket', 
                `width=${width},height=${height},left=${left},top=${top},toolbar=0,scrollbars=1`);
}
</script>

<?php require_once '../../templates/footer.php'; ?>