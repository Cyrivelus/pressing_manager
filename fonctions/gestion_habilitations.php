<?php
// fonctions/gestion_habilitations.php

/**
 * Récupère les informations détaillées des rôles (profils)
 * * @param PDO $pdo Instance de connexion à la base de données
 * @return array Tableau des rôles avec statistiques d'utilisation
 */
function getHabilitationsProfilsAvecDetails(PDO $pdo) {
    try {
        $sql = "SELECT 
                    r.id_role as ID_Habilitation_Profil,
                    r.nom_role as Nom_Profil,
                    r.description,
                    r.niveau_permission,
                    r.created_at,
                    COUNT(u.id_utilisateur) as nombre_utilisateurs,
                    GROUP_CONCAT(u.nom_complet SEPARATOR ', ') as utilisateurs_noms
                FROM roles r
                LEFT JOIN utilisateurs u ON r.id_role = u.id_role
                GROUP BY r.id_role
                ORDER BY r.niveau_permission DESC, r.nom_role ASC";
        
        $stmt = $pdo->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formater le résultat
        $formattedResult = [];
        foreach ($result as $row) {
            $formattedResult[] = [
                'ID_Habilitation_Profil' => $row['ID_Habilitation_Profil'],
                'Nom_Profil' => $row['Nom_Profil'],
                // CORRECTION : Suppression de $this-> car nous sommes dans une fonction simple
                'Objet' => getPermissionLevelText($row['niveau_permission']), 
                'niveau_permission' => $row['niveau_permission'],
                'description' => $row['description'],
                'nombre_utilisateurs' => $row['nombre_utilisateurs'],
                'utilisateurs_noms' => $row['utilisateurs_noms']
            ];
        }
        
        return $formattedResult;
    } catch (PDOException $e) {
        error_log("Erreur dans getHabilitationsProfilsAvecDetails: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les habilitations spécifiques par utilisateur
 * * @param PDO $pdo Instance de connexion à la base de données
 * @return array Tableau des utilisateurs avec leurs rôles
 */
function getHabilitationsUtilisateursAvecDetails(PDO $pdo) {
    try {
        $sql = "SELECT 
                    u.id_utilisateur as ID_Habilitation_Utilisateur,
                    u.nom_complet as Nom_Utilisateur,
                    u.login_utilisateur,
                    r.nom_role as Objet,
                    r.niveau_permission,
                    u.est_actif
                FROM utilisateurs u
                LEFT JOIN roles r ON u.id_role = r.id_role
                WHERE u.id_role IS NOT NULL
                ORDER BY u.nom_complet ASC";
        
        $stmt = $pdo->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $formattedResult = [];
        foreach ($result as $row) {
            $formattedResult[] = [
                'ID_Habilitation_Utilisateur' => $row['ID_Habilitation_Utilisateur'],
                'Nom_Utilisateur' => $row['Nom_Utilisateur'],
                'Objet' => $row['Objet'] . ' (Niv. ' . $row['niveau_permission'] . ')',
                'login_utilisateur' => $row['login_utilisateur'],
                'est_actif' => $row['est_actif']
            ];
        }
        
        return $formattedResult;
    } catch (PDOException $e) {
        error_log("Erreur dans getHabilitationsUtilisateursAvecDetails: " . $e->getMessage());
        return [];
    }
}

/**
 * Convertit le niveau de permission en texte lisible
 */
function getPermissionLevelText($level) {
    $levels = [
        1 => 'Basique - Accès limité',
        2 => 'Standard - Accès standard',
        3 => 'Élevé - Accès étendu',
        4 => 'Administrateur - Accès complet',
        5 => 'Super Admin - Tous les droits'
    ];
    
    return $levels[$level] ?? 'Niveau ' . $level . ' - Non défini';
}

/**
 * Récupère tous les rôles disponibles
 */
function getTousLesRoles(PDO $pdo) {
    try {
        $sql = "SELECT * FROM roles ORDER BY niveau_permission DESC, nom_role ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur dans getTousLesRoles: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les statistiques d'utilisation des rôles
 */
function getStatistiquesHabilitations(PDO $pdo) {
    try {
        // Correction de la requête pour obtenir des comptes réels
        $sqlRoles = "SELECT COUNT(*) FROM roles";
        $sqlUsers = "SELECT COUNT(*) FROM utilisateurs";
        $sqlWithRole = "SELECT COUNT(*) FROM utilisateurs WHERE id_role IS NOT NULL";
        $sqlNoRole = "SELECT COUNT(*) FROM utilisateurs WHERE id_role IS NULL";

        return [
            'total_roles' => $pdo->query($sqlRoles)->fetchColumn(),
            'total_utilisateurs' => $pdo->query($sqlUsers)->fetchColumn(),
            'utilisateurs_avec_role' => $pdo->query($sqlWithRole)->fetchColumn(),
            'utilisateurs_sans_role' => $pdo->query($sqlNoRole)->fetchColumn()
        ];
    } catch (PDOException $e) {
        error_log("Erreur dans getStatistiquesHabilitations: " . $e->getMessage());
        return ['total_roles' => 0, 'total_utilisateurs' => 0, 'utilisateurs_avec_role' => 0, 'utilisateurs_sans_role' => 0];
    }
}

/**
 * Récupère un rôle par son ID
 */
function getRoleParId(PDO $pdo, $roleId) {
    try {
        $sql = "SELECT * FROM roles WHERE id_role = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$roleId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Ajoute un nouveau rôle
 */
function ajouterHabilitationProfil(PDO $pdo, $nom, $description = null, $niveauPermission = 1) {
    try {
        $sqlInsert = "INSERT INTO roles (nom_role, description, niveau_permission) VALUES (?, ?, ?)";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([$nom, $description, $niveauPermission]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Modifie une habilitation
 */
function modifierHabilitationProfil(PDO $pdo, $roleId, $nom, $description = null, $niveauPermission = 1) {
    try {
        $sqlUpdate = "UPDATE roles SET nom_role = ?, description = ?, niveau_permission = ? WHERE id_role = ?";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        return $stmtUpdate->execute([$nom, $description, $niveauPermission, $roleId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Supprime une habilitation (si non utilisée)
 */
function supprimerHabilitationProfil(PDO $pdo, $roleId) {
    try {
        $sqlCheck = "SELECT COUNT(*) FROM utilisateurs WHERE id_role = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$roleId]);
        
        if ($stmtCheck->fetchColumn() > 0) return false;
        
        $sqlDelete = "DELETE FROM roles WHERE id_role = ?";
        $stmtDelete = $pdo->prepare($sqlDelete);
        return $stmtDelete->execute([$roleId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Vérifie si un utilisateur a une permission spécifique
 */
function utilisateurAPermission(PDO $pdo, $userId, $requiredLevel) {
    try {
        $sql = "SELECT r.niveau_permission 
                FROM utilisateurs u
                LEFT JOIN roles r ON u.id_role = r.id_role
                WHERE u.id_utilisateur = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || $result['niveau_permission'] === null) return false;
        
        return $result['niveau_permission'] >= $requiredLevel;
    } catch (PDOException $e) {
        return false;
    }
}