<?php
// fonctions/gestion_utilisateurs.php

/**
 * Fonctions de gestion des utilisateurs pour la table "utilisateurs"
 * Compatible avec la structure de base de données du pressing
 */

// --- Fonctions principales de gestion des utilisateurs ---

/**
 * Récupère tous les utilisateurs avec leurs rôles
 * @param PDO $pdo Instance de connexion à la base de données
 * @return array Tableau d'utilisateurs
 */
function getTousLesUtilisateurs(PDO $pdo): array {
    try {
        $sql = "SELECT u.*, r.nom_role, r.description as role_description 
                FROM utilisateurs u 
                LEFT JOIN roles r ON u.id_role = r.id_role 
                ORDER BY u.nom_complet";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des utilisateurs: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère un utilisateur par son ID
 * @param PDO $pdo Instance de connexion à la base de données
 * @param int $id_utilisateur ID de l'utilisateur
 * @return array|null Tableau de l'utilisateur ou null si non trouvé
 */
function getUtilisateurParId(PDO $pdo, int $id_utilisateur): ?array {
    try {
        $sql = "SELECT u.*, r.nom_role 
                FROM utilisateurs u 
                LEFT JOIN roles r ON u.id_role = r.id_role 
                WHERE u.id_utilisateur = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id_utilisateur, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération de l'utilisateur ID $id_utilisateur: " . $e->getMessage());
        return null;
    }
}

/**
 * Récupère les utilisateurs connectés récemment
 * @param PDO $pdo Instance de connexion à la base de données
 * @param int $periodeMinutes Période en minutes (défaut: 30)
 * @return array Liste des utilisateurs connectés
 */
function getUtilisateursConnectesRecemment(PDO $pdo, int $periodeMinutes = 30): array {
    try {
        $seuil_temps = date('Y-m-d H:i:s', strtotime("-$periodeMinutes minutes"));
        
        $sql = "SELECT u.*, r.nom_role, u.derniere_connexion
                FROM utilisateurs u
                LEFT JOIN roles r ON u.id_role = r.id_role
                WHERE u.derniere_connexion >= :seuil_temps
                AND u.est_actif = 1
                ORDER BY u.derniere_connexion DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':seuil_temps', $seuil_temps);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des utilisateurs connectés: " . $e->getMessage());
        return [];
    }
}

/**
 * Crée un nouvel utilisateur
 * @param PDO $pdo Instance de connexion à la base de données
 * @param array $data Données de l'utilisateur
 * @return array Résultat de l'opération
 */
function creerUtilisateur(PDO $pdo, array $data): array {
    try {
        // Validation des données requises
        $required = ['nom_complet', 'login_utilisateur', 'mot_de_passe', 'email', 'id_role'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Le champ '$field' est requis"];
            }
        }
        
        // Vérifier si le login existe déjà
        $sqlCheck = "SELECT COUNT(*) FROM utilisateurs WHERE login_utilisateur = :login";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->bindParam(':login', $data['login_utilisateur']);
        $stmtCheck->execute();
        
        if ($stmtCheck->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Ce nom d\'utilisateur existe déjà'];
        }
        
        // Hacher le mot de passe
        $hashedPassword = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        
        // Insertion de l'utilisateur
        $sql = "INSERT INTO utilisateurs 
                (nom_complet, login_utilisateur, mot_de_passe, email, telephone, id_role, code_agence, est_actif) 
                VALUES 
                (:nom_complet, :login_utilisateur, :mot_de_passe, :email, :telephone, :id_role, :code_agence, :est_actif)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nom_complet' => $data['nom_complet'],
            ':login_utilisateur' => $data['login_utilisateur'],
            ':mot_de_passe' => $hashedPassword,
            ':email' => $data['email'],
            ':telephone' => $data['telephone'] ?? null,
            ':id_role' => $data['id_role'],
            ':code_agence' => $data['code_agence'] ?? null,
            ':est_actif' => $data['est_actif'] ?? 1
        ]);
        
        $id = $pdo->lastInsertId();
        return ['success' => true, 'message' => 'Utilisateur créé avec succès', 'id' => $id];
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la création de l'utilisateur: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur technique: ' . $e->getMessage()];
    }
}

/**
 * Modifie un utilisateur existant
 * @param PDO $pdo Instance de connexion à la base de données
 * @param int $id_utilisateur ID de l'utilisateur
 * @param array $data Données à modifier
 * @return array Résultat de l'opération
 */
