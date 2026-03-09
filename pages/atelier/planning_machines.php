<?php
// planning_machines.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification authentification
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Configuration et initialisation
$titre = "Planning d'Utilisation des Machines";
require_once '../../fonctions/database.php';

// Fonction pour récupérer les machines disponibles
function getMachinesDisponibles($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   COUNT(DISTINCT cp.id_cycle) as cycles_en_cours
            FROM machines m
            LEFT JOIN cycles_production cp ON m.id_machine = cp.id_machine 
                AND cp.statut = 'en_cours'
                AND DATE(cp.heure_debut) = CURDATE()
            WHERE m.statut IN ('disponible', 'maintenance', 'en_cours')
            GROUP BY m.id_machine
            ORDER BY m.type_machine, m.nom_machine
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur récupération machines: " . $e->getMessage());
        return [];
    }
}

// Fonction pour récupérer les cycles du jour
function getCyclesDuJour($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                cp.*,
                m.nom_machine,
                m.type_machine,
                t.numero_ticket,
                c.nom as client_nom,
                c.prenom as client_prenom,
                TIME_TO_SEC(TIMEDIFF(cp.heure_fin, cp.heure_debut)) as duree_secondes
            FROM cycles_production cp
            INNER JOIN machines m ON cp.id_machine = m.id_machine
            LEFT JOIN tickets t ON cp.id_ticket = t.id_ticket
            LEFT JOIN clients c ON t.id_client = c.id_client
            WHERE DATE(cp.heure_debut) = CURDATE()
            ORDER BY cp.heure_debut ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur récupération cycles: " . $e->getMessage());
        return [];
    }
}

// Fonction pour calculer les statistiques
function getStatistiques($pdo) {
    try {
        // Initialiser le tableau avec des valeurs par défaut
        $stats = [
            'total_cycles' => 0,
            'cycles_en_cours' => 0,
            'cycles_termines' => 0,
            'machines_actives' => 0,
            'temps_total' => 0
        ];
        
        // Statistiques des cycles
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
                SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END) as termines
            FROM cycles_production 
            WHERE DATE(heure_debut) = CURDATE()
        ");
        
        if ($stmt) {
            $cycles_stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cycles_stats) {
                $stats['total_cycles'] = (int)($cycles_stats['total'] ?? 0);
                $stats['cycles_en_cours'] = (int)($cycles_stats['en_cours'] ?? 0);
                $stats['cycles_termines'] = (int)($cycles_stats['termines'] ?? 0);
            }
        }
        
        // Machines actives (statut = 'disponible' ou 'en_cours')
        $stmt = $pdo->query("
            SELECT COUNT(*) as total 
            FROM machines 
            WHERE statut IN ('disponible', 'en_cours')
        ");
        
        if ($stmt) {
            $machines_stats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['machines_actives'] = (int)($machines_stats['total'] ?? 0);
        }
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log("Erreur statistiques: " . $e->getMessage());
        // Retourner des valeurs par défaut en cas d'erreur
        return [
            'total_cycles' => 0,
            'cycles_en_cours' => 0,
            'cycles_termines' => 0,
            'machines_actives' => 0,
            'temps_total' => 0
        ];
    }
}

// Récupération des données
try {
    $machines = getMachinesDisponibles($pdo);
    $cycles = getCyclesDuJour($pdo);
    $statistiques = getStatistiques($pdo);
    
    // Heures de la journée pour la timeline
    $heures_journee = [];
    for ($i = 6; $i <= 22; $i++) {
        $heures_journee[] = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
    }
    
} catch (PDOException $e) {
    die("<div class='alert alert-danger'>Erreur de connexion à la base de données: " . $e->getMessage() . "</div>");
}

