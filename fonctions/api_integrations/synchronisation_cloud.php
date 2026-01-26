<?php
/**
 * Synchronise les photos et la base de données vers le Cloud
 */
function synchroniserVersCloud() {
    // 1. Exécuter un dump de la DB
    $backup_file = $root . '/backups/db_' . date('Ymd_His') . '.sql';
    exec("mysqldump -u " . DB_USER . " -p" . DB_PASS . " " . DB_NAME . " > " . $backup_file);

    // 2. Envoyer via cURL ou SDK vers le serveur de secours
    // simulation :
    $ch = curl_init('https://cloud-backup.votrepressing.com/upload');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile($backup_file)]);
    $result = curl_exec($ch);
    
    return $result;
}