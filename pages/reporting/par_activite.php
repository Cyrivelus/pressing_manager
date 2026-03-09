<?php
/**
 * pages/reporting/par_activite.php
 * Reporting par activité - Version Épurée Professionnelle
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit();
}

require_once realpath(__DIR__ . '/../../fonctions/database.php');

// 1. Initialisation des variables (Évite les Warnings)
$ca_total = 0;
$ca_precedent = 0;
$nb_tickets = 0;
$nb_services = 0;
$ca_par_categorie = [];
$top_services = [];
$perf_agences = [];
$taux_croissance = 0;

// 2. Paramètres de filtrage
$periode = $_GET['periode'] ?? 'mois_courant';
$date_debut = $_GET['debut'] ?? date('Y-m-01');
$date_fin = $_GET['fin'] ?? date('Y-m-d');
$agence_id = $_GET['agence'] ?? 'all';

// (Logique de switch identique pour les dates...)
switch ($periode) {
    case 'aujourdhui': $date_debut = $date_fin = date('Y-m-d'); break;
    case 'mois_courant': $date_debut = date('Y-m-01'); $date_fin = date('Y-m-t'); break;
    case 'annee': $date_debut = date('Y-01-01'); $date_fin = date('Y-12-31'); break;
}

try {
    $params = [':debut' => $date_debut, ':fin' => $date_fin];
    $where_agence = ($agence_id !== 'all') ? " AND t.id_agence = :agence_id" : "";
    if ($agence_id !== 'all') $params[':agence_id'] = $agence_id;

    // Récupération des données essentielles
    $agences = $pdo->query("SELECT id_agence, nom, code_agence FROM agences WHERE est_actif = 1")->fetchAll();
    
    // CA Total
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(lt.sous_total), 0) FROM lignes_ticket lt 
                           JOIN tickets t ON lt.id_ticket = t.id_ticket 
                           WHERE DATE(t.date_depot) BETWEEN :debut AND :fin AND t.statut != 'annule' $where_agence");
    $stmt->execute($params);
    $ca_total = $stmt->fetchColumn() ?: 0;

    // Tickets
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets t WHERE DATE(t.date_depot) BETWEEN :debut AND :fin AND t.statut != 'annule' $where_agence");
    $stmt->execute($params);
    $nb_tickets = $stmt->fetchColumn() ?: 0;

    // CA par catégorie (Correction du Fatal Error count())
    $sql_cat = "SELECT cs.nom_categorie, COALESCE(SUM(lt.sous_total), 0) as ca_categorie, 
                COUNT(DISTINCT t.id_ticket) as nb_tickets
                FROM categories_service cs
                LEFT JOIN services s ON cs.id_categorie = s.id_categorie
                LEFT JOIN lignes_ticket lt ON s.id_service = lt.id_service
                LEFT JOIN tickets t ON lt.id_ticket = t.id_ticket AND DATE(t.date_depot) BETWEEN :debut AND :fin $where_agence
                GROUP BY cs.id_categorie ORDER BY ca_categorie DESC";
    $stmt = $pdo->prepare($sql_cat);
    $stmt->execute($params);
    $ca_par_categorie = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

} catch (Exception $e) {
    $error_message = "Erreur technique : " . $e->getMessage();
}

require_once realpath(__DIR__ . '/../../templates/header.php');
require_once realpath(__DIR__ . '/../../templates/navigation.php');
?>

<style>
:root {
    --brand-dark: #1a252f;    /* Bleu Nuit Profond */
    --brand-main: #34495e;    /* Gris Acier */
    --brand-light: #f4f7f6;   /* Gris Très Clair */
    --accent: #2c3e50;        /* Couleur de contraste */
}

body { background-color: var(--brand-light); color: var(--brand-dark); font-family: 'Segoe UI', Roboto, sans-serif; }

.report-container { padding: 30px; margin-left: 250px; }

/* Cards Épurées - Pas de dégradés */
.stat-box {
    background: #ffffff;
    border: 1px solid #e0e0e0;
    padding: 20px;
    border-radius: 4px; /* Carré professionnel */
}

.stat-value { font-size: 1.5rem; font-weight: bold; color: var(--brand-dark); display: block; }
.stat-label { font-size: 0.85rem; color: #7f8c8d; text-transform: uppercase; }

/* Tableaux Modernes */
.table-custom { width: 100%; background: white; border-collapse: collapse; margin-top: 20px; }
.table-custom th { 
    background: var(--brand-dark); 
    color: white; 
    padding: 12px; 
    text-align: left; 
    font-weight: 500; 
}
.table-custom td { padding: 12px; border-bottom: 1px solid #eee; }

/* Liens sans Glyphs */
.btn-link-clean { color: var(--brand-main); text-decoration: none; font-weight: 600; border-bottom: 1px solid transparent; }
.btn-link-clean:hover { border-bottom: 1px solid var(--brand-main); }

.filter-bar { background: #fff; padding: 20px; border-radius: 4px; border: 1px solid #e0e0e0; margin-bottom: 25px; }

@media (max-width: 992px) { .report-container { margin-left: 0; } }
</style>

<div class="report-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 style="font-weight: 300;">Analyse d'Activité</h1>
        <div>
            <a href="export_pdf.php" class="btn-link-clean" style="margin-right: 20px;">Exporter en PDF</a>
            <a href="export_excel.php" class="btn-link-clean">Exporter en Excel</a>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="stat-label">Période d'analyse</label>
                <select name="periode" class="form-control" onchange="this.form.submit()">
                    <option value="mois_courant" <?= $periode == 'mois_courant' ? 'selected' : '' ?>>Mois en cours</option>
                    <option value="annee" <?= $periode == 'annee' ? 'selected' : '' ?>>Année complète</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="stat-label">Agence</label>
                <select name="agence" class="form-control" onchange="this.form.submit()">
                    <option value="all">Toutes les agences</option>
                    <?php foreach($agences as $a): ?>
                        <option value="<?= $a['id_agence'] ?>" <?= $agence_id == $a['id_agence'] ? 'selected' : '' ?>><?= $a['nom'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-box">
                <span class="stat-label">Chiffre d'Affaires</span>
                <span class="stat-value"><?= number_format($ca_total, 0, '.', ' ') ?> FCFA</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-box">
                <span class="stat-label">Volume de Tickets</span>
                <span class="stat-value"><?= $nb_tickets ?></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-box">
                <span class="stat-label">Performance Catégories</span>
                <span class="stat-value"><?= count($ca_par_categorie) ?></span>
            </div>
        </div>
    </div>

    <div class="stat-box">
        <h3 style="font-size: 1.1rem; margin-bottom: 20px;">Détails par catégorie de service</h3>
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Catégorie</th>
                    <th style="text-align: right;">Volume Tickets</th>
                    <th style="text-align: right;">Chiffre d'Affaires</th>
                    <th style="text-align: right;">Part (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($ca_par_categorie)): ?>
                    <?php foreach($ca_par_categorie as $cat): 
                        $part = ($ca_total > 0) ? ($cat['ca_categorie'] / $ca_total) * 100 : 0;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($cat['nom_categorie']) ?></td>
                        <td style="text-align: right;"><?= $cat['nb_tickets'] ?></td>
                        <td style="text-align: right; font-weight: bold;"><?= number_format($cat['ca_categorie'], 0, '.', ' ') ?></td>
                        <td style="text-align: right; color: #7f8c8d;"><?= number_format($part, 1) ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align: center;">Aucune donnée sur cette période.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php 
require_once realpath(__DIR__ . '/../../templates/footer.php');
?>