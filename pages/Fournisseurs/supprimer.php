<?php
// pages/Fournisseurs/supprimer.php

ob_start();
require_once(__DIR__ . '/../../templates/navigation.php');

// 1. Vérification des permissions
if (!isset($roleUtilisateur) || !hasPermission($roleUtilisateur, 'gestion_fournisseurs')) {
    header('Location: ' . generateUrl('pages/dashboard.php'));
    exit();
}

// 2. Récupération de l'ID
$fournisseur_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$fournisseur_id) {
    header('Location: ' . generateUrl('pages/Fournisseurs/index.php?msg=ID_manquant'));
    exit();
}

try {
    // 3. Vérifier si le fournisseur existe
    $stmt = $pdo->prepare("SELECT nom FROM fournisseurs WHERE id = ?");
    $stmt->execute([$fournisseur_id]);
    $fournisseur = $stmt->fetch();

    if (!$fournisseur) {
        header('Location: ' . generateUrl('pages/Fournisseurs/index.php?msg=fournisseur_introuvable'));
        exit();
    }

    // 4. GESTION DE L'INTÉGRITÉ DES DONNÉES
    // Option choisie ici : On met à NULL le fournisseur_id dans la table produits 
    // pour ne pas supprimer les produits eux-mêmes (ce qui casserait les stocks).
    
    $pdo->beginTransaction();

    // Détacher les produits liés
    $stmtUpdate = $pdo->prepare("UPDATE produits SET fournisseur_id = NULL WHERE fournisseur_id = ?");
    $stmtUpdate->execute([$fournisseur_id]);

    // Supprimer le fournisseur
    $stmtDelete = $pdo->prepare("DELETE FROM fournisseurs WHERE id = ?");
    $stmtDelete->execute([$fournisseur_id]);

    $pdo->commit();

    // 5. Redirection avec succès
    header('Location: ' . generateUrl('pages/Fournisseurs/index.php?success=1'));
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Erreur souvent liée à une contrainte de clé étrangère (ex: lié à une facture)
    header('Location: ' . generateUrl('pages/Fournisseurs/index.php?error=' . urlencode($e->getMessage())));
    exit();
}

ob_end_flush();