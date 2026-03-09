<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Planning des Tournées de Livraison";
$attentes = [];
$tournees = [];
$livreurs = [];

try {
    // 1. Récupération des livraisons en attente
    // Utilisation de requêtes préparées ou query selon le besoin
    $sql_attente = "SELECT t.*, c.nom_client, c.adresse, c.telephone 
                    FROM tickets t 
                    JOIN clients c ON t.id_client = c.id_client 
                    WHERE t.statut = 'pret' 
                    AND t.mode_retrait = 'livraison'
                    AND t.id_ticket NOT IN (SELECT id_ticket FROM tournees_details)
                    ORDER BY c.adresse ASC";
    $attentes = $pdo->query($sql_attente)->fetchAll();

    // 2. Récupération des tournées du jour
    $aujourdhui = date('Y-m-d');
    $sql_tournees = "SELECT tr.*, u.nom_complet as livreur_nom 
                     FROM tournees tr
                     JOIN utilisateurs u ON tr.id_livreur = u.id_utilisateur
                     WHERE tr.date_tournee = ?
                     ORDER BY tr.heure_depart ASC";
    $stmt = $pdo->prepare($sql_tournees);
    $stmt->execute([$aujourdhui]);
    $tournees = $stmt->fetchAll();

    // 3. CORRECTION : Récupération des livreurs
    // On suppose l'existence d'une table 'roles'. Si vous n'avez pas de table roles, 
    // remplacez par : WHERE id_role = [ID_DU_ROLE_LIVREUR]
    $sql_livreurs = "SELECT u.id_utilisateur, u.nom_complet 
                     FROM utilisateurs u
                     INNER JOIN roles r ON u.id_role = r.id_role
                     WHERE r.nom_role = 'livreur' AND u.est_actif = 1";
    
    // Si vous n'avez pas encore de table 'roles', utilisez l'ID direct (ex: 3 pour livreur)
    // $sql_livreurs = "SELECT id_utilisateur, nom_complet FROM utilisateurs WHERE id_role = 3 AND est_actif = 1";
    
    $livreurs = $pdo->query($sql_livreurs)->fetchAll();

} catch (PDOException $e) {
    $error_db = "Erreur de base de données : " . $e->getMessage();
}

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<style>
    #panelNouvelleTournee { display: none; animation: slideDown 0.4s ease-out; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    .bg-light-blue { background-color: #f0f7ff; }
    .badge-soft-primary { background-color: #e7f1ff; color: #0d6efd; border: 1px solid #cfe2ff; }
</style>

<br><br><br>
<div class="container-fluid py-5">
    <?php if (isset($error_db)): ?>
        <div class="alert alert-danger shadow-sm border-start border-4 border-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>[ERREUR SYSTÈME]</strong> <?= htmlspecialchars($error_db) ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-primary"><i class="bi bi-truck me-2"></i>[LOGISTIQUE] <?= $titre ?></h2>
            <p class="text-muted small fw-bold text-uppercase">Optimisation des trajets et suivi temps réel</p>
        </div>
        <button onclick="toggleTourneePanel()" class="btn btn-primary fw-bold shadow-sm px-4">
            <i class="bi bi-plus-circle me-2"></i>CRÉER UNE TOURNÉE
        </button>
    </div>

    <div id="panelNouvelleTournee" class="card border-0 shadow-lg mb-4 bg-light-blue border-start border-primary border-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold m-0 text-primary">Initialiser une nouvelle feuille de route</h4>
                <button type="button" class="btn-close" onclick="toggleTourneePanel()"></button>
            </div>
            <form action="creer_tournee_action.php" method="POST" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-dark">CHOISIR UN LIVREUR</label>
                    <select name="id_livreur" class="form-select border-0 shadow-sm" required>
                        <option value="" disabled selected>Sélectionner un agent...</option>
                        <?php foreach($livreurs as $l): ?>
                            <option value="<?= $l['id_utilisateur'] ?>"><?= htmlspecialchars($l['nom_complet']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-dark">HEURE DE DÉPART</label>
                    <input type="time" name="heure_depart" class="form-control border-0 shadow-sm" value="<?= date('H:i') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-dark">ZONE GÉOGRAPHIQUE</label>
                    <input type="text" name="zone" class="form-control border-0 shadow-sm" placeholder="ex: Bastos, Akwa...">
                </div>
                <div class="col-md-2">
                    <label class="form-label d-none d-md-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100 fw-bold shadow">LANCER</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h6 class="fw-bold mb-0 text-uppercase small"><i class="bi bi-hourglass-split me-2"></i>Colis en attente d'attribution</h6>
                </div>
                <form action="assigner_tournee.php" method="POST">
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush" style="max-height: 600px; overflow-y: auto;">
                            <?php foreach($attentes as $a): ?>
                            <div class="list-group-item p-3 border-start border-4 border-warning">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="w-75">
                                        <span class="badge bg-light text-dark mb-1">TICKET #<?= $a['numero_ticket'] ?></span>
                                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($a['nom_client']) ?></h6>
                                        <div class="small text-muted fw-bold">[DESTINATION]</div>
                                        <div class="small text-dark text-truncate"><?= htmlspecialchars($a['adresse'] ?? 'Non spécifiée') ?></div>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input border-primary shadow-sm" style="transform: scale(1.4);" type="checkbox" name="selection_livraison[]" value="<?= $a['id_ticket'] ?>">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if(empty($attentes)): ?>
                                <div class="p-5 text-center text-muted fw-bold">
                                    <i class="bi bi-check2-circle fs-1 d-block mb-2 text-success"></i>
                                    TOUT EST À JOUR
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if(!empty($attentes)): ?>
                    <div class="card-footer bg-white border-0 p-3">
                        <button type="submit" class="btn btn-dark w-100 fw-bold">ASSIGNER À UNE TOURNÉE ACTIVE</button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <h6 class="fw-bold mb-0">TOURNEES DU JOUR (<?= date('d/m/Y') ?>)</h6>
                    <span class="badge badge-soft-primary fw-bold px-3"><?= count($tournees) ?> ACTIVE(S)</span>
                </div>
                <div class="card-body">
                    <?php foreach($tournees as $t): ?>
                    <div class="border rounded-3 p-3 mb-3 bg-white shadow-sm border-start border-4 border-primary">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <span class="d-block small text-muted text-uppercase fw-bold">Chauffeur</span>
                                <span class="fw-bold text-primary"><?= htmlspecialchars($t['livreur_nom']) ?></span>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="d-block small text-muted text-uppercase fw-bold">Départ</span>
                                <span class="badge bg-dark fs-6"><?= date('H:i', strtotime($t['heure_depart'])) ?></span>
                            </div>
                            <div class="col-md-3">
                                <span class="d-block small text-muted text-uppercase fw-bold">Secteur</span>
                                <span class="fw-bold"><?= htmlspecialchars($t['zone_livraison'] ?? 'NON DÉFINI') ?></span>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="status-dot bg-success"></span>
                                <span class="fw-bold small text-uppercase"><?= htmlspecialchars($t['statut']) ?></span>
                            </div>
                            <div class="col-md-2 text-end">
                                <a href="details_tournee.php?id=<?= $t['id_tournee'] ?>" class="btn btn-sm btn-outline-dark fw-bold px-3">GÉRER</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if(empty($tournees)): ?>
                        <div class="text-center py-5 text-muted border border-dashed rounded-3">
                            <p class="mb-0 fw-bold text-uppercase opacity-50">Aucune expédition en cours de planification.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="alert bg-dark text-white border-0 shadow-sm d-flex align-items-center p-4">
                <i class="bi bi-info-circle-fill fs-2 text-warning me-3"></i>
                <div>
                    <strong class="text-warning text-uppercase">Optimisation de trajet :</strong><br>
                    Utilisez le regroupement par quartier pour réduire les kilomètres parcourus et garantir le respect des délais.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleTourneePanel() {
        const panel = document.getElementById('panelNouvelleTournee');
        if (panel.style.display === 'block') {
            panel.style.display = 'none';
        } else {
            panel.style.display = 'block';
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
</script>

<?php require_once '../../templates/footer.php'; ?>