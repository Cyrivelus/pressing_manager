<?php
/**
 * Traite les notifications de paiement entrantes (Webhooks)
 */
function traiterWebhookPaiement($source, $data) {
    global $pdo;
    
    // 1. Log de la réception pour audit
    $logSql = "INSERT INTO logs_api (source, payload, date_reception) VALUES (?, ?, NOW())";
    $pdo->prepare($logSql)->execute([$source, json_encode($data)]);

    // 2. Logique selon le fournisseur (Exemple: Stripe ou Opérateur Local)
    if ($source === 'OM_CAMEROON' && $data['status'] === 'SUCCESS') {
        $id_ticket = $data['external_reference'];
        
        // Mettre à jour le statut du ticket
        $update = "UPDATE tickets SET statut_paiement = 'paye', mode_paiement = 'Mobile Money' 
                   WHERE numero_ticket = ?";
        return $pdo->prepare($update)->execute([$id_ticket]);
    }
    return false;
}