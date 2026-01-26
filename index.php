<?php
session_start();

// --- 1. DÉCONNEXION ---
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    $_SESSION = array();
    session_destroy();
    header('Location: index.php');
    exit;
}

// --- 2. GESTION DE LA LANGUE ---
$lang = $_SESSION['lang'] ?? 'fr';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$translations = [
    'fr' => [
        'app_name' => 'KAYADE Manager',
        'app_tagline' => 'Gestion Commerciale',
        'username_placeholder' => 'Identifiant',
        'password_placeholder' => 'Mot de passe',
        'show_password' => 'Voir',
        'hide_password' => 'Cacher',
        'login_button' => 'Connexion',
        'guest_login' => 'Invité',
        'remember_me' => 'Se souvenir',
        'forgot_password' => 'Mot de passe oublié ?',
        'dashboard' => 'Tableau de bord',
        'logout' => 'Déconnexion',
        'loading' => 'Chargement...'
    ],
    'en' => [
        'app_name' => 'KAYADE Manager',
        'app_tagline' => 'Business Management',
        'username_placeholder' => 'Username',
        'password_placeholder' => 'Password',
        'show_password' => 'Show',
        'hide_password' => 'Hide',
        'login_button' => 'Login',
        'guest_login' => 'Guest',
        'remember_me' => 'Remember me',
        'forgot_password' => 'Forgot password?',
        'dashboard' => 'Dashboard',
        'logout' => 'Logout',
        'loading' => 'Loading...'
    ]
];

$text = $translations[$lang] ?? $translations['fr'];

