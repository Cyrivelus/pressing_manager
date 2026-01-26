<?php
// Script destiné à être exécuté par le serveur ou via une requête AJAX
require_once __DIR__ . '/../../fonctions/database.php';

function verifierEtNotifier($pdo) {
    $sql = "SELECT nom_produit, quantite_stock, seuil_alerte FROM consommables WHERE quantite_stock <= seuil_alerte";
    $critiques = $pdo->query($sql)->fetchAll();

    if (!empty($critiques)) {
        $liste = "";
        foreach ($critiques as $c) {
            $liste .= "- " . $c['nom_produit'] . " (Reste: " . $c['quantite_stock'] . ")\n";
        }
        
        // Exemple d'enregistrement de l'alerte dans l'historique
        $log = $pdo->prepare("INSERT INTO historique_alertes (message_alerte, type_alerte) VALUES (?, 'STOCK_CRITIQUE')");
        $log->execute(["Pénurie imminente sur : " . count($critiques) . " produits."]);

        return "Notifications générées pour " . count($critiques) . " produits.";
    }
    return "Stock suffisant.";
}

// Pour test direct
if (isset($_GET['run'])) {
    echo verifierEtNotifier($pdo);
}