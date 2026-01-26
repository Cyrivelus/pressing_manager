<?php
// fonctions/gestion_profils.php

/**
 * Récupère tous les rôles (profils) avec les utilisateurs associés
 *
 * @param PDO $pdo Instance de connexion à la base de données
 * @return array Tableau des rôles avec informations des utilisateurs
 */
function getTousLesProfils(PDO $pdo) {
    try {
        $sql = "SELECT
                    r.id_role,
                    r.nom_role,
                    r.description,
                    r.niveau_permission,
                    r.created_at,
                    COUNT(u.id_utilisateur) as nombre_utilisateurs,
                    GROUP_CONCAT(u.nom_complet SEPARATOR ', ') as utilisateurs_associes
                FROM roles AS r
                LEFT JOIN utilisateurs AS u ON r.id_role = u.id_role
                GROUP BY r.id_role
                ORDER BY r.niveau_permission DESC, r.nom_role ASC";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur dans getTousLesProfils: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les informations d'un rôle spécifique
 *
 * @param PDO $pdo Instance de connexion à la base de données
 * @param int $roleId ID du rôle à récupérer
 * @return array|bool Informations du rôle ou false si non trouvé
 */
function getProfilParId(PDO $pdo, int $roleId) {
    try {
        $sql = "SELECT * FROM roles WHERE id_role = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$roleId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur dans getProfilParId: " . $e->getMessage());
        return false;
    }
}

/**
 * Ajoute un nouveau rôle (profil)
 *
 * @param PDO $pdo Instance de connexion à la base de données
 * @param string $nom Nom du rôle
 * @param string|null $description Description du rôle
 * @param int $niveauPermission Niveau de permission (1 = bas, plus élevé = plus de permissions)
 * @return int|bool ID du nouveau rôle ou false en cas d'erreur
 */
function ajouterProfil(PDO $pdo, string $nom, ?string $description = null, int $niveauPermission = 1) {
    try {
        // Vérifier si le nom du rôle existe déjà
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM roles WHERE nom_role = ?");
        $stmtCheck->execute([$nom]);
        if ($stmtCheck->fetchColumn() > 0) {
            return false; // Le nom du rôle existe déjà
        }

        $sql = "INSERT INTO roles (nom_role, description, niveau_permission) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nom, $description, $niveauPermission]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Erreur dans ajouterProfil: " . $e->getMessage());
        return false;
    }
}

/**
 * Modifie un rôle existant
 *
 * @param PDO $pdo Instance de connexion à la base de données
 * @param int $roleId ID du rôle à modifier
 * @param string $nom Nouveau nom du rôle
 * @param string|null $description Nouvelle description
 * @param int $niveauPermission Nouveau niveau de permission
 * @return bool True en cas de succès, false sinon
 */
function modifierProfil(PDO $pdo, int $roleId, string $nom, ?string $description = null, int $niveauPermission = 1) {
    try {
        // Vérifier si le nouveau nom existe déjà pour un autre rôle
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM roles WHERE nom_role = ? AND id_role != ?");
        $stmtCheck->execute([$nom, $roleId]);
        if ($stmtCheck->fetchColumn() > 0) {
            return false; // Le nom existe déjà pour un autre rôle
        }

        $sql = "UPDATE roles SET nom_role = ?, description = ?, niveau_permission = ? WHERE id_role = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$nom, $description, $niveauPermission, $roleId]);
    } catch (PDOException $e) {
        error_log("Erreur dans modifierProfil: " . $e->getMessage());
        return false;
    }
}

/**
 * Supprime un rôle (profil)
 *
 * @param PDO $pdo Instance de connexion à la base de données
 * @param int $roleId ID du rôle à supprimer
 * @return bool True en cas de succès, false sinon
 */
