<?php
/**
 * Ce script est appelé par AJAX pour récupérer la liste des budgets.
 */

header('Content-Type: application/json; charset=utf-8');

// Activer l'affichage des erreurs pour le débogage (à désactiver en production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inclure le fichier de fonctions de gestion des budgets
require_once('gestion_budgets.php');

// S'assurer que la connexion à la base de données est disponible
require_once('database.php');

try {
    // Récupérer les paramètres de la requête GET
    $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sortField = isset($_GET['sort']) ? $_GET['sort'] : 'ID_Budget';
    $sortOrder = isset($_GET['order']) ? $_GET['order'] : 'DESC';
    
    // Appeler la fonction qui récupère les données des budgets
    $budgets = getListeBudgets($searchTerm, $sortField, $sortOrder);
    
    // Formater les données pour l'affichage dans le tableau
    $formattedBudgets = [];
    
    foreach ($budgets as $budget) {
        // Calculer le montant réel et l'écart (à adapter selon votre logique métier)
        $montantReel = 0; // À remplacer par le calcul réel si disponible
        $ecart = $montantReel - $budget['Montant_Budgetise'];
        
        $formattedBudgets[] = [
            'ID_Budget' => $budget['ID_Budget'],
            'Annee' => $budget['Annee_Budgetaire'],
            'Mois' => 1, // À adapter si vous avez un champ mois
            'Nom_Budget' => htmlspecialchars($budget['Type_Budget'] . ' - ' . ($budget['Nom_Compte'] ?? 'Compte ' . $budget['Numero_Compte'])),
            'Montant_Prev' => floatval($budget['Montant_Budgetise']),
            'Montant_Reel' => $montantReel,
            'Ecart' => $ecart,
            'Description' => htmlspecialchars($budget['Description_Budget'] ?? ''),
            'Date_Creation' => $budget['Date_Creation'],
            'Compte' => $budget['Numero_Compte'] . ' - ' . $budget['Nom_Compte'],
            'Utilisateur' => $budget['Nom_Utilisateur'] ?? 'N/A'
        ];
    }
    
    // Retourner les données au format JSON
    echo json_encode($formattedBudgets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // En cas d'erreur, retourner un message d'erreur au format JSON
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Erreur lors de la récupération des budgets: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}