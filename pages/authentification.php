<?php
/**
 * authentification.php - Version Hybride (Redis/MySQL) avec Honeypot 🍯
 * Sécurité : CSRF, Honeypot, Rate Limiting, Session Fixation Protection.
 */

// --- 1. Sécurité et Erreurs ---
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

require_once __DIR__ . "/../fonctions/database.php"; 
require_once __DIR__ . "/../fonctions/gestion_utilisateurs.php";

// --- 2. Initialisation de Redis (Mode Résilient) ---
$redis = null;
$useRedis = false;
try {
    if (class_exists('Redis')) {
        $redis = new Redis();
        if ($redis->connect('127.0.0.1', 6379, 0.5)) {
            $useRedis = true;
        }
    }
} catch (Exception $e) {
    error_log("Redis indisponible : " . $e->getMessage());
    $useRedis = false;
}

// --- 3. Configuration Session Sécurisée ---
session_set_cookie_params([
    'lifetime' => 0, 
    'path' => '/', 
    'domain' => $_SERVER['HTTP_HOST'] ?? '', 
    'httponly' => true, 
    'secure' => isset($_SERVER['HTTPS']), 
    'samesite' => 'Strict',
]);

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- 4. Fonctions de Sécurité ---

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Gestionnaire de tentatives de connexion via Redis
 */
function checkRateLimit($login, $redis, $useRedis) {
    if ($useRedis) {
        $key = "login_attempt:" . md5($login);
        $attempts = $redis->get($key);
        return ($attempts && $attempts >= 5);
    }
    return false;
}

/**
 * Logique d'authentification
 */
function authentifier($pdo, $login, $password, $redis, $useRedis, $maxAttempts = 5) {
    try {
        if (checkRateLimit($login, $redis, $useRedis)) {
            return "Trop de tentatives. Veuillez patienter.";
        }

        $sql = "SELECT u.*, r.nom_role 
                FROM utilisateurs u
                LEFT JOIN roles r ON u.id_role = r.id_role
                WHERE u.login_utilisateur = :login LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':login' => $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            usleep(random_int(100000, 300000));
            return "Identifiants incorrects.";
        }

        if ($user['tentatives_echec'] >= $maxAttempts && strtotime($user['date_blocage']) > time()) {
            return "Compte bloqué temporairement.";
        }

        if (password_verify($password, $user['mot_de_passe'])) {
            // SUCCÈS
            $pdo->prepare("UPDATE utilisateurs SET tentatives_echec = 0, date_blocage = NULL, derniere_connexion = NOW() WHERE id_utilisateur = ?")
                ->execute([$user['id_utilisateur']]);
            
            if ($useRedis) { $redis->del("login_attempt:" . md5($login)); }
            unset($user['mot_de_passe']);
            return $user;
        } else {
            // ÉCHEC
            $newAttempts = $user['tentatives_echec'] + 1;
            $lockUntil = ($newAttempts >= $maxAttempts) ? date('Y-m-d H:i:s', strtotime('+15 minutes')) : null;
            
            $pdo->prepare("UPDATE utilisateurs SET tentatives_echec = ?, date_blocage = ? WHERE id_utilisateur = ?")
                ->execute([$newAttempts, $lockUntil, $user['id_utilisateur']]);
            
            if ($useRedis) {
                $key = "login_attempt:" . md5($login);
                $redis->incr($key);
                $redis->expire($key, 900);
            }
            return "Identifiants incorrects.";
        }
    } catch (PDOException $e) {
        error_log("Erreur Critique Auth: " . $e->getMessage());
        return "Erreur technique.";
    }
}

// --- 5. Traitement du POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // A. HONEYPOT CHECK 🍯
    // Si 'phone_home' est rempli, c'est un robot.
    if (!empty($_POST['phone_home'])) {
        error_log("Spam detected via Honeypot: " . ($_POST['login'] ?? 'unknown'));
        // On simule une réussite ou on redirige sans erreur pour ne pas donner d'indice au robot
        header("Location: ../index.php?success=1"); 
        exit();
    }

    // B. CSRF CHECK
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: ../index.php?error=Session expirée");
        exit();
    }

    $login = sanitizeInput($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        header("Location: ../index.php?error=Champs requis");
        exit();
    }

    $authResult = authentifier($pdo, $login, $password, $redis, $useRedis);

    if (is_array($authResult)) {
        session_regenerate_id(true);

        $_SESSION['utilisateur_id'] = $authResult['id_utilisateur'];
        $_SESSION['nom_complet']    = $authResult['nom_complet'];
        $_SESSION['role']           = $authResult['nom_role'];
        $_SESSION['code_agence']    = $authResult['code_agence'];
        $_SESSION['last_activity']  = time();

        if ($useRedis) {
            $redis->setex("user_session:".$authResult['id_utilisateur'], 3600, json_encode([
                'role' => $authResult['nom_role'],
                'ip' => $_SERVER['REMOTE_ADDR']
            ]));
        }

        // Redirections par rôle
        $redirectMap = [
            'patron'            => '../pages/admin/dashboard.php',
            'admin'             => '../pages/admin/dashboard.php',
            'caissier'          => '../pages/caisse/index.php',
            'caissière'         => '../pages/caisse/index.php',
            'receptionniste'    => '../pages/Tickets/create.php',
            'réceptionniste'    => '../pages/Tickets/create.php',
            'gestionnaire_stock'=> '../pages/stock/inventory.php',
            'employe_pressing'  => '../pages/reception/receptionniste.php'
        ];

        $roleKey = mb_strtolower($authResult['nom_role'], 'UTF-8');
        $location = $redirectMap[$roleKey] ?? '../pages/tableau_bord.php';
        
        header("Location: " . $location);
        exit();

    } else {
        header("Location: ../index.php?error=" . urlencode($authResult));
        exit();
    }
}