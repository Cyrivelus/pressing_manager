<?php
/**
 * Applique une remise sur un montant total
 */
function appliquerRemise($montant_total, $valeur_remise, $type = 'pourcentage') {
    if ($type === 'pourcentage') {
        return $montant_total * (1 - ($valeur_remise / 100));
    } else {
        // Remise fixe (ex: -500 FCFA)
        return max(0, $montant_total - $valeur_remise);
    }
}

/**
 * Calcule la remise automatique selon les points fidélité du client
 */
function calculerRemiseFidelite($id_client, $pdo) {
    $stmt = $pdo->prepare("SELECT points_fidelite FROM clients WHERE id_client = ?");
    $stmt->execute([$id_client]);
    $points = $stmt->fetchColumn();
    
    // Exemple : 100 points = 5% de remise
    return ($points >= 100) ? 5 : 0;
}