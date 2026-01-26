<?php
// fonctions/gestion_clients.php

/**
 * Récupère la liste de tous les clients
 * @param PDO $pdo
 * @return array
 */
function listerClients($pdo) {
    try {
        $sql = "SELECT * FROM clients ORDER BY nom_client ASC, prenom_client ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Erreur lors de la récupération des clients : " . $e->getMessage());
    }
}

/**
 * Trouve un client par son identifiant unique
 * @param PDO $pdo
 * @param int $id_client
 * @return array|false
 */
function trouverClientParId($pdo, $id_client) {
    try {
        $sql = "SELECT c.*, a.nom_agence 
                FROM clients c
                LEFT JOIN agences a ON c.id_agence = a.id_agence
                WHERE c.id_client = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id_client]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Ajoute un nouveau client dans la base de données
 * @param PDO $pdo
 * @param array $data Données du formulaire
 * @return bool|int ID du client inséré ou false
 */
function ajouterNouveauClient($pdo, $data) {
    try {
        $sql = "INSERT INTO clients (
                    nom_client, prenom_client, telephone, email, 
                    adresse, remise_speciale, notes, id_agence, est_actif
                ) VALUES (
                    :nom, :prenom, :tel, :email, 
                    :adr, :remise, :notes, :id_agence, 1
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nom'       => strtoupper($data['nom_client']), // Nom en majuscules
            ':prenom'    => ucwords(strtolower($data['prenom_client'])), // Prénom propre
            ':tel'       => $data['telephone'],
            ':email'     => $data['email'] ?: null,
            ':adr'       => $data['adresse'] ?: null,
            ':remise'    => $data['remise_speciale'] ?: 0,
            ':notes'     => $data['notes'] ?: null,
            ':id_agence' => $data['id_agence']
        ]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        throw new Exception("Erreur d'insertion : " . $e->getMessage());
    }
}

/**
 * Met à jour les informations d'un client existant
 */
function modifierClient($pdo, $id_client, $data) {
    try {
        $sql = "UPDATE clients SET 
                    nom_client = :nom, 
                    prenom_client = :prenom, 
                    telephone = :tel, 
                    email = :email, 
                    adresse = :adr, 
                    remise_speciale = :remise, 
                    notes = :notes,
                    est_actif = :statut
                WHERE id_client = :id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':nom'     => strtoupper($data['nom_client']),
            ':prenom'  => ucwords(strtolower($data['prenom_client'])),
            ':tel'     => $data['telephone'],
            ':email'   => $data['email'],
            ':adr'     => $data['adresse'],
            ':remise'  => $data['remise_speciale'],
            ':notes'   => $data['notes'],
            ':statut'  => $data['est_actif'],
            ':id'      => $id_client
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Supprime un client (Attention : vérifie si le client a des tickets avant)
 */
function supprimerClient($pdo, $id_client) {
    try {
        // Optionnel : On peut préférer désactiver (est_actif = 0) plutôt que supprimer
        // pour garder l'historique comptable.
        $sql = "DELETE FROM clients WHERE id_client = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id_client]);
    } catch (PDOException $e) {
        throw new Exception("Impossible de supprimer le client (il possède peut-être un historique de tickets).");
    }
}

/**
 * Recherche rapide de clients (par nom ou téléphone)
 * Utile pour l'autocomplétion lors de la création d'un ticket
 */
function rechercherClients($pdo, $recherche) {
    $term = "%$recherche%";
    $sql = "SELECT * FROM clients 
            WHERE nom_client LIKE :t 
            OR prenom_client LIKE :t 
            OR telephone LIKE :t 
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':t' => $term]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Gestion de la fidélité : Ajouter des points
 */
function ajouterPointsFidelite($pdo, $id_client, $points) {
    $sql = "UPDATE clients SET points_fidelite = points_fidelite + :p WHERE id_client = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([':p' => $points, ':id' => $id_client]);
}