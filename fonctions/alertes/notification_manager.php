<?php
/**
 * notification_manager.php
 * Gère l'envoi effectif des messages aux responsables
 */

/**
 * Formate et envoie une alerte de stock au gérant
 */
function envoyerNotificationStock($produits_critiques) {
    if (empty($produits_critiques)) return false;

    $nb = count($produits_critiques);
    $message = "⚠️ ALERTE KAYADE : $nb produit(s) en rupture imminente.\n";
    
    foreach ($produits_critiques as $p) {
        $message .= "- " . $p['nom_produit'] . " (Restant : " . $p['quantite_stock'] . ")\n";
    }

    // Ici, vous pouvez intégrer une API SMS (ex: Orange SMS, Twilio) ou mail()
    // error_log("SMS Envoyé au Patron : " . $message); 
    
    return true;
}

/**
 * Génère une notification visuelle pour le Dashboard (bulle rouge)
 */
function getNbAlertesActives($pdo) {
    $sql = "SELECT COUNT(*) FROM consommables WHERE quantite_stock <= seuil_alerte";
    return $pdo->query($sql)->fetchColumn();
}