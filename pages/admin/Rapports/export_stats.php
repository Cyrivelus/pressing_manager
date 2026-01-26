<?php
// pages/admin/agences/export_stats.php

session_start();

// Vérifier l'authentification et les permissions
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'patron') {
    header("Location: ../../../index.php?error=Accès non autorisé");
    exit();
}

require_once(__DIR__ . '/../../../fonctions/database.php');
require_once(__DIR__ . '/../../../fonctions/gestion_agences.php');

// Récupérer les paramètres
$id_agence = isset($_GET['id']) ? intval($_GET['id']) : 0;
$periode = $_GET['periode'] ?? 'mois';
$format = $_GET['format'] ?? 'pdf';

// Récupérer l'agence
$agence = getAgenceById($pdo, $id_agence);



// Définir les dates selon la période
$now = new DateTime();
switch ($periode) {
    case 'jour':
        $date_debut = $now->format('Y-m-d');
        $date_fin = $now->format('Y-m-d');
        $titre_periode = 'Aujourd\'hui';
        break;
    case 'semaine':
        $date_debut = $now->modify('-7 days')->format('Y-m-d');
        $date_fin = date('Y-m-d');
        $titre_periode = '7 derniers jours';
        break;
    case 'mois':
        $date_debut = date('Y-m-01');
        $date_fin = date('Y-m-t');
        $titre_periode = date('F Y');
        break;
    case 'annee':
        $date_debut = date('Y-01-01');
        $date_fin = date('Y-12-31');
        $titre_periode = date('Y');
        break;
    default:
        $date_debut = date('Y-m-01');
        $date_fin = date('Y-m-t');
        $titre_periode = date('F Y');
}

// Récupérer les statistiques
$stats_agence = getPerformancesAgence($pdo, $id_agence, $periode);
$top_services = getTopServicesAgence($pdo, $id_agence, $date_debut, $date_fin);
$top_clients = getTopClientsAgence($pdo, $id_agence, $date_debut, $date_fin);

if ($format === 'pdf') {
    // Générer un PDF simple (vous pouvez utiliser une librairie comme TCPDF ou MPDF)
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="statistiques_' . $agence['nom_agence'] . '_' . date('Y-m-d') . '.pdf"');
    
    // Pour l'instant, on redirige vers une version imprimable
    // Vous devriez installer une librairie PDF pour une vraie génération
    echo '<html>
    <head>
        <title>Statistiques ' . htmlspecialchars($agence['nom_agence']) . '</title>
        <style>
            body { font-family: Arial, sans-serif; }
            h1 { color: #333; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h1>Statistiques: ' . htmlspecialchars($agence['nom_agence']) . '</h1>
        <p>Période: ' . $titre_periode . ' (du ' . date('d/m/Y', strtotime($date_debut)) . ' au ' . date('d/m/Y', strtotime($date_fin)) . ')</p>
        
        <h2>Performances</h2>
        <ul>
            <li>Chiffre d\'affaires: ' . number_format($stats_agence['total_ventes'] ?? 0, 0, ',', ' ') . ' FCFA</li>
            <li>Tickets traités: ' . ($stats_agence['total_tickets'] ?? 0) . '</li>
            <li>Taux de complétion: ' . (($stats_agence['total_tickets'] ?? 0) > 0 ? 
                number_format((($stats_agence['tickets_termines'] ?? 0) / ($stats_agence['total_tickets'] ?? 1)) * 100, 1) : 0) . '%</li>
        </ul>
        
        <h2>Top services</h2>
        <table>
            <tr><th>Service</th><th>Quantité</th><th>CA</th></tr>';
    
    foreach ($top_services as $service) {
        echo '<tr>
                <td>' . htmlspecialchars($service['nom_service']) . '</td>
                <td>' . $service['quantite_vendue'] . '</td>
                <td>' . number_format($service['chiffre_affaires'], 0, ',', ' ') . ' FCFA</td>
              </tr>';
    }
    
    echo '</table></body></html>';
    exit;
    
} elseif ($format === 'excel') {
    // Générer un fichier Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="statistiques_' . $agence['nom_agence'] . '_' . date('Y-m-d') . '.xls"');
    
    echo '<table border="1">
            <tr><th colspan="3">Statistiques: ' . htmlspecialchars($agence['nom_agence']) . '</th></tr>
            <tr><th colspan="3">Période: ' . $titre_periode . ' (du ' . date('d/m/Y', strtotime($date_debut)) . ' au ' . date('d/m/Y', strtotime($date_fin)) . ')</th></tr>
            <tr><td colspan="3">&nbsp;</td></tr>
            
            <tr><th colspan="3">Performances</th></tr>
            <tr><th>Indicateur</th><th>Valeur</th><th>Unité</th></tr>
            <tr><td>Chiffre d\'affaires</td><td>' . ($stats_agence['total_ventes'] ?? 0) . '</td><td>FCFA</td></tr>
            <tr><td>Tickets traités</td><td>' . ($stats_agence['total_tickets'] ?? 0) . '</td><td>unités</td></tr>
            <tr><td>Tickets terminés</td><td>' . ($stats_agence['tickets_termines'] ?? 0) . '</td><td>unités</td></tr>
            <tr><td>Taux de complétion</td><td>' . (($stats_agence['total_tickets'] ?? 0) > 0 ? 
                number_format((($stats_agence['tickets_termines'] ?? 0) / ($stats_agence['total_tickets'] ?? 1)) * 100, 1) : 0) . '</td><td>%</td></tr>
            
            <tr><td colspan="3">&nbsp;</td></tr>
            
            <tr><th colspan="3">Top services</th></tr>
            <tr><th>Service</th><th>Quantité</th><th>Chiffre d\'affaires (FCFA)</th></tr>';
    
    foreach ($top_services as $service) {
        echo '<tr>
                <td>' . htmlspecialchars($service['nom_service']) . '</td>
                <td>' . $service['quantite_vendue'] . '</td>
                <td>' . $service['chiffre_affaires'] . '</td>
              </tr>';
    }
    
    echo '</table>';
    exit;
}

// Redirection si format non supporté
header("Location: agence_stats.php?id=" . $id_agence);
exit();
?>