<?php
/**
 * Estime l'heure de fin de traitement d'un article
 * @param int $id_ligne
 * @return string Heure estimée de disponibilité
 */
function estimerHeureFin($pdo, $id_ligne) {
    // 1. Temps de base du service
    $sql = "SELECT s.duree_cycle_minutes FROM lignes_ticket l 
            JOIN services s ON l.id_service = s.id_service WHERE l.id_ligne = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_ligne]);
    $base_time = $stmt->fetchColumn();

    // 2. Coefficient de surcharge (nb d'articles en attente / nb de machines)
    $attente = $pdo->query("SELECT COUNT(*) FROM lignes_ticket WHERE etat_avancement != 'PRET'")->fetchColumn();
    $coeff = ($attente > 50) ? 1.5 : 1.0; 

    $minutes_finales = $base_time * $coeff;
    return date('H:i', strtotime("+$minutes_finales minutes"));
}