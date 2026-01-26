<?php
// pages/factures/import.php
require_once('../../templates/header.php');
require_once('../../templates/navigation.php');
require_once('../../fonctions/database.php');

// Configuration
$titre = 'Import des Factures';
$current_page = basename(__FILE__);
$cheminDossierUpload = '../../uploads/factures/'; // Dossier de stockage des fichiers uploadés
$typesFichiersAutorises = ['csv', 'xml']; // Formats supportés
$tailleMaxFichier = 5 * 1024 * 1024; // 5 Mo

// Créer le dossier d'upload s'il n'existe pas
if (!file_exists($cheminDossierUpload)) {
    mkdir($cheminDossierUpload, 0755, true);
}

// Gestion des messages
$message = '';

// Traitement du formulaire d'upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichier_factures'])) {
    $nomFichier = $_FILES['fichier_factures']['name'];
    $tailleFichier = $_FILES['fichier_factures']['size'];
    $erreurFichier = $_FILES['fichier_factures']['error'];
    $tmpFichier = $_FILES['fichier_factures']['tmp_name'];
    $extensionFichier = strtolower(pathinfo($nomFichier, PATHINFO_EXTENSION));

    try {
        // Validation du fichier
        if ($erreurFichier !== UPLOAD_ERR_OK) {
            throw new Exception(getUploadErrorMessage($erreurFichier));
        }

        if ($tailleFichier > $tailleMaxFichier) {
            throw new Exception("La taille du fichier dépasse la limite autorisée (5 Mo).");
        }

        if (!in_array($extensionFichier, $typesFichiersAutorises)) {
            throw new Exception("Type de fichier non autorisé. Formats acceptés: " . implode(', ', $typesFichiersAutorises));
        }

        // Générer un nom de fichier unique
        $nomFichierUnique = uniqid('facture_') . '.' . $extensionFichier;
        $cheminFichierDestination = $cheminDossierUpload . $nomFichierUnique;

        // Déplacer le fichier uploadé
        if (!move_uploaded_file($tmpFichier, $cheminFichierDestination)) {
            throw new Exception("Erreur lors du déplacement du fichier uploadé.");
        }

        // Traiter le fichier selon son type
        $pdo = getPdoConnection(); // Assurez-vous d'initialiser $pdo
        $resultatImport = traiterFichierFacture($cheminFichierDestination, $extensionFichier, $pdo);

        if ($resultatImport['succes']) {
            $message = '<div class="alert alert-success">' . 
                       $resultatImport['nombre'] . ' factures importées avec succès.</div>';
        } else {
            $message = '<div class="alert alert-warning">' . 
                       $resultatImport['message'] . '</div>';
        }

    } catch (Exception $e) {
        $message = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// Fonction pour traiter les fichiers selon leur type
function traiterFichierFacture($cheminFichier, $type, $pdo) {
    $resultat = ['succes' => false, 'nombre' => 0, 'message' => ''];
    // ... (Logique de traitement de fichier)
    
    try {
        // ... (Logique de traitement de fichier)
        switch ($type) {
            case 'csv':
                $donnees = lireFichierCSV($cheminFichier);
                break;
            case 'xml':
                $donnees = lireFichierXML($cheminFichier);
                break;
            default:
                throw new Exception("Format de fichier non supporté");
        }

        // Valider et importer les données
        if (!empty($donnees)) {
            $nombreImportees = 0;
            // Vérification de l'état de la connexion et transaction
            if ($pdo instanceof PDO) {
                $pdo->beginTransaction();
            } else {
                 throw new Exception("Erreur de connexion à la base de données.");
            }

            foreach ($donnees as $facture) {
                if (importerFacture($facture, $pdo)) {
                    $nombreImportees++;
                }
            }

            $pdo->commit();
            $resultat['succes'] = true;
            $resultat['nombre'] = $nombreImportees;
            $resultat['message'] = "Importation terminée";
        } else {
            $resultat['message'] = "Aucune donnée valide trouvée dans le fichier";
        }

    } catch (Exception $e) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $resultat['message'] = $e->getMessage();
    }

    return $resultat;
}

