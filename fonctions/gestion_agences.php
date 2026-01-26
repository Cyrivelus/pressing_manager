<?php
// fonctions/gestion_agences.php

/**
 * Ce fichier contient les fonctions de gestion des agences (pressing)
 * Il interagit avec la table "agences" selon votre schéma.
 */

// --- Fonctions principales de gestion des agences ---

/**
 * Récupère toutes les agences avec leurs responsables
 * @param PDO $pdo Instance de connexion à la base de données
 * @return array Liste des agences
 */
function getAllAgences(PDO $pdo): array {
    try {
        $sql = "SELECT a.*, u.nom_complet as responsable_nom 
                FROM agences a 
                LEFT JOIN utilisateurs u ON a.responsable_id = u.id_utilisateur 
                ORDER BY a.nom_agence";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des agences: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère une agence par son ID
 * @param PDO $pdo Instance de connexion à la base de données
 * @param int $id_agence ID de l'agence
 * @return array|null Tableau de l'agence ou null si non trouvée
 */
function getAgenceById(PDO $pdo, int $id_agence): ?array {
    try {
        $sql = "SELECT a.*, u.nom_complet as responsable_nom 
                FROM agences a 
                LEFT JOIN utilisateurs u ON a.responsable_id = u.id_utilisateur 
                WHERE a.id_agence = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id_agence, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération de l'agence ID $id_agence: " . $e->getMessage());
        return null;
    }
}

/**
 * Récupère une agence par son code (pour compatibilité)
 * @param PDO $pdo Instance de connexion à la base de données
 * @param string $code Code de l'agence
 * @return array|null Tableau de l'agence ou null si non trouvée
 */
function getAgenceByCode(PDO $pdo, string $code): ?array {
    try {
        // Note: Votre table "agences" n'a pas de colonne "code"
        // Cette fonction est conservée pour compatibilité
        // Vous pouvez l'adapter si vous ajoutez un champ code
        return null;
    } catch (PDOException $e) {
        error_log("Erreur dans getAgenceByCode: " . $e->getMessage());
        return null;
    }
}

/**
 * Crée une nouvelle agence
 * @param PDO $pdo Instance de connexion à la base de données
 * @param array $data Données de l'agence
 * @return array Résultat de l'opération
 */
function creerAgence(PDO $pdo, array $data): array {
    try {
        // Validation des données requises
        $required = ['nom_agence', 'adresse', 'telephone'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Le champ '$field' est requis"];
            }
        }
        
        // Insertion de l'agence
        $sql = "INSERT INTO agences 
                (nom_agence, adresse, telephone, email, responsable_id, date_ouverture, est_actif) 
                VALUES 
                (:nom_agence, :adresse, :telephone, :email, :responsable_id, :date_ouverture, :est_actif)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nom_agence', $data['nom_agence']);
        $stmt->bindParam(':adresse', $data['adresse']);
        $stmt->bindParam(':telephone', $data['telephone']);
        $stmt->bindParam(':email', $data['email'] ?? null);
        $stmt->bindParam(':responsable_id', $data['responsable_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindParam(':date_ouverture', $data['date_ouverture'] ?? date('Y-m-d'));
        $stmt->bindParam(':est_actif', $data['est_actif'] ?? 1, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $id = $pdo->lastInsertId();
            return ['success' => true, 'message' => 'Agence créée avec succès', 'id' => $id];
        }
        
        return ['success' => false, 'message' => 'Erreur lors de la création de l\'agence'];
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la création de l'agence: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur technique: ' . $e->getMessage()];
    }
}

/**
 * Modifie une agence existante
 * @param PDO $pdo Instance de connexion à la base de données
 * @param int $id_agence ID de l'agence
 * @param array $data Données à modifier
 * @return array Résultat de l'opération
 */
function modifierAgence(PDO $pdo, int $id_agence, array $data): array {
    try {
        // Vérifier si l'agence existe
        $agence = getAgenceById($pdo, $id_agence);
        if (!$agence) {
            return ['success' => false, 'message' => 'Agence non trouvée'];
        }
        
        // Construire la requête de mise à jour dynamiquement
        $fields = [];
        $params = [':id' => $id_agence];
        
        if (isset($data['nom_agence'])) {
            $fields[] = 'nom_agence = :nom_agence';
            $params[':nom_agence'] = $data['nom_agence'];
        }
        
        if (isset($data['adresse'])) {
            $fields[] = 'adresse = :adresse';
            $params[':adresse'] = $data['adresse'];
        }
        
        if (isset($data['telephone'])) {
            $fields[] = 'telephone = :telephone';
            $params[':telephone'] = $data['telephone'];
        }
        
        if (isset($data['email'])) {
            $fields[] = 'email = :email';
            $params[':email'] = $data['email'];
        }
        
        if (isset($data['responsable_id'])) {
            $fields[] = 'responsable_id = :responsable_id';
            $params[':responsable_id'] = $data['responsable_id'];
        }
        
        if (isset($data['date_ouverture'])) {
            $fields[] = 'date_ouverture = :date_ouverture';
            $params[':date_ouverture'] = $data['date_ouverture'];
        }
        
        if (isset($data['est_actif'])) {
            $fields[] = 'est_actif = :est_actif';
            $params[':est_actif'] = $data['est_actif'];
        }
        
        if (empty($fields)) {
            return ['success' => false, 'message' => 'Aucune donnée à modifier'];
        }
        
        $sql = "UPDATE agences SET " . implode(', ', $fields) . " WHERE id_agence = :id";
        $stmt = $pdo->prepare($sql);
        
        foreach ($params as $key => &$value) {
            $stmt->bindParam($key, $value);
        }
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Agence modifiée avec succès'];
        }
        
        return ['success' => false, 'message' => 'Erreur lors de la modification'];
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la modification de l'agence ID $id_agence: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur technique: ' . $e->getMessage()];
    }
}

