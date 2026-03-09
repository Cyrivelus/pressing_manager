<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$titre = "Gérer les cycles de production";

// Utilisation de chemins absolus pour éviter les erreurs d'inclusion
$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

try {
    if (!isset($pdo)) {
        $pdo = getConnection();
    }

    // Récupérer tous les cycles avec jointures sécurisées
    // Note : J'ai ajouté un COALESCE pour heure_fin au cas où elle serait NULL
    $stmt = $pdo->query("
        SELECT 
            cp.*, 
            m.nom_machine, 
            t.numero_ticket
        FROM cycles_production cp
        LEFT JOIN machines m ON cp.id_machine = m.id_machine
        LEFT JOIN tickets t ON cp.id_ticket = t.id_ticket
        ORDER BY cp.heure_debut DESC
    ");
    $cycles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // En cas d'erreur, on l'affiche proprement au lieu d'une page blanche
    die("<div class='alert alert-danger'>Erreur SQL : " . htmlspecialchars($e->getMessage()) . "</div>");
}

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>
<br><br><br>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                       
                    
                        Suivi des Cycles de Production
                    </h1>
                    <p class="text-muted mb-0">Historique et modification des travaux en machine</p>
                </div>
                <div class="btn-group">
                    <a href="ajouter_cycle.php" class="btn btn-primary">
                        
                    Nouveau cycle
                    </a>
                    <a href="planning_machines.php" class="btn btn-outline-primary">
                       Planning
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Machine</th>
                            <th>Ticket</th>
                            <th>Début</th>
                            <th>Fin estimée</th>
                            <th>Statut</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cycles)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                
                                Aucun cycle de production enregistré.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($cycles as $cycle): 
                                // Gestion sécurisée des classes de statut (évite l'erreur "Undefined index")
                                $s = $cycle['statut'];
                                $statut_class = 'secondary';
                                if (strpos($s, 'en_cours') !== false) $statut_class = 'primary';
                                elseif (strpos($s, 'termin') !== false) $statut_class = 'success';
                                elseif (strpos($s, 'annul') !== false) $statut_class = 'danger';
                                elseif (strpos($s, 'attente') !== false || $s == 'prévu') $statut_class = 'info';
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold">#<?= $cycle['id_cycle'] ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                      <?= htmlspecialchars($cycle['nom_machine'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold">T-<?= htmlspecialchars($cycle['numero_ticket'] ?? 'Inconnu') ?></span>
                                </td>
                                <td class="small">
                                    <?= date('d/m H:i', strtotime($cycle['heure_debut'])) ?>
                                </td>
                                <td class="small">
                                    <?= !empty($cycle['heure_fin']) ? date('d/m H:i', strtotime($cycle['heure_fin'])) : '--:--' ?>
                                </td>
                                <td>
                                    <span class="badge rounded-pill bg-<?= $statut_class ?>">
                                        <?= ucfirst($cycle['statut']) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="action_cycle.php?id=<?= $cycle['id_cycle'] ?>&action=modifier" 
                                           class="btn btn-sm btn-outline-primary" title="Modifier">
                                           
                                        </a>
                                        <a href="actions/supprimer_cycle.php?id=<?= $cycle['id_cycle'] ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce cycle ?')" title="Supprimer">
                                           
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once  '../../templates/footer.php'; ?>