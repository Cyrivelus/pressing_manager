<?php
/**
 * Active un nouvel abonnement pour un client
 */
function activerAbonnement($pdo, $id_client, $id_forfait) {
    $sql_forfait = "SELECT * FROM types_forfaits WHERE id_forfait = ?";
    $stmt = $pdo->prepare($sql_forfait);
    $stmt->execute([$id_forfait]);
    $f = $stmt->fetch();

    $date_debut = date('Y-m-d');
    $date_fin = date('Y-m-d', strtotime("+{$f['validite_mois']} months"));

    $sql = "INSERT INTO abonnements_clients (id_client, id_forfait, date_debut, date_fin, solde_restant, statut)
            VALUES (?, ?, ?, ?, ?, 'actif')";
    
    return $pdo->prepare($sql)->execute([
        $id_client, $id_forfait, $date_debut, $date_fin, $f['valeur_initiale']
    ]);
}

/**
 * Suspend un abonnement (ex: impayé ou demande client)
 */
function suspendreAbonnement($pdo, $id_abonnement) {
    $sql = "UPDATE abonnements_clients SET statut = 'suspendu' WHERE id_abonnement = ?";
    return $pdo->prepare($sql)->execute([$id_abonnement]);
}