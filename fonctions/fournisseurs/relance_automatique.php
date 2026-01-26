<?php
/**
 * relance_automatique.php
 * Système de rappel pour le gérant avant les échéances
 */

/**
 * Récupère les dettes arrivant à échéance demain pour notification
 */
function getEcheancesDemain($pdo) {
    $sql = "SELECT d.*, f.nom_fournisseur, f.telephone 
            FROM dettes_fournisseur d
            JOIN fournisseurs f ON d.id_fournisseur = f.id_fournisseur
            WHERE d.date_echeance = CURRENT_DATE + INTERVAL 1 DAY
            AND d.statut != 'SOLDE'";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Génère un texte de message type pour WhatsApp/SMS à envoyer au fournisseur
 * en cas de besoin de report de paiement.
 */
function genererMessageDelai($nom_fournisseur, $montant, $date_facture) {
    return "Bonjour M. " . $nom_fournisseur . ", concernant notre facture du " . $date_facture . " d'un montant de " . number_format($montant, 0) . " F, nous rencontrons un léger contretemps. Serait-il possible de décaler le règlement de 48h ? Merci de votre compréhension. - Direction Pressing.";
}