/**
 * Supprime une agence
 * @param PDO $pdo Instance de connexion à la base de données
 * @param int $id_agence ID de l'agence
 * @return array Résultat de l'opération
 */
function supprimerAgence(PDO $pdo, int $id_agence): array {
    try {
        // Vérifier si l'agence existe
        $agence = getAgenceById($pdo, $id_agence);
        if (!$agence) {
            return ['success' => false, 'message' => 'Agence non trouvée'];
        }
        
        // Vérifier si l'agence a des dépendances (clients, tickets, etc.)
        $sqlCheckClients = "SELECT COUNT(*) FROM clients WHERE id_agence = :id";
        $stmtCheckClients = $pdo->prepare($sqlCheckClients);
        $stmtCheckClients->bindParam(':id', $id_agence, PDO::PARAM_INT);
        $stmtCheckClients->execute();
        
        if ($stmtCheckClients->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Impossible de supprimer cette agence car elle a des clients associés'];
        }
        
        $sqlCheckTickets = "SELECT COUNT(*) FROM tickets WHERE id_agence = :id";
        $stmtCheckTickets = $pdo->prepare($sqlCheckTickets);
        $stmtCheckTickets->bindParam(':id', $id_agence, PDO::PARAM_INT);
        $stmtCheckTickets->execute();
        
        if ($stmtCheckTickets->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Impossible de supprimer cette agence car elle a des tickets associés'];
        }
        
        // Désactiver plutôt que supprimer (meilleure pratique)
        $sql = "UPDATE agences SET est_actif = 0 WHERE id_agence = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id_agence, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Agence désactivée avec succès'];
        }
        
        return ['success' => false, 'message' => 'Erreur lors de la suppression'];
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la suppression de l'agence ID $id_agence: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur technique: ' . $e->getMessage()];
    }
}

/**
 * Récupère les agences actives
 * @param PDO $pdo Instance de connexion à la base de données
 * @return array Liste des agences actives
 */