function modifierUtilisateur(PDO $pdo, int $id_utilisateur, array $data): array {
    try {
        // Vérifier si l'utilisateur existe
        $utilisateur = getUtilisateurParId($pdo, $id_utilisateur);
        if (!$utilisateur) {
            return ['success' => false, 'message' => 'Utilisateur non trouvé'];
        }
        
        // Vérifier si le login est déjà pris par un autre utilisateur
        if (isset($data['login_utilisateur']) && $data['login_utilisateur'] !== $utilisateur['login_utilisateur']) {
            $sqlCheck = "SELECT COUNT(*) FROM utilisateurs 
                        WHERE login_utilisateur = :login 
                        AND id_utilisateur != :id";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->execute([
                ':login' => $data['login_utilisateur'],
                ':id' => $id_utilisateur
            ]);
            
            if ($stmtCheck->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Ce nom d\'utilisateur est déjà utilisé'];
            }
        }
        
        // Construire la requête de mise à jour dynamiquement
        $fields = [];
        $params = [':id' => $id_utilisateur];
        
        if (isset($data['nom_complet'])) {
            $fields[] = 'nom_complet = :nom_complet';
            $params[':nom_complet'] = $data['nom_complet'];
        }
        
        if (isset($data['login_utilisateur'])) {
            $fields[] = 'login_utilisateur = :login_utilisateur';
            $params[':login_utilisateur'] = $data['login_utilisateur'];
        }
        
        if (isset($data['email'])) {
            $fields[] = 'email = :email';
            $params[':email'] = $data['email'];
        }
        
        if (isset($data['telephone'])) {
            $fields[] = 'telephone = :telephone';
            $params[':telephone'] = $data['telephone'];
        }
        
        if (isset($data['id_role'])) {
            $fields[] = 'id_role = :id_role';
            $params[':id_role'] = $data['id_role'];
        }
        
        if (isset($data['code_agence'])) {
            $fields[] = 'code_agence = :code_agence';
            $params[':code_agence'] = $data['code_agence'];
        }
        
        if (isset($data['est_actif'])) {
            $fields[] = 'est_actif = :est_actif';
            $params[':est_actif'] = $data['est_actif'];
        }
        
        if (empty($fields)) {
            return ['success' => false, 'message' => 'Aucune donnée à modifier'];
        }
        
        $sql = "UPDATE utilisateurs SET " . implode(', ', $fields) . " WHERE id_utilisateur = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return ['success' => true, 'message' => 'Utilisateur modifié avec succès'];
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la modification de l'utilisateur ID $id_utilisateur: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur technique: ' . $e->getMessage()];
    }
}

/**
 * Modifie le mot de passe d'un utilisateur
 * @param PDO $pdo Instance de connexion à la base de données
 * @param int $id_utilisateur ID de l'utilisateur
 * @param string $nouveau_mot_de_passe Nouveau mot de passe
 * @return array Résultat de l'opération
 */
function modifierMotDePasse(PDO $pdo, int $id_utilisateur, string $nouveau_mot_de_passe): array {
    try {
        // Vérifier si l'utilisateur existe
        $utilisateur = getUtilisateurParId($pdo, $id_utilisateur);
        if (!$utilisateur) {
            return ['success' => false, 'message' => 'Utilisateur non trouvé'];
        }
        
        // Valider le mot de passe
        if (strlen($nouveau_mot_de_passe) < 6) {
            return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères'];
        }
        
        // Hacher le nouveau mot de passe
        $hashedPassword = password_hash($nouveau_mot_de_passe, PASSWORD_DEFAULT);
        
        $sql = "UPDATE utilisateurs SET mot_de_passe = :mot_de_passe WHERE id_utilisateur = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':mot_de_passe' => $hashedPassword,
            ':id' => $id_utilisateur
        ]);
        
        return ['success' => true, 'message' => 'Mot de passe modifié avec succès'];
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la modification du mot de passe de l'utilisateur ID $id_utilisateur: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur technique: ' . $e->getMessage()];
    }
}

/**
 * Réinitialise le mot de passe d'un utilisateur
 * @param PDO $pdo Instance de connexion à la base de données
 * @param int $id_utilisateur ID de l'utilisateur
 * @return array Résultat avec nouveau mot de passe
 */
function resetUserPassword(PDO $pdo, int $id_utilisateur): array {
    try {
        // Vérifier si l'utilisateur existe
        $utilisateur = getUtilisateurParId($pdo, $id_utilisateur);
        if (!$utilisateur) {
            return ['success' => false, 'message' => 'Utilisateur non trouvé'];
        }
        
        // Générer un mot de passe aléatoire
        $nouveau_mot_de_passe = bin2hex(random_bytes(8));
        $hashedPassword = password_hash($nouveau_mot_de_passe, PASSWORD_DEFAULT);
        
        $sql = "UPDATE utilisateurs SET mot_de_passe = :mot_de_passe WHERE id_utilisateur = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':mot_de_passe' => $hashedPassword,
            ':id' => $id_utilisateur
        ]);
        
        // Réinitialiser les tentatives d'échec
        $sqlReset = "UPDATE utilisateurs SET tentatives_echec = 0, date_blocage = NULL WHERE id_utilisateur = :id";
        $stmtReset = $pdo->prepare($sqlReset);
        $stmtReset->execute([':id' => $id_utilisateur]);
        
        return [
            'success' => true, 
            'message' => 'Mot de passe réinitialisé avec succès', 
            'nouveau_mot_de_passe' => $nouveau_mot_de_passe
        ];
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la réinitialisation du mot de passe de l'utilisateur ID $id_utilisateur: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur technique: ' . $e->getMessage()];
    }
}

