<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once '../../fonctions/database.php';

$id_agence = $_SESSION['id_agence'] ?? 1;
$id_user = $_SESSION['id_utilisateur'] ?? 1;
$aujourdhui = date('Y-m-d');
$message = "";

// --- 1. LOGIQUE DE CLÔTURE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_cloture'])) {
    try {
        $pdo->beginTransaction();

        $check = $pdo->prepare("SELECT id_cloture FROM clotures_caisse WHERE id_agence = ? AND DATE(date_cloture) = ?");
        $check->execute([$id_agence, $aujourdhui]);
        
        if ($check->fetch()) {
            throw new Exception("Une clôture a déjà été effectuée pour aujourd'hui.");
        }

        $m_especes = (float)($_POST['m_especes'] ?? 0);
        $m_mobile = (float)($_POST['m_mobile'] ?? 0);
        $m_autres = (float)($_POST['m_autres'] ?? 0);
        $total = (float)($_POST['total_a_cloturer'] ?? 0);
        $notes = htmlspecialchars($_POST['notes'] ?? '');

        $sql = "INSERT INTO clotures_caisse (
                    id_agence, date_cloture, montant_total, 
                    montant_especes, montant_mobile, montant_autres, 
                    notes, id_utilisateur
                ) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_agence, $total, $m_especes, $m_mobile, $m_autres, $notes, $id_user]);

        $pdo->commit();
        $message = "<div class='alert alert-success fw-bold text-center border-0 shadow-sm'>✅ Caisse clôturée avec succès !</div>";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "<div class='alert alert-danger fw-bold'>❌ Erreur : " . $e->getMessage() . "</div>";
    }
}

// --- 2. RÉCUPÉRATION DES STATISTIQUES ET TICKETS ---
$total_especes = 0; $total_mobile = 0; $total_autres = 0; $total_jour = 0;
$tickets_a_encaisser = [];

