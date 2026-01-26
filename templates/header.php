<?php
// templates/header.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Logique de Connexion & Identité (Basée sur votre table 'utilisateurs')
$estConnecte = isset($_SESSION['utilisateur_id']);
$nomUtilisateur = $estConnecte ? ($_SESSION['nom_complet'] ?? 'Utilisateur') : 'Invité';
$roleUtilisateur = $_SESSION['nom_role'] ?? ''; // ex: Patron, Caissier

// 2. Paramètres d'affichage
$titrePage = isset($titre) ? $titre : 'Pressing / Commerce Manager';
$couleurPrincipale = '#2c3e50'; // Bleu nuit professionnel pour un Pressing
$couleurAccent = '#ffff';

// 3. Gestion de l'activité
$derniereActivite = $_SESSION['LAST_ACTIVITY'] ?? null;
$formatDerniereActivite = ($estConnecte && $derniereActivite) ? date('d/m/Y H:i', $derniereActivite) : date('d/m/Y H:i');

// 4. Traitement Déconnexion
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: /pressing_manager/index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titrePage) ?></title>

    <link rel="stylesheet" href="../../css/bootstrap-3.4.1.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="shortcut icon" href="../../images/laundry_icon.ico" type="image/x-icon">
    
    <style>
        /* --- Styles Critiques & Structure --- */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding-top: 70px;
            margin: 0;
            transition: all 0.3s ease;
        }

        .main-header {
            background-color: <?= $couleurPrincipale ?>;
            color: white;
            padding: 0 20px;
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 65px;
            z-index: 1050;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo-area h1 {
            margin: 0;
            font-size: 1.4em;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .logo-area span { color: <?= $couleurAccent ?>; }

        .user-controls {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .info-block {
            text-align: right;
            line-height: 1.2;
            border-right: 1px solid rgba(255,255,255,0.2);
            padding-right: 15px;
        }

        .user-name { font-weight: bold; font-size: 0.95em; color: #fff; }
        .user-role { font-size: 0.75em; color: <?= $couleurAccent ?>; text-transform: uppercase; }

        /* --- Boutons Utilitaires --- */
        .header-actions { display: flex; gap: 8px; }
        
        .btn-tool {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-tool:hover { background: rgba(255,255,255,0.2); }

        .btn-logout { background: #e74c3c; border: none; }
        .btn-logout:hover { background: #c0392b; }

        /* --- Mode Sombre Forcé --- */
        body.dark-mode { background-color: #121212; color: #e0e0e0; }
        body.dark-mode .main-header { background-color: #000; border-bottom: 1px solid #333; }
        body.dark-mode .card, body.dark-mode .panel { background-color: #1e1e1e; border-color: #333; }

        /* Spinner de chargement */
        #page-loader {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: white; z-index: 9999; display: flex;
            align-items: center; justify-content: center;
        }
        .spinner {
            width: 40px; height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid <?= $couleurAccent ?>;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        @media (max-width: 768px) {
            .info-block { display: none; }
            .logo-area h1 { font-size: 1.1em; }
        }
    </style>
</head>
<body>

<div id="page-loader"><div class="spinner"></div></div>

<header class="main-header">
   <div class="logo-area">
    <?php 
// On définit dynamiquement la racine du projet si ce n'est pas déjà fait
// Cela permet au logo de s'afficher peu importe la profondeur du dossier
$root = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/pressing_manager/";
?>

<div style="display: flex; align-items: center; gap: 15px;">
    <img src="<?php echo $root; ?>images/Logo Kayade.jpeg" alt="Logo Kayade" 
         style="height: 63px; width: auto; border-radius: 8px; box-shadow: 0px 2px 4px rgba(0,0,0,0.2);">

    <h1 style="color: white; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-weight: 800; text-transform: uppercase; letter-spacing: -1px; margin: 0; line-height: 1.2;">
        <span style="color: #FFD700; text-shadow: 0px 2px 4px rgba(0,0,0,0.3);">Kayade</span> 
        PRESSING/COMMERCE <span style="font-weight: 300; opacity: 0.9;">Manager</span>
    </h1>
</div>
</div>

    <div class="user-controls">
        <div class="header-actions">
            <button onclick="toggleDarkMode()" class="btn-tool" title="Mode Sombre">
                Clair/Sombre
            </button>
            <button onclick="changeFontSize(-1)" class="btn-tool">A-</button>
            <button onclick="changeFontSize(1)" class="btn-tool">A+</button>
        </div>

        <div class="info-block">
            <div class="user-name"><?= htmlspecialchars($nomUtilisateur) ?></div>
            <div class="user-role"><?= htmlspecialchars($roleUtilisateur) ?></div>
            <div id="live-clock" style="font-size: 0.8em; opacity: 0.7;">00:00:00</div>
        </div>

        <div class="header-actions">
            <?php if ($estConnecte): ?>
                <a href="mon_compte.php" class="btn-tool" title="Paramètres">
                   Paramètres
                </a>
                <form method="post" style="display:inline;">
                    <button type="submit" name="logout" class="btn-tool btn-logout">
                       Quitter
                    </button>
                </form>
            <?php else: ?>
                <a href="../../index.php" class="btn-tool">Connexion</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<script>
    // 1. Gestion du chargement
    window.addEventListener('load', () => {
        document.getElementById('page-loader').style.display = 'none';
    });

    // 2. Horloge Temps Réel
    function updateClock() {
        const now = new Date();
        document.getElementById('live-clock').textContent = now.toLocaleTimeString('fr-FR');
    }
    setInterval(updateClock, 1000);
    updateClock();

    // 3. Mode Sombre (Persistant)
    function toggleDarkMode() {
        const isDark = document.body.classList.toggle('dark-mode');
        localStorage.setItem('pressing-dark-mode', isDark ? 'enabled' : 'disabled');
    }

    if (localStorage.getItem('pressing-dark-mode') === 'enabled') {
        document.body.classList.add('dark-mode');
    }

    // 4. Taille de police
    let currentFontSize = 100;
    function changeFontSize(delta) {
        currentFontSize += (delta * 5);
        document.body.style.fontSize = currentFontSize + '%';
    }
</script>