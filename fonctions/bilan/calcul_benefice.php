<?php
/**
 * calcul_benefice.php
 * Fonctions de calcul des indicateurs de performance (KPI)
 */

/**
 * Calcule le bénéfice net sur une période donnée
 */
function calculerBilanPeriode($pdo, $date_debut, $date_fin) {
    // 1. Total des Recettes (Tickets encaissés)
    $sql_recettes = "SELECT SUM(montant_total) FROM tickets WHERE date_depot BETWEEN ? AND ?";
    $stmt_r = $pdo->prepare($sql_recettes);
    $stmt_r->execute([$date_debut, $date_fin]);
    $total_recettes = $stmt_r->fetchColumn() ?: 0;

    // 2. Total des Dépenses (Charges, salaires, consommables)
    $sql_charges = "SELECT SUM(montant) FROM depenses WHERE date_depense BETWEEN ? AND ?";
    $stmt_c = $pdo->prepare($sql_charges);
    $stmt_c->execute([$date_debut, $date_fin]);
    $total_charges = $stmt_c->fetchColumn() ?: 0;

    $benefice_net = $total_recettes - $total_charges;
    $marge_pourcentage = ($total_recettes > 0) ? ($benefice_net / $total_recettes) * 100 : 0;

    return [
        'recettes' => $total_recettes,
        'charges' => $total_charges,
        'benefice' => $benefice_net,
        'marge' => round($marge_pourcentage, 2)
    ];
}