function getAgencesActives(PDO $pdo): array {
    try {
        $sql = "SELECT a.*, u.nom_complet as responsable_nom 
                FROM agences a 
                LEFT JOIN utilisateurs u ON a.responsable_id = u.id_utilisateur 
                WHERE a.est_actif = 1 
                ORDER BY a.nom_agence";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des agences actives: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les statistiques des agences
 * @param PDO $pdo Instance de connexion à la base de données
 * @return array Statistiques des agences
 */
function getStatistiquesAgences(PDO $pdo): array {
    try {
        $statistiques = [];
        
        // Total agences
        $sqlTotal = "SELECT COUNT(*) as total FROM agences";
        $stmtTotal = $pdo->query($sqlTotal);
        $statistiques['total'] = $stmtTotal->fetchColumn();
        
        // Agences actives
        $sqlActives = "SELECT COUNT(*) as actives FROM agences WHERE est_actif = 1";
        $stmtActives = $pdo->query($sqlActives);
        $statistiques['actives'] = $stmtActives->fetchColumn();
        
        // Agences inactives
        $statistiques['inactives'] = $statistiques['total'] - $statistiques['actives'];
        
        // Agences par responsable
        $sqlResponsables = "SELECT u.nom_complet, COUNT(a.id_agence) as nombre_agences
                           FROM utilisateurs u
                           LEFT JOIN agences a ON u.id_utilisateur = a.responsable_id
                           WHERE u.est_actif = 1
                           GROUP BY u.id_utilisateur, u.nom_complet
                           HAVING COUNT(a.id_agence) > 0
                           ORDER BY nombre_agences DESC";
        $stmtResponsables = $pdo->query($sqlResponsables);
        $statistiques['par_responsable'] = $stmtResponsables->fetchAll(PDO::FETCH_ASSOC);
        
        // Répartition géographique (par adresse)
        $sqlGeo = "SELECT 
                    CASE 
                        WHEN adresse LIKE '%Paris%' THEN 'Paris'
                        WHEN adresse LIKE '%Lyon%' THEN 'Lyon'
                        WHEN adresse LIKE '%Marseille%' THEN 'Marseille'
                        ELSE 'Autre'
                    END as ville,
                    COUNT(*) as nombre
                   FROM agences
                   WHERE est_actif = 1
                   GROUP BY ville
                   ORDER BY nombre DESC";
        $stmtGeo = $pdo->query($sqlGeo);
        $statistiques['par_ville'] = $stmtGeo->fetchAll(PDO::FETCH_ASSOC);
        
        return $statistiques;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques agences: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les utilisateurs pouvant être responsables d'agence
 * @param PDO $pdo Instance de connexion à la base de données
 * @return array Liste des utilisateurs éligibles
 */
function getResponsablesEligibles(PDO $pdo): array {
    try {
        $sql = "SELECT id_utilisateur, nom_complet, login_utilisateur, nom_role
                FROM utilisateurs u
                LEFT JOIN roles r ON u.id_role = r.id_role
                WHERE u.est_actif = 1 
                AND r.nom_role IN ('patron', 'receptionniste', 'caissier')
                ORDER BY nom_complet";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des responsables éligibles: " . $e->getMessage());
        return [];
    }
}

/**
 * Basculer le statut actif/inactif d'une agence
 * @param PDO $pdo Instance de connexion à la base de données
 * @param int $id_agence ID de l'agence
 * @return array Résultat de l'opération
 */
function toggleAgenceStatus(PDO $pdo, int $id_agence): array {
    try {
        // Vérifier si l'agence existe
        $agence = getAgenceById($pdo, $id_agence);
        if (!$agence) {
            return ['success' => false, 'message' => 'Agence non trouvée'];
        }
        
        $nouveau_statut = $agence['est_actif'] ? 0 : 1;
        
        $sql = "UPDATE agences SET est_actif = :est_actif WHERE id_agence = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':est_actif', $nouveau_statut, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id_agence, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $message = $nouveau_statut ? 'Agence activée avec succès' : 'Agence désactivée avec succès';
            return ['success' => true, 'message' => $message];
        }
        
        return ['success' => false, 'message' => 'Erreur lors du changement de statut'];
        
    } catch (PDOException $e) {
        error_log("Erreur lors du changement de statut de l'agence ID $id_agence: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur technique: ' . $e->getMessage()];
    }
}

/**
 * Recherche des agences
 * @param PDO $pdo Instance de connexion à la base de données
 * @param string $term Terme de recherche
 * @return array Liste des agences trouvées
 */
function rechercherAgences(PDO $pdo, string $term): array {
    try {
        $sql = "SELECT a.*, u.nom_complet as responsable_nom 
                FROM agences a 
                LEFT JOIN utilisateurs u ON a.responsable_id = u.id_utilisateur 
                WHERE a.nom_agence LIKE :term 
                   OR a.adresse LIKE :term 
                   OR a.telephone LIKE :term 
                   OR a.email LIKE :term
                   OR u.nom_complet LIKE :term
                ORDER BY a.nom_agence";
        
        $stmt = $pdo->prepare($sql);
        $searchTerm = '%' . $term . '%';
        $stmt->bindParam(':term', $searchTerm);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la recherche d'agences: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les performances d'une agence (ventes, tickets, etc.)
 * @param PDO $pdo Instance de connexion à la base de données
 * @param int $id_agence ID de l'agence
 * @param string $periode Periode (jour, semaine, mois, annee)
 * @return array Performances de l'agence
 */
function getPerformancesAgence(PDO $pdo, int $id_agence, string $periode = 'mois'): array {
    try {
        // Définir la période
        switch ($periode) {
            case 'jour':
                $date_debut = date('Y-m-d');
                $date_fin = date('Y-m-d');
                break;
            case 'semaine':
                $date_debut = date('Y-m-d', strtotime('-7 days'));
                $date_fin = date('Y-m-d');
                break;
            case 'mois':
                $date_debut = date('Y-m-01');
                $date_fin = date('Y-m-t');
                break;
            case 'annee':
                $date_debut = date('Y-01-01');
                $date_fin = date('Y-12-31');
                break;
            default:
                $date_debut = date('Y-m-01');
                $date_fin = date('Y-m-t');
        }
        
        $performances = [
            'total_tickets' => 0,
            'total_ventes' => 0,
            'tickets_en_cours' => 0,
            'tickets_termines' => 0,
            'ventes_par_jour' => []
        ];
        
        // Total tickets pour la période
        $sqlTickets = "SELECT COUNT(*) as total, 
                              SUM(montant_total) as chiffre_affaires
                       FROM tickets 
                       WHERE id_agence = :id_agence 
                       AND DATE(date_depot) BETWEEN :date_debut AND :date_fin
                       AND statut != 'annule'";
        
        $stmtTickets = $pdo->prepare($sqlTickets);
        $stmtTickets->bindParam(':id_agence', $id_agence, PDO::PARAM_INT);
        $stmtTickets->bindParam(':date_debut', $date_debut);
        $stmtTickets->bindParam(':date_fin', $date_fin);
        $stmtTickets->execute();
        $resultTickets = $stmtTickets->fetch(PDO::FETCH_ASSOC);
        
        if ($resultTickets) {
            $performances['total_tickets'] = $resultTickets['total'];
            $performances['total_ventes'] = $resultTickets['chiffre_affaires'] ?? 0;
        }
        
        // Tickets en cours
        $sqlEnCours = "SELECT COUNT(*) as en_cours
                      FROM tickets 
                      WHERE id_agence = :id_agence 
                      AND statut IN ('en_attente', 'en_traitement', 'pret')
                      AND DATE(date_depot) BETWEEN :date_debut AND :date_fin";
        
        $stmtEnCours = $pdo->prepare($sqlEnCours);
        $stmtEnCours->bindParam(':id_agence', $id_agence, PDO::PARAM_INT);
        $stmtEnCours->bindParam(':date_debut', $date_debut);
        $stmtEnCours->bindParam(':date_fin', $date_fin);
        $stmtEnCours->execute();
        $resultEnCours = $stmtEnCours->fetch(PDO::FETCH_ASSOC);
        
        if ($resultEnCours) {
            $performances['tickets_en_cours'] = $resultEnCours['en_cours'];
            $performances['tickets_termines'] = $performances['total_tickets'] - $resultEnCours['en_cours'];
        }
        
        // Ventes par jour
        $sqlVentesJour = "SELECT DATE(date_depot) as jour, 
                                 COUNT(*) as nb_tickets,
                                 SUM(montant_total) as ventes
                          FROM tickets 
                          WHERE id_agence = :id_agence 
                          AND DATE(date_depot) BETWEEN :date_debut AND :date_fin
                          AND statut != 'annule'
                          GROUP BY DATE(date_depot)
                          ORDER BY DATE(date_depot)";
        
        $stmtVentesJour = $pdo->prepare($sqlVentesJour);
        $stmtVentesJour->bindParam(':id_agence', $id_agence, PDO::PARAM_INT);
        $stmtVentesJour->bindParam(':date_debut', $date_debut);
        $stmtVentesJour->bindParam(':date_fin', $date_fin);
        $stmtVentesJour->execute();
        $performances['ventes_par_jour'] = $stmtVentesJour->fetchAll(PDO::FETCH_ASSOC);
        
        return $performances;
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des performances de l'agence ID $id_agence: " . $e->getMessage());
        return [];
    }
}

// --- Fonctions pour la compatibilité avec l'ancien code ---

/**
 * @deprecated Utiliser getAllAgences() à la place
 */
function getAllAgencesOld($pdo) {
    return getAllAgences($pdo);
}

/**
 * @deprecated Utiliser creerAgence() à la place
 */
function addAgence(PDO $pdo, string $code, string $nom, string $adresse): bool {
    $data = [
        'nom_agence' => $nom,
        'adresse' => $adresse,
        'telephone' => 'Non renseigné'
    ];
    $result = creerAgence($pdo, $data);
    return $result['success'];
}

/**
 * @deprecated Utiliser modifierAgence() à la place
 */
function updateAgence(PDO $pdo, string $code, string $nom, string $adresse): bool {
    // Cette fonction ne peut pas être utilisée sans ID
    return false;
}

/**
 * @deprecated Utiliser supprimerAgence() à la place
 */
function deleteAgence(PDO $pdo, string $code): bool {
    // Cette fonction ne peut pas être utilisée sans ID
    return false;
}


// fonctions/gestion_agences.php

/**
 * Récupère les services les plus vendus pour une agence donnée
 */

/**
 * Récupère les meilleurs clients pour une agence donnée
 */


/**
 * Récupère les performances globales de l'agence
 */

/**
 * Récupère les services les plus vendus pour une agence donnée
 */
function getTopServicesAgence($pdo, $id_agence, $date_debut, $date_fin) {
    $sql = "SELECT s.nom_service, SUM(lt.quantite) as quantite_vendue, SUM(lt.sous_total) as chiffre_affaires
            FROM lignes_ticket lt
            JOIN services s ON lt.id_service = s.id_service
            JOIN tickets t ON lt.id_ticket = t.id_ticket
            WHERE t.id_agence = :id_agence 
            AND t.date_depot BETWEEN :debut AND :fin
            AND t.statut != 'annule'
            GROUP BY s.id_service
            ORDER BY chiffre_affaires DESC
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'id_agence' => $id_agence,
        'debut' => $date_debut . ' 00:00:00',
        'fin' => $date_fin . ' 23:59:59'
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère les meilleurs clients pour une agence donnée
 */
function getTopClientsAgence($pdo, $id_agence, $date_debut, $date_fin) {
    $sql = "SELECT c.nom_client, c.prenom_client, COUNT(t.id_ticket) as nombre_tickets, SUM(t.montant_total) as total_depense
            FROM clients c
            JOIN tickets t ON c.id_client = t.id_client
            WHERE t.id_agence = :id_agence 
            AND t.date_depot BETWEEN :debut AND :fin
            GROUP BY c.id_client
            ORDER BY total_depense DESC
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'id_agence' => $id_agence,
        'debut' => $date_debut . ' 00:00:00',
        'fin' => $date_fin . ' 23:59:59'
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère les performances globales de l'agence
 */
