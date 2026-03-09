<?php
/**
 * authentification.php - Version corrigée pour InfinityFree
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

require_once __DIR__ . "/../fonctions/database.php"; 
require_once __DIR__ . "/../fonctions/gestion_utilisateurs.php";

// --- Détection environnement ---
$isLocal = (
    $_SERVER['REMOTE_ADDR'] === '127.0.0.1' || 
    $_SERVER['REMOTE_ADDR'] === '::1' ||
    strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
);

// --- Redis (désactivé sur InfinityFree) ---
$redis    = null;
$useRedis = false;
if ($isLocal) {
    try {
        if (class_exists('Redis')) {
            $redis = new Redis();
            if ($redis->connect('127.0.0.1', 6379, 0.5)) {
                $useRedis = true;
            }
        }
    } catch (Exception $e) {
        error_log("Redis indisponible : " . $e->getMessage());
    }
}

// --- Configuration Session adaptée à l'environnement ---
if ($isLocal) {
    // Configuration locale complète
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'httponly' => true,
        'secure'   => false,
        'samesite' => 'Strict',
    ]);
} else {
    // Configuration simplifiée pour InfinityFree
    // ⚠️ Ne pas spécifier domain sur hébergement mutualisé
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_path', '/');
    ini_set('session.use_strict_mode', 1);
    // Pas de session_set_cookie_params() — trop risqué sur mutualisé
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- Fonctions ---

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function checkRateLimit($login, $redis, $useRedis) {
    if ($useRedis && $redis) {
        $key      = "login_attempt:" . md5($login);
        $attempts = $redis->get($key);
        return ($attempts && $attempts >= 5);
    }
    return false;
}

function getRedirectByRole($roleName) {
    $roleLower = mb_strtolower(trim($roleName), 'UTF-8');

    $redirectMap = [
        'patron'             => '../pages/admin/dashboard.php',
        'admin'              => '../pages/admin/dashboard.php',
        'directeur'          => '../pages/admin/dashboard.php',
        'caissier'           => '../pages/caisse/index.php',
        'caissière'          => '../pages/caisse/index.php',
        'caissier_boutique'  => '../pages/caisse/index.php',
        'receptionniste'     => '../pages/Tickets/create.php',
        'réceptionniste'     => '../pages/Tickets/create.php',
        'reception_hotel'    => '../pages/Tickets/create.php',
        'gestionnaire_stock' => '../pages/stock/inventory.php',
        'gestion_hotel'      => '../pages/admin/gestion_hotel.php',
        'gestion_commerce'   => '../pages/admin/gestion_commerce.php',
        'employe_pressing'   => '../pages/reception/receptionniste.php',
        'service_chambre'    => '../pages/service_chambre/dashboard.php',
        'vendeur_boutique'   => '../pages/boutique/ventes.php',
    ];

    return $redirectMap[$roleLower] ?? '../pages/tableau_bord.php';
}

function authentifier($pdo, $login, $password, $redis, $useRedis, $maxAttempts = 5) {
    try {
        if (checkRateLimit($login, $redis, $useRedis)) {
            return "Trop de tentatives. Veuillez patienter.";
        }

        $sql = "SELECT u.*, r.nom_role 
                FROM utilisateurs u
                LEFT JOIN roles r ON u.id_role = r.id_role
                WHERE u.login_utilisateur = :login 
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':login' => $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            usleep(random_int(100000, 300000));
            return "Identifiants incorrects.";
        }

        if (!$user['est_actif']) {
            return "Compte désactivé. Contactez l'administrateur.";
        }

        if (
            $user['tentatives_echec'] >= $maxAttempts &&
            !empty($user['date_blocage']) &&
            strtotime($user['date_blocage']) > time()
        ) {
            $remaining = strtotime($user['date_blocage']) - time();
            return "Compte bloqué. Réessayez dans " . ceil($remaining / 60) . " minute(s).";
        }

        if (password_verify($password, $user['mot_de_passe'])) {

            $pdo->prepare("UPDATE utilisateurs 
                           SET tentatives_echec = 0, 
                               date_blocage = NULL, 
                               derniere_connexion = NOW() 
                           WHERE id_utilisateur = ?")
                ->execute([$user['id_utilisateur']]);

            if ($useRedis && $redis) {
                $redis->del("login_attempt:" . md5($login));
            }

            unset($user['mot_de_passe'], $user['remember_token']);
            return $user;

        } else {

            $newAttempts = $user['tentatives_echec'] + 1;
            $lockUntil   = ($newAttempts >= $maxAttempts)
                ? date('Y-m-d H:i:s', strtotime('+15 minutes'))
                : null;

            $pdo->prepare("UPDATE utilisateurs 
                           SET tentatives_echec = ?, date_blocage = ? 
                           WHERE id_utilisateur = ?")
                ->execute([$newAttempts, $lockUntil, $user['id_utilisateur']]);

            if ($useRedis && $redis) {
                $key = "login_attempt:" . md5($login);
                $redis->incr($key);
                $redis->expire($key, 900);
            }

            return "Identifiants incorrects.";
        }

    } catch (PDOException $e) {
        error_log("Erreur Auth: " . $e->getMessage());
        return "Erreur technique lors de l'authentification.";
    }
}

// ================================================================
// --- Traitement POST ---
// ================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Honeypot
    if (!empty($_POST['phone_home'])) {
        usleep(random_int(500000, 1000000));
        header("Location: ../index.php?success=1");
        exit();
    }

    // CSRF
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        header("Location: ../index.php?error=" . urlencode("Session expirée. Veuillez réessayer."));
        exit();
    }

    $login    = sanitizeInput($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        header("Location: ../index.php?error=" . urlencode("Tous les champs sont requis."));
        exit();
    }

    $authResult = authentifier($pdo, $login, $password, $redis, $useRedis);

    if (is_array($authResult)) {

        // ⚠️ CORRECTION PRINCIPALE :
        // Sur InfinityFree, session_regenerate_id(true) détruit la session
        // On l'utilise uniquement en local
        if ($isLocal) {
            session_regenerate_id(true);
        }

        // Stocker toutes les données APRÈS la régénération (ou sans elle)
        $_SESSION['utilisateur_id'] = $authResult['id_utilisateur'];
        $_SESSION['nom_complet']    = $authResult['nom_complet'];
        $_SESSION['role']           = $authResult['nom_role'];
        $_SESSION['nom_role']       = $authResult['nom_role'];
        $_SESSION['id_agence']      = $authResult['id_agence']    ?? null;
        $_SESSION['code_agence']    = $authResult['code_agence']  ?? null;
        $_SESSION['email']          = $authResult['email']        ?? null;
        $_SESSION['LAST_ACTIVITY']  = time();
        $_SESSION['ip_address']     = $_SERVER['REMOTE_ADDR'];

        // ⚠️ Forcer l'écriture de la session avant la redirection
        session_write_close();

        $redirectUrl = getRedirectByRole($authResult['nom_role']);

        error_log("Connexion OK - " . $authResult['nom_complet'] .
                  " | Rôle: " . $authResult['nom_role'] .
                  " | Local: " . ($isLocal ? 'oui' : 'non'));

        header("Location: " . $redirectUrl);
        exit();

    } else {
        header("Location: ../index.php?error=" . urlencode($authResult));
        exit();
    }
}

// --- Déjà connecté ---
if (isset($_SESSION['utilisateur_id']) && isset($_SESSION['role'])) {
    header("Location: " . getRedirectByRole($_SESSION['role']));
    exit();
}

// --- Fallback ---
header("Location: ../index.php");
exit();
?>