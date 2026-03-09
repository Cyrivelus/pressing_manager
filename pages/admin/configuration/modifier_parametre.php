<?php
// pages/admin/configuration/modifier_parametre.php

session_start();

// Protection de base (à adapter selon votre système)
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'admin') {
    // header("Location: ../../../login.php"); // Décommenter en production
}

$message = '';
$messageType = ''; 

$param = $_GET['param'] ?? '';
$section = '';
$nomParametre = '';
$valeurActuelle = '';
$iniKey = ''; // La clé réelle dans le fichier INI

$configFile = __DIR__ . '/../../../fonctions/config/config.ini';
$config = [];

if (empty($param)) {
    $message = "Paramètre non spécifié dans l'URL.";
    $messageType = 'danger';
} else {
    if (!file_exists($configFile)) {
        $message = "Erreur: Fichier de configuration introuvable.";
        $messageType = 'danger';
    } else {
        $config = parse_ini_file($configFile, true);

        // MAPPING DES PARAMÈTRES
        // On lie le nom reçu dans l'URL (?param=...) à la section et la clé du fichier INI
        switch ($param) {
            // --- Section [app] ---
            case 'name':
            case 'app_name':
                $section = 'app';
                $iniKey = 'name';
                $nomParametre = "Nom de l'Application";
                break;
            case 'version':
                $section = 'app';
                $iniKey = 'version';
                $nomParametre = "Version";
                break;
            case 'env':
                $section = 'app';
                $iniKey = 'env';
                $nomParametre = "Environnement";
                break;
            case 'url':
                $section = 'app';
                $iniKey = 'url';
                $nomParametre = "URL de l'application";
                break;

            // --- Section [database] ---
            case 'host':
            case 'db_host':
                $section = 'database';
                $iniKey = 'host';
                $nomParametre = "Hôte de la base de données";
                break;
            case 'dbname':
                $section = 'database';
                $iniKey = 'dbname';
                $nomParametre = "Nom de la base de données";
                break;
            case 'username':
                $section = 'database';
                $iniKey = 'username';
                $nomParametre = "Utilisateur SQL";
                break;
            case 'charset':
                $section = 'database';
                $iniKey = 'charset';
                $nomParametre = "Encodage (Charset)";
                break;

            default:
                $message = "Paramètre URL inconnu: " . htmlspecialchars($param);
                $messageType = 'danger';
                break;
        }

        // Récupération de la valeur si aucune erreur
        if ($messageType === '' && isset($config[$section][$iniKey])) {
            $valeurActuelle = $config[$section][$iniKey];
        } elseif ($messageType === '') {
            $message = "Clé [$iniKey] introuvable dans la section [$section] du fichier INI.";
            $messageType = 'danger';
        }
    }
}

// TRAITEMENT DU FORMULAIRE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $messageType === '') {
    $nouvelleValeur = $_POST['valeur'] ?? '';

    // Mise à jour du tableau PHP
    $config[$section][$iniKey] = $nouvelleValeur;

    // Reconstruction du contenu INI
    $newContent = '';
    foreach ($config as $sectionName => $sectionData) {
        $newContent .= "[" . $sectionName . "]\n";
        foreach ($sectionData as $key => $value) {
            // On protège les valeurs avec des guillemets
            $newContent .= $key . " = \"" . str_replace('"', '\"', $value) . "\"\n";
        }
        $newContent .= "\n";
    }

    if (file_put_contents($configFile, $newContent)) {
        $message = "Le paramètre '" . htmlspecialchars($nomParametre) . "' a été mis à jour.";
        $messageType = 'success';
        $valeurActuelle = $nouvelleValeur; // Pour l'affichage immédiat
    } else {
        $message = "Erreur d'écriture : vérifiez les permissions sur le fichier config.ini.";
        $messageType = 'danger';
    }
}

// On définit le titre avant l'inclusion du header
$title = "Modifier " . $nomParametre;
include('../../../templates/header.php');
include('../../../templates/navigation.php');
?>

<div class="container" style="margin-top: 50px;">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Administration - Modifier le paramètre</h3>
                </div>
                <div class="panel-body">
                    
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($messageType !== 'danger'): ?>
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="valeur"><?php echo htmlspecialchars($nomParametre); ?> (Clé : <?php echo $iniKey; ?>)</label>
                                <input type="text" class="form-control" id="valeur" name="valeur" 
                                       value="<?php echo htmlspecialchars($valeurActuelle); ?>" required>
                                <p class="help-block">Section : [<?php echo htmlspecialchars($section); ?>]</p>
                            </div>
                            
                            <hr>
                            <button type="submit" class="btn btn-primary">
                                Enregistrer les modifications
                            </button>
                            <a href="index.php" class="btn btn-default">Retour</a>
                        </form>
                    <?php else: ?>
                        <a href="index.php" class="btn btn-warning">Retour à la liste</a>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../../../templates/footer.php'); ?>