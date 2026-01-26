<?php
// pages/admin/habilitations/ajouter.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits administrateur
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'Admin') {
    $_SESSION['flash_message'] = "Vous n'avez pas les droits pour accéder à cette page.";
    $_SESSION['flash_type'] = 'error';
    header('Location: ../../../index.php?error=Accès non autorisé');
    exit;
}

// Inclure les fichiers de fonctions et de configuration
require_once('../../../fonctions/database.php');
require_once('../../../fonctions/gestion_habilitations.php');

// Configuration de la page
$title = "Ajouter une Habilitation";

// Inclure l'en-tête de la page
include('../../../templates/header.php');

// Inclure la barre de navigation
include('../../../templates/navigation.php');

// Récupérer la liste des profils et utilisateurs pour les dropdowns
$profils = getAllProfils($pdo);
$utilisateurs = getAllUtilisateurs($pdo);

// Récupérer la liste des objets de permission potentiels
$permissionObjects = getPotentialPermissionObjects();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BailCompta 360 | <?= htmlspecialchars($title) ?></title>
    <link rel="shortcut icon" href="../../../images/logo_bailcompta.png" type="image/x-icon">
  
    <link rel="stylesheet" href="../../css/bootstrap-3.4.1.min.css">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/admin_style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* Main content wrapper */
        .main-content-wrapper {
            margin-left: 250px; /* Espace pour la navigation */
            padding: 2%;
            width: calc(100% - 250px); /* Largeur totale moins la navigation */
            min-height: 100vh;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        /* Ajustements pour les tablettes */
        @media (max-width: 992px) {
            .main-content-wrapper {
                margin-left: 0;
                width: 100%;
                padding: 3%;
            }
        }
        
        /* Ajustements pour les téléphones */
        @media (max-width: 768px) {
            .main-content-wrapper {
                padding: 2%;
            }
            
            .container.mx-auto {
                padding: 0 !important;
            }
        }
        
        /* Style du conteneur principal */
        .container.mx-auto {
            max-width: 800px;
            width: 100%;
            padding: 1.5em;
            background-color: white;
            border-radius: 0.5em;
            box-shadow: 0 0.2em 1em rgba(0, 0, 0, 0.1);
        }
        
        @media (max-width: 768px) {
            .container.mx-auto {
                padding: 1em;
                margin: 0.5em;
            }
        }

        /* Titres */
        h1.text-2xl {
            font-size: 1.8em;
            font-weight: 700;
            margin-bottom: 1em;
            color: #2c3e50;
            padding-bottom: 0.5em;
            border-bottom: 0.1em solid #eaeaea;
        }
        
        @media (max-width: 768px) {
            h1.text-2xl {
                font-size: 1.5em;
                text-align: center;
            }
        }

        /* Alertes */
        .alert {
            padding: 0.8em 1em;
            margin-bottom: 1.5em;
            border-radius: 0.4em;
            font-size: 0.95em;
        }
        
        .alert-success {
            background-color: #d4edda;
            border: 0.1em solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            border: 0.1em solid #f5c6cb;
            color: #721c24;
        }

        /* Formulaire */
        form.bg-white {
            padding: 1.5em;
            border-radius: 0.5em;
            background-color: white;
        }
        
        @media (max-width: 768px) {
            form.bg-white {
                padding: 1em;
            }
        }

        /* Labels et champs de formulaire */
        label.block {
            display: block;
            margin-bottom: 0.5em;
            font-weight: 600;
            color: #4a5568;
            font-size: 0.95em;
        }
        
        .mb-4 {
            margin-bottom: 1.5em;
        }
        
        @media (max-width: 768px) {
            .mb-4 {
                margin-bottom: 1em;
            }
        }

        /* Champs de sélection et input */
        select.shadow, input[type="radio"] {
            width: 100%;
            padding: 0.6em 0.8em;
            border: 0.1em solid #cbd5e0;
            border-radius: 0.4em;
            font-size: 0.95em;
            color: #4a5568;
            background-color: white;
            transition: border-color 0.3s;
        }
        
        select.shadow:focus, input[type="radio"]:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 0.2em rgba(66, 153, 225, 0.2);
        }
        
        /* Boutons radio personnalisés */
        input[type="radio"] {
            width: auto;
            margin-right: 0.5em;
        }
        
        .radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1em;
            margin-top: 0.5em;
        }
        
        .radio-group label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-weight: normal;
        }
        
        @media (max-width: 768px) {
            .radio-group {
                flex-direction: column;
                gap: 0.5em;
            }
        }

        /* Boutons */
        .flex.items-center.justify-between {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-top: 2em;
            padding-top: 1.5em;
            border-top: 0.1em solid #eaeaea;
        }
        
        @media (max-width: 768px) {
            .flex.items-center.justify-between {
                flex-direction: column;
                gap: 1em;
                align-items: stretch;
            }
        }

        button.bg-blue-500 {
            background-color: #4299e1;
            color: white;
            padding: 0.6em 1.5em;
            border: none;
            border-radius: 0.4em;
            font-weight: 600;
            font-size: 0.95em;
            cursor: pointer;
            transition: background-color 0.3s;
            width: auto;
        }
        
        button.bg-blue-500:hover {
            background-color: #3182ce;
        }
        
        @media (max-width: 768px) {
            button.bg-blue-500 {
                width: 100%;
                padding: 0.7em;
            }
        }

        /* Lien Annuler */
        a.inline-block {
            color: #4299e1;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95em;
            transition: color 0.3s;
        }
        
        a.inline-block:hover {
            color: #2c5282;
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            a.inline-block {
                text-align: center;
                display: block;
                margin-top: 1em;
            }
        }

        /* Gestion de la visibilité */
        .hidden {
            display: none !important;
        }

        /* Animation pour les transitions */
        #profil_select_container, #utilisateur_select_container {
            transition: opacity 0.3s ease;
        }

        /* Styles spécifiques pour les champs désactivés */
        select:disabled {
            background-color: #f7fafc;
            color: #a0aec0;
            cursor: not-allowed;
        }

        /* Amélioration de la lisibilité sur mobile */
        @media (max-width: 480px) {
            .container.mx-auto {
                padding: 0.8em;
                margin: 0.3em;
            }
            
            form.bg-white {
                padding: 0.8em;
            }
            
            h1.text-2xl {
                font-size: 1.3em;
            }
            
            label.block {
                font-size: 0.9em;
            }
            
            select.shadow {
                font-size: 0.9em;
                padding: 0.5em 0.7em;
            }
        }

        /* Espacement supplémentaire pour les sections */
        .form-section {
            margin-bottom: 2em;
            padding: 1.5em;
            background-color: #f8fafc;
            border-radius: 0.5em;
            border: 0.1em solid #e2e8f0;
        }
        
        @media (max-width: 768px) {
            .form-section {
                padding: 1em;
                margin-bottom: 1.5em;
            }
        }
    </style>
