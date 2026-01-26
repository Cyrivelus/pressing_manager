<?php
/**
 * Exporte les ventes au format CSV pour la comptabilité
 */
function genererExportCompta($date_debut, $date_fin) {
    global $pdo;
    $sql = "SELECT t.date_depot, t.numero_ticket, t.montant_hors_taxe, t.montant_tva, t.montant_total, 
                   m.label as mode_paiement
            FROM tickets t
            JOIN modes_paiement m ON t.id_mode_paiement = m.id_mode
            WHERE t.date_depot BETWEEN ? AND ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date_debut, $date_fin]);
    $ventes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Référence', 'HT', 'TVA', 'TTC', 'Journal']);
    
    foreach ($ventes as $v) {
        fputcsv($output, [$v['date_depot'], $v['numero_ticket'], $v['montant_hors_taxe'], 
                          $v['montant_tva'], $v['montant_total'], 'VT_PRESSING']);
    }
    fclose($output);
}