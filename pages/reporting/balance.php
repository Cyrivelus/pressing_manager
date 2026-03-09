<?php
// pages/comptabilite/bilan.php
session_start();

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit();
}

require_once '../../fonctions/database.php';

$titre = 'Bilan Financier Professionnel';
$asOfDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

try {
    // --- 1. ACTIFS (Ce que l'entreprise possède) ---
    $actifs = [];
    
    // Caisse & Banques
    $sqlCaisse = "SELECT 
                    SUM(CASE WHEN mode_paiement = 'especes' THEN montant ELSE 0 END) as especes,
                    SUM(CASE WHEN mode_paiement = 'carte' THEN montant ELSE 0 END) as carte,
                    SUM(CASE WHEN mode_paiement = 'mobile' THEN montant ELSE 0 END) as mobile
                  FROM paiements WHERE DATE(date_paiement) <= ?";
    $stmt = $pdo->prepare($sqlCaisse);
    $stmt->execute([$asOfDate]);
    $resCaisse = $stmt->fetch();

    $actifs[] = ['code' => '521', 'nom' => 'Disponibilités (Espèces)', 'montant' => $resCaisse['especes'] ?? 0];
    $actifs[] = ['code' => '522', 'nom' => 'Banque / Carte Bancaire', 'montant' => $resCaisse['carte'] ?? 0];
    $actifs[] = ['code' => '571', 'nom' => 'Mobile Money', 'montant' => $resCaisse['mobile'] ?? 0];

    // Créances Clients (Tickets non encore payés)
    $sqlCreances = "SELECT SUM(montant_total - montant_verse) as total FROM tickets 
                    WHERE statut != 'annule' AND DATE(date_depot) <= ?";
    $stmt = $pdo->prepare($sqlCreances);
    $stmt->execute([$asOfDate]);
    $resCreances = $stmt->fetch();
    $actifs[] = ['code' => '411', 'nom' => 'Créances Clients (Tickets à encaisser)', 'montant' => $resCreances['total'] ?? 0];

    // --- 2. PASSIFS (Dettes & Capitaux) ---
    $passifs = [];
    
    // Dettes fournisseurs
    $sqlDettes = "SELECT SUM(solde_du) as total FROM fournisseurs WHERE est_actif = 1";
    $resDettes = $pdo->query($sqlDettes)->fetch();
    $passifs[] = ['code' => '401', 'nom' => 'Dettes Fournisseurs', 'montant' => $resDettes['total'] ?? 0];

    // Résultat (Chiffre d'Affaires - Dépenses)
    $sqlCA = "SELECT SUM(montant_total) as total FROM tickets WHERE statut != 'annule' AND DATE(date_depot) <= ?";
    $stmt = $pdo->prepare($sqlCA);
    $stmt->execute([$asOfDate]);
    $resCA = $stmt->fetch();

    $sqlDep = "SELECT SUM(montant) as total FROM depenses WHERE DATE(date_depense) <= ?";
    $stmt = $pdo->prepare($sqlDep);
    $stmt->execute([$asOfDate]);
    $resDep = $stmt->fetch();

    $resultatNet = ($resCA['total'] ?? 0) - ($resDep['total'] ?? 0);
    $passifs[] = ['code' => '131', 'nom' => 'Résultat Net (Bénéfice/Perte)', 'montant' => $resultatNet];

    // Calculs finaux
    $totalActifs = array_sum(array_column($actifs, 'montant'));
    $totalPassifs = array_sum(array_column($passifs, 'montant'));

} catch (Exception $e) {
    $error = "Erreur de calcul : " . $e->getMessage();
}

require_once('../../templates/header.php');
require_once('../../templates/navigation.php');
?>

