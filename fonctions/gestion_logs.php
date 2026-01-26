<?php
// fonctions/gestion_logs.php
// Fonctions de gestion des logs et historique de connexion

// Require database connection
require_once __DIR__ . '/database.php';

/**
 * Enregistre une activité utilisateur dans la base de données
 * 
 * @param PDO $pdo Connexion PDO
 * @param int|null $id_utilisateur ID de l'utilisateur (null pour actions non authentifiées)
 * @param string $action Description de l'action
 * @param string|null $table_concernee Table concernée par l'action
 * @param int|null $id_enregistrement ID de l'enregistrement concerné
 * @param array|null $anciennes_valeurs Anciennes valeurs (pour les modifications)
 * @param array|null $nouvelles_valeurs Nouvelles valeurs (pour les modifications)
 * @return bool Succès de l'opération
 */
function logUserActivity(
    PDO $pdo,
    ?int $id_utilisateur,
    string $action,
    ?string $table_concernee = null,
    ?int $id_enregistrement = null,
    ?array $anciennes_valeurs = null,
    ?array $nouvelles_valeurs = null
): bool {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO logs_activite (
                id_utilisateur,
                action,
                table_concernee,
                id_enregistrement,
                anciennes_valeurs,
                nouvelles_valeurs,
                ip_adresse,
                user_agent,
                date_action
            ) VALUES (
                :id_utilisateur,
                :action,
                :table_concernee,
                :id_enregistrement,
                :anciennes_valeurs,
                :nouvelles_valeurs,
                :ip_adresse,
                :user_agent,
                NOW()
            )
        ");
        
        $stmt->bindValue(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
        $stmt->bindValue(':action', $action, PDO::PARAM_STR);
        $stmt->bindValue(':table_concernee', $table_concernee, PDO::PARAM_STR);
        $stmt->bindValue(':id_enregistrement', $id_enregistrement, PDO::PARAM_INT);
        $stmt->bindValue(':anciennes_valeurs', $anciennes_valeurs ? json_encode($anciennes_valeurs, JSON_UNESCAPED_UNICODE) : null, PDO::PARAM_STR);
        $stmt->bindValue(':nouvelles_valeurs', $nouvelles_valeurs ? json_encode($nouvelles_valeurs, JSON_UNESCAPED_UNICODE) : null, PDO::PARAM_STR);
        $stmt->bindValue(':ip_adresse', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', PDO::PARAM_STR);
        $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu', PDO::PARAM_STR);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erreur lors de l'enregistrement de l'activité: " . $e->getMessage());
        return false;
    }
}

/**
 * Enregistre une erreur d'application dans la base de données
 * 
 * @param PDO $pdo Connexion PDO
 * @param string $error_description Description de l'erreur
 * @param string|null $fichier Fichier source de l'erreur
 * @param int|null $ligne Ligne de l'erreur
 * @param array|null $contexte Contexte supplémentaire
 * @return bool Succès de l'opération
 */
function logApplicationError(
    PDO $pdo,
    string $error_description,
    ?string $fichier = null,
    ?int $ligne = null,
    ?array $contexte = null
): bool {
    try {
        // Créer une table d'erreurs si elle n'existe pas
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS logs_erreurs (
                id_log_erreur INT PRIMARY KEY AUTO_INCREMENT,
                description TEXT NOT NULL,
                fichier VARCHAR(255),
                ligne INT,
                contexte TEXT,
                ip_adresse VARCHAR(45),
                user_agent TEXT,
                date_erreur TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        $stmt = $pdo->prepare("
            INSERT INTO logs_erreurs (
                description,
                fichier,
                ligne,
                contexte,
                ip_adresse,
                user_agent,
                date_erreur
            ) VALUES (
                :description,
                :fichier,
                :ligne,
                :contexte,
                :ip_adresse,
                :user_agent,
                NOW()
            )
        ");
        
        $stmt->bindValue(':description', $error_description, PDO::PARAM_STR);
        $stmt->bindValue(':fichier', $fichier, PDO::PARAM_STR);
        $stmt->bindValue(':ligne', $ligne, PDO::PARAM_INT);
        $stmt->bindValue(':contexte', $contexte ? json_encode($contexte, JSON_UNESCAPED_UNICODE) : null, PDO::PARAM_STR);
        $stmt->bindValue(':ip_adresse', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', PDO::PARAM_STR);
        $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu', PDO::PARAM_STR);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        // Fallback: log dans un fichier si la DB échoue
        $logEntry = sprintf(
            "[%s] ERREUR: %s | Fichier: %s:%d | IP: %s | UserAgent: %s\n",
            date('Y-m-d H:i:s'),
            $error_description,
            $fichier ?? 'N/A',
            $ligne ?? 0,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu'
        );
        
        $logFile = __DIR__ . '/../logs/application_errors.log';
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        
        error_log($logEntry, 3, $logFile);
        return false;
    }
}

/**
 * Récupère les logs d'activité depuis la base de données
 * 
 * @param PDO $pdo Connexion PDO
 * @param int|null $id_utilisateur Filtrer par ID utilisateur
 * @param string|null $start_date Date de début (YYYY-MM-DD)
 * @param string|null $end_date Date de fin (YYYY-MM-DD)
 * @param string|null $table_concernee Filtrer par table
 * @param int $limit Limite de résultats
 * @param int $offset Offset pour pagination
 * @return array Tableau des logs
 */
function getAuditLogs(
    PDO $pdo,
    ?int $id_utilisateur = null,
    ?string $start_date = null,
    ?string $end_date = null,
    ?string $table_concernee = null,
    int $limit = 50,
    int $offset = 0
): array {
    $sql = "
        SELECT 
            la.id_log,
            la.id_utilisateur,
            u.login_utilisateur,
            u.nom_complet,
            la.action,
            la.table_concernee,
            la.id_enregistrement,
            la.anciennes_valeurs,
            la.nouvelles_valeurs,
            la.ip_adresse,
            la.user_agent,
            DATE_FORMAT(la.date_action, '%d/%m/%Y %H:%i:%s') as date_action_format
        FROM logs_activite la
        LEFT JOIN utilisateurs u ON la.id_utilisateur = u.id_utilisateur
    ";
    
    $where = [];
    $params = [];
    
    if ($id_utilisateur !== null) {
        $where[] = "la.id_utilisateur = :id_utilisateur";
        $params[':id_utilisateur'] = $id_utilisateur;
    }
    
    if ($table_concernee !== null) {
        $where[] = "la.table_concernee = :table_concernee";
        $params[':table_concernee'] = $table_concernee;
    }
    
    if ($start_date !== null) {
        $where[] = "DATE(la.date_action) >= :start_date";
        $params[':start_date'] = $start_date;
    }
    
    if ($end_date !== null) {
        $where[] = "DATE(la.date_action) <= :end_date";
        $params[':end_date'] = $end_date;
    }
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    $sql .= " ORDER BY la.date_action DESC LIMIT :limit OFFSET :offset";
    
    try {
        $stmt = $pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des logs d'audit: " . $e->getMessage());
        return [];
    }
}

/**
 * Compte le nombre total de logs d'activité pour la pagination
 * 
 * @param PDO $pdo Connexion PDO
 * @param int|null $id_utilisateur Filtrer par ID utilisateur
 * @param string|null $start_date Date de début
 * @param string|null $end_date Date de fin
 * @param string|null $table_concernee Filtrer par table
 * @return int Nombre total de logs
 */
function countAuditLogs(
    PDO $pdo,
    ?int $id_utilisateur = null,
    ?string $start_date = null,
    ?string $end_date = null,
    ?string $table_concernee = null
): int {
    $sql = "SELECT COUNT(*) FROM logs_activite la";
    
    $where = [];
    $params = [];
    
    if ($id_utilisateur !== null) {
        $where[] = "la.id_utilisateur = :id_utilisateur";
        $params[':id_utilisateur'] = $id_utilisateur;
    }
    
    if ($table_concernee !== null) {
        $where[] = "la.table_concernee = :table_concernee";
        $params[':table_concernee'] = $table_concernee;
    }
    
    if ($start_date !== null) {
        $where[] = "DATE(la.date_action) >= :start_date";
        $params[':start_date'] = $start_date;
    }
    
    if ($end_date !== null) {
        $where[] = "DATE(la.date_action) <= :end_date";
        $params[':end_date'] = $end_date;
    }
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des logs d'audit: " . $e->getMessage());
        return 0;
    }
}

/**
 * Enregistre une tentative de connexion
 * 
 * @param PDO $pdo Connexion PDO
 * @param string $username_attempted Nom d'utilisateur tenté
 * @param bool $is_successful Succès de la tentative
 * @param int|null $id_utilisateur ID utilisateur (si succès)
 * @param string|null $failure_reason Raison de l'échec
 * @return bool Succès de l'opération
 */
function recordLoginAttempt(
    PDO $pdo,
    string $username_attempted,
    bool $is_successful,
    ?int $id_utilisateur = null,
    ?string $failure_reason = null
): bool {
    try {
        // Créer la table si elle n'existe pas
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id_attempt INT PRIMARY KEY AUTO_INCREMENT,
                id_utilisateur INT,
                username_attempted VARCHAR(100) NOT NULL,
                login_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                is_successful BOOLEAN NOT NULL DEFAULT 0,
                failure_reason VARCHAR(255),
                FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        $stmt = $pdo->prepare("
            INSERT INTO login_attempts (
                id_utilisateur,
                username_attempted,
                ip_address,
                user_agent,
                is_successful,
                failure_reason
            ) VALUES (
                :id_utilisateur,
                :username_attempted,
                :ip_address,
                :user_agent,
                :is_successful,
                :failure_reason
            )
        ");
        
        $stmt->bindValue(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
        $stmt->bindValue(':username_attempted', $username_attempted, PDO::PARAM_STR);
        $stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', PDO::PARAM_STR);
        $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu', PDO::PARAM_STR);
        $stmt->bindValue(':is_successful', $is_successful, PDO::PARAM_BOOL);
        $stmt->bindValue(':failure_reason', $failure_reason, PDO::PARAM_STR);
        
        $success = $stmt->execute();
        
        // Si la connexion a échoué, incrémenter le compteur d'échecs
        if (!$is_successful && $id_utilisateur) {
            $stmt = $pdo->prepare("
                UPDATE utilisateurs 
                SET tentatives_echec = tentatives_echec + 1
                WHERE id_utilisateur = :id_utilisateur
            ");
            $stmt->bindValue(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        return $success;
    } catch (PDOException $e) {
        error_log("Erreur lors de l'enregistrement de la tentative de connexion: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère l'historique des connexions
 * 
 * @param PDO $pdo Connexion PDO
 * @param int|null $id_utilisateur Filtrer par ID utilisateur
 * @param bool|null $is_successful Filtrer par succès
 * @param string|null $start_date Date de début
 * @param string|null $end_date Date de fin
 * @param int $limit Limite de résultats
 * @param int $offset Offset pour pagination
 * @return array Historique des connexions
 */
function getLoginHistory(
    PDO $pdo,
    ?int $id_utilisateur = null,
    ?bool $is_successful = null,
    ?string $start_date = null,
    ?string $end_date = null,
    int $limit = 50,
    int $offset = 0
): array {
    $sql = "
        SELECT 
            la.id_attempt,
            la.id_utilisateur,
            u.login_utilisateur,
            u.nom_complet,
            la.username_attempted,
            DATE_FORMAT(la.login_timestamp, '%d/%m/%Y %H:%i:%s') as login_timestamp_format,
            la.ip_address,
            la.user_agent,
            la.is_successful,
            la.failure_reason
        FROM login_attempts la
        LEFT JOIN utilisateurs u ON la.id_utilisateur = u.id_utilisateur
    ";
    
    $where = [];
    $params = [];
    
    if ($id_utilisateur !== null) {
        $where[] = "la.id_utilisateur = :id_utilisateur";
        $params[':id_utilisateur'] = $id_utilisateur;
    }
    
    if ($is_successful !== null) {
        $where[] = "la.is_successful = :is_successful";
        $params[':is_successful'] = $is_successful ? 1 : 0;
    }
    
    if ($start_date !== null) {
        $where[] = "DATE(la.login_timestamp) >= :start_date";
        $params[':start_date'] = $start_date;
    }
    
    if ($end_date !== null) {
        $where[] = "DATE(la.login_timestamp) <= :end_date";
        $params[':end_date'] = $end_date;
    }
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    $sql .= " ORDER BY la.login_timestamp DESC LIMIT :limit OFFSET :offset";
    
    try {
        $stmt = $pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération de l'historique de connexion: " . $e->getMessage());
        return [];
    }
}

/**
 * Compte le nombre total d'entrées dans l'historique des connexions
 * 
 * @param PDO $pdo Connexion PDO
 * @param int|null $id_utilisateur Filtrer par ID utilisateur
 * @param bool|null $is_successful Filtrer par succès
 * @param string|null $start_date Date de début
 * @param string|null $end_date Date de fin
 * @return int Nombre total d'entrées
 */
function countLoginHistory(
    PDO $pdo,
    ?int $id_utilisateur = null,
    ?bool $is_successful = null,
    ?string $start_date = null,
    ?string $end_date = null
): int {
    $sql = "SELECT COUNT(*) FROM login_attempts la";
    
    $where = [];
    $params = [];
    
    if ($id_utilisateur !== null) {
        $where[] = "la.id_utilisateur = :id_utilisateur";
        $params[':id_utilisateur'] = $id_utilisateur;
    }
    
    if ($is_successful !== null) {
        $where[] = "la.is_successful = :is_successful";
        $params[':is_successful'] = $is_successful ? 1 : 0;
    }
    
    if ($start_date !== null) {
        $where[] = "DATE(la.login_timestamp) >= :start_date";
        $params[':start_date'] = $start_date;
    }
    
    if ($end_date !== null) {
        $where[] = "DATE(la.login_timestamp) <= :end_date";
        $params[':end_date'] = $end_date;
    }
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage de l'historique de connexion: " . $e->getMessage());
        return 0;
    }
}

/**
 * Récupère les statistiques de connexion
 * 
 * @param PDO $pdo Connexion PDO
 * @param string $period Période: 'day', 'week', 'month', 'year'
 * @return array Statistiques
 */
function getLoginStatistics(PDO $pdo, string $period = 'month'): array {
    $periods = [
        'day' => 'DATE(login_timestamp)',
        'week' => 'YEARWEEK(login_timestamp)',
        'month' => 'DATE_FORMAT(login_timestamp, "%Y-%m")',
        'year' => 'YEAR(login_timestamp)'
    ];
    
    if (!isset($periods[$period])) {
        $period = 'month';
    }
    
    $groupBy = $periods[$period];
    
    $sql = "
        SELECT 
            $groupBy as period,
            COUNT(*) as total_attempts,
            SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as successful_attempts,
            SUM(CASE WHEN is_successful = 0 THEN 1 ELSE 0 END) as failed_attempts
        FROM login_attempts
        WHERE login_timestamp >= DATE_SUB(NOW(), INTERVAL 1 $period)
        GROUP BY $groupBy
        ORDER BY period DESC
    ";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques de connexion: " . $e->getMessage());
        return [];
    }
}

/**
 * Nettoie les anciens logs
 * 
 * @param PDO $pdo Connexion PDO
 * @param int $days_to_keep Nombre de jours à conserver
 * @return bool Succès de l'opération
 */
function cleanupOldLogs(PDO $pdo, int $days_to_keep = 90): bool {
    try {
        // Nettoyer les logs d'activité
        $stmt = $pdo->prepare("
            DELETE FROM logs_activite 
            WHERE date_action < DATE_SUB(NOW(), INTERVAL :days DAY)
        ");
        $stmt->bindValue(':days', $days_to_keep, PDO::PARAM_INT);
        $stmt->execute();
        
        // Nettoyer l'historique des connexions
        $stmt = $pdo->prepare("
            DELETE FROM login_attempts 
            WHERE login_timestamp < DATE_SUB(NOW(), INTERVAL :days DAY)
        ");
        $stmt->bindValue(':days', $days_to_keep, PDO::PARAM_INT);
        $stmt->execute();
        
        // Nettoyer les logs d'erreurs
        $stmt = $pdo->prepare("
            DELETE FROM logs_erreurs 
            WHERE date_erreur < DATE_SUB(NOW(), INTERVAL :days DAY)
        ");
        $stmt->bindValue(':days', $days_to_keep, PDO::PARAM_INT);
        $stmt->execute();
        
        return true;
    } catch (PDOException $e) {
        error_log("Erreur lors du nettoyage des anciens logs: " . $e->getMessage());
        return false;
    }
}