<?php
/**
 * seuils_automatiques.php
 * Calcule les seuils recommandés basés sur la consommation historique
 */

/**
 * Suggère un nouveau seuil basé sur la consommation des 30 derniers jours
 * Formule : Consommation Moyenne Journalière * 7 jours de sécurité
 */
function suggererSeuil($id_produit, $pdo) {
    // On calcule ce qui a été consommé le mois dernier
    $sql = "SELECT SUM(quantite_utilisee) as total_conso 
            FROM mouvement_stock 
            WHERE id_produit = ? 
            AND type_mouvement = 'SORTIE' 
            AND date_mouvement >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_produit]);
    $result = $stmt->fetch();

    if ($result && $result['total_conso'] > 0) {
        $conso_jour = $result['total_conso'] / 30;
        // On recommande un stock de sécurité de 10 jours
        return ceil($conso_jour * 10); 
    }

    return 5; // Seuil par défaut si aucune donnée
}

/**
 * Applique les suggestions à tous les produits (Optimisation de masse)
 */
function autoOptimiserSeuils($pdo) {
    $produits = $pdo->query("SELECT id_produit FROM consommables")->fetchAll();
    foreach ($produits as $p) {
        $nouveau_seuil = suggererSeuil($p['id_produit'], $pdo);
        $pdo->prepare("UPDATE consommables SET seuil_alerte = ? WHERE id_produit = ?")
            ->execute([$nouveau_seuil, $p['id_produit']]);
    }
}