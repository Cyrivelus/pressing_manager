<?php
// pages/comptabilite/bilan.php

// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et a les permissions
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit();
}

// Vérifier les permissions (seulement patron et caissier)
$allowed_roles = ['patron', 'caissier'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header('Location: ../index.php?error=Permission non autorisée');
    exit();
}

// Inclure les fonctions
require_once '../../fonctions/database.php';
require_once '../../fonctions/gestion_reports.php';

$titre = 'Bilan Financier';

// Date par défaut (aujourd'hui)
$asOfDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Récupérer les données du bilan
try {
    // 1. Actifs (Caisse + Valeur du stock)
    $actifs = [];
    
    // Caisse (total des paiements non dépensés)
    $sqlCaisse = "SELECT 
                    'Caisse' as type,
                    'Liquidités disponibles' as description,
                    SUM(CASE 
                        WHEN mode_paiement = 'especes' THEN montant
                        ELSE 0 
                    END) as especes,
                    SUM(CASE 
                        WHEN mode_paiement = 'carte' THEN montant
                        ELSE 0 
                    END) as carte,
                    SUM(CASE 
                        WHEN mode_paiement = 'mobile' THEN montant
                        ELSE 0 
                    END) as mobile,
                    SUM(montant) as total
                  FROM paiements 
                  WHERE DATE(date_paiement) <= ?";
    $stmtCaisse = $pdo->prepare($sqlCaisse);
    $stmtCaisse->execute([$asOfDate]);
    $caisse = $stmtCaisse->fetch(PDO::FETCH_ASSOC);
    
    if ($caisse) {
        $actifs[] = [
            'numero' => '1001',
            'nom' => 'Caisse (Espèces)',
            'solde' => number_format($caisse['especes'] ?? 0, 0, ',', ' ') . ' FCFA'
        ];
        
        $actifs[] = [
            'numero' => '1002',
            'nom' => 'Caisse (Carte)',
            'solde' => number_format($caisse['carte'] ?? 0, 0, ',', ' ') . ' FCFA'
        ];
        
        $actifs[] = [
            'numero' => '1003',
            'nom' => 'Caisse (Mobile)',
            'solde' => number_format($caisse['mobile'] ?? 0, 0, ',', ' ') . ' FCFA'
        ];
    }
    
    // Valeur du stock
    $sqlStock = "SELECT 
                    'Stock' as type,
                    SUM(p.quantite_stock * p.prix_unitaire) as valeur_totale
                 FROM produits p
                 WHERE p.est_actif = 1";
    $stmtStock = $pdo->query($sqlStock);
    $stock = $stmtStock->fetch(PDO::FETCH_ASSOC);
    
    if ($stock && $stock['valeur_totale'] > 0) {
        $actifs[] = [
            'numero' => '2001',
            'nom' => 'Valeur du stock',
            'solde' => number_format($stock['valeur_totale'], 0, ',', ' ') . ' FCFA'
        ];
    }
    
    // Créances clients (tickets non payés)
    $sqlCreances = "SELECT 
                       'Créances' as type,
                       COUNT(*) as nb_tickets,
                       SUM(montant_total - montant_verse) as montant_total
                    FROM tickets 
                    WHERE statut != 'annule' 
                    AND montant_verse < montant_total
                    AND DATE(date_depot) <= ?";
    $stmtCreances = $pdo->prepare($sqlCreances);
    $stmtCreances->execute([$asOfDate]);
    $creances = $stmtCreances->fetch(PDO::FETCH_ASSOC);
    
    if ($creances && $creances['montant_total'] > 0) {
        $actifs[] = [
            'numero' => '3001',
            'nom' => 'Créances clients (' . ($creances['nb_tickets'] ?? 0) . ' tickets)',
            'solde' => number_format($creances['montant_total'], 0, ',', ' ') . ' FCFA'
        ];
    }
    
    // 2. Passifs (Dettes fournisseurs)
    $passifs = [];
    
    $sqlDettes = "SELECT 
                     'Dettes' as type,
                     COUNT(*) as nb_fournisseurs,
                     SUM(solde_du) as total_dettes
                  FROM fournisseurs 
                  WHERE est_actif = 1 
                  AND solde_du > 0";
    $stmtDettes = $pdo->query($sqlDettes);
    $dettes = $stmtDettes->fetch(PDO::FETCH_ASSOC);
    
    if ($dettes && $dettes['total_dettes'] > 0) {
        $passifs[] = [
            'numero' => '4001',
            'nom' => 'Dettes fournisseurs (' . ($dettes['nb_fournisseurs'] ?? 0) . ' fournisseurs)',
            'solde' => number_format($dettes['total_dettes'], 0, ',', ' ') . ' FCFA'
        ];
    }
    
    // 3. Capitaux propres (Résultat d'exploitation)
    $capitaux_propres = [];
    
    // Chiffre d'affaires total
    $sqlCA = "SELECT 
                 'Chiffre d\'affaires' as type,
                 SUM(montant_total) as total_ca
              FROM tickets 
              WHERE statut != 'annule'
              AND DATE(date_depot) <= ?";
    $stmtCA = $pdo->prepare($sqlCA);
    $stmtCA->execute([$asOfDate]);
    $ca = $stmtCA->fetch(PDO::FETCH_ASSOC);
    
    // Dépenses totales
    $sqlDepenses = "SELECT 
                       'Dépenses' as type,
                       SUM(montant) as total_depenses
                    FROM depenses 
                    WHERE DATE(date_depense) <= ?";
    $stmtDepenses = $pdo->prepare($sqlDepenses);
    $stmtDepenses->execute([$asOfDate]);
    $depenses = $stmtDepenses->fetch(PDO::FETCH_ASSOC);
    
    // Bénéfice/Perte
    $benefice = ($ca['total_ca'] ?? 0) - ($depenses['total_depenses'] ?? 0);
    
    $capitaux_propres[] = [
        'numero' => '5001',
        'nom' => 'Chiffre d\'affaires cumulé',
        'solde' => number_format($ca['total_ca'] ?? 0, 0, ',', ' ') . ' FCFA'
    ];
    
    $capitaux_propres[] = [
        'numero' => '5002',
        'nom' => 'Dépenses cumulées',
        'solde' => '-' . number_format($depenses['total_depenses'] ?? 0, 0, ',', ' ') . ' FCFA'
    ];
    
    $capitaux_propres[] = [
        'numero' => '5003',
        'nom' => $benefice >= 0 ? 'Bénéfice net' : 'Perte nette',
        'solde' => number_format($benefice, 0, ',', ' ') . ' FCFA'
    ];
    
    // Calcul des totaux
    $total_actifs = ($caisse['total'] ?? 0) + ($stock['valeur_totale'] ?? 0) + ($creances['montant_total'] ?? 0);
    $total_passifs = $dettes['total_dettes'] ?? 0;
    $total_capitaux = $benefice;
    $total_passifs_capitaux = $total_passifs + $total_capitaux;
    
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des données : " . $e->getMessage();
}