<style>
    :root {
        --dark-navy: #1e293b;
        --soft-gray: #f8fafc;
        --border-color: #e2e8f0;
        --accent-blue: #0ea5e9;
    }

    .content-wrapper {
        margin-left: 140px;
        padding: 40px;
        background-color: var(--soft-gray);
        min-height: 100vh;
        font-family: 'Inter', -apple-system, sans-serif;
    }

    /* En-tête minimaliste */
    .report-header {
        border-bottom: 2px solid var(--dark-navy);
        padding-bottom: 20px;
        margin-bottom: 30px;
    }

    .report-title {
        text-transform: uppercase;
        letter-spacing: 2px;
        font-weight: 800;
        color: var(--dark-navy);
        margin: 0;
    }

    /* Grille de statistiques propres */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border: 1px solid var(--border-color);
        border-radius: 4px;
    }

    .stat-label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 600;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark-navy);
    }

    /* Tableau style comptable */
    .ledger-table {
        width: 100%;
        background: white;
        border-collapse: collapse;
        border: 1px solid var(--border-color);
    }

    .ledger-table th {
        background: #f1f5f9;
        color: var(--dark-navy);
        text-align: left;
        padding: 12px 15px;
        font-size: 0.8rem;
        border-bottom: 2px solid var(--border-color);
    }

    .ledger-table td {
        padding: 12px 15px;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.9rem;
    }

    .ledger-table .code-cell {
        color: #94a3b8;
        font-family: monospace;
        width: 80px;
    }

    .amount-cell {
        text-align: right;
        font-weight: 600;
        font-family: 'JetBrains Mono', monospace;
    }

    .total-row {
        background: var(--dark-navy);
        color: white;
    }

    .total-row td {
        padding: 15px;
        font-weight: 700;
        font-size: 1rem;
    }

    /* Boutons de contrôle */
    .btn-report {
        background: white;
        border: 1px solid var(--border-color);
        padding: 8px 16px;
        font-size: 0.85rem;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-report:hover {
        background: var(--soft-gray);
        border-color: #cbd5e1;
    }

    @media print {
        .content-wrapper { margin-left: 0; padding: 0; background: white; }
        .no-print { display: none; }
    }
    @media print {
    /* 1. Cacher absolument tout sauf le contenu du rapport */
    header, footer, .sidebar, .navbar, .no-print, .btn-report, nav, aside {
        display: none !important;
    }

    /* 2. Réinitialiser les marges du wrapper pour utiliser toute la feuille */
    .content-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
        width: 100% !important;
        position: absolute;
        top: 0;
        left: 0;
    }

    /* 3. Forcer les couleurs et bordures (parfois désactivées par les navigateurs) */
    .ledger-table {
        border: 1px solid #000 !important;
    }
    .ledger-table th {
        background-color: #f0f0f0 !important;
        -webkit-print-color-adjust: exact;
    }
    .total-row {
        background-color: #1e293b !important;
        color: white !important;
        -webkit-print-color-adjust: exact;
    }

    /* 4. Ajouter un pied de page spécifique à l'imprimé */
    .content-wrapper::after {
        content: "Document généré le " attr(data-date-print) " - Pressing Manager Logiciel";
        display: block;
        text-align: center;
        font-size: 0.7rem;
        margin-top: 50px;
        color: #666;
    }

    /* 5. Éviter de couper les tableaux sur deux pages */
    tr { page-break-inside: avoid; }
}
</style>
<br><br><br>
<div class="content-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <form class="d-flex gap-2">
            <input type="date" name="date" value="<?= $asOfDate ?>" class="form-control form-control-sm">
            <button type="submit" class="btn-report">Générer le rapport</button>
        </form>
        <div>
            <div class="print-only-header mb-4" style="display: none;">
    <div class="d-flex justify-content-between border-bottom pb-3">
        <div>
            <h2 style="margin:0; color:#1e293b;">NOM DE VOTRE ETABLISSEMENT</h2>
            <p style="margin:0; font-size:0.8rem;">Adresse, Ville, Téléphone</p>
            <p style="margin:0; font-size:0.8rem;">RC: XXXXXXXX | NIF: XXXXXXXX</p>
        </div>
        <div class="text-right">
            <h3 style="margin:0;">BILAN FINANCIER</h3>
            <p style="margin:0;">Période : <?= date('d/m/Y', strtotime($asOfDate)) ?></p>
        </div>
    </div>
