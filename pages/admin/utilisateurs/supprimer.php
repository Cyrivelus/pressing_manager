<?php
session_start();
require_once "../../../fonctions/database.php"; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $mon_id = $_SESSION['utilisateur_id']; 

    // 1. Sécurité : On ne se supprime pas soi-même
    if ($id === $mon_id) {
        header("Location: index.php?error=self_delete");
        exit();
    }

    try {
        // 2. SUPPRESSION DÉFINITIVE
        $sql = "DELETE FROM utilisateurs WHERE id_utilisateur = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        // 3. Redirection avec succès
        header("Location: index.php?msg=deleted_final");
        exit();

    } catch (PDOException $e) {
        // 4. Gestion de l'échec dû aux contraintes d'intégrité
        // Si l'utilisateur a créé des tickets ou gère une agence, MySQL bloque le DELETE.
        header("Location: index.php?error=sql_constraint&details=has_history");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}