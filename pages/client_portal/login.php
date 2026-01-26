<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Redirection si déjà connecté
if (isset($_SESSION['client_id'])) {
    header('Location: index.php');
    exit();
}

$root = realpath(__DIR__ . '/../../');
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once $root . '/fonctions/database.php';
    
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';

    // CORRECTION : Utilisation des colonnes exactes de votre table 'clients'
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE email = ? AND telephone = ? AND est_actif = 1");
    $stmt->execute([$email, $phone]);
    $client = $stmt->fetch();

    if ($client) {
        $_SESSION['client_id'] = $client['id_client'];
        $_SESSION['client_nom'] = $client['nom_client'];
        $_SESSION['client_prenom'] = $client['prenom_client'];
        header('Location: index.php');
        exit();
    } else {
        $error = "Identifiants invalides ou compte inactif.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Client - Kayade Manager</title>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #f8b500 0%, #f1f2f6 100%); 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-family: 'Segoe UI', sans-serif; 
            margin: 0;
        }
        .login-card { 
            background: #fff; 
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.1); 
            width: 100%; 
            max-width: 400px; 
            overflow: hidden; 
        }
        .login-header { 
            background: #2c3e50; 
            color: white; 
            padding: 30px; 
            text-align: center; 
        }
        .login-body { 
            padding: 30px; 
        }
        .btn-client { 
            background: #f8b500; 
            color: #2c3e50; 
            font-weight: 700; 
            width: 100%; 
            border-radius: 10px; 
            padding: 12px; 
            border: none; 
            margin-bottom: 15px;
        }
        .btn-back {
            background: none;
            border: 1px solid #ccc;
            color: #7f8c8d;
            width: 100%;
            border-radius: 10px;
            padding: 8px;
            font-size: 0.9rem;
            transition: 0.3s;
        }
        .btn-back:hover {
            background: #f8f9fa;
            color: #2c3e50;
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <h4 class="fw-bold m-0">ESPACE CLIENT</h4>
        <small>Accédez à votre suivi</small>
    </div>
    <div class="login-body">
        <?php if ($error): ?>
            <div class="alert alert-danger small"><?= $error ?></div>
        <?php endif; ?>
        
        <form action="login.php" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">Email</label>
                <input type="email" name="email" class="form-control" placeholder="votre@email.com" required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Téléphone</label>
                <input type="text" name="phone" class="form-control" placeholder="696..." required>
            </div>
            
            <button type="submit" class="btn btn-client">SE CONNECTER</button>
            
            <button type="button" onclick="history.back()" class="btn btn-back">
                Retour
            </button>
        </form>
    </div>
</div>

<script src="../../js/bootstrap.bundle.min.js"></script>
</body>
</html>