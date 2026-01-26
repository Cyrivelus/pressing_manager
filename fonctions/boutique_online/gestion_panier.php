<?php
/**
 * Ajoute ou met à jour un article dans le panier en session
 */
function modifierPanier($id_article, $quantite, $type = 'produit') {
    if (!isset($_SESSION['panier'])) $_SESSION['panier'] = [];

    if ($quantite <= 0) {
        unset($_SESSION['panier'][$type][$id_article]);
    } else {
        $_SESSION['panier'][$type][$id_article] = $quantite;
    }
    return calculerTotalPanier();
}

/**
 * Calcule le total TTC du panier pour l'affichage
 */
function calculerTotalPanier() {
    $total = 0;
    // Logique de récupération des prix en base de données pour éviter la fraude côté client
    if (isset($_SESSION['panier']['produit'])) {
        // SQL: SELECT prix FROM produits WHERE id IN (...)
    }
    return $total;
}