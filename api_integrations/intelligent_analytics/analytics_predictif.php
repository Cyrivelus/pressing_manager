<?php
// Algorithme simple de prédiction de charge basé sur l'historique
function predireAffluence($pdo, $date_cible) {
    $jourSemaine = date('N', strtotime($date_cible));
    $stmt = $pdo->prepare("SELECT AVG(nb_tickets) FROM (
        SELECT COUNT(*) as nb_tickets FROM tickets 
        WHERE DAYOFWEEK(date_depot) = ? GROUP BY DATE(date_depot)
    ) as stats");
    $stmt->execute([$jourSemaine]);
    return $stmt->fetchColumn();
}