<?php

require_once 'database.php';
require_once 'gestion_logs.php';

/**
 * Récupère la liste des budgets avec recherche et tri
 *
 * @param string $searchTerm Terme de recherche
 * @param string $sortField Champ de tri
 * @param string $sortOrder Ordre de tri (ASC/DESC)
 * @return array Tableau des budgets
 */
function getListeBudgets(string $searchTerm = '', string $sortField = 'ID_Budget', string $sortOrder = 'DESC'): array {
    global $pdo;
    
    try {
        // Valider le champ de tri pour éviter les injections SQL
        $allowedSortFields = ['ID_Budget', 'Annee_Budgetaire', 'Type_Budget', 'Montant_Budgetise', 'Date_Creation'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'ID_Budget';
        }
        
        // Valider l'ordre de tri
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
        
        // Requête SQL de base
        $sql = "SELECT 
                    b.ID_Budget,
                    b.Annee_Budgetaire,
                    b.Type_Budget,
                    b.Montant_Budgetise,
                    b.Description_Budget,
                    b.Date_Creation,
                    b.Date_Mise_a_Jour,
                    c.Numero_Compte,
                    c.Nom_Compte,
                    u.Nom_Utilisateur
                FROM Budget b
                LEFT JOIN Comptes_compta c ON b.ID_Compte = c.ID_Compte
                LEFT JOIN Utilisateurs u ON b.ID_Utilisateur = u.ID_Utilisateur
                WHERE 1=1";
        
        $params = [];
        
        // Ajouter les conditions de recherche si un terme est fourni
        if (!empty($searchTerm)) {
            $sql .= " AND (
                b.ID_Budget LIKE :search OR
                b.Annee_Budgetaire LIKE :search OR
                b.Type_Budget LIKE :search OR
                b.Description_Budget LIKE :search OR
                c.Numero_Compte LIKE :search OR
                c.Nom_Compte LIKE :search
            )";
            $params[':search'] = '%' . $searchTerm . '%';
        }
        
        // Ajouter le tri
        $sql .= " ORDER BY $sortField $sortOrder";
        
        // Préparer et exécuter la requête
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        logError("Erreur lors de la récupération de la liste des budgets: " . $e->getMessage());
        return [];
    }
}

/**
 * Ajoute un nouveau budget dans la base de données
 *
 * @param array $budgetData Données du budget
 * @return int|false ID du budget inséré ou false en cas d'erreur
 */
