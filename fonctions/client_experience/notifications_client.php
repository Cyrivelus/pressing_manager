<?php
/**
 * Envoie une notification multicanal selon l'événement
 * @param string $evenement (PRET, RETARD, PROMO)
 */
function envoyerNotificationClient($pdo, $id_client, $evenement, $params = []) {
    $client = $pdo->query("SELECT email, telephone, pref_canal FROM clients WHERE id_client = $id_client")->fetch();
    
    $message = genererTemplateMessage($evenement, $params);

    // Logique de routage intelligente
    if ($client['pref_canal'] === 'SMS') {
        return envoyerSMS($client['telephone'], $message);
    } elseif ($client['pref_canal'] === 'EMAIL') {
        return envoyerEmail($client['email'], "Pressing - Mise à jour", $message);
    }
    return true; 
}

function genererTemplateMessage($type, $p) {
    $templates = [
        'PRET' => "Votre commande #{$p['ticket']} est prête ! Vous pouvez passer la récupérer.",
        'COLLECTE' => "Livreur en route : passage prévu dans environ 15 minutes."
    ];
    return $templates[$type] ?? "Votre pressing vous informe d'une mise à jour de votre dossier.";
}