// Inclusion des templates
require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titre) ?> - Pressing Manager</title>
    <style>
        :root {
            --couleur-lavage: #3498db;
            --couleur-sechage: #f39c12;
            --couleur-repassage: #2ecc71;
            --couleur-attente: #e74c3c;
            --couleur-termine: #27ae60;
        }
        
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .machine-card {
            border-left: 4px solid;
            transition: all 0.3s;
        }
        .machine-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .machine-lavage { border-left-color: var(--couleur-lavage); }
        .machine-sechage { border-left-color: var(--couleur-sechage); }
        .machine-repassage { border-left-color: var(--couleur-repassage); }
        
        .timeline-container {
            height: 70px;
            background: linear-gradient(to right, #f8f9fa 0%, #f8f9fa 100%);
            border: 1px solid #dee2e6;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .timeline-grid {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
        }
        
        .timeline-hour {
            flex: 1;
            border-right: 1px dashed #dee2e6;
            position: relative;
        }
        
        .timeline-hour:last-child {
            border-right: none;
        }
        
        .timeline-label {
            position: absolute;
            bottom: 5px;
            right: 5px;
            font-size: 10px;
            color: #6c757d;
            font-weight: 500;
        }
        
        .cycle-block {
            position: absolute;
            height: 60%;
            top: 20%;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 10;
        }
        
        .cycle-block:hover {
            transform: scale(1.05);
            z-index: 100;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .cycle-lavage { background: var(--couleur-lavage); }
        .cycle-sechage { background: var(--couleur-sechage); }
        .cycle-repassage { background: var(--couleur-repassage); }
        .cycle-attente { background: var(--couleur-attente); }
        .cycle-termine { background: var(--couleur-termine); }
        
        .current-time {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e74c3c;
            z-index: 50;
        }
        
        .current-time::after {
            content: '';
            position: absolute;
            top: -5px;
            left: -4px;
            width: 10px;
            height: 10px;
            background: #e74c3c;
            border-radius: 50%;
        }
        
        .machine-status {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        
        .status-disponible { background: #27ae60; }
        .status-en_cours { background: #3498db; }
        .status-maintenance { background: #f39c12; }
        .status-hors_service { background: #e74c3c; }
        
        .badge-cycle {
            font-size: 0.7em;
            padding: 3px 8px;
        }
        
        .progress-thin {
            height: 6px;
        }
        
        @media (max-width: 768px) {
            .machine-info {
                margin-bottom: 15px;
            }
            .timeline-container {
                height: 50px;
            }
        }
    </style>
</head>
<body>
    
<div class="container-fluid py-4">
    <!-- En-tête avec statistiques -->
     <br><br><br>
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">
                      
                        <?= htmlspecialchars($titre) ?>
                    </h1>
                    <p class="text-muted mb-0">
                       
                        Visualisation en temps réel - <?= date('d/m/Y') ?>
                    </p>
                </div>
                <div>
                    <!-- Bouton "Nouveau Cycle" redirige vers ajouter_cycle.php -->
                    <a href="ajouter_cycle.php" class="btn btn-primary me-2">
                       Nouveau Cycle
                    </a>
                    <!-- Bouton "Actualiser" redirige vers planning_machines.php -->
                    <a href="planning_machines.php" class="btn btn-outline-secondary">
                       Actualiser
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Cartes statistiques -->
        <div class="col-md-3 mb-3">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Cycles du jour</h6>
                            <h3 class="mb-0"><?= (int)($statistiques['total_cycles'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                          
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="badge bg-primary me-2">
                            <?= (int)($statistiques['cycles_en_cours'] ?? 0) ?> en cours
                        </span>
                        <span class="badge bg-success">
                            <?= (int)($statistiques['cycles_termines'] ?? 0) ?> terminés
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Machines actives</h6>
                            <h3 class="mb-0"><?= (int)($statistiques['machines_actives'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                         
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                           
                            <?= count($machines) ?> machines au total
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Taux occupation</h6>
                            <h3 class="mb-0">
                                <?php
                                $total_cycles = (int)($statistiques['total_cycles'] ?? 0);
                                $cycles_en_cours = (int)($statistiques['cycles_en_cours'] ?? 0);
                                $total_machines = count($machines);
                                
                                if ($total_machines > 0 && $total_cycles > 0) {
                                    $taux = round(($cycles_en_cours / $total_machines) * 100);
                                } else {
                                    $taux = 0;
                                }
                                echo $taux;
                                ?>%
                            </h3>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                           
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress progress-thin">
                            <div class="progress-bar bg-info" style="width: <?= $taux ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Heure actuelle</h6>
                            <h3 class="mb-0" id="current-time"><?= date('H:i') ?></h3>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded-circle">
                         
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            
                            <?= date('d F Y', strtotime('today')) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Légende -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="mb-3">Légende des types</h6>
                    <div class="d-flex flex-wrap gap-3">
                        <span class="d-flex align-items-center">
                            <span class="machine-status status-disponible me-2"></span>
                            <small>Disponible</small>
                        </span>
                        <span class="d-flex align-items-center">
                            <span class="machine-status status-en_cours me-2"></span>
                            <small>En cours</small>
                        </span>
                        <span class="d-flex align-items-center">
                            <span class="machine-status status-maintenance me-2"></span>
                            <small>Maintenance</small>
                        </span>
                        <span class="d-flex align-items-center">
                            <span style="background: var(--couleur-lavage); width: 15px; height: 15px; border-radius: 3px; margin-right: 8px;"></span>
                            <small>Lavage</small>
                        </span>
                        <span class="d-flex align-items-center">
                            <span style="background: var(--couleur-sechage); width: 15px; height: 15px; border-radius: 3px; margin-right: 8px;"></span>
                            <small>Séchage</small>
                        </span>
                        <span class="d-flex align-items-center">
                            <span style="background: var(--couleur-repassage); width: 15px; height: 15px; border-radius: 3px; margin-right: 8px;"></span>
                            <small>Repassage</small>
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-3">Statuts des cycles</h6>
                    <div class="d-flex flex-wrap gap-3">
                        <span class="badge bg-info badge-cycle">Prévu</span>
                        <span class="badge bg-primary badge-cycle">En cours</span>
                        <span class="badge bg-success badge-cycle">Terminé</span>
                        <span class="badge bg-warning badge-cycle">Annulé</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Planning principal -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    
                    Planning détaillé par machine
                </h5>
                <div class="text-muted small">
                    Période: 06:00 - 22:00
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- En-tête timeline -->
            <div class="sticky-top bg-white z-3" style="top: 0;">
                <div class="timeline-container border-0 rounded-0" style="height: 40px; border-bottom: 1px solid #dee2e6 !important;">
                    <div class="timeline-grid">
                        <?php foreach ($heures_journee as $heure): ?>
                        <div class="timeline-hour">
                            <div class="timeline-label"><?= $heure ?></div>
                        </div>
                        <?php endforeach; ?>
                        <div class="current-time" id="current-time-line"></div>
                    </div>
                </div>
            </div>
            
            <!-- Liste des machines -->
            <div class="list-group list-group-flush">
                <?php if (empty($machines)): ?>
                <div class="list-group-item p-4 text-center">
                    <div class="alert alert-info mb-0">
                    
                        Aucune machine disponible pour le moment.
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($machines as $machine): 
                        $type_class = 'machine-' . strtolower($machine['type_machine']);
                        $status_class = 'status-' . str_replace(' ', '_', $machine['statut']);
                    ?>
                    <div class="list-group-item p-0 border-bottom">
                        <div class="row align-items-center g-0">
                            <!-- Info machine -->
                            <div class="col-md-3 col-lg-2">
                                <div class="p-3 machine-card <?= $type_class ?>">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="machine-status <?= $status_class ?>"></span>
                                        <strong class="text-dark"><?= htmlspecialchars($machine['nom_machine']) ?></strong>
                                    </div>
                                    <div class="small text-muted">
                                        <div>
                                          
                                            <?= ucfirst($machine['type_machine']) ?>
                                        </div>
                                        <div>
                                          
                                            <?= (int)($machine['cycles_en_cours'] ?? 0) ?> cycle(s) en cours
                                        </div>
                                        <?php if (!empty($machine['capacite'])): ?>
                                        <div>
                                        
                                            Capacité: <?= $machine['capacite'] ?> kg
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Timeline -->
                            <div class="col-md-9 col-lg-10">
                                <div class="p-3">
                                    <div class="timeline-container">
                                        <div class="timeline-grid">
                                            <?php foreach ($heures_journee as $heure): ?>
                                            <div class="timeline-hour"></div>
                                            <?php endforeach; ?>
                                            
                                            <!-- Affichage des cycles pour cette machine -->
                                            <?php 
                                            $cycles_machine = array_filter($cycles, function($c) use ($machine) {
                                                return $c['id_machine'] == $machine['id_machine'];
                                            });
                                            
                                            foreach ($cycles_machine as $cycle):
                                                $heure_debut = strtotime($cycle['heure_debut']);
                                                $heure_fin = strtotime($cycle['heure_fin'] ?? $cycle['heure_debut']);
                                                $jour_debut = strtotime(date('Y-m-d 06:00:00'));
                                                $jour_fin = strtotime(date('Y-m-d 22:00:00'));
                                                
                                                // Calcul positions
                                                if ($heure_debut < $jour_debut) {
                                                    $position_debut = 0;
                                                } else {
                                                    $position_debut = max(0, ($heure_debut - $jour_debut) / (16 * 3600) * 100);
                                                }
                                                
                                                if ($heure_fin > $jour_fin) {
                                                    $position_fin = 100;
                                                } else {
                                                    $position_fin = min(100, ($heure_fin - $jour_debut) / (16 * 3600) * 100);
                                                }
                                                
                                                $largeur = max(2, $position_fin - $position_debut);
                                                
                                                // Classes CSS basées sur le type de machine
                                                $cycle_class = 'cycle-' . strtolower($machine['type_machine']);
                                                if ($cycle['statut'] == 'termine') $cycle_class = 'cycle-termine';
                                                if ($cycle['statut'] == 'annule') $cycle_class = 'cycle-attente';
                                                
                                                // Tooltip
                                                $tooltip = "Ticket: #" . ($cycle['numero_ticket'] ?? 'N/A') . "\\n";
                                                $tooltip .= "Client: " . ($cycle['client_prenom'] ?? '') . " " . ($cycle['client_nom'] ?? '') . "\\n";
                                                $tooltip .= "Début: " . date('H:i', strtotime($cycle['heure_debut'])) . "\\n";
                                                $tooltip .= "Fin: " . date('H:i', strtotime($cycle['heure_fin'])) . "\\n";
                                                $tooltip .= "Statut: " . ucfirst($cycle['statut'] ?? 'prévu');
                                            ?>
                                            <div class="cycle-block <?= $cycle_class ?>"
                                                 style="left: <?= $position_debut ?>%; width: <?= $largeur ?>%;"
                                                 data-bs-toggle="tooltip"
                                                 data-bs-html="true"
                                                 title="<?= htmlspecialchars($tooltip, ENT_QUOTES) ?>"
                                                 onclick="afficherDetailsCycle(<?= $cycle['id_cycle'] ?>)">
                                                T-<?= $cycle['numero_ticket'] ?? '?' ?>
                                                <?php if (($cycle['statut'] ?? '') == 'en_cours'): ?>
                                               
                                                <?php endif; ?>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="current-time" id="current-time-machine-<?= $machine['id_machine'] ?>"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Boutons d'action supplémentaires -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                      Actions rapides
                    </h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="suivi_production.php" class="btn btn-outline-primary">
                           Suivi Production
                        </a>
                        <a href="controle_qualite.php" class="btn btn-outline-success">
                           Contrôle Qualité
                        </a>
                        <a href="temps_cycles.php" class="btn btn-outline-info">
                            Temps des Cycles
                        </a>
                        <a href="gerer_cycle.php" class="btn btn-outline-warning">
                            Gérer les Cycles
                        </a>
                        <a href="action_cycle.php" class="btn btn-outline-secondary">
                            Actions Cycles
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts JavaScript -->
<script>
// Initialisation des tooltips Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Mettre à jour l'heure en temps réel
    function updateCurrentTime() {
        const now = new Date();
        const currentHour = now.getHours();
        const currentMinute = now.getMinutes();
        const totalMinutes = currentHour * 60 + currentMinute;
        
        // Position dans la timeline (de 6h à 22h = 16h = 960 minutes)
        const startMinutes = 6 * 60; // 6h du matin
        const totalDayMinutes = 16 * 60; // 16 heures
        const position = ((totalMinutes - startMinutes) / totalDayMinutes) * 100;
        
        // Mettre à jour l'heure affichée
        document.getElementById('current-time').textContent = 
            String(currentHour).padStart(2, '0') + ':' + 
            String(currentMinute).padStart(2, '0');
        
        // Mettre à jour la ligne de temps
        const timeLine = document.getElementById('current-time-line');
        if (timeLine) {
            timeLine.style.left = Math.max(0, Math.min(100, position)) + '%';
        }
        
        // Mettre à jour pour chaque machine
        document.querySelectorAll('[id^="current-time-machine-"]').forEach(el => {
            el.style.left = Math.max(0, Math.min(100, position)) + '%';
        });
    }
    
    // Mettre à jour toutes les minutes
    updateCurrentTime();
    setInterval(updateCurrentTime, 60000);
    
    // Fonction pour afficher les détails d'un cycle (redirige vers get_cycle_details.php)
    window.afficherDetailsCycle = function(cycleId) {
        window.open('get_cycle_details.php?id=' + cycleId, '_blank');
    };
    
    // Fonctions utilitaires
    function showAlert(type, message) {
        const alertHTML = `
            <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; max-width: 400px;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', alertHTML);
        
        // Supprimer automatiquement après 5 secondes
        setTimeout(() => {
            const alert = document.querySelector('.alert.position-fixed');
            if (alert) alert.remove();
        }, 5000);
    }
});

// Rafraîchissement automatique toutes les 5 minutes
setTimeout(() => {
    if (confirm('Voulez-vous rafraîchir le planning pour voir les dernières mises à jour ?')) {
        window.location.href = 'planning_machines.php';
    }
}, 300000);
</script>

<?php require_once '../../templates/footer.php'; ?>
</body>
</html>