<?php
// api/mobile/index.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/middleware/MobileAuthMiddleware.php';

// Gestion des requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Routes API Mobile
$routes = [
    'POST' => [
        '/api/mobile/auth/login' => 'MobileAuthController@login',
        '/api/mobile/auth/logout' => 'MobileAuthController@logout',
        '/api/mobile/tickets' => 'MobileTicketController@create',
        '/api/mobile/clients' => 'MobileClientController@create',
        '/api/mobile/sync/upload' => 'MobileSyncController@upload',
    ],
    'GET' => [
        '/api/mobile/tickets' => 'MobileTicketController@list',
        '/api/mobile/tickets/today' => 'MobileTicketController@today',
        '/api/mobile/clients' => 'MobileClientController@list',
        '/api/mobile/stock/alerts' => 'MobileStockController@alerts',
        '/api/mobile/dashboard/stats' => 'MobileDashboardController@stats',
        '/api/mobile/sync/download' => 'MobileSyncController@download',
    ],
    'PUT' => [
        '/api/mobile/tickets/{id}/status' => 'MobileTicketController@updateStatus',
    ]
];

// Trouver la route correspondante
$route_found = false;
foreach ($routes[$method] as $route => $handler) {
    if (preg_match('#^' . preg_quote($route, '#') . '$#', $request_uri)) {
        $route_found = true;
        list($controller, $action) = explode('@', $handler);
        require_once __DIR__ . "/controllers/$controller.php";
        
        // Vérifier l'authentification (sauf pour login)
        if ($route !== '/api/mobile/auth/login') {
            $authMiddleware = new MobileAuthMiddleware();
            if (!$authMiddleware->validateToken()) {
                http_response_code(401);
                echo json_encode(['error' => 'Non authentifié']);
                exit();
            }
        }
        
        // Exécuter le contrôleur
        $controllerInstance = new $controller();
        $controllerInstance->$action();
        break;
    }
}

if (!$route_found) {
    http_response_code(404);
    echo json_encode(['error' => 'Route non trouvée']);
}
?>