<?php
// pages/admin/configuration/restore_default.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Vérification de sécurité (Rôle 'patron' uniquement)
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'patron') {
    header("Location: ../../../index.php?error=Accès non autorisé");
    exit();
}

$configPath = '../../../fonctions/config/';
$configFile = $configPath . 'config.ini';
$defaultFile = $configPath . 'config.default.ini';
$backupFile = $configPath . 'config.backup.' . date('Ymd_His') . '.ini';

// 2. Vérifier si le fichier par défaut existe
if (!file_exists($defaultFile)) {
    $_SESSION['flash_message'] = "Erreur : Le fichier de configuration par défaut (config.default.ini) est introuvable.";
    $_SESSION['flash_type'] = "error";
    header("Location: index.php");
    exit();
}

try {
    // 3. Créer une sauvegarde de sécurité du fichier actuel avant écrasement
    if (file_exists($configFile)) {
        if (!copy($configFile, $backupFile)) {
            throw new Exception("Impossible de créer une sauvegarde de sécurité du fichier actuel.");
        }
    }

    // 4. Restaurer les paramètres par défaut
    if (copy($defaultFile, $configFile)) {
        // Invalider le cache PHP
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($configFile, true);
        }

        $_SESSION['flash_message'] = "Configuration restaurée avec succès. Une sauvegarde de votre ancienne configuration a été créée sous le nom : " . basename($backupFile);
        $_SESSION['flash_type'] = "success";
    } else {
        throw new Exception("Échec de la copie du fichier par défaut.");
    }

} catch (Exception $e) {
    $_SESSION['flash_message'] = "Erreur lors de la restauration : " . $e->getMessage();
    $_SESSION['flash_type'] = "error";
}

// 5. Retour à la page de configuration
header("Location: index.php");
exit();