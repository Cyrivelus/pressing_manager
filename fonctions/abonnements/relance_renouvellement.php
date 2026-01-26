<?php
/**
 * Identifie les abonnements expirant dans X jours
 * @return array Liste des clients à relancer
 */
function verifierAbonnementsAExpirer($pdo, $jours = 7) {
    $sql = "SELECT a.*, c.nom_client, c.telephone, c.email 
            FROM abonnements_clients a
            JOIN clients c ON a.id_client = c.id_client
            WHERE a.date_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            AND a.statut = 'actif'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$jours]);
    return $stmt->fetchAll();
}

/**
 * Simule l'envoi d'une notification de relance
 */
function envoyerRelance($id_client, $type = 'SMS') {
    // Logique d'appel API SMS ou Mail
    // Update de la table pour marquer 'relance_envoyee = 1'
    return true; 
}