<?php
// Initialisation de la session
session_start();

// 1. Suppression des variables de session spécifiques au portail client
unset($_SESSION['client_id']);
unset($_SESSION['client_nom']);
unset($_SESSION['client_prenom']);

// 2. Si vous voulez vider complètement la session (recommandé pour la sécurité)
// session_destroy(); 

// 3. Redirection vers la page de connexion du portail client
header("Location: login.php");
exit;
?>