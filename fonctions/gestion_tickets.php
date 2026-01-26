<?php
// fonctions/gestion_tickets.php

/**
 * Génère un numéro de ticket unique
 */
function genererNumeroTicket($pdo) {
    $prefix = 'TK-' . date('ymd') . '-';
    $sql = "SELECT COUNT(*) as count FROM tickets WHERE numero_ticket LIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$prefix . '%']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $numero = $prefix . str_pad(($result['count'] + 1), 4, '0', STR_PAD_LEFT);
    return $numero;
}

/**
 * Calcule le montant total d'un ticket
 */
function calculerMontantTicket($lignes) {
    $total = 0;
    foreach ($lignes as $ligne) {
        $total += $ligne['sous_total'];
    }
    return $total;
}

/**
 * Récupère un ticket avec toutes ses informations
 */
function getTicketComplet($pdo, $id_ticket) {
    $sql = "SELECT 
                t.*,
                c.nom_client,
                c.prenom_client,
                c.telephone,
                c.email,
                a.nom_agence,
                a.adresse as adresse_agence,
                u.nom_complet as receptionniste
            FROM tickets t
            LEFT JOIN clients c ON t.id_client = c.id_client
            LEFT JOIN agences a ON t.id_agence = a.id_agence
            LEFT JOIN utilisateurs u ON t.id_utilisateur = u.id_utilisateur
            WHERE t.id_ticket = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_ticket]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Récupère les lignes d'un ticket
 */
function getLignesTicket($pdo, $id_ticket) {
    $sql = "SELECT 
                lt.*,
                s.nom_service,
                s.description as description_service,
                cs.nom_categorie
            FROM lignes_ticket lt
            LEFT JOIN services s ON lt.id_service = s.id_service
            LEFT JOIN categories_service cs ON s.id_categorie = cs.id_categorie
            WHERE lt.id_ticket = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_ticket]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Met à jour le statut d'un ticket
 */
function updateStatutTicket($pdo, $id_ticket, $statut) {
    $sql = "UPDATE tickets SET statut = ?, updated_at = NOW() WHERE id_ticket = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$statut, $id_ticket]);
}

/**
 * Récupère les statistiques des tickets
 */
function getStatsTickets($pdo, $id_agence = null) {
    $where = '';
    $params = [];
    
    if ($id_agence) {
        $where = " WHERE t.id_agence = ?";
        $params[] = $id_agence;
    }
    
    $sql = "SELECT 
                COUNT(*) as total_tickets,
                SUM(t.montant_total) as chiffre_affaires,
                SUM(CASE WHEN t.statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,+
                SUM(CASE WHEN t.statut = 'en_traitement' THEN 1 ELSE 0 END) as en_traitement,
                SUM(CASE WHEN t.statut = 'pret' THEN 1 ELSE 0 END) as pret,
                SUM(CASE WHEN t.statut = 'recupere' THEN 1 ELSE 0 END) as recupere,
                SUM(CASE WHEN t.statut = 'annule' THEN 1 ELSE 0 END) as annule,
                AVG(t.montant_total) as moyenne_ticket
            FROM tickets t" . $where;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}