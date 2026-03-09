<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité et Connexion
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

// 2. LOGIQUE D'ENREGISTREMENT (Traitement AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_async'])) {
    header('Content-Type: application/json');
    
    try {
        // Validation CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Erreur de sécurité (CSRF)");
        }

        $sql = "INSERT INTO cycles_production (id_machine, id_ticket, heure_debut, duree_estimee, notes, statut) 
                VALUES (:id_m, :id_t, :h_deb, :duree, :notes, 'en_cours')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_m'   => $_POST['id_machine'],
            ':id_t'   => $_POST['id_ticket'],
            ':h_deb'  => $_POST['heure_debut'],
            ':duree'  => $_POST['duree_estimee'],
            ':notes'  => $_POST['notes'],
        ]);

        // Mise à jour optionnelle du statut du ticket
        $pdo->prepare("UPDATE tickets SET statut = 'en_cours' WHERE id_ticket = ?")
            ->execute([$_POST['id_ticket']]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Erreur : " . $e->getMessage()]);
    }
    exit;
}

// 3. RÉCUPÉRATION DES DONNÉES POUR L'AFFICHAGE
try {
    $machines = $pdo->query("SELECT * FROM machines WHERE statut != 'hors_service' ORDER BY type_machine")->fetchAll(PDO::FETCH_ASSOC);
    $tickets = $pdo->query("SELECT * FROM tickets WHERE statut = 'en_attente' ORDER BY date_depot DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("
        SELECT cp.*, m.nom_machine 
        FROM cycles_production cp
        JOIN machines m ON cp.id_machine = m.id_machine
        ORDER BY cp.id_cycle DESC LIMIT 5
    ");
    $derniers_cycles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}

$titre_page = "Nouveau Cycle";
require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<link rel="stylesheet" href="../../css/select2.min.css">
<link rel="stylesheet" href="../../css/select2-bootstrap.min.css">

<br> <br> <br> 
<div class="container-fluid py-5">
    <div class="row mb-4 mt-5">
        <div class="col-12">
            <h2 class="fw-bold text-primary">Nouveau Cycle de Production</h2>
            <p class="text-muted small text-uppercase">Lancer un traitement en machine</p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <form id="formCycle">
                        <?php if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action_async" value="1">
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">1. CHOIX DE LA MACHINE</label>
                                <select name="id_machine" id="machineSelect" class="form-control" required>
                                    <option value=""></option>
                                    <?php foreach ($machines as $m): ?>
                                        <option value="<?= $m['id_machine'] ?>">
                                            <?= strtoupper($m['type_machine']) ?> - <?= htmlspecialchars($m['nom_machine']) ?> (<?= $m['capacite'] ?>kg)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">2. TICKET CLIENT</label>
                                <select name="id_ticket" id="ticketSelect" class="form-control" required>
                                    <option value=""></option>
                                    <?php foreach ($tickets as $t): ?>
                                        <option value="<?= $t['id_ticket'] ?>">
                                            #<?= htmlspecialchars($t['numero_ticket']) ?> - Reçu le <?= date('d/m/Y', strtotime($t['date_depot'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small">3. HEURE DE DÉPART</label>
                                <input type="datetime-local" class="form-control border-2" name="heure_debut" value="<?= date('Y-m-d\TH:i') ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">4. DURÉE (MINUTES)</label>
                                <input type="number" class="form-control border-2" name="duree_estimee" value="45" min="1" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small">NOTES & INSTRUCTIONS</label>
                                <textarea class="form-control border-2" name="notes" rows="2" placeholder="Ex: Lavage délicat, sans adoucissant..."></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-5">
                            <a href="planning_machines.php" class="btn btn-light px-4 rounded-pill">Annuler</a>
                            <button type="submit" id="btnSubmit" class="btn btn-primary px-5 fw-bold rounded-pill shadow">
                                DÉMARRER LE CYCLE
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold">[...] Activités récentes</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($derniers_cycles as $c): ?>
                        <div class="list-group-item border-0 px-4 py-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-bold"><?= htmlspecialchars($c['nom_machine']) ?></span>
                                <span class="badge bg-light text-primary"><?= date('H:i', strtotime($c['heure_debut'])) ?></span>
                            </div>
                            <div class="text-muted small">Ticket #<?= $c['id_ticket'] ?> (<?= $c['duree_estimee'] ?> min)</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../js/jquery.min.js"></script>
<script src="../../js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Initialisation intelligente des Select2
    $('#machineSelect').select2({
        theme: "bootstrap",
        placeholder: "Rechercher une machine...",
        allowClear: true
    });

    $('#ticketSelect').select2({
        theme: "bootstrap",
        placeholder: "N° Ticket ou Date...",
        allowClear: true
    });

    // Envoi asynchrone sans rechargement
    $('#formCycle').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#btnSubmit');
        btn.prop('disabled', true).text('Traitement...');

        $.post(window.location.href, $(this).serialize(), function(response) {
            if(response.success) {
                window.location.href = 'planning_machines.php?status=started';
            } else {
                alert(response.message);
                btn.prop('disabled', false).text('DÉMARRER LE CYCLE');
            }
        }, 'json').fail(function() {
            alert("Erreur critique de communication.");
            btn.prop('disabled', false).text('DÉMARRER LE CYCLE');
        });
    });
});
</script>

<?php require_once '../../templates/footer.php'; ?>