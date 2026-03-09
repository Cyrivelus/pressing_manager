<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Suivi de Production en Temps Réel";

// 2. Récupération des commandes avec gestion des erreurs SQL
try {
    $sql = "SELECT t.*, c.nom_client, 
            (SELECT status_etape FROM etapes_production WHERE id_ticket = t.id_ticket ORDER BY date_update DESC LIMIT 1) as etape_actuelle,
            (SELECT AVG(progression) FROM etapes_production WHERE id_ticket = t.id_ticket) as progression_globale
            FROM tickets t
            JOIN clients c ON t.id_client = c.id_client
            WHERE t.statut_livraison NOT IN ('livré', 'annulé')
            ORDER BY t.date_depot ASC";
    $commandes = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    die("<div class='alert alert-danger m-5'>Erreur de base de données : Table 'etapes_production' manquante. <br>Veuillez exécuter le script SQL fourni.</div>");
}

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<style>
    .kanban-board { display: flex; gap: 15px; overflow-x: auto; padding: 20px 0; align-items: flex-start; }
    .kanban-col { min-width: 320px; background: #f4f5f7; border-radius: 12px; padding: 15px; min-height: 80vh; border: 1px solid #dfe1e6; }
    .kanban-header { font-weight: 800; padding-bottom: 15px; text-transform: uppercase; font-size: 0.85rem; color: #444; letter-spacing: 0.5px; }
    .card-ticket { 
        background: white; border-radius: 8px; padding: 15px; margin-bottom: 15px; 
        box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 5px solid #ddd;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); cursor: pointer;
    }
    .card-ticket:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
    
    .border-lavage { border-left-color: #007bff !important; }
    .border-sechage { border-left-color: #fd7e14 !important; }
    .border-repassage { border-left-color: #6f42c1 !important; }
    .border-pret { border-left-color: #28a745 !important; }

    .progress { height: 8px; border-radius: 10px; background-color: #e9ecef; }
    .auto-refresh-indicator { font-size: 0.8rem; color: #28a745; font-weight: 600; }
</style>

<br><br><br>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-layer-group text-primary me-2"></i><?= $titre ?></h2>
            <p class="text-muted small"><i class="fas fa-sync-alt fa-spin auto-refresh-indicator"></i> Flux de travail synchronisé</p>
        </div>
        <div class="btn-group shadow-sm">
            <button class="btn btn-white active">Vue Kanban</button>
            <a href="planning_machines.php" class="btn btn-white">Vue Machines</a>
        </div>
    </div>

    <div class="kanban-board">
        <?php
        $etapes = [
            'lavage' => ['titre' => '🌊 Lavage / Nettoyage', 'classe' => 'border-lavage'],
            'sechage' => ['titre' => '🔥 Séchage', 'classe' => 'border-sechage'],
            'repassage' => ['titre' => '✨ Repassage', 'classe' => 'border-repassage'],
            'pret' => ['titre' => '✅ Prêt', 'classe' => 'border-pret']
        ];

        foreach ($etapes as $key => $info):
            // Filtrage des commandes pour cette colonne
            $col_items = array_filter($commandes, function($c) use ($key) { 
                return ($c['etape_actuelle'] ?? 'lavage') == $key; 
            });
        ?>
        <div class="kanban-col">
            <div class="kanban-header d-flex justify-content-between align-items-center">
                <span><?= $info['titre'] ?></span>
                <span class="badge bg-dark rounded-pill"><?= count($col_items) ?></span>
            </div>
            
            <div class="kanban-content mt-3">
                <?php foreach ($col_items as $ticket): ?>
                <div class="card-ticket <?= $info['classe'] ?>" onclick="showDetail(<?= $ticket['id_ticket'] ?>)">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold text-primary">#<?= $ticket['numero_ticket'] ?></span>
                        <span class="small text-muted fw-bold"><?= date('H:i', strtotime($ticket['date_depot'])) ?></span>
                    </div>
                    <div class="mb-2 fw-bold text-dark"><?= htmlspecialchars($ticket['nom_client']) ?></div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <small class="text-muted"><i class="fas fa-tshirt me-1"></i> <?= $ticket['nombre_articles'] ?? 0 ?> art.</small>
                        <small class="badge bg-light text-dark border fw-normal">Urgences</small>
                    </div>
                    
                    <div class="progress">
                        <?php $prog = $ticket['progression_globale'] ?? 0; ?>
                        <div class="progress-bar <?= $prog >= 100 ? 'bg-success' : 'bg-primary' ?>" 
                             role="progressbar" style="width: <?= max(15, $prog) ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    // Rafraîchissement automatique intelligent (30s)
    setInterval(() => location.reload(), 30000);

    function showDetail(id) {
        // Redirection vers le contrôle qualité ou ouverture d'un modal
        window.location.href = 'controle_qualite.php?id=' + id;
    }
</script>

<?php require_once  '../../templates/footer.php'; ?>