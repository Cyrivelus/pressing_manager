<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';


$titre = "Alertes de Réapprovisionnement";

// 2. Récupération des articles sous le seuil avec calcul de priorité
// Formule de priorité : Plus le stock actuel est éloigné du seuil, plus c'est critique
try {
    $sql = "SELECT *, 
            (stock_actuel / stock_alerte) as ratio_survie,
            (stock_alerte * 2 - stock_actuel) as qte_suggeree
            FROM consommables 
            WHERE stock_actuel <= stock_alerte 
            ORDER BY ratio_survie ASC";
    $stmt = $pdo->query($sql);
    $alertes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<style>
    .card-critical { border-left: 5px solid #dc3545 !important; }
    .card-warning { border-left: 5px solid #ffc107 !important; }
    
    @media print {
        .d-print-none, .btn, .navigation, footer, header { display: none !important; }
        .container-fluid { width: 100% !important; padding: 0 !important; }
        .card { border: 1px solid #eee !important; box-shadow: none !important; margin-bottom: 10px !important; }
        .badge { border: 1px solid #000 !important; color: #000 !important; background: none !important; }
        .qte-suggeree { font-weight: bold; text-decoration: underline; }
    }
</style>

<br><br><br>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4 d-print-none">
        <div>
            <h2 class="fw-bold m-0"><?= $titre ?></h2>
            <p class="text-muted small">Articles dont le stock est inférieur ou égal au seuil de sécurité.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="javascript:history.back()" class="btn btn-outline-secondary shadow-sm">
                <- Retour
            </a>
            <button onclick="printProfessional()" class="btn btn-dark shadow-sm">
     Imprimer la liste
</button>

<script>
function printProfessional() {
    // 1. Créer un style d'impression dynamique
    const style = document.createElement('style');
    style.innerHTML = `
        @media print {
            header, footer, nav, .navigation, .d-print-none, .btn, .btn-group, .navbar { 
                display: none !important; 
            }
            body { 
                background: white !important; 
                margin: 1.5cm !important; 
            }
            .container, .container-fluid { 
                width: 100% !important; 
                padding: 0 !important; 
                margin: 0 !important; 
            }
            .card { 
                border: 1px solid #eee !important; 
                box-shadow: none !important; 
            }
            .table {
                width: 100% !important;
                border-collapse: collapse !important;
            }
            /* Assure que les couleurs des badges s'impriment */
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    `;
    
    // 2. Ajouter le style au document
    document.head.appendChild(style);
    
    // 3. Lancer l'impression
    window.print();
    
    // 4. Supprimer le style après l'impression pour revenir à la normale
    setTimeout(() => {
        document.head.removeChild(style);
    }, 1000);
}
</script>
        </div>
    </div>
    <br><br><br>
    <div class="d-none d-print-block text-center mb-5">
        <h1 class="fw-bold">BON DE COMMANDE INTERNE</h1>
        <p class="text-muted">Généré le <?= date('d/m/Y à H:i') ?> par <?= $_SESSION['utilisateur_nom'] ?? 'Gestionnaire' ?></p>
        <hr>
    </div>

    
   <br><br><br>
    <div class="row">
    <br><br><br>
        <div class="col-lg-12">
            <?php if (empty($alertes)): ?>
                <div class="card border-0 shadow-sm p-5 text-center" style="border-radius: 15px;">
                    
                    <h4 class="fw-bold">Tout est sous contrôle !</h4>
                    <p class="text-muted">Aucun article ne nécessite de réapprovisionnement pour le moment.</p>
                    <div class="mt-3">
                        <a href="gestion_consommables.php" class="btn btn-primary rounded-pill px-4">Voir tout le stock</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info d-print-none border-0 shadow-sm mb-4" style="border-radius: 12px;">
                     <strong>Astuce :</strong> La "Quantité suggérée" est calculée pour couvrir vos besoins immédiats et reconstituer un stock de confort.
                </div>

                <?php foreach ($alertes as $a): 
                    $is_critical = ($a['stock_actuel'] <= ($a['stock_alerte'] * 0.3));
                    $class = $is_critical ? 'card-critical' : 'card-warning';
                ?>
                <div class="card border-0 shadow-sm mb-3 <?= $class ?>" style="border-radius: 12px;">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <h5 class="fw-bold mb-1"><?= htmlspecialchars($a['nom_article']) ?></h5>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($a['categorie']) ?></span>
                                <div class="mt-2 small text-muted d-print-none">
                                    Fournisseur : <?= htmlspecialchars($a['fournisseur_habituel'] ?? 'Non défini') ?>
                                </div>
                            </div>

                            <div class="col-md-4 text-center">
                                <div class="d-flex justify-content-center align-items-baseline gap-2">
                                    <h3 class="fw-bold <?= $is_critical ? 'text-danger' : 'text-warning' ?> mb-0">
                                        <?= number_format($a['stock_actuel'], 0) ?>
                                    </h3>
                                    <span class="text-muted">/ <?= $a['stock_alerte'] ?> <?= $a['unite_mesure'] ?></span>
                                </div>
                                <small class="text-uppercase fw-bold text-muted small">Stock Actuel / Seuil</small>
                            </div>

                            <div class="col-md-4 text-end">
                                <div class="p-3 bg-light rounded-3 d-inline-block text-center border">
                                    <small class="d-block text-muted text-uppercase mb-1" style="font-size: 10px;">Commander environ</small>
                                    <h4 class="fw-bold m-0 qte-suggeree">
                                        <?= max(0, ceil($a['qte_suggeree'])) ?> <?= $a['unite_mesure'] ?>
                                    </h4>
                                </div>
                                <div class="mt-2 d-print-none">
                                    <a href="passer_commande.php?id=<?= $a['id_consommable'] ?>" class="btn btn-sm btn-primary px-3 rounded-pill">
                                        Commander
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="d-none d-print-block mt-5">
                    <div class="row">
                        <div class="col-6">
                            <p class="fw-bold text-decoration-underline">Signature Magasinier</p>
                            <br><br>
                        </div>
                        <div class="col-6 text-end">
                            <p class="fw-bold text-decoration-underline">Validation Direction</p>
                            <br><br>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>