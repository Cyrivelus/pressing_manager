<?php
/**
 * MODULE DE GESTION DES SERVICES
 * Pressing Manager - Logique métier
 */

// --- SECTION : CATÉGORIES ---

/**
 * Récupère toutes les catégories de services
 */
function getAllCategories($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM categories_service ORDER BY nom_categorie ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur getAllCategories : " . $e->getMessage());
        return [];
    }
}

/**
 * Ajoute une nouvelle catégorie
 */
function addCategorie($pdo, $nom, $description, $delai, $prix_base, $couleur) {
    $sql = "INSERT INTO categories_service (nom_categorie, description, delai_standard, prix_base, couleur_etiquette) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$nom, $description, $delai, $prix_base, $couleur]);
}

// --- SECTION : SERVICES ---

/**
 * Récupère tous les services avec les informations de leur catégorie
 */
function getAllServices($pdo) {
    try {
        $sql = "SELECT s.*, c.nom_categorie, c.couleur_etiquette 
                FROM services s
                LEFT JOIN categories_service c ON s.id_categorie = c.id_categorie
                ORDER BY c.nom_categorie ASC, s.nom_service ASC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur getAllServices : " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les services d'une catégorie spécifique
 */
function getServicesByCategorie($pdo, $id_categorie) {
    $sql = "SELECT s.*, c.nom_categorie 
            FROM services s
            JOIN categories_service c ON s.id_categorie = c.id_categorie
            WHERE s.id_categorie = :id_cat
            ORDER BY s.nom_service ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_cat' => $id_categorie]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Ajoute un nouveau service au catalogue
 */
function addService($pdo, $data) {
    $sql = "INSERT INTO services (nom_service, id_categorie, description, prix_unitaire, duree_estimee, unite_mesure, est_disponible) 
            VALUES (:nom, :id_cat, :desc, :prix, :duree, :unite, :dispo)";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        'nom'   => $data['nom_service'],
        'id_cat' => $data['id_categorie'],
        'desc'  => $data['description'] ?? null,
        'prix'  => $data['prix_unitaire'],
        'duree' => $data['duree_estimee'] ?? 0,
        'unite' => $data['unite_mesure'] ?? 'pièce',
        'dispo' => $data['est_disponible'] ?? 1
    ]);
}

/**
 * Met à jour un service existant
 */
function updateService($pdo, $id_service, $data) {
    $sql = "UPDATE services SET 
                nom_service = :nom, 
                id_categorie = :id_cat, 
                prix_unitaire = :prix, 
                unite_mesure = :unite,
                est_disponible = :dispo
            WHERE id_service = :id";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        'nom'   => $data['nom_service'],
        'id_cat' => $data['id_categorie'],
        'prix'  => $data['prix_unitaire'],
        'unite' => $data['unite_mesure'],
        'dispo' => $data['est_disponible'],
        'id'    => $id_service
    ]);
}

/**
 * Supprime un service (uniquement s'il n'est pas utilisé dans un ticket)
 */
function deleteService($pdo, $id_service) {
    // Vérifier si le service est utilisé dans lignes_ticket
    $check = $pdo->prepare("SELECT COUNT(*) FROM lignes_ticket WHERE id_service = ?");
    $check->execute([$id_service]);
    
    if ($check->fetchColumn() > 0) {
        // Désactivation au lieu de suppression pour garder l'historique
        $stmt = $pdo->prepare("UPDATE services SET est_disponible = 0 WHERE id_service = ?");
        return $stmt->execute([$id_service]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM services WHERE id_service = ?");
        return $stmt->execute([$id_service]);
    }
}