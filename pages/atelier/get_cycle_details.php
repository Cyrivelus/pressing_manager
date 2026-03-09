<?php
session_start();
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$cycle_id = $_GET['id'] ?? null;
if (!$cycle_id) {
    header('Location: planning_machines.php');
    exit;
}

$titre = "Détails du cycle #" . $cycle_id;
require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
require_once '../../fonctions/database.php';

$pdo = getConnection();

// Récupérer les détails du cycle
$stmt = $pdo->prepare("
    SELECT 
        cp.*,
        m.nom_machine,
        m.type_machine,
        t.numero_ticket,
        c.nom as client_nom,
        c.prenom as client_prenom
    FROM cycles_production cp
    JOIN machines m ON cp.id_machine = m.id_machine
    JOIN tickets t ON cp.id_ticket = t.id_ticket
    JOIN clients c ON t.id_client = c.id_client
    WHERE cp.id_cycle = ?
");
$stmt->execute([$cycle_id]);
$cycle = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cycle) {
    header('Location: planning_machines.php');
    exit;
}

$statut_color = [
    'prévu' => 'info',
    'en_cours' => 'primary',
    'termine' => 'success',
    'annule' => 'danger',
    'pause' => 'warning'
][$cycle['statut']] ?? 'secondary';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-info-circle me-2 text-primary"></i>
                        Détails du cycle #<?= $cycle_id ?>
                    </h1>
                    <p class="text-muted mb-0">Informations complètes sur ce cycle de production</p>
                </div>
                <div>
                    <span class="badge bg-<?= $statut_color ?> fs-6 p-2">
                        <?= ucfirst($cycle['statut']) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-industry me-2"></i>Informations machine</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Machine:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($cycle['nom_machine']) ?></dd>
                        
                        <dt class="col-sm-4">Type:</dt>
                        <dd class="col-sm-8"><?= ucfirst($cycle['type_machine']) ?></dd>
                    </dl>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Informations ticket</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">N° Ticket:</dt>
                        <dd class="col-sm-8">T-<?= $cycle['numero_ticket'] ?></dd>
                        
                        <dt class="col-sm-4">Client:</dt>
                        <dd class="col-sm-8"><?= $cycle['client_prenom'] ?> <?= $cycle['client_nom'] ?></dd>
                    </dl>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Horaires</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Début prévu:</dt>
                        <dd class="col-sm-8"><?= date('d/m/Y H:i', strtotime($cycle['heure_debut'])) ?></dd>
                        
                        <dt class="col-sm-4">Fin prévue:</dt>
                        <dd class="col-sm-8"><?= date('d/m/Y H:i', strtotime($cycle['heure_fin'])) ?></dd>
                        
                        <?php if ($cycle['heure_debut_reel']): ?>
                        <dt class="col-sm-4">Début réel:</dt>
                        <dd class="col-sm-8"><?= date('d/m/Y H:i', strtotime($cycle['heure_debut_reel'])) ?></dd>
                        <?php endif; ?>
                        
                        <?php if ($cycle['heure_fin_reel']): ?>
                        <dt class="col-sm-4">Fin réelle:</dt>
                        <dd class="col-sm-8"><?= date('d/m/Y H:i', strtotime($cycle['heure_fin_reel'])) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
            
            <?php if ($cycle['notes']): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Notes</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($cycle['notes'])) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <a href="planning_machines.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour au planning
                        </a>
                        <div class="btn-group">
                            <a href="gerer_cycle.php" class="btn btn-outline-primary">
                                <i class="fas fa-edit me-2"></i>Gérer les cycles
                            </a>
                            <a href="action_cycle.php?id=<?= $cycle_id ?>" class="btn btn-outline-warning">
                                <i class="fas fa-cogs me-2"></i>Actions
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>