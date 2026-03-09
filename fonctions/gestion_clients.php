<?php
// fonctions/gestion_clients.php

/**
 * =================================================================================
 * GESTION DES LECTURES (SELECT)
 * =================================================================================
 */

/**
 * Récupère la liste de tous les clients actifs
 */
function listerClients($pdo) {
    try {
        $sql = "SELECT * FROM clients WHERE est_actif = 1 ORDER BY nom_client ASC, prenom_client ASC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur listerClients : " . $e->getMessage());
        return [];
    }
}

/**
 * Trouve un client par son identifiant avec le nom de son agence
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
 * Recherche rapide de clients (par nom ou téléphone)
 */
function rechercherClients($pdo, $recherche) {
    $term = "%$recherche%";
    $sql = "SELECT * FROM clients 
            WHERE (nom_client LIKE :t OR prenom_client LIKE :t OR telephone LIKE :t)
            AND est_actif = 1
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':t' => $term]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère les secteurs d'activités liés à un client
 */
function getClientActivites($pdo, $id_client) {
    $sql = "SELECT ca.type_activite, ac.nom_activite
            FROM client_activites ca
            LEFT JOIN activites_config ac ON ca.type_activite = ac.type_activite
            WHERE ca.id_client = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_client]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * =================================================================================
 * GESTION DES ÉCRITURES (INSERT / UPDATE)
 * =================================================================================
 */

/**
 * Ajoute un nouveau client avec gestion multi-activités (Pressing, Hôtel, Boutique)
 */
function ajouterNouveauClient($pdo, $donnees) {
    try {
        $pdo->beginTransaction();

        // 1. Insertion de base
        $sql = "INSERT INTO clients (
                    nom_client, prenom_client, telephone, email, 
                    adresse, remise_speciale, notes, id_agence, 
                    date_creation, est_actif
                ) VALUES (
                    :nom, :prenom, :tel, :email, 
                    :adr, :remise, :notes, :id_agence, 
                    NOW(), 1
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nom'       => strtoupper($donnees['nom_client']),
            ':prenom'    => ucwords(strtolower($donnees['prenom_client'] ?? '')),
            ':tel'       => $donnees['telephone'],
            ':email'     => $donnees['email'] ?: null,
            ':adr'       => $donnees['adresse'] ?: null,
            ':remise'    => $donnees['remise_speciale'] ?? 0,
            ':notes'     => $donnees['notes'] ?: null,
            ':id_agence' => $donnees['id_agence']
        ]);
        
        $id_client = $pdo->lastInsertId();

        // 2. Gestion des activités (Catégorisation)
        if (!empty($donnees['activites_client'])) {
            $stmtActivite = $pdo->prepare("INSERT INTO client_activites (id_client, type_activite, date_premiere_visite) VALUES (?, ?, NOW())");
            
            foreach ($donnees['activites_client'] as $activite) {
                if (in_array($activite, ['pressing', 'commerce', 'hotel'])) {
                    $stmtActivite->execute([$id_client, $activite]);

                    // Initialisation des tables spécifiques
                    if ($activite === 'hotel') {
                        $pdo->prepare("INSERT INTO hotel_clients (id_client, statut, created_at) VALUES (?, 'prospect', NOW())")
                            ->execute([$id_client]);
                    }
                    if ($activite === 'commerce') {
                        $pdo->prepare("INSERT INTO commerce_clients (id_client, points_fidelite, created_at) VALUES (?, 0, NOW())")
                            ->execute([$id_client]);
                    }
                }
            }
        }

        $pdo->commit();
        return $id_client;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception("Erreur lors de la création du client : " . $e->getMessage());
    }
}

/**
 * Met à jour les informations d'un client et ses activités
 */
function mettreAJourClient($pdo, $id_client, $data) {
    try {
        $pdo->beginTransaction();

        // 1. Mise à jour infos de base
        $sql = "UPDATE clients SET 
                    nom_client = :nom, prenom_client = :prenom, telephone = :tel, 
                    email = :email, adresse = :adr, remise_speciale = :remise, 
                    notes = :notes, est_actif = :statut
                WHERE id_client = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nom'    => strtoupper($data['nom_client']),
            ':prenom' => ucwords(strtolower($data['prenom_client'] ?? '')),
            ':tel'    => $data['telephone'],
            ':email'  => $data['email'] ?: null,
            ':adr'    => $data['adresse'] ?: null,
            ':remise' => $data['remise_speciale'] ?? 0,
            ':notes'  => $data['notes'] ?: null,
            ':statut' => $data['est_actif'] ?? 1,
            ':id'     => $id_client
        ]);

        // 2. Mise à jour des activités (On remplace les anciennes par les nouvelles)
        if (isset($data['activites_client'])) {
            $pdo->prepare("DELETE FROM client_activites WHERE id_client = ?")->execute([$id_client]);
            $stmtInsert = $pdo->prepare("INSERT INTO client_activites (id_client, type_activite, date_premiere_visite) VALUES (?, ?, NOW())");
            
            foreach ($data['activites_client'] as $activite) {
                if (in_array($activite, ['pressing', 'commerce', 'hotel'])) {
                    $stmtInsert->execute([$id_client, $activite]);
                }
            }
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Supprime un client ou le désactive si historique présent
 */
function supprimerClient($pdo, $id_client) {
    try {
        // On vérifie d'abord s'il y a des tickets
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE id_client = ?");
        $stmt->execute([$id_client]);
        
        if ($stmt->fetchColumn() > 0) {
            // Désactivation simple pour préserver l'historique
            return $pdo->prepare("UPDATE clients SET est_actif = 0 WHERE id_client = ?")->execute([$id_client]);
        } else {
            // Suppression réelle car pas d'historique
            return $pdo->prepare("DELETE FROM clients WHERE id_client = ?")->execute([$id_client]);
        }
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la suppression.");
    }
}