</div>

<style>
    /* Afficher le header spécial uniquement à l'impression */
    @media print {
        .print-only-header {
            display: block !important;
        }
    }
</style>
         <div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div class="filter-section">
        <form class="d-flex gap-2">
            <input type="date" name="date" value="<?= $asOfDate ?>" class="form-control form-control-sm">
            <button type="submit" class="btn-report">Actualiser</button>
        </form>
    </div>
    <div class="action-buttons d-flex gap-2">
        <button onclick="window.print()" class="btn-report d-flex align-items-center gap-2">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><path d="M6 14h12v8H6z"></path></svg>
            Imprimer l'état
        </button>
        <button class="btn-report d-flex align-items-center gap-2" style="background: #15803d; color: white; border: none;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Exporter Excel
        </button>
    </div>
</div>
        </div>
    </div>

    <div class="report-header d-flex justify-content-between align-items-end">
        <div>
            <h1 class="report-title">Bilan de Situation</h1>
            <p class="text-muted mb-0">Arrêté au <?= date('d/m/Y', strtotime($asOfDate)) ?></p>
        </div>
        <div class="text-right">
            <h5 class="mb-0">KAYADE MANAGER V3</h5>
            <small class="text-muted">Rapport Comptable Officiel</small>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card" style="border-left: 4px solid var(--accent-blue);">
            <div class="stat-label">Chiffre d'Affaires Global</div>
            <div class="stat-value"><?= number_format($resCA['total'], 0, '.', ' ') ?> <small>FCFA</small></div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #f43f5e;">
            <div class="stat-label">Dépenses Cumulées</div>
            <div class="stat-value"><?= number_format($resDep['total'], 0, '.', ' ') ?> <small>FCFA</small></div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #10b981;">
            <div class="stat-label">Résultat Net</div>
            <div class="stat-value"><?= number_format($resultatNet, 0, '.', ' ') ?> <small>FCFA</small></div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <h5 class="mb-3 text-muted">ACTIF (Emplois)</h5>
            <table class="ledger-table">
                <thead>
                    <tr>
                        <th class="code-cell">Code</th>
                        <th>Désignation du poste</th>
                        <th class="amount-cell">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($actifs as $a): ?>
                    <tr>
                        <td class="code-cell"><?= $a['code'] ?></td>
                        <td><?= $a['nom'] ?></td>
                        <td class="amount-cell"><?= number_format($a['montant'], 0, '.', ' ') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2">TOTAL ACTIF</td>
                        <td class="amount-cell"><?= number_format($totalActifs, 0, '.', ' ') ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="col-lg-6">
            <h5 class="mb-3 text-muted">PASSIF (Ressources)</h5>
            <table class="ledger-table">
                <thead>
                    <tr>
                        <th class="code-cell">Code</th>
                        <th>Désignation du poste</th>
                        <th class="amount-cell">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($passifs as $p): ?>
                    <tr>
                        <td class="code-cell"><?= $p['code'] ?></td>
                        <td><?= $p['nom'] ?></td>
                        <td class="amount-cell"><?= number_format($p['montant'], 0, '.', ' ') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td class="code-cell">101</td>
                        <td>Capital Social / Apports</td>
                        <td class="amount-cell"><?= number_format($totalActifs - $totalPassifs, 0, '.', ' ') ?></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2">TOTAL PASSIF</td>
                        <td class="amount-cell"><?= number_format($totalActifs, 0, '.', ' ') ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="mt-5 p-4 bg-white border rounded">
        <h6 class="text-uppercase font-weight-bold mb-3" style="font-size: 0.7rem; color: #64748b;">Note d'analyse</h6>
        <p class="small text-muted mb-0">
            Ce bilan présente la situation patrimoniale de l'établissement. L'équilibre Actif/Passif est maintenu par l'ajustement du résultat net de l'exercice. 
            Toutes les valeurs sont exprimées en Francs CFA (XAF).
        </p>
    </div>
</div>

<?php require_once('../../templates/footer.php'); ?>