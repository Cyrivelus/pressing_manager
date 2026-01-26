<?php
/**
 * Récupère la vue consolidée du tableau de bord client
 * @param int $id_client
 * @return array Données de synthèse (Points, Encours, Statut)
 */
function obtenirResumePortail($pdo, $id_client) {
    $data = [];

    // 1. Solde de points et remises disponibles
    $sql_fidelite = "SELECT points_cumules, code_parrainage FROM clients WHERE id_client = ?";
    $stmt = $pdo->prepare($sql_fidelite);
    $stmt->execute([$id_client]);
    $data['fidelite'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Commandes actuellement à l'atelier (en cours)
    $sql_encours = "SELECT * FROM tickets WHERE id_client = ? AND statut NOT IN ('recupere', 'annule')";
    $stmt_encours = $pdo->prepare($sql_encours);
    $stmt_encours->execute([$id_client]);
    $data['en_cours'] = $stmt_encours->fetchAll(PDO::FETCH_ASSOC);

    // 3. Calcul du statut VIP (ex: basé sur les dépenses annuelles)
    $total_depense = $pdo->query("SELECT SUM(montant_total) FROM tickets WHERE id_client = $id_client")->fetchColumn();
    $data['grade'] = ($total_depense > 500000) ? 'PLATINUM' : (($total_depense > 150000) ? 'GOLD' : 'SILVER');

    return $data;
}