<?php
/**
 * rapport_financier.php
 * Prépare les données pour les rapports PDF ou Excel
 */

/**
 * Génère un tableau comparatif Mois M vs Mois M-1
 */
function comparerMoisPrecedent($pdo) {
    $mois_actuel = date('m');
    $mois_dernier = date('m', strtotime("-1 month"));
    
    $stats_m = $pdo->query("SELECT SUM(montant_total) FROM tickets WHERE MONTH(date_depot) = $mois_actuel")->fetchColumn() ?: 0;
    $stats_m_1 = $pdo->query("SELECT SUM(montant_total) FROM tickets WHERE MONTH(date_depot) = $mois_dernier")->fetchColumn() ?: 0;
    
    $croissance = 0;
    if ($stats_m_1 > 0) {
        $croissance = (($stats_m - $stats_m_1) / $stats_m_1) * 100;
    }

    return [
        'actuel' => $stats_m,
        'precedent' => $stats_m_1,
        'croissance' => round($croissance, 2)
    ];
}

/**
 * Liste les flux de trésorerie (Cashflow)
 */
function getFluxTresorerie($pdo, $limit = 10) {
    return $pdo->query("
        (SELECT 'RECETTE' as type, montant_total as montant, date_depot as date_action, 'Ticket Client' as motif FROM tickets)
        UNION
        (SELECT 'DEPENSE' as type, montant, date_depense as date_action, motif FROM depenses)
        ORDER BY date_action DESC LIMIT $limit
    ")->fetchAll(PDO::FETCH_ASSOC);
}