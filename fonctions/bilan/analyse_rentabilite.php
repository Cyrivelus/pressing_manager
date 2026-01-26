<?php
/**
 * analyse_rentabilite.php
 * Identifie les services les plus rentables
 */

function getTopServicesRentables($pdo, $limite = 5) {
    $sql = "SELECT s.nom_service, COUNT(lt.id_service) as volume, SUM(lt.prix_ligne) as ca_genere
            FROM ligne_ticket lt
            JOIN services s ON lt.id_service = s.id_service
            GROUP BY s.id_service
            ORDER BY ca_genere DESC
            LIMIT ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limite]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Calcule le coût moyen d'acquisition client
 */
function calculerCoutMoyenTraitement($pdo) {
    $total_charges = $pdo->query("SELECT SUM(montant) FROM depenses")->fetchColumn();
    $total_tickets = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
    
    return ($total_tickets > 0) ? ($total_charges / $total_tickets) : 0;
}