<?php
/**
 * Enregistre les exigences techniques spécifiques
 */
function sauvegarderPreferencesQualite($pdo, $id_client, $preferences) {
    // $preferences = ['cintre' => true, 'amidon' => 'fort', 'parfum' => 'lavande']
    $json_prefs = json_encode($preferences);
    
    $sql = "UPDATE clients SET preferences_techniques = ? WHERE id_client = ?";
    return $pdo->prepare($sql)->execute([$json_prefs, $id_client]);
}

/**
 * Récupère les préférences pour impression sur le ticket atelier
 */
function obtenirInstructionsAtelier($pdo, $id_client) {
    $res = $pdo->query("SELECT preferences_techniques FROM clients WHERE id_client = $id_client")->fetchColumn();
    return json_decode($res, true) ?: ['standard' => 'Pas de consignes particulières'];
}