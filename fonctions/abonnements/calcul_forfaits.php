<?php
/**
 * Calcule le montant restant sur un forfait après un dépôt
 * @param int $id_client
 * @param array $articles_deposes [['id_service' => 1, 'qty' => 2], ...]
 * @return array ['eligible' => bool, 'nouveau_solde' => float, 'economie' => float]
 */
function calculerConsommationForfait($pdo, $id_client, $articles_deposes) {
    // 1. Vérifier si le client a un abonnement actif
    $sql = "SELECT * FROM abonnements_clients 
            WHERE id_client = ? AND statut = 'actif' AND date_fin >= CURDATE()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_client]);
    $abo = $stmt->fetch();

    if (!$abo) return ['eligible' => false];

    $economie = 0;
    // Logique de déduction (Points, Unités ou Montant prépayé)
    // Ici on simule une déduction sur un solde monétaire prépayé
    foreach ($articles_deposes as $item) {
        $economie += ($item['prix_unitaire'] * $item['qty']);
    }

    return [
        'eligible' => true,
        'solde_actuel' => $abo['solde_restant'],
        'nouveau_solde' => $abo['solde_restant'] - $economie,
        'economie_realisee' => $economie
    ];
}