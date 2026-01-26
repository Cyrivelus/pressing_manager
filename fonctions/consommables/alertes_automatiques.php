<?php
/**
 * Vérifie les articles proches de la rupture
 * @return array Liste des articles sous le seuil critique
 */
function verifierSeuilsCritiques($pdo) {
    $sql = "SELECT nom_article, stock_actuel, seuil_alerte, fournisseur_principal 
            FROM consommables 
            WHERE stock_actuel <= seuil_alerte AND statut = 'actif'";
    
    $alertes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($alertes)) {
        // Déclencher une notification pour le gérant (via /fonctions/notifications.php)
        notifierAlerteStock($alertes);
    }
    
    return $alertes;
}