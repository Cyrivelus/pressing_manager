<?php
/**
 * Met à jour le prix d'un service et archive l'ancien prix
 */
function modifierPrixService($id_service, $nouveau_prix, $id_utilisateur, $pdo) {
    try {
        $pdo->beginTransaction();

        // 1. Récupérer l'ancien prix pour l'historique
        $stmt = $pdo->prepare("SELECT prix_unitaire FROM services WHERE id_service = ?");
        $stmt->execute([$id_service]);
        $ancien_prix = $stmt->fetchColumn();

        // 2. Mettre à jour le service
        $update = $pdo->prepare("UPDATE services SET prix_unitaire = ? WHERE id_service = ?");
        $update->execute([$nouveau_prix, $id_service]);

        // 3. Enregistrer dans l'historique
        $log = $pdo->prepare("INSERT INTO historique_tarifs (id_service, ancien_prix, nouveau_prix, id_utilisateur) VALUES (?, ?, ?, ?)");
        $log->execute([$id_service, $ancien_prix, $nouveau_prix, $id_utilisateur]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}