// Fonction pour lire les fichiers CSV
function lireFichierCSV($cheminFichier) {
    // ... (Code existant)
    $donnees = [];
    
    if (($handle = fopen($cheminFichier, "r")) !== false) {
        $entetes = fgetcsv($handle, 0, ";"); // Séparateur point-virgule
        
        while (($ligne = fgetcsv($handle, 0, ";")) !== false) {
            if (count($entetes) === count($ligne)) {
                $donnees[] = array_combine($entetes, $ligne);
            }
        }
        
        fclose($handle);
    }
    
    return $donnees;
}

// Fonction pour lire les fichiers XML (simplifiée)
function lireFichierXML($cheminFichier) {
    // ... (Code existant)
    $donnees = [];
    $xml = simplexml_load_file($cheminFichier);
    
    if ($xml !== false) {
        foreach ($xml->facture as $facture) {
            $donnees[] = [
                'Numero_Facture' => (string)$facture->numero,
                'Date_Emission' => (string)$facture->date_emission,
                'Montant_HT' => (float)$facture->montant_ht,
                // Ajouter les autres champs nécessaires
            ];
        }
    }
    
    return $donnees;
}

// Fonction pour importer une facture en base
function importerFacture($data, $pdo) {
    // ... (Code existant)
    // Validation des données requises
    $required = ['Numero_Facture', 'Date_Emission', 'Montant_HT'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Champ requis manquant: $field");
        }
    }

    // Préparation de la requête d'insertion
    $sql = "INSERT INTO Factures (
                Numero_Facture, Date_Emission, Date_Reception, Date_Echeance,
                Montant_HT, Montant_TVA, Montant_TTC, Statut_Facture,
                Nom_Fournisseur, Commentaire
            ) VALUES (
                :numero, :date_emission, :date_reception, :date_echeance,
                :montant_ht, :montant_tva, :montant_ttc, :statut,
                :fournisseur, :commentaire
            )";

    $stmt = $pdo->prepare($sql);
    
    // Calcul des montants si nécessaire
    $montantHT = (float)$data['Montant_HT'];
    $montantTVA = isset($data['Montant_TVA']) ? (float)$data['Montant_TVA'] : $montantHT * 0.2; // TVA 20% par défaut
    $montantTTC = $montantHT + $montantTVA;

    // Exécution de la requête
    return $stmt->execute([
        ':numero' => $data['Numero_Facture'],
        ':date_emission' => $data['Date_Emission'],
        ':date_reception' => $data['Date_Reception'] ?? null,
        ':date_echeance' => $data['Date_Echeance'] ?? null,
        ':montant_ht' => $montantHT,
        ':montant_tva' => $montantTVA,
        ':montant_ttc' => $montantTTC,
        ':statut' => $data['Statut_Facture'] ?? 'Nouvelle',
        ':fournisseur' => $data['Nom_Fournisseur'] ?? null,
        ':commentaire' => $data['Commentaire'] ?? null
    ]);
}

