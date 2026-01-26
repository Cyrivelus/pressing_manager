<?php
// fonctions/gestion_fournisseurs.php

/**
 * Ajouter un fournisseur
 */
function ajouterFournisseur($pdo, $data) {
    try {
        $sql = "INSERT INTO fournisseurs 
                (nom_fournisseur, contact, telephone, email, adresse, categorie, solde_du, est_actif) 
                VALUES (:nom_fournisseur, :contact, :telephone, :email, :adresse, :categorie, :solde_du, 1)";
        
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([
            ':nom_fournisseur' => $data['nom_fournisseur'],
            ':contact' => $data['contact'] ?: null,
            ':telephone' => $data['telephone'] ?: null,
            ':email' => $data['email'] ?: null,
            ':adresse' => $data['adresse'] ?: null,
            ':categorie' => $data['categorie'],
            ':solde_du' => $data['solde_du'] ?: 0.00
        ]);
        
    } catch (PDOException $e) {
        error_log("Erreur ajout fournisseur: " . $e->getMessage());
        return false;
    }
}




/**
 * Mettre à jour un fournisseur
 */
function modifierFournisseur($pdo, $id_fournisseur, $data) {
    try {
        $sql = "UPDATE fournisseurs SET 
                nom_fournisseur = :nom_fournisseur,
                contact = :contact,
                telephone = :telephone,
                email = :email,
                adresse = :adresse,
                categorie = :categorie,
                solde_du = :solde_du,
                est_actif = :est_actif
                WHERE id_fournisseur = :id_fournisseur";
        
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([
            ':nom_fournisseur' => $data['nom_fournisseur'],
            ':contact' => $data['contact'] ?: null,
            ':telephone' => $data['telephone'] ?: null,
            ':email' => $data['email'] ?: null,
            ':adresse' => $data['adresse'] ?: null,
            ':categorie' => $data['categorie'],
            ':solde_du' => $data['solde_du'] ?: 0.00,
            ':est_actif' => $data['est_actif'] ? 1 : 0,
            ':id_fournisseur' => $id_fournisseur
        ]);
        
    } catch (PDOException $e) {
        error_log("Erreur modification fournisseur: " . $e->getMessage());
        return false;
    }
}

/**
 * Supprimer un fournisseur (désactivation)
 */
function supprimerFournisseur($pdo, $id_fournisseur) {
    try {
        $sql = "UPDATE fournisseurs SET est_actif = 0 WHERE id_fournisseur = :id_fournisseur";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id_fournisseur' => $id_fournisseur]);
        
    } catch (PDOException $e) {
        error_log("Erreur suppression fournisseur: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupérer un fournisseur par son ID
 */
function getFournisseurById($pdo, $id_fournisseur) {
    try {
        $sql = "SELECT * FROM fournisseurs WHERE id_fournisseur = :id_fournisseur";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_fournisseur' => $id_fournisseur]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erreur récupération fournisseur: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupérer tous les fournisseurs (avec option de filtrage)
 */
function getAllFournisseurs($pdo, $filtres = []) {
    try {
        $sql = "SELECT * FROM fournisseurs WHERE 1=1";
        $params = [];
        
        // Filtre par catégorie
        if (!empty($filtres['categorie'])) {
            $sql .= " AND categorie = :categorie";
            $params[':categorie'] = $filtres['categorie'];
        }
        
        // Filtre par statut
        if (isset($filtres['est_actif'])) {
            $sql .= " AND est_actif = :est_actif";
            $params[':est_actif'] = $filtres['est_actif'];
        } else {
            // Par défaut, seulement les actifs
            $sql .= " AND est_actif = 1";
        }
        
        // Filtre par recherche
        if (!empty($filtres['recherche'])) {
            $sql .= " AND (nom_fournisseur LIKE :recherche OR contact LIKE :recherche)";
            $params[':recherche'] = '%' . $filtres['recherche'] . '%';
        }
        
        $sql .= " ORDER BY nom_fournisseur";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erreur récupération fournisseurs: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtenir les statistiques des fournisseurs
 */
function getStatsFournisseurs($pdo) {
    try {
        $sql = "SELECT 
                COUNT(*) as total_fournisseurs,
                COUNT(CASE WHEN solde_du > 0 THEN 1 END) as fournisseurs_avec_solde,
                SUM(solde_du) as total_soldes,
                AVG(solde_du) as moyenne_solde,
                COUNT(CASE WHEN categorie = 'produit_nettoyage' THEN 1 END) as produits_nettoyage,
                COUNT(CASE WHEN categorie = 'equipement' THEN 1 END) as equipement,
                COUNT(CASE WHEN categorie = 'emballage' THEN 1 END) as emballage,
                COUNT(CASE WHEN categorie = 'autre' THEN 1 END) as autre
                FROM fournisseurs 
                WHERE est_actif = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erreur statistiques fournisseurs: " . $e->getMessage());
        return false;
    }
}