<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité : Vérification de l'utilisateur (id_client ou utilisateur_id)
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$id_ticket = $_GET['id'] ?? null;
$utilisateur_id = $_SESSION['utilisateur_id'];

// 2. Récupération des détails de la commande (sécurisée par l'ID client)
$stmt = $pdo->prepare("
    SELECT t.*, 
           (SELECT status_etape FROM etapes_production WHERE id_ticket = t.id_ticket ORDER BY date_update DESC LIMIT 1) as etape_actuelle
    FROM tickets t 
    WHERE t.id_ticket = ? AND t.id_client = ?
");
$stmt->execute([$id_ticket, $utilisateur_id]);
$commande = $stmt->fetch();

if (!$commande) {
    header('Location: index.php');
    exit;
}

// Mapping des étapes pour la barre de progression
$etapes = [
    'reception' => ['label' => 'Reçu', 'icon' => 'fa-check-circle', 'percent' => 10],
    'lavage'    => ['label' => 'Lavage', 'icon' => 'fa-tint', 'percent' => 40],
    'sechage'   => ['label' => 'Séchage', 'icon' => 'fa-wind', 'percent' => 60],
    'repassage' => ['label' => 'Finition', 'icon' => 'fa-tshirt', 'percent' => 85],
    'pret'      => ['label' => 'Prêt', 'icon' => 'fa-store', 'percent' => 100]
];

$etape_cle = $commande['etape_actuelle'] ?? 'reception';
$progression = $etapes[$etape_cle]['percent'] ?? 10;

$titre = "Suivi Ticket #" . $commande['code_ticket'];
require_once $root . '/templates/header_client.php';
?>

<style>
    .stepper-wrapper { display: flex; justify-content: space-between; margin-top: 20px; position: relative; }
    .stepper-item { position: relative; display: flex; flex-direction: column; align-items: center; flex: 1; z-index: 2; }
    .stepper-item::before { position: absolute; content: ""; border-bottom: 2px solid #ccc; width: 100%; top: 20px; left: -50%; z-index: -1; }
    .stepper-item:first-child::before { content: none; }
    .step-counter { width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; background: #ccc; border-radius: 50%; color: white; margin-bottom: 6px; }
    .active .step-counter { background-color: #3498db; }
    .completed .step-counter { background-color: #2ecc71; }
    .step-name { font-size: 10px; text-transform: uppercase; font-weight: bold; color: #777; }
</style>

<div class="container py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="index.php" class="btn btn-light rounded-circle me-3"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h4 class="fw-bold m-0">Commande #<?= $commande['code_ticket'] ?></h4>
            <span class="badge bg-soft-success text-success">Dépôt le <?= date('d/m/Y', strtotime($commande['date_reception'])) ?></span>
        </div>
    </div>

    <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 20px;">
        <h6 class="fw-bold text-center mb-4">État d'avancement : <span class="text-primary"><?= $etapes[$etape_cle]['label'] ?></span></h6>
        
        <div class="stepper-wrapper">
            <?php 
            $current_found = false;
            foreach ($etapes as $key => $info): 
                $class = "";
                if ($key == $etape_cle) { $class = "active"; $current_found = true; }
                elseif (!$current_found) { $class = "completed"; }
            ?>
            <div class="stepper-item <?= $class ?>">
                <div class="step-counter"><i class="fas <?= $info['icon'] ?>"></i></div>
                <div class="step-name"><?= $info['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    

    <div class="card border-0 shadow-sm p-4" style="border-radius: 20px;">
        <h6 class="fw-bold mb-3">Détails du linge</h6>
        <ul class="list-group list-group-flush">
            <?php 
            $items = json_decode($commande['details_articles'], true) ?? [];
            foreach ($items as $item): 
            ?>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span><?= htmlspecialchars($item['libelle'] ?? 'Article') ?> x<?= $item['qte'] ?? 1 ?></span>
                <span class="fw-bold"><?= number_format($item['prix'] ?? 0, 0, ',', ' ') ?> FCFA</span>
            </li>
            <?php endforeach; ?>
        </ul>
        
        <hr>
        
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted small">Total à régler</span>
            <h4 class="fw-bold text-primary m-0"><?= number_format($commande['montant_total'], 0, ',', ' ') ?> FCFA</h4>
        </div>
    </div>

    <?php if ($etape_cle == 'pret'): ?>
    <div class="alert alert-success mt-4 border-0 shadow-sm" style="border-radius: 15px;">
        <div class="d-flex">
            <i class="fas fa-bell fa-2x me-3"></i>
            <div>
                <h6 class="fw-bold mb-1">Votre linge est prêt !</h6>
                <p class="small mb-0">Vous pouvez passer le récupérer à la boutique munis de votre ticket numérique.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once $root . '/templates/footer_client.php'; ?>