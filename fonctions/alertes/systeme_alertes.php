<?php
/**
 * systeme_alertes.php
 * Gère la détection globale des anomalies (Stock, Retards, Finance)
 */

/**
 * Récupère tous les produits dont le stock est sous le seuil
 */
function detecterStocksCritiques($pdo) {
    $sql = "SELECT id_produit, nom_produit, quantite_stock, seuil_alerte 
            FROM consommables 
            WHERE quantite_stock <= seuil_alerte AND est_actif = 1";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Détecte les tickets déposés depuis plus de X jours et non livrés
 */
function detecterRetardsProduction($pdo, $jours_limite = 3) {
    $sql = "SELECT t.id_ticket, t.numero_ticket, c.nom_client, t.date_depot 
            FROM tickets t
            JOIN clients c ON t.id_client = c.id_client
            WHERE t.statut != 'LIVRE' 
            AND DATEDIFF(NOW(), t.date_depot) >= ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$jours_limite]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Enregistre une alerte dans la table historique pour traçabilité
 */
function logAlerte($pdo, $message, $type = 'STOCK', $id_reference = null) {
    $sql = "INSERT INTO historique_alertes (message_alerte, type_alerte, id_reference) 
            VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$message, $type, $id_reference]);
}