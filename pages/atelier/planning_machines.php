<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité et Permissions
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}


require_once '../../fonctions/database.php';

$titre = "Planning d'Utilisation des Machines";

// 2. Récupération sécurisée des données
try {
    $machines = $pdo->query("SELECT * FROM machines WHERE statut != 'hors_service' ORDER BY type_machine, nom_machine")->fetchAll();

    $cycles = $pdo->query("
        SELECT c.*, t.numero_ticket as code_ticket, cl.nom_client as client_nom 
        FROM cycles_production c
        LEFT JOIN tickets t ON c.id_ticket = t.id_ticket
        LEFT JOIN clients cl ON t.id_client = cl.id_client
        WHERE DATE(c.heure_debut) = CURDATE()
        ORDER BY c.heure_debut ASC
    ")->fetchAll();
} catch (PDOException $e) {
    die("<div class='alert alert-danger m-5'>Erreur de base de données : Tables manquantes ou colonnes incorrectes. Vérifiez votre structure SQL.</div>");
}

require_once '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<style>
    .machine-row { border-bottom: 1px solid #dee2e6; min-height: 85px; display: flex; align-items: center; background: #fff; }
    .machine-label { width: 180px; font-weight: bold; background: #f8f9fa; padding: 15px; border-right: 3px solid #0d6efd; height: 100%; }
    .timeline-container { flex-grow: 1; position: relative; height: 50px; background: #e9ecef; border-radius: 8px; margin: 10px 25px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
    .cycle-bar { 
        position: absolute; height: 80%; top: 10%; color: white; 
        font-size: 0.75rem; font-weight: bold; display: flex; align-items: center; justify-content: center;
        border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        cursor: pointer; transition: all 0.2s; white-space: nowrap; overflow: hidden;
    }
    .cycle-bar:hover { transform: scaleY(1.1); z-index: 10; filter: brightness(1.1); }
    .legend-item { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; }
    .status-dot { width: 14px; height: 14px; border-radius: 4px; display: inline-block; }
</style>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold text-dark m-0"><i class="fas fa-microchip text-primary me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Suivi du flux de travail atelier - <?= date('d/m/Y') ?></p>
        </div>
        <button class="btn btn-primary shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#modalLancerCycle">
            <i class="fas fa-plus-circle me-2"></i> Programmer un cycle
        </button>
    </div>

    <div class="card border-0 shadow-sm p-4 mb-4">
        <div class="d-flex gap-4 mb-3">
            <div class="legend-item"><span class="status-dot" style="background: #3498db;"></span> Lavage</div>
            <div class="legend-item"><span class="status-dot" style="background: #f39c12;"></span> Séchage</div>
            <div class="legend-item"><span class="status-dot" style="background: #27ae60;"></span> Repassage</div>
        </div>
        
        <div class="d-flex justify-content-between text-muted small fw-bold border-top pt-2" style="padding: 0 100px 0 200px;">
            <span>08:00</span><span>10:00</span><span>12:00</span><span>14:00</span><span>16:00</span><span>18:00</span><span>20:00</span>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-body p-0">
            <?php if (empty($machines)): ?>
                <div class="p-5 text-center text-muted">Aucune machine configurée.</div>
            <?php else: ?>
                <?php foreach ($machines as $m): ?>
                <div class="machine-row">
                    <div class="machine-label">
                        <i class="fas <?= $m['type_machine'] == 'lavage' ? 'fa-water' : ($m['type_machine'] == 'sechage' ? 'fa-wind' : 'fa-iron') ?> text-primary me-2"></i>
                        <?= htmlspecialchars($m['nom_machine']) ?>
                        <div class="small text-muted fw-normal"><?= $m['capacite'] ?> kg max</div>
                    </div>
                    <div class="timeline-container">
                        <?php 
                        foreach ($cycles as $c) {
                            if ($c['id_machine'] == $m['id_machine']) {
                                $start = strtotime($c['heure_debut']);
                                $end = strtotime($c['heure_fin']);
                                $dayStart = strtotime(date('Y-m-d 08:00:00'));
                                $dayTotal = 12 * 3600; 
                                
                                $left = max(0, (($start - $dayStart) / $dayTotal) * 100);
                                $width = (($end - $start) / $dayTotal) * 100;
                                
                                $color = '#3498db'; // Par défaut
                                if($m['type_machine'] == 'sechage') $color = '#f39c12';
                                if($m['type_machine'] == 'repassage') $color = '#27ae60';
                                
                                echo "<div class='cycle-bar' style='left: {$left}%; width: {$width}%; background: {$color};' 
                                      title='Ticket #{$c['code_ticket']} - {$c['client_nom']}'>
                                      #{$c['code_ticket']}
                                      </div>";
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalLancerCycle" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" action="action_cycle.php" method="POST">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Démarrer une machine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Machine</label>
                    <select name="id_machine" class="form-select" required>
                        <?php foreach($machines as $m): ?>
                            <option value="<?= $m['id_machine'] ?>"><?= $m['nom_machine'] ?> (<?= $m['capacite'] ?>kg)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">ID Ticket</label>
                    <input type="number" name="id_ticket" class="form-control" placeholder="ID numérique du ticket" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Durée (min)</label>
                    <input type="number" name="duree" class="form-control" value="45">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="submit" class="btn btn-primary w-100">Lancer le cycle maintenant</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>