<?php
// 1. Démarrage de la session sécurisé
if (session_status() === PHP_SESSION_NONE) session_start();

// 2. Vérification de l'authentification
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

// 3. Définition de la racine du projet pour des inclusions fiables
// On remonte au dossier principal (pressing_manager)
$root = realpath(__DIR__ . '/../../');

// 4. Inclusion de la base de données (pour vérifier si le cycle existe)
require_once $root . '/fonctions/database.php';

$cycle_id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Redirection si pas d'ID valide
if (!$cycle_id) {
    header('Location: planning_machines.php');
    exit;
}

try {
    $pdo = getConnection();
    // On récupère les infos du cycle pour un affichage plus pro
    $stmt = $pdo->prepare("
        SELECT cp.*, m.nom_machine, t.numero_ticket 
        FROM cycles_production cp 
        JOIN machines m ON cp.id_machine = m.id_machine 
        JOIN tickets t ON cp.id_ticket = t.id_ticket 
        WHERE cp.id_cycle = ?
    ");
    $stmt->execute([$cycle_id]);
    $cycle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cycle) {
        die("<div class='alert alert-danger'>Erreur : Ce cycle n'existe pas.</div>");
    }

} catch (PDOException $e) {
    // Si la table n'existe pas encore ou erreur SQL, on affiche l'erreur au lieu d'une page blanche
    die("<div class='alert alert-danger'>Erreur de base de données : " . htmlspecialchars($e->getMessage()) . "</div>");
}

$titre = "Actions sur les cycles";
require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="h3 fw-bold">
                <i class="fas fa-sync-alt fa-spin me-2 text-primary"></i>
                Gestion du Cycle #<?= $cycle['id_cycle'] ?>
            </h1>
            <p class="text-muted">
                Machine : <strong><?= htmlspecialchars($cycle['nom_machine']) ?></strong> | 
                Ticket : <strong>#<?= htmlspecialchars($cycle['numero_ticket']) ?></strong>
            </p>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4 text-center">Actions de Production</h5>
                    
                    <div class="d-grid gap-3">
                        <a href="actions/gerer_cycle.php?action=demarrer&id=<?= $cycle_id ?>" 
                           class="btn btn-success btn-lg py-3 shadow-sm">
                            <i class="fas fa-play me-2"></i>DÉMARRER
                        </a>
                        
                        <a href="actions/gerer_cycle.php?action=terminer&id=<?= $cycle_id ?>" 
                           class="btn btn-primary btn-lg py-3 shadow-sm">
                            <i class="fas fa-check-double me-2"></i>TERMINER
                        </a>
                        
                        <a href="actions/gerer_cycle.php?action=pause&id=<?= $cycle_id ?>" 
                           class="btn btn-warning btn-lg py-3 shadow-sm text-white">
                            <i class="fas fa-pause me-2"></i>PAUSE
                        </a>
                        
                        <hr>
                        
                        <a href="actions/gerer_cycle.php?action=annuler&id=<?= $cycle_id ?>" 
                           class="btn btn-outline-danger btn-lg py-3"
                           onclick="return confirm('Attention : Cette action annulera le cycle définitivement. Confirmer ?')">
                            <i class="fas fa-ban me-2"></i>ANNULER LE CYCLE
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 bg-light shadow-sm" style="border-radius: 15px;">
                <div class="card-body p-4">
                    <h6 class="fw-bold"><i class="fas fa-info-circle me-2 text-info"></i>Rappel des règles</h6>
                    <ul class="small mt-3">
                        <li class="mb-2"><strong>Démarrer :</strong> Change le statut de la machine en "En cours".</li>
                        <li class="mb-2"><strong>Terminer :</strong> Libère la machine et marque le ticket comme "Prêt".</li>
                        <li class="mb-2"><strong>Annuler :</strong> Remet le ticket en attente et libère la machine.</li>
                    </ul>
                    <div class="d-grid mt-4">
                        <a href="planning_machines.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour au planning
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>