// Inclure les templates
require_once('../../templates/header.php');
require_once('../../templates/navigation.php');
?>

<style>
    /* Structure principale alignée avec la navigation */
    .content-wrapper {
        margin-left: 250px; /* Même largeur que la sidebar */
        padding: 20px;
        transition: all 0.3s;
        background-color: #f8f9fc;
        min-height: 100vh;
    }
    
    /* Quand la sidebar est repliée */
    body.sidebar-collapsed .content-wrapper {
        margin-left: 90px;
    }
    
    /* Responsive pour mobile */
    @media (max-width: 768px) {
        .content-wrapper {
            margin-left: 0 !important;
            padding: 15px;
            margin-top: 70px; /* Pour la navbar fixe */
        }
    }
    
    /* Cartes stylisées */
    .balance-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        margin-bottom: 25px;
        overflow: hidden;
    }
    
    .card-header-balance {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px 12px 0 0 !important;
        padding: 1rem 1.5rem;
    }
    
    /* Tableaux */
    .balance-table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }
    
    .balance-table th {
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background-color: #f8f9fc;
        padding: 12px 15px;
        border-bottom: 2px solid #e9ecef;
    }
    
    .balance-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f3f7;
        vertical-align: middle;
    }
    
    .balance-table tfoot td {
        font-weight: 700;
        font-size: 1.1rem;
        background-color: #f8f9fc;
        border-top: 2px solid #dee2e6;
    }
    
    /* Badges pour les totaux */
    .badge-total {
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 1rem;
    }
    
    .badge-actifs {
        background-color: #d4edda;
        color: #155724;
    }
    
    .badge-passifs {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    /* Animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .animate-fadeIn {
        animation: fadeIn 0.5s ease-out;
    }
    
    /* Responsive spécifique */
    @media (max-width: 992px) {
        .table-responsive {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow-x: auto;
        }
        
        .balance-table {
            min-width: 600px;
        }
    }
    
    /* Style d'impression */
    @media print {
        .content-wrapper {
            margin-left: 0 !important;
            padding: 0;
        }
        
        .no-print {
            display: none !important;
        }
        
        .balance-card {
            box-shadow: none;
            border: 1px solid #dee2e6;
        }
    }
</style>

<div class="content-wrapper animate-fadeIn">
    <!-- Fil d'Ariane -->
    <nav aria-label="breadcrumb" class="mb-4 no-print">
        <ol class="breadcrumb bg-white shadow-sm p-3 rounded">
            <li class="breadcrumb-item">
                <a href="<?= generateUrl('pages/dashboard.php') ?>">
                   
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= generateUrl('pages/comptabilite/index.php') ?>">Comptabilité</a>
            </li>
            <li class="breadcrumb-item active">Bilan financier</li>
        </ol>
    </nav>

    <!-- En-tête -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h1 class="h3 font-weight-bold text-dark mb-1">
             
                Bilan Financier
            </h1>
            <p class="text-muted mb-0">
                Situation au <?= date('d/m/Y', strtotime($asOfDate)) ?>
            </p>
        </div>
        
        <div class="d-flex flex-wrap gap-2 no-print">
            <form action="" method="GET" class="form-inline">
                <div class="input-group input-group-sm">
                    <input type="date" class="form-control" id="date" name="date" 
                           value="<?= htmlspecialchars($asOfDate) ?>"
                           max="<?= date('Y-m-d') ?>">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary">
                           Mettre à jour
                        </button>
                    </div>
                </div>
            </form>
            
            <button type="button" class="btn btn-info btn-sm" onclick="window.print()">
                 Imprimer
            </button>
            
            <button type="button" class="btn btn-success btn-sm" onclick="exportToPDF()">
               PDF
            </button>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      
        <?= htmlspecialchars($error) ?>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Vue d'ensemble -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Total Actifs</h6>
                            <h4 class="mb-0"><?= number_format($total_actifs, 0, ',', ' ') ?> FCFA</h4>
                        </div>
                        <div>
                          
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Total Passifs</h6>
                            <h4 class="mb-0"><?= number_format($total_passifs, 0, ',', ' ') ?> FCFA</h4>
                        </div>
                        <div>
                       
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Capitaux Propres</h6>
                            <h4 class="mb-0"><?= number_format($total_capitaux, 0, ',', ' ') ?> FCFA</h4>
                        </div>
                        <div>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actifs -->
    <div class="card balance-card mb-4">
        <div class="card-header card-header-balance">
            <h4 class="mb-0">
            Actifs
                <span class="badge badge-light float-right"><?= count($actifs) ?> postes</span>
            </h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table balance-table">
                    <thead>
                        <tr>
                            <th class="pl-4">N° Compte</th>
                            <th>Libellé</th>
                            <th class="text-right pr-4">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($actifs)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                               
                                    Aucun actif enregistré
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($actifs as $actif): ?>
                            <tr>
                                <td class="pl-4">
                                    <span class="badge badge-light"><?= $actif['numero'] ?></span>
                                </td>
                                <td>
                                    <div class="font-weight-medium"><?= htmlspecialchars($actif['nom']) ?></div>
                                </td>
                                <td class="text-right pr-4 font-weight-bold text-success">
                                    <?= htmlspecialchars($actif['solde']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="pl-4 font-weight-bold">
                               Total Actifs
                            </td>
                            <td class="text-right pr-4">
                                <span class="badge badge-total badge-actifs">
                                    <?= number_format($total_actifs, 0, ',', ' ') ?> FCFA
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Passifs et Capitaux Propres -->
    <div class="row">
        <div class="col-md-6">
            <div class="card balance-card h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        Passifs
                        <span class="badge badge-light float-right"><?= count($passifs) ?> postes</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table balance-table">
                            <thead>
                                <tr>
                                    <th class="pl-4">N° Compte</th>
                                    <th>Libellé</th>
                                    <th class="text-right pr-4">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($passifs)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">
                                          
                                            Aucune dette
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($passifs as $passif): ?>
                                    <tr>
                                        <td class="pl-4">
                                            <span class="badge badge-light"><?= $passif['numero'] ?></span>
                                        </td>
                                        <td>
                                            <div class="font-weight-medium"><?= htmlspecialchars($passif['nom']) ?></div>
                                        </td>
                                        <td class="text-right pr-4 font-weight-bold text-danger">
                                            <?= htmlspecialchars($passif['solde']) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="pl-4 font-weight-bold">
                                    Total Passifs
                                    </td>
                                    <td class="text-right pr-4">
                                        <span class="badge badge-total badge-passifs">
                                            <?= number_format($total_passifs, 0, ',', ' ') ?> FCFA
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card balance-card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        Capitaux Propres
                        <span class="badge badge-light float-right"><?= count($capitaux_propres) ?> postes</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table balance-table">
                            <thead>
                                <tr>
                                    <th class="pl-4">N° Compte</th>
                                    <th>Libellé</th>
                                    <th class="text-right pr-4">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($capitaux_propres)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">
                                          
                                            Aucun résultat
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($capitaux_propres as $cp): ?>
                                    <tr>
                                        <td class="pl-4">
                                            <span class="badge badge-light"><?= $cp['numero'] ?></span>
                                        </td>
                                        <td>
                                            <div class="font-weight-medium"><?= htmlspecialchars($cp['nom']) ?></div>
                                        </td>
                                        <td class="text-right pr-4 font-weight-bold <?= strpos($cp['solde'], '-') !== false ? 'text-danger' : 'text-success' ?>">
                                            <?= htmlspecialchars($cp['solde']) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="pl-4 font-weight-bold">
                                     Total Capitaux Propres
                                    </td>
                                    <td class="text-right pr-4">
                                        <span class="badge badge-total badge-success">
                                            <?= number_format($total_capitaux, 0, ',', ' ') ?> FCFA
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Équilibre du bilan -->
    <div class="card balance-card mt-4">
        <div class="card-body text-center">
            <h4 class="mb-3">
                Équilibre du Bilan
            </h4>
            
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="alert <?= abs($total_actifs - $total_passifs_capitaux) < 0.01 ? 'alert-success' : 'alert-danger' ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">
                                    <i class="-<?= abs($total_actifs - $total_passifs_capitaux) < 0.01 ? 'check-circle' : 'exclamation-triangle' ?> mr-2"></i>
                                    <?= abs($total_actifs - $total_passifs_capitaux) < 0.01 ? 'Bilan équilibré' : 'Bilan déséquilibré' ?>
                                </h5>
                                <p class="mb-0 small">
                                    Actifs = Passifs + Capitaux Propres
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="h4 mb-0">
                                    <?= number_format($total_actifs, 0, ',', ' ') ?> FCFA
                                </div>
                                <div class="small">
                                    vs <?= number_format($total_passifs_capitaux, 0, ',', ' ') ?> FCFA
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (abs($total_actifs - $total_passifs_capitaux) >= 0.01): ?>
                    <div class="alert alert-warning">
                        <h6 class="alert-heading">
                           Différence constatée
                        </h6>
                        <p class="mb-2">
                            Écart : 
                            <span class="font-weight-bold">
                                <?= number_format(abs($total_actifs - $total_passifs_capitaux), 0, ',', ' ') ?> FCFA
                            </span>
                        </p>
                        <p class="mb-0 small text-muted">
                            Cette différence peut être due à des paiements en cours de traitement 
                            ou des mouvements non comptabilisés.
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction d'export PDF (simulée)
function exportToPDF() {
    // Cette fonction pourrait utiliser une bibliothèque comme jsPDF
    // Pour l'instant, on simule avec une alerte
    Swal.fire({
        title: 'Export PDF',
        text: 'Cette fonctionnalité sera disponible prochainement',
        icon: 'info',
        confirmButtonText: 'OK'
    });
}

// Mise à jour automatique de la date
document.getElementById('date').addEventListener('change', function() {
    if (this.value) {
        document.querySelector('form').submit();
    }
});

// Animation des totaux
document.addEventListener('DOMContentLoaded', function() {
    // Animer les totaux
    const totals = document.querySelectorAll('.badge-total');
    totals.forEach((total, index) => {
        total.style.animationDelay = (index * 0.2) + 's';
    });
});

// Fonction d'impression améliorée
function printBalance() {
    const originalTitle = document.title;
    document.title = 'Bilan Financier - ' + new Date().toLocaleDateString('fr-FR');
    
    // Cacher les éléments non nécessaires
    const noPrintElements = document.querySelectorAll('.no-print');
    noPrintElements.forEach(el => el.style.display = 'none');
    
    window.print();
    
    // Restaurer
    document.title = originalTitle;
    noPrintElements.forEach(el => el.style.display = '');
}
</script>

<?php 
require_once('../../templates/footer.php');
?>