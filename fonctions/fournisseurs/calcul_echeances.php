<?php
/**
 * calcul_echeances.php
 * Logique de calcul des dates de paiement et alertes de retard
 */

/**
 * Calcule le niveau de risque d'une dette
 * @return string 'CRITIQUE' (en retard), 'URGENT' (< 3 jours), 'OK'
 */
function evaluerUrgenceDette($date_echeance) {
    $aujourdhui = new DateTime();
    $echeance = new DateTime($date_echeance);
    $intervalle = $aujourdhui->diff($echeance);
    
    if ($echeance < $aujourdhui) return 'CRITIQUE';
    if ($intervalle->days <= 3) return 'URGENT';
    return 'OK';
}

/**
 * Calcule la somme totale due par fournisseur pour un reporting rapide
 */
function totalDuParFournisseur($pdo, $id_fournisseur) {
    $stmt = $pdo->prepare("SELECT SUM(reste_a_payer) FROM dettes_fournisseur WHERE id_fournisseur = ? AND statut != 'SOLDE'");
    $stmt->execute([$id_fournisseur]);
    return $stmt->fetchColumn() ?: 0;
}