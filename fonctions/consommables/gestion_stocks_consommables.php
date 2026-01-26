<?php
/**
 * Déduit les consommables en fonction des cycles de lavage effectués
 * @param string $type_service (ex: 'NETTOYAGE_SEC')
 * @param int $nb_cycles
 */
function deduireConsommationAutomatique($pdo, $type_service, $nb_cycles = 1) {
    // 1. Récupérer la recette technique (ex: 1 cycle = 150ml de solvant)
    $sql_recette = "SELECT id_article, quantite_par_cycle FROM recettes_techniques WHERE code_service = ?";
    $stmt = $pdo->prepare($sql_recette);
    $stmt->execute([$type_service]);
    $ingredients = $stmt->fetchAll();

    foreach ($ingredients as $ing) {
        $quantite_totale = $ing['quantite_par_cycle'] * $nb_cycles;
        
        // 2. Mise à jour du stock physique
        $update = "UPDATE consommables SET stock_actuel = stock_actuel - ? WHERE id_article = ?";
        $pdo->prepare($update)->execute([$quantite_totale, $ing['id_article']]);
    }
}