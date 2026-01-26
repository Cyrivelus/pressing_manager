<?php
/**
 * Met à jour le statut d'expédition d'une commande boutique
 */
function mettreAJourExpedition($commande_id, $statut, $tracking_number = null) {
    global $pdo;
    
    $sql = "UPDATE commandes_boutique SET 
            statut_livraison = ?, 
            date_maj = NOW(), 
            numero_suivi = ? 
            WHERE id_commande = ?";
            
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$statut, $tracking_number, $commande_id]);

    if ($success && $statut == 'EXPEDIE') {
        // Déclencher l'envoi d'un email automatique au client via /fonctions/notifications.php
        notifierClientExpedition($commande_id);
    }
    
    return $success;
}