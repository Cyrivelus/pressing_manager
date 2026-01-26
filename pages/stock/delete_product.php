<?php
// Initialisation de la session et mise en tampon de sortie
session_start();
ob_start();

// Connexion à la base de données (Ajustez le chemin selon votre config)
require_once(__DIR__ . '/../../fonctions/database.php'); 




// 2. Vérification de l'existence de l'ID
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_produit = intval($_GET['id']);

    try {
        // Optionnel : Vérifier si le produit est lié à d'autres tables (ventes, etc.)
        // Si vous avez des clés étrangères, cette requête échouera d'elle-même
        
        $stmt = $pdo->prepare("DELETE FROM produits WHERE id_produit = ?");
        $result = $stmt->execute([$id_produit]);

        if ($stmt->rowCount() > 0) {
            // Succès : Redirection vers l'inventaire avec message positif
            header('Location: index.php?status=success&message=produit_supprime');
        } else {
            // Produit inexistant
            header('Location: index.php?status=warning&message=produit_introuvable');
        }

    } catch (PDOException $e) {
        // Gestion d'erreur (ex: violation de contrainte d'intégrité si le produit est dans une vente)
        if ($e->getCode() == '23000') {
            header('Location: index.php?status=error&message=impossible_supprimer_lie');
        } else {
            header('Location: index.php?status=error&message=' . urlencode($e->getMessage()));
        }
    }
} else {
    // Pas d'ID fourni
    header('Location: index.php');
}

ob_end_flush();
?>