</head>
<body>
    <div class="main-content-wrapper">
        <div class="container mx-auto p-4 sm:p-6 lg:p-8">
            <h1 class="text-2xl sm:text-3xl font-bold mb-6 text-gray-800">Ajouter une Habilitation</h1>

            <?php
            // Afficher les messages flash (succès, erreur) s'il y en a
            if (isset($_SESSION['flash_message'])) {
                $message = $_SESSION['flash_message'];
                $type = isset($_SESSION['flash_type']) && $_SESSION['flash_type'] === 'error' ? 'error' : 'success';
                $alertClass = $type === 'error' ? 'alert-error' : 'alert-success';
                $alertTitle = $type === 'error' ? 'Erreur' : 'Succès';
                
                echo "<div class='alert {$alertClass}' role='alert'>";
                echo "<strong class='font-bold'>{$alertTitle}!</strong>";
                echo "<span class='block sm:inline'> " . htmlspecialchars($message) . "</span>";
                echo "</div>";
                
                // Supprimer le message après l'affichage
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_type']);
            }
            ?>

            <div class="form-section">
                <form action="traitement_ajout_habilitation.php" method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="objet">
                            Objet (Permission) :
                        </label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                id="objet" name="objet" required>
                            <option value="">-- Sélectionner un objet --</option>
                            <?php if (!empty($permissionObjects)) : ?>
                                <?php foreach ($permissionObjects as $object) : ?>
                                    <option value="<?php echo htmlspecialchars($object); ?>">
                                        <?php echo htmlspecialchars($object); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <option value="">Aucun objet de permission disponible</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Assigner à :
                        </label>
                        <div class="radio-group">
                            <label for="assign_profil">
                                <input type="radio" id="assign_profil" name="assign_type" value="profil" checked>
                                <span>Profil</span>
                            </label>

                            <label for="assign_utilisateur">
                                <input type="radio" id="assign_utilisateur" name="assign_type" value="utilisateur">
                                <span>Utilisateur Spécifique</span>
                            </label>
                        </div>
                    </div>

                    <div id="profil_select_container" class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="id_profil">
                            Choisir un Profil :
                        </label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                id="id_profil" name="id_profil">
                            <?php if ($profils) : ?>
                                <?php foreach ($profils as $profil) : ?>
                                    <option value="<?php echo htmlspecialchars($profil['ID_Profil']); ?>">
                                        <?php echo htmlspecialchars($profil['Nom_Profil']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <option value="">Aucun profil trouvé</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div id="utilisateur_select_container" class="mb-4 hidden">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="id_utilisateur">
                            Choisir un Utilisateur :
                        </label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                id="id_utilisateur" name="id_utilisateur">
                            <?php if ($utilisateurs) : ?>
                                <?php foreach ($utilisateurs as $utilisateur) : ?>
                                    <option value="<?php echo htmlspecialchars($utilisateur['ID_Utilisateur']); ?>">
                                        <?php echo htmlspecialchars($utilisateur['Nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <option value="">Aucun utilisateur trouvé</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="flex items-center justify-between">
                        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                            Ajouter l'Habilitation
                        </button>
                        <a href="index.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                            Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // JavaScript pour montrer/cacher les dropdowns profil/utilisateur
        const assignProfilRadio = document.getElementById('assign_profil');
        const assignUtilisateurRadio = document.getElementById('assign_utilisateur');
        const profilSelectContainer = document.getElementById('profil_select_container');
        const utilisateurSelectContainer = document.getElementById('utilisateur_select_container');
        const idProfilSelect = document.getElementById('id_profil');
        const idUtilisateurSelect = document.getElementById('id_utilisateur');

        function toggleSelects() {
            if (assignProfilRadio.checked) {
                profilSelectContainer.classList.remove('hidden');
                utilisateurSelectContainer.classList.add('hidden');
                idProfilSelect.setAttribute('required', 'required');
                idUtilisateurSelect.removeAttribute('required');
                idProfilSelect.disabled = false;
                idUtilisateurSelect.disabled = true;
                idUtilisateurSelect.value = ''; // Vider la valeur du select caché
            } else {
                profilSelectContainer.classList.add('hidden');
                utilisateurSelectContainer.classList.remove('hidden');
                idProfilSelect.removeAttribute('required');
                idUtilisateurSelect.setAttribute('required', 'required');
                idProfilSelect.disabled = true;
                idUtilisateurSelect.disabled = false;
                idProfilSelect.value = ''; // Vider la valeur du select caché
            }
        }

        // Appeler au chargement de la page et lors du changement de radio
        toggleSelects();
        assignProfilRadio.addEventListener('change', toggleSelects);
        assignUtilisateurRadio.addEventListener('change', toggleSelects);

        // Validation du formulaire
        const form = document.querySelector('form');
        form.addEventListener('submit', function(event) {
            const objetSelect = document.getElementById('objet');
            const assignType = document.querySelector('input[name="assign_type"]:checked').value;
            
            // Validation de l'objet
            if (objetSelect.value === '') {
                event.preventDefault();
                alert('Veuillez sélectionner un objet de permission.');
                objetSelect.focus();
                return false;
            }
            
            // Validation selon le type d'assignation
            if (assignType === 'profil' && idProfilSelect.value === '') {
                event.preventDefault();
                alert('Veuillez sélectionner un profil.');
                idProfilSelect.focus();
                return false;
            }
            
            if (assignType === 'utilisateur' && idUtilisateurSelect.value === '') {
                event.preventDefault();
                alert('Veuillez sélectionner un utilisateur.');
                idUtilisateurSelect.focus();
                return false;
            }
            
            return true;
        });

        // Ajustement responsive supplémentaire
        function handleResponsive() {
            const container = document.querySelector('.container.mx-auto');
            const mainWrapper = document.querySelector('.main-content-wrapper');
            const windowWidth = window.innerWidth;
            
            if (windowWidth < 768) {
                container.style.padding = '0.8em';
                mainWrapper.style.padding = '1em';
            } else if (windowWidth < 992) {
                container.style.padding = '1.5em';
                mainWrapper.style.padding = '2em';
            } else {
                container.style.padding = '2em';
                mainWrapper.style.padding = '2.5em';
            }
        }

        // Exécuter au chargement et au redimensionnement
        window.addEventListener('load', handleResponsive);
        window.addEventListener('resize', handleResponsive);
    </script>

    <?php
    // Inclure le pied de page
    include('../../../templates/footer.php');
    ?>
</body>
</html>