<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

// Vérification de la méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupération et nettoyage des données
    $nom_hotel = trim($_POST['nom_hotel'] ?? '');
    $responsable = trim($_POST['responsable'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $contrat = $_POST['contrat'] ?? 'Industrial';
    $date_creation = date('Y-m-d H:i:s');

    // Validation minimale
    if (empty($nom_hotel) || empty($telephone)) {
        header('Location: gestion_hotel.php?action=nouveau&error=Veuillez remplir les champs obligatoires');
        exit;
    }

    try {
        // Préparation du marqueur spécial pour le filtre de la page liste
        // On stocke le type de contrat dans les notes avec le tag de partenariat
        $notes = "PARTENAIRE_HOTEL | Contrat: " . $contrat . " | Créé le: " . $date_creation;

        $sql = "INSERT INTO clients (nom_client, prenom_client, telephone, notes, date_inscription) 
                VALUES (:nom, :prenom, :tel, :notes, :date_insc)";
        
        $stmt = $pdo->prepare($sql);
        
        $result = $stmt->execute([
            ':nom' => strtoupper($nom_hotel), // Nom de l'hôtel en majuscule
            ':prenom' => $responsable,        // Le responsable est stocké comme "Prénom"
            ':tel' => $telephone,
            ':notes' => $notes,
            ':date_insc' => $date_creation
        ]);

        if ($result) {
            // Succès : redirection vers la liste avec un message positif
            header('Location: gestion_hotel.php?action=liste&success=Hôtel partenaire enregistré avec succès');
        } else {
            header('Location: gestion_hotel.php?action=nouveau&error=Erreur lors de l\'enregistrement');
        }

    } catch (PDOException $e) {
        // En cas d'erreur de base de données (ex: doublon de téléphone)
        $error_msg = urlencode("Erreur technique : " . $e->getMessage());
        header("Location: gestion_hotel.php?action=nouveau&error=$error_msg");
    }

} else {
    // Si on tente d'accéder au fichier sans POST
    header('Location: gestion_hotel.php');
}
exit;