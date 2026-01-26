<?php
// pressing_manager/api/v1/auth.php

// Entêtes de sécurité pour l'application mobile
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config/database.php';

// Récupération des données envoyées par l'App (React Native envoie du JSON)
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->username) && !empty($data->password)) {
    try {
        $db = Database::getInstance();

        // On cherche l'utilisateur et son rôle
        $query = "SELECT u.*, r.nom_role 
                  FROM utilisateurs u 
                  LEFT JOIN roles r ON u.id_role = r.id_role 
                  WHERE u.login_utilisateur = :login LIMIT 1";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':login', $data->username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();

            // Vérification du mot de passe (compatible avec password_hash utilisé par le site)
            if (password_verify($data->password, $user['mot_de_passe'])) {
                
                if ($user['est_actif'] == 0) {
                    http_response_code(403);
                    echo json_encode(["status" => "error", "message" => "Compte désactivé."]);
                    exit;
                }

                // Réponse de succès envoyée à React Native
                http_response_code(200);
                echo json_encode([
                    "status" => "success",
                    "message" => "Connexion réussie",
                    "user" => [
                        "id" => $user['id_utilisateur'],
                        "nom" => $user['nom_complet'],
                        "role" => $user['nom_role'],
                        "agence_id" => $user['code_agence']
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(["status" => "error", "message" => "Mot de passe incorrect."]);
            }
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Utilisateur non trouvé."]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Erreur serveur : " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Données incomplètes."]);
}