/**
 * Basculer le statut actif/inactif d'un utilisateur
 * @param PDO $pdo Instance de connexion à la base de données
 * @param int $id_utilisateur ID de l'utilisateur
 * @return array Résultat de l'opération
 */
function toggleUserStatus(PDO $pdo, int $id_utilisateur): array {
    try {
        // Vérifier si l'utilisateur existe
        $utilisateur = getUtilisateurParId($pdo, $id_utilisateur);
        if (!$utilisateur) {
            return ['success' => false, 'message' => 'Utilisateur non trouvé'];
        }
        
        // Empêcher de désactiver son propre compte
        if (isset($_SESSION['utilisateur_id']) && $id_utilisateur == $_SESSION['utilisateur_id']) {
            return ['success' => false, 'message' => 'Vous ne pouvez pas désactiver votre propre compte'];
        }
        
        $nouveau_statut = $utilisateur['est_actif'] ? 0 : 1;
        
        $sql = "UPDATE utilisateurs SET est_actif = :est_actif WHERE id_utilisateur = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':est_actif' => $nouveau_statut,
            ':id' => $id_utilisateur
        ]);
        
        $message = $nouveau_statut ? 'Utilisateur activé avec succès' : 'Utilisateur désactivé avec succès';
        return ['success' => true, 'message' => $message];
        
    } catch (PDOException $e) {
        error_log("Erreur lors du changement de statut de l'utilisateur ID $id_utilisateur: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur technique: ' . $e->getMessage()];
    }
}

/**
 * Supprime un utilisateur
 * @param PDO $pdo Instance de connexion à la base de données
 * @param int $id_utilisateur ID de l'utilisateur
 * @return array Résultat de l'opération
 */
function supprimerUtilisateur(PDO $pdo, int $id_utilisateur): array {
    try {
        // Vérifier si l'utilisateur existe
        $utilisateur = getUtilisateurParId($pdo, $id_utilisateur);
        if (!$utilisateur) {
            return ['success' => false, 'message' => 'Utilisateur non trouvé'];
        }
        
        // Empêcher de supprimer son propre compte
        if (isset($_SESSION['utilisateur_id']) && $id_utilisateur == $_SESSION['utilisateur_id']) {
            return ['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre compte'];
        }
        
        // Vérifier si l'utilisateur a des actions en cours (tickets, etc.)
        $sqlCheckTickets = "SELECT COUNT(*) FROM tickets WHERE id_utilisateur = :id";
        $stmtCheck = $pdo->prepare($sqlCheckTickets);
        $stmtCheck->execute([':id' => $id_utilisateur]);
        
        if ($stmtCheck->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Impossible de supprimer cet utilisateur car il a des tickets en cours'];
        }
        
        // Supprimer l'utilisateur
        $sql = "DELETE FROM utilisateurs WHERE id_utilisateur = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id_utilisateur]);
        
        return ['success' => true, 'message' => 'Utilisateur supprimé avec succès'];
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la suppression de l'utilisateur ID $id_utilisateur: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur technique: ' . $e->getMessage()];
    }
}

/**
 * Récupère les rôles disponibles
 * @param PDO $pdo Instance de connexion à la base de données
 * @return array Liste des rôles
 */
function getRolesDisponibles(PDO $pdo): array {
    try {
        $sql = "SELECT id_role, nom_role, description, niveau_permission 
                FROM roles 
                ORDER BY niveau_permission DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des rôles: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les statistiques des utilisateurs
 * @param PDO $pdo Instance de connexion à la base de données
 * @return array Statistiques
 */
function getStatistiquesUtilisateurs(PDO $pdo): array {
    try {
        $statistiques = [];
        
        // Total utilisateurs
        $sqlTotal = "SELECT COUNT(*) as total FROM utilisateurs";
        $stmtTotal = $pdo->query($sqlTotal);
        $statistiques['total'] = $stmtTotal->fetchColumn();
        
        // Utilisateurs actifs
        $sqlActifs = "SELECT COUNT(*) as actifs FROM utilisateurs WHERE est_actif = 1";
        $stmtActifs = $pdo->query($sqlActifs);
        $statistiques['actifs'] = $stmtActifs->fetchColumn();
        
        // Utilisateurs inactifs
        $statistiques['inactifs'] = $statistiques['total'] - $statistiques['actifs'];
        
        // Utilisateurs par rôle
        $sqlRoles = "SELECT r.nom_role, COUNT(u.id_utilisateur) as nombre
                    FROM roles r
                    LEFT JOIN utilisateurs u ON r.id_role = u.id_role
                    GROUP BY r.id_role, r.nom_role
                    ORDER BY nombre DESC";
        $stmtRoles = $pdo->query($sqlRoles);
        $statistiques['par_role'] = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);
        
        // Dernières connexions (7 derniers jours)
        $sqlRecent = "SELECT COUNT(*) as recent
                     FROM utilisateurs 
                     WHERE derniere_connexion >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stmtRecent = $pdo->query($sqlRecent);
        $statistiques['connectes_7jours'] = $stmtRecent->fetchColumn();
        
        return $statistiques;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques utilisateurs: " . $e->getMessage());
        return [];
    }
}