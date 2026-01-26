<?php
// pages/admin/configuration/reload_config.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Vérification de sécurité (Rôle 'patron' uniquement)
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'patron') {
    header("Location: ../../../index.php?error=Accès non autorisé");
    exit();
}

/**
 * Le rechargement de la configuration consiste principalement à :
 * - Forcer PHP à relire le fichier config.ini (vider le cache statique)
 * - S'assurer que les variables de session liées à la config sont actualisées si nécessaire
 */

$configFile = '../../../fonctions/config/config.ini';

if (file_exists($configFile)) {
    // 2. Invalider le cache d'OPcache pour ce fichier si activé
    if (function_exists('opcache_invalidate')) {
        opcache_invalidate($configFile, true);
    }

    // 3. Tenter une lecture de test pour vérifier la validité du fichier
    $testConfig = parse_ini_file($configFile, true);

    if ($testConfig !== false) {
        // Optionnel : On peut stocker la date de dernière actualisation en session
        $_SESSION['config_last_reload'] = date('Y-m-d H:i:s');
        
        $_SESSION['flash_message'] = "La configuration a été rechargée avec succès depuis le fichier source.";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Erreur : Le fichier de configuration est corrompu ou mal formaté.";
        $_SESSION['flash_type'] = "error";
    }
} else {
    $_SESSION['flash_message'] = "Erreur : Le fichier config.ini est introuvable.";
    $_SESSION['flash_type'] = "error";
}

// 4. Redirection vers la page de configuration
header("Location: index.php");
exit();