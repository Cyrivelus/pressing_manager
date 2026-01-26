<?php
/**
 * Suggère une liste de commande optimisée
 * @return array [id_article, quantite_suggeree, cout_estime]
 */
function suggererCommandeFournisseur($pdo) {
    // Analyse de la consommation moyenne sur les 3 derniers mois
    $sql = "SELECT id_article, nom_article, consommation_mensuelle_moy, stock_actuel, conditionnement 
            FROM consommables 
            WHERE stock_actuel < (consommation_mensuelle_moy * 1.5)";
    
    $items = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $suggestion = [];

    foreach ($items as $item) {
        // On commande pour couvrir 2 mois de stock, arrondi au conditionnement supérieur
        $besoin = ($item['consommation_mensuelle_moy'] * 2) - $item['stock_actuel'];
        $nb_unites = ceil($besoin / $item['conditionnement']) * $item['conditionnement'];

        $suggestion[] = [
            'article' => $item['nom_article'],
            'quantite' => $nb_unites,
            'motif' => 'Réapprovisionnement prévisionnel'
        ];
    }
    return $suggestion;
}