function addBudget(
    PDO $pdo,
    int $accountId,
    string $budgetPeriodType,
    string $startDate,
    string $endDate,
    float $budgetAmount,
    int $userId
): int|false {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO Budget
            (ID_Compte, Annee_Budgetaire, Type_Budget, Montant_Budgetise, Date_Creation, ID_Utilisateur)
            VALUES (:id_compte, :annee_budgetaire, :type_budget, :montant_budgetise, NOW(), :id_utilisateur)
        ");
        $stmt->bindParam(':id_compte', $accountId, PDO::PARAM_INT);
        $stmt->bindParam(':annee_budgetaire', $budgetPeriodType, PDO::PARAM_INT);
        $stmt->bindParam(':type_budget', $budgetPeriodType, PDO::PARAM_STR);
        $stmt->bindParam(':montant_budgetise', $budgetAmount, PDO::PARAM_STR);
        $stmt->bindParam(':id_utilisateur', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $budgetId = $pdo->lastInsertId();

        $pdo->commit();
        logActivity("Budget ID {$budgetId} ajouté pour le compte ID {$accountId} par Utilisateur ID {$userId}.");
        return (int)$budgetId;

    } catch (PDOException $e) {
        $pdo->rollBack();
        logError("Erreur PDO lors de l'ajout du budget: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les budgets selon des critères
 *
 * @param PDO $pdo Connexion PDO
 * @param int|null $budgetId ID spécifique
 * @param int|null $accountId ID du compte
 * @param string|null $periodType Type de période
 * @param string|null $dateWithinPeriod Date dans la période
 * @return array Tableau des budgets
 */
function getBudgets(
    PDO $pdo,
    ?int $budgetId = null,
    ?int $accountId = null,
    ?string $periodType = null,
    ?string $dateWithinPeriod = null
): array {
    $sql = "
        SELECT
            b.ID_Budget,
            b.ID_Compte,
            b.Annee_Budgetaire,
            b.Montant_Budgetise,
            b.Type_Budget,
            b.Description_Budget,
            b.Date_Creation,
            b.Date_Mise_a_Jour,
            b.ID_Utilisateur,
            c.Numero_Compte,
            c.Nom_Compte,
            u.Nom_Utilisateur
        FROM Budget b
        LEFT JOIN Comptes_compta c ON b.ID_Compte = c.ID_Compte
        LEFT JOIN Utilisateurs u ON b.ID_Utilisateur = u.ID_Utilisateur
        WHERE 1=1
    ";
    
    $params = [];
    $where = [];

    if ($budgetId !== null) {
        $where[] = "b.ID_Budget = :id_budget";
        $params[':id_budget'] = $budgetId;
    }
    if ($accountId !== null) {
        $where[] = "b.ID_Compte = :id_compte";
        $params[':id_compte'] = $accountId;
    }
    if ($periodType !== null) {
        $where[] = "b.Type_Budget = :type_budget";
        $params[':type_budget'] = $periodType;
    }

    if (!empty($where)) {
        $sql .= " AND " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY b.Annee_Budgetaire DESC, b.Date_Creation DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logError("Erreur PDO lors de la récupération des budgets: " . $e->getMessage());
        return [];
    }
}

/**
 * Met à jour un budget existant
 *
 * @param PDO $pdo Connexion PDO
 * @param int $budgetId ID du budget
 * @param array $data Données à mettre à jour
 * @return bool Succès ou échec
 */
function updateBudget(
    PDO $pdo,
    int $budgetId,
    int $accountId,
    string $budgetPeriodType,
    string $startDate,
    string $endDate,
    float $budgetAmount,
    int $userId,
    bool $isActive
): bool {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE Budget SET
                ID_Compte = :id_compte,
                Type_Budget = :type_budget,
                Montant_Budgetise = :montant_budgetise,
                Date_Mise_a_Jour = NOW(),
                ID_Utilisateur = :id_utilisateur
            WHERE ID_Budget = :id_budget
        ");
        $stmt->bindParam(':id_compte', $accountId, PDO::PARAM_INT);
        $stmt->bindParam(':type_budget', $budgetPeriodType, PDO::PARAM_STR);
        $stmt->bindParam(':montant_budgetise', $budgetAmount, PDO::PARAM_STR);
        $stmt->bindParam(':id_utilisateur', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':id_budget', $budgetId, PDO::PARAM_INT);
        $stmt->execute();

        $pdo->commit();
        logActivity("Budget ID {$budgetId} mis à jour pour le compte ID {$accountId} par Utilisateur ID {$userId}.");
        return true;

    } catch (PDOException $e) {
        $pdo->rollBack();
        logError("Erreur PDO lors de la mise à jour du budget: " . $e->getMessage());
        return false;
    }
}

/**
 * Supprime un budget
 *
 * @param PDO $pdo Connexion PDO
 * @param int $budgetId ID du budget
 * @return bool Succès ou échec
 */
function deleteBudget(PDO $pdo, int $budgetId): bool {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM Budget WHERE ID_Budget = :id_budget");
        $stmt->bindParam(':id_budget', $budgetId, PDO::PARAM_INT);
        $stmt->execute();

        $pdo->commit();
        logActivity("Budget ID {$budgetId} supprimé.");
        return true;

    } catch (PDOException $e) {
        $pdo->rollBack();
        logError("Erreur PDO lors de la suppression du budget: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les détails d'un compte
 *
 * @param PDO $pdo Connexion PDO
 * @param int $accountId ID du compte
 * @return array|false Détails du compte ou false
 */
function getAccountDetails(PDO $pdo, int $accountId): array|false {
    $stmt = $pdo->prepare("SELECT Numero_Compte, Nom_Compte FROM Comptes_compta WHERE ID_Compte = :id_compte");
    $stmt->bindParam(':id_compte', $accountId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Helper pour logger les erreurs
function logError(string $message) {
    if (function_exists('logApplicationError')) {
        logApplicationError($message);
    } else {
        error_log($message);
    }
}

// Helper pour logger les warnings
function logWarning(string $message) {
    if (function_exists('logApplicationWarning')) {
        logApplicationWarning($message);
    } else {
        error_log("WARNING: " . $message);
    }
}

?>