// Fonction pour les messages d'erreur d'upload
function getUploadErrorMessage($code) {
    // ... (Code existant)
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE: return 'Le fichier dépasse la taille maximale autorisée.';
        case UPLOAD_ERR_FORM_SIZE: return 'Le fichier dépasse la taille spécifiée dans le formulaire.';
        case UPLOAD_ERR_PARTIAL: return 'Le fichier n\'a été que partiellement uploadé.';
        case UPLOAD_ERR_NO_FILE: return 'Aucun fichier n\'a été uploadé.';
        case UPLOAD_ERR_NO_TMP_DIR: return 'Dossier temporaire manquant.';
        case UPLOAD_ERR_CANT_WRITE: return 'Échec de l\'écriture du fichier sur le disque.';
        case UPLOAD_ERR_EXTENSION: return 'Une extension PHP a arrêté l\'upload du fichier.';
        default: return 'Erreur inconnue lors de l\'upload.';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BailCompta 360 | <?= htmlspecialchars($titre) ?></title>
    
     <link rel="stylesheet" href="../css/bootstrap-3.4.1.min.css">
    <link rel="stylesheet" href="../css/monstyle.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* ------------------------------------------------ */
        /* 💡 STYLES CORRIGÉS POUR LA RÉACTIVITÉ (EN %) */
        /* ------------------------------------------------ */

        /* Variable pour la largeur de la sidebar (supposée être dans navigation.php) */
        :root {
            --sidebar-width: 20%;
        }

        /* Conteneur principal du contenu, décalé vers la droite */
        .main-content-wrapper {
            margin-left: var(--sidebar-width); 
            width: calc(100% - var(--sidebar-width)); 
            min-height: 100vh;
            padding: 2%; /* Rembourrage en % */
            box-sizing: border-box; 
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        /* Conteneur interne pour centrer le contenu */
        .page-container {
            width: 95%; 
            margin: 0 auto; 
        }

        /* Ajustements d'espacement en % */
        .page-header { margin-bottom: 2%; }
        .alert { margin-bottom: 2%; }
        .panel { margin-top: 2%; }

        /* Ajustement des contrôles de formulaire pour la réactivité */
        .form-horizontal .control-label {
            padding-top: 0.7%; /* Garder un alignement vertical */
            margin-bottom: 0;
            text-align: right;
        }
        
        .help-block {
            margin-top: 0.5%;
        }

        /* Ajustement des boutons */
        .btn {
            margin-top: 1%;
        }

        /* ------------------------------------------------ */
        /* @media queries pour la réactivité */
        /* ------------------------------------------------ */

        @media (max-width: 992px) { 
            :root {
                --sidebar-width: 0; 
            }
            .main-content-wrapper {
                margin-left: 0; 
                width: 100%;
            }
            .page-container {
                width: 100%;
                padding: 1%;
            }
        }
        
        @media (max-width: 768px) { 
             /* Pour les petits écrans, désactiver l'alignement horizontal du formulaire */
            .form-horizontal .control-label {
                text-align: left;
                margin-bottom: 0.5%;
            }
             /* Rendre le bouton plus visible */
            .form-group .col-sm-offset-3.col-sm-9 {
                margin-left: 0;
                width: 100%;
            }
             .btn {
                display: block;
                width: 100%;
                margin-top: 1%;
            }
        }
    </style>
</head>
<body>
<div class="main-content-wrapper">
    <div class="page-container">
        <h2 class="page-header"><?= htmlspecialchars($titre) ?></h2>

        <?= $message ?>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Importer des factures</h3>
            </div>
            <div class="panel-body">
                <form action="<?= htmlspecialchars($current_page) ?>" method="POST" enctype="multipart/form-data" class="form-horizontal">
                    <div class="form-group">
                        <label for="fichier_factures" class="col-sm-3 control-label">Fichier de factures</label>
                        <div class="col-sm-9">
                            <input type="file" class="form-control" id="fichier_factures" name="fichier_factures" required>
                            <p class="help-block">Formats acceptés: **<?= implode(', ', $typesFichiersAutorises) ?>**. Taille max: **5 Mo**.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-9">
                            <button type="submit" class="btn btn-primary">
                                <i class="glyphicon glyphicon-upload"></i> Importer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Instructions</h3>
            </div>
            <div class="panel-body">
                <h4>Format CSV requis:</h4>
                <p>Le fichier CSV doit utiliser le **point-virgule (;)** comme séparateur et contenir les colonnes suivantes:</p>
                <ul>
                    <li>**Numero_Facture** (requis)</li>
                    <li>**Date_Emission** (format YYYY-MM-DD, requis)</li>
                    <li>Date_Reception</li>
                    <li>Date_Echeance</li>
                    <li>**Montant_HT** (requis)</li>
                    <li>Montant_TVA</li>
                    <li>Nom_Fournisseur</li>
                    <li>Commentaire</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="../js/jquery-3.6.0.js" defer></script>
    <script src="../js/bootstrap-3.4.1.min.js" defer></script>
<?php require_once('../../templates/footer.php'); ?>
</body>
</html>