<?php
// api/mobile/controllers/MobileAuthController.php
require_once __DIR__ . '/../models/MobileUser.php';

class MobileAuthController {
    private $db;
    private $userModel;
    
    public function __construct() {
        global $pdo;
        $this->db = $pdo;
        $this->userModel = new MobileUser($this->db);
    }
    
    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $login = $data['login'] ?? '';
        $password = $data['password'] ?? '';
        $deviceId = $data['device_id'] ?? '';
        
        // Vérifier l'utilisateur
        $user = $this->userModel->authenticate($login, $password);
        
        if ($user) {
            // Générer un token JWT simple
            $token = $this->generateToken($user['id_utilisateur']);
            
            // Enregistrer la session mobile
            $this->userModel->registerMobileSession($user['id_utilisateur'], $deviceId, $token);
            
            echo json_encode([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user['id_utilisateur'],
                    'nom_complet' => $user['nom_complet'],
                    'login' => $user['login_utilisateur'],
                    'role' => $user['id_role'],
                    'agence' => $user['code_agence']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Identifiants incorrects']);
        }
    }
    
    private function generateToken($userId) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $userId,
            'exp' => time() + (7 * 24 * 60 * 60) // 7 jours
        ]);
        
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'votre_secret_key', true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
}
?>