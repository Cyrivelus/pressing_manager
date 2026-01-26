<?php
/**
 * Propose des lots de lavage basés sur le linge en attente
 * @return array Groupes d'articles optimisés (ex: Blancs Coton, Délicat Soie)
 */
function suggererGroupementsLavage($pdo) {
    // On récupère tout ce qui est marqué 'A_LAVER'
    $sql = "SELECT l.*, s.categorie_textile, s.couleur_requise, s.poids_estime
            FROM lignes_ticket l
            JOIN services s ON l.id_service = s.id_service
            WHERE l.etat_avancement = 'RECEPTIONNE' 
            ORDER BY s.categorie_textile, s.couleur_requise";
    
    $articles = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $lots = [];

    foreach ($articles as $art) {
        $cle = $art['categorie_textile'] . '_' . $art['couleur_requise'];
        $lots[$cle]['articles'][] = $art;
        $lots[$cle]['poids_total'] += $art['poids_estime'];
    }

    return $lots; // Utilisé par l'écran atelier pour lancer une machine
}