try {
    // Calcul des totaux du jour
    $stmt = $pdo->prepare("SELECT LOWER(mode_paiement) as mode, SUM(montant) as total FROM paiements 
                           WHERE DATE(date_paiement) = ? GROUP BY mode");
    $stmt->execute([$aujourdhui]);
    $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $total_especes = $stats['especes'] ?? 0;
    $total_mobile = $stats['mobile'] ?? 0;
    $total_autres = ($stats['carte'] ?? 0) + ($stats['cheque'] ?? 0) + ($stats['autres'] ?? 0);
    $total_jour = array_sum($stats);

    // CORRECTION : Sélection de TOUS les statuts pour traitement
    // On retire la restriction sur le statut pour voir 'en_traitement', 'livre', etc.
    $sql_tickets = "SELECT t.*, c.nom_client, c.prenom_client 
                    FROM tickets t 
                    JOIN clients c ON t.id_client = c.id_client 
                    WHERE (t.montant_total > (t.montant_verse + t.montant_remise))
                    ORDER BY t.date_depot DESC";
    
    $stmt = $pdo->prepare($sql_tickets);
    $stmt->execute();
    $tickets_a_encaisser = $stmt->fetchAll();
} catch (Exception $e) {
    $message = "<div class='alert alert-warning'>Erreur chargement : " . $e->getMessage() . "</div>";
}

include '../../templates/header.php';
include '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <br><br><br>
    <?= $message ?>

    <div id="cloturePanel" class="card shadow border-0 mb-4" style="display:none; border-radius: 15px;">
        <div class="card-body p-4 text-center">
            <h5 class="fw-black text-uppercase mb-3">Validation de la clôture</h5>
            <p class="text-muted">Vous allez clôturer la caisse pour un montant total de <b><?= number_format($total_jour, 0) ?> XAF</b></p>
            <form method="POST" class="row justify-content-center">
                <div class="col-md-6">
                    <input type="hidden" name="action_cloture" value="1">
                    <input type="hidden" name="total_a_cloturer" value="<?= $total_jour ?>">
                    <input type="hidden" name="m_especes" value="<?= $total_especes ?>">
                    <input type="hidden" name="m_mobile" value="<?= $total_mobile ?>">
                    <input type="hidden" name="m_autres" value="<?= $total_autres ?>">
                    <textarea name="notes" class="form-control mb-3 shadow-sm border-0 bg-light" placeholder="Observations facultatives..."></textarea>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" onclick="document.getElementById('cloturePanel').style.display='none'" class="btn btn-light rounded-pill px-4">Annuler</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow">CONFIRMER LA CLÔTURE</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-4 mb-3 bg-primary text-white" style="border-radius: 20px;">
                <small class="fw-bold opacity-75">RECETTE TOTALE DU JOUR</small>
                <h2 class="fw-black m-0" style="letter-spacing: -1px;"><?= number_format($total_jour, 0) ?> <span style="font-size: 1rem;">XAF</span></h2>
            </div>
            
            <div class="card border-0 shadow-sm mb-3" style="border-radius: 20px;">
                <div class="card-body p-0">
                    <div class="p-3 d-flex justify-content-between align-items-center border-bottom">
                        <span class="small fw-bold text-muted">ESPECÈS</span>
                        <span class="fw-bold"><?= number_format($total_especes, 0) ?></span>
                    </div>
                    <div class="p-3 d-flex justify-content-between align-items-center border-bottom">
                        <span class="small fw-bold text-muted">MOBILE PAY</span>
                        <span class="fw-bold"><?= number_format($total_mobile, 0) ?></span>
                    </div>
                    <div class="p-3 d-flex justify-content-between align-items-center">
                        <span class="small fw-bold text-muted">AUTRES (CARTE/CHQ)</span>
                        <span class="fw-bold"><?= number_format($total_autres, 0) ?></span>
                    </div>
                </div>
            </div>

            <button onclick="document.getElementById('cloturePanel').style.display='block'" class="btn btn-dark w-100 fw-bold py-3 shadow-sm rounded-pill">
                🔒 CLÔTURER LA CAISSE
            </button>
        </div>

        <div class="col-md-9">
            <div class="card border-0 shadow-sm" style="border-radius: 20px; overflow: hidden;">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold m-0 text-dark">Flux Financier : Toutes les Factures Non Soldées</h5>
                    <span class="badge bg-soft-primary text-primary rounded-pill"><?= count($tickets_a_encaisser) ?> dossiers</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="small text-muted text-uppercase">
                                <th class="ps-4">Ticket</th>
                                <th>Statut</th>
                                <th>Client</th>
                                <th>Total</th>
                                <th>Reste à payer</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tickets_a_encaisser)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">Aucune facture en attente de règlement.</td>
                                </tr>
                            <?php endif; ?>
                            
                            <?php foreach ($tickets_a_encaisser as $t): 
                                $reste = $t['montant_total'] - $t['montant_verse'] - $t['montant_remise'];
                                
                                // Définition des couleurs de badge selon le statut
                                $status_badge = 'bg-secondary';
                                switch($t['statut']) {
                                    case 'en_attente': $status_badge = 'bg-warning text-dark'; break;
                                    case 'en_traitement': $status_badge = 'bg-info text-white'; break;
                                    case 'pret': $status_badge = 'bg-success text-white'; break;
                                    case 'recupere': case 'livre': $status_badge = 'bg-dark text-white'; break;
                                }
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold text-dark">#<?= $t['numero_ticket'] ?></span>
                                        <div class="small text-muted"><?= date('d/m/Y', strtotime($t['date_depot'])) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill <?= $status_badge ?> small fw-bold text-uppercase" style="font-size: 0.65rem;">
                                            <?= str_replace('_', ' ', $t['statut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-uppercase" style="font-size: 0.85rem;"><?= htmlspecialchars($t['nom_client']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($t['prenom_client']) ?></div>
                                    </td>
                                    <td class="fw-bold"><?= number_format($t['montant_total'], 0) ?></td>
                                    <td>
                                        <span class="badge bg-soft-danger text-danger fs-6 fw-black">
                                            <?= number_format($reste, 0) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="encaisser.php?id=<?= $t['id_ticket'] ?>" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
                                            Encaisser
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .fw-black { font-weight: 900; }
    .bg-soft-danger { background-color: #fee2e2; }
    .bg-soft-primary { background-color: #e0e7ff; }
    .table thead th { border: none; font-weight: 700; }
    .card { transition: all 0.3s ease; }
</style>

<?php include '../../templates/footer.php'; ?>