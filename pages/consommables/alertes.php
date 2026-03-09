<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';


$titre = "Alertes & Réapprovisionnement";

// 2. Récupération uniquement des articles en alerte
$sql = "SELECT * FROM consommables 
        WHERE stock_actuel <= stock_alerte 
        ORDER BY (stock_actuel / stock_alerte) ASC";
$stmt = $pdo->query($sql);
$alertes = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<style>
    /* Styles pour l'écran */
    .critical-low { border-left: 5px solid #e74c3c !important; }
    .warning-low { border-left: 5px solid #f39c12 !important; }
    .progress-stock { height: 10px; border-radius: 5px; }

    /* Configuration Impression Professionnelle */
    @media print {
        /* Masquage des éléments inutiles */
        header, .navigation, footer, nav, .btn, .btn-group, .col-lg-4, hr {
            display: none !important;
        }

        /* Mise en page plein écran */
        body { background-color: white !important; font-size: 12pt; }
        .container-fluid { width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .col-lg-8 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }

        /* Style des cartes pour l'impression */
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
            margin-bottom: 10px !important;
            page-break-inside: avoid;
        }

        /* Titre spécial pour l'impression */
        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        h2.text-danger { color: black !important; }
        
        /* Ajustement des couleurs de barres de progression pour l'encre */
        .progress { border: 1px solid #000 !important; background-color: transparent !important; }
        .progress-bar { background-color: #333 !important; -webkit-print-color-adjust: exact; }
    }

    /* Cacher le header d'impression sur l'écran */
    .print-header { display: none; }
</style>

<div class="container-fluid py-5">
    
    <div class="print-header">
        <h1 class="fw-bold">LISTE DE RÉAPPROVISIONNEMENT</h1>
        <p>Date : <?= date('d/m/Y H:i') ?> | Établi par : <?= $_SESSION['utilisateur_nom'] ?? 'Gestionnaire' ?></p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-danger"> <?= $titre ?></h2>
            <p class="text-muted">Articles nécessitant une commande immédiate</p>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-dark shadow-sm">
                Imprimer la liste de courses
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <?php if (empty($alertes)): ?>
                <div class="card border-0 shadow-sm p-5 text-center">
                    <h5 class="text-success">Tous les stocks sont optimaux !</h5>
                </div>
            <?php else: ?>
                <?php foreach ($alertes as $a): 
                    $ratio = ($a['stock_alerte'] > 0) ? ($a['stock_actuel'] / $a['stock_alerte']) * 100 : 0;
                    $status_class = ($a['stock_actuel'] <= ($a['stock_alerte'] * 0.2)) ? 'critical-low' : 'warning-low';
                    $bar_color = ($ratio <= 20) ? 'bg-danger' : 'bg-warning';
                ?>
                <div class="card border-0 shadow-sm mb-3 <?= $status_class ?>">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-5 col-7">
                                <h6 class="fw-bold mb-0 text-uppercase"><?= htmlspecialchars($a['nom_article']) ?></h6>
                                <small class="text-muted">Fournisseur : <?= htmlspecialchars($a['fournisseur_habituel'] ?? 'Non défini') ?></small>
                            </div>
                            <div class="col-md-4 col-5">
                                <div class="d-flex justify-content-between mb-1 small">
                                    <span>Stock : <strong><?= number_format($a['stock_actuel'], 2) ?></strong> / <?= $a['stock_alerte'] ?></span>
                                    <span class="fw-bold"><?= round($ratio) ?>%</span>
                                </div>
                                <div class="progress progress-stock">
                                    <div class="progress-bar <?= $bar_color ?>" style="width: <?= $ratio ?>%"></div>
                                </div>
                            </div>
                            <div class="col-md-3 text-end d-print-none">
                                <a href="passer_commande.php?id=<?= $a['id_consommable'] ?>" class="btn btn-sm btn-outline-primary">
                                    Commander
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="col-lg-4 d-print-none">
            <div class="card border-0 shadow-sm p-4 bg-light">
                <h5 class="fw-bold mb-4">Par Fournisseur</h5>
                <?php
                $sql_f = "SELECT fournisseur_habituel, COUNT(*) as nb FROM consommables WHERE stock_actuel <= stock_alerte GROUP BY fournisseur_habituel";
                $groupes = $pdo->query($sql_f)->fetchAll(PDO::FETCH_ASSOC);
                
                foreach($groupes as $g):
                ?>
                <div class="d-flex justify-content-between align-items-center mb-2 bg-white p-2 rounded border">
                    <small class="fw-bold"><?= htmlspecialchars($g['fournisseur_habituel'] ?? 'Divers') ?></small>
                    <span class="badge bg-danger rounded-pill"><?= $g['nb'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once  '../../templates/footer.php'; ?>