function supprimerProfil(PDO $pdo, int $roleId) {
    try {
        // Vérifier si des utilisateurs sont associés à ce rôle
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE id_role = ?");
        $stmtCheck->execute([$roleId]);
        if ($stmtCheck->fetchColumn() > 0) {
            return false; // Des utilisateurs sont encore associés à ce rôle
        }

        $sql = "DELETE FROM roles WHERE id_role = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$roleId]);
    } catch (PDOException $e) {
        error_log("Erreur dans supprimerProfil: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les utilisateurs associés à un rôle
 *
 * @param PDO $pdo Instance de connexion à la base de données
 * @param int $roleId ID du rôle
 * @return array Liste des utilisateurs
 */
function getUtilisateursParRole(PDO $pdo, int $roleId) {
    try {
        $sql = "SELECT 
                    u.id_utilisateur,
                    u.nom_complet,
                    u.login_utilisateur,
                    u.email,
                    u.telephone,
                    u.date_creation,
                    u.est_actif
                FROM utilisateurs u
                WHERE u.id_role = ?
                ORDER BY u.nom_complet ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur dans getUtilisateursParRole: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère tous les niveaux de permission disponibles
 *
 * @param PDO $pdo Instance de connexion à la base de données
 * @return array Niveaux de permission avec description
 */
function getNiveauxPermission(PDO $pdo) {
    return [
        ['id' => 1, 'nom' => 'Basique', 'description' => 'Permissions minimales'],
        ['id' => 2, 'nom' => 'Standard', 'description' => 'Permissions standard'],
        ['id' => 3, 'nom' => 'Élevé', 'description' => 'Permissions élevées'],
        ['id' => 4, 'nom' => 'Administrateur', 'description' => 'Toutes les permissions'],
        ['id' => 5, 'nom' => 'Super Admin', 'description' => 'Permissions complètes']
    ];
}

/**
 * Vérifie si un nom de rôle existe déjà
 *
 * @param PDO $pdo Instance de connexion à la base de données
 * @param string $nom Nom du rôle à vérifier
 * @param int|null $excludeId ID du rôle à exclure (pour la modification)
 * @return bool True si le nom existe, false sinon
 */
function nomRoleExiste(PDO $pdo, string $nom, ?int $excludeId = null) {
    try {
        if ($excludeId) {
            $sql = "SELECT COUNT(*) FROM roles WHERE nom_role = ? AND id_role != ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nom, $excludeId]);
        } else {
            $sql = "SELECT COUNT(*) FROM roles WHERE nom_role = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nom]);
        }
        
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Erreur dans nomRoleExiste: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère le nombre d'utilisateurs par rôle
 *
 * @param PDO $pdo Instance de connexion à la base de données
 * @return array Statistiques d'utilisation des rôles
 */
function getStatistiquesRoles(PDO $pdo) {
    try {
        $sql = "SELECT 
                    r.id_role,
                    r.nom_role,
                    COUNT(u.id_utilisateur) as nombre_utilisateurs,
                    SUM(CASE WHEN u.est_actif = 1 THEN 1 ELSE 0 END) as utilisateurs_actifs
                FROM roles r
                LEFT JOIN utilisateurs u ON r.id_role = u.id_role
                GROUP BY r.id_role
                ORDER BY r.niveau_permission DESC";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur dans getStatistiquesRoles: " . $e->getMessage());
        return [];
    }
}

/**
 * Duplique un rôle existant
 *
 * @param PDO $pdo Instance de connexion à la base de données
 * @param int $roleId ID du rôle à dupliquer
 * @param string $nouveauNom Nom pour la copie
 * @return int|bool ID du nouveau rôle ou false en cas d'erreur
 */
function dupliquerRole(PDO $pdo, int $roleId, string $nouveauNom) {
    try {
        $pdo->beginTransaction();
        
        // Récupérer le rôle source
        $roleSource = getProfilParId($pdo, $roleId);
        if (!$roleSource) {
            throw new Exception("Rôle source non trouvé");
        }
        
        // Vérifier si le nouveau nom existe déjà
        if (nomRoleExiste($pdo, $nouveauNom)) {
            throw new Exception("Ce nom de rôle existe déjà");
        }
        
        // Créer le nouveau rôle
        $sql = "INSERT INTO roles (nom_role, description, niveau_permission) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nouveauNom,
            $roleSource['description'],
            $roleSource['niveau_permission']
        ]);
        
        $nouveauRoleId = $pdo->lastInsertId();
        
        $pdo->commit();
        return $nouveauRoleId;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erreur dans dupliquerRole: " . $e->getMessage());
        return false;
    }
}
?>