// Fonction pour mettre KAYADE en jaune
function highlightKayade($str) {
    return str_replace('KAYADE', '<span class="highlight-name">KAYADE</span>', $str);
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KAYADE - Login</title>
    
    <style>
        :root {
            --primary: #2c3e50;
            --accent: #3498db;
            --yellow: #f1c40f;
            --light: #f8f9fa;
            --gray: #95a5a6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a2530 0%, #2c3e50 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 360px;
            overflow: hidden;
            position: relative;
        }

        .card-header {
            background: var(--primary);
            color: white;
            padding: 25px 30px 20px;
            text-align: center;
            position: relative;
        }

        .card-header h1 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .card-header p {
            font-size: 0.85rem;
            opacity: 0.9;
            margin: 0;
        }

        .highlight-name {
            color: var(--yellow);
            font-weight: 700;
        }

        .logo-container {
            margin-bottom: 15px;
        }

        .logo-container img {
            height: 70px;
            width: auto;
        }

        .language-switcher {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 10;
        }

        .lang-btn {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            font-weight: 500;
        }

        .lang-menu {
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: none;
            min-width: 80px;
            overflow: hidden;
        }

        .lang-item {
            display: block;
            padding: 8px 12px;
            color: var(--primary);
            text-decoration: none;
            font-size: 0.85rem;
            border-bottom: 1px solid #eee;
        }

        .lang-item:last-child {
            border-bottom: none;
        }

        .lang-item:hover {
            background: #f5f5f5;
        }

        .card-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: border 0.2s;
            background: var(--light);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .input-icon {
            position: relative;
        }

        .input-icon::before {
            content: '';
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            background-size: contain;
            background-repeat: no-repeat;
        }

        .user-icon::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2395a5a6'%3E%3Cpath d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/%3E%3C/svg%3E");
        }

        .lock-icon::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2395a5a6'%3E%3Cpath d='M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z'/%3E%3C/svg%3E");
        }

        .input-icon input {
            padding-left: 40px;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            font-size: 0.8rem;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 3px;
        }

        .password-toggle:hover {
            background: #eee;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .checkbox-group label {
            font-size: 0.9rem;
            color: #555;
            cursor: pointer;
        }

        .btn-login {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 14px;
            width: 100%;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-bottom: 15px;
        }

        .btn-login:hover {
            background: #1a252f;
        }

        .btn-guest {
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px;
            width: 100%;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-guest:hover {
            background: #219653;
        }

        .forgot-link {
            display: block;
            text-align: center;
            color: var(--accent);
            font-size: 0.85rem;
            margin-top: 15px;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        /* Session active */
        .session-active {
            text-align: center;
            padding: 30px;
        }

        .session-active .alert {
            background: #e8f4fd;
            border: 1px solid #b6e0fe;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        /* Loading */
        #loading {
            position: fixed;
            inset: 0;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.3s;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            100% { transform: rotate(360deg); }
        }

        /* Footer */
        footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            padding: 10px;
            background: rgba(26, 37, 48, 0.9);
            color: rgba(255,255,255,0.7);
            font-size: 0.75rem;
        }

        /* Responsive */
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .login-card {
                max-width: 100%;
            }
            
            .card-body {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    
    <div id="loading">
        <div class="spinner"></div>
        <div style="margin-top: 15px; color: var(--primary); font-size: 0.9rem;">
            <?= $text['loading'] ?>
        </div>
    </div>

    <div class="login-card">
        <div class="card-header">
            <div class="language-switcher">
                <button class="lang-btn" onclick="toggleLang()">
                    <?= strtoupper($lang) ?>
                </button>
                <div class="lang-menu" id="langMenu">
                    <a class="lang-item" href="?lang=fr">FR</a>
                    <a class="lang-item" href="?lang=en">EN</a>
                </div>
            </div>
            
           <div class="logo-container" style="text-align: center; width: 100%;">
    <?php if (file_exists('images/Logo Kayade.jpeg')): ?>
        <img src="images/Logo Kayade.jpeg" 
             alt="KAYADE" 
             style="display: block; 
                    margin: 20px auto; 
                    max-width: 130px; 
                    height: auto; 
                    border-radius: 25px; 
                    background-color: #ffffff; 
                    padding: 10px; 
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                    border: 1px solid rgba(255, 255, 255, 0.5);">
    <?php endif; ?> 
</div>
            
            <h1><?= highlightKayade($text['app_name']) ?></h1>
            <p><?= $text['app_tagline'] ?></p>
        </div>

        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != ''): ?>
            <div class="session-active">
                <div class="alert">
                    Connecté : <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Utilisateur') ?></strong>
                </div>
                <button onclick="window.location.href='pages/dashboard.php'" class="btn-login">
                    <?= $text['dashboard'] ?>
                </button>
                <a href="?logout=1" class="forgot-link" style="margin-top: 10px;">
                    <?= $text['logout'] ?>
                </a>
            </div>
        <?php else: ?>
            <div class="card-body">
                <form action="pages/authentification.php" method="POST" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="form-group">
                        <div class="input-icon user-icon">
                            <input type="text" class="form-control" name="login" placeholder="<?= $text['username_placeholder'] ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-icon lock-icon">
                            <input type="password" class="form-control" id="password" name="password" placeholder="<?= $text['password_placeholder'] ?>" required>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <?= $text['show_password'] ?>
                            </button>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="rememberMe" name="rememberMe">
                        <label for="rememberMe"><?= $text['remember_me'] ?></label>
                    </div>

                    <button type="submit" class="btn-login">
                        <?= $text['login_button'] ?>
                    </button>

                    <button type="submit" name="guestLogin" class="btn-guest">
                        <?= $text['guest_login'] ?>
                    </button>

                    <a href="forgot_password.php" class="forgot-link">
                        <?= $text['forgot_password'] ?>
                    </a>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        &copy; <?= date('Y') ?> <?= highlightKayade($text['app_name']) ?>
    </footer>

    <script>
        // Cacher le loading
        window.addEventListener('load', function() {
            setTimeout(() => {
                document.getElementById('loading').style.opacity = '0';
                setTimeout(() => {
                    document.getElementById('loading').style.display = 'none';
                }, 300);
            }, 500);
        });

        // Gestion langue
        function toggleLang() {
            const menu = document.getElementById('langMenu');
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }

        // Fermer le menu langue en cliquant ailleurs
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.language-switcher')) {
                document.getElementById('langMenu').style.display = 'none';
            }
        });

        // Toggle mot de passe
        function togglePassword() {
            const pwd = document.getElementById('password');
            const btn = document.querySelector('.password-toggle');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                btn.textContent = '<?= $text["hide_password"] ?>';
            } else {
                pwd.type = 'password';
                btn.textContent = '<?= $text["show_password"] ?>';
            }
        }

        // Gestion soumission formulaire
        document.getElementById('loginForm')?.addEventListener('submit', function() {
            document.getElementById('loading').style.display = 'flex';
            document.getElementById('loading').style.opacity = '1';
        });
    </script>
</body>
</html>