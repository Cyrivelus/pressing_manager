<?php
session_start();

// Définir la langue par défaut
$lang = $_SESSION['lang'] ?? 'fr';

// Charger les traductions
$translations = [
    'fr' => [
        'title' => 'Réinitialisation du mot de passe',
        'info' => 'Procédure de récupération',
        'instruction' => 'Pour des raisons de sécurité, la réinitialisation est gérée par l\'administration. Veuillez suivre ces étapes :',
        'steps' => [
            'Contactez votre administrateur système ou responsable d\'agence',
            'Communiquez votre nom d\'utilisateur et votre matricule',
            'Récupérez votre nouveau mot de passe temporaire',
            'Changez votre mot de passe dès votre première connexion'
        ],
        'contact_title' => 'Contact Support Technique',
        'phone' => 'Téléphone',
        'email' => 'Email',
        'back' => 'Retour à la connexion'
    ],
    'en' => [
        'title' => 'Password Reset',
        'info' => 'Recovery Procedure',
        'instruction' => 'For security reasons, password resets are handled by the administration. Please follow these steps:',
        'steps' => [
            'Contact your system administrator or branch manager',
            'Provide your username and employee ID',
            'Get your new temporary password',
            'Change your password immediately after logging in'
        ],
        'contact_title' => 'Technical Support Contact',
        'phone' => 'Phone',
        'email' => 'Email',
        'back' => 'Back to login'
    ]
];

$text = $translations[$lang] ?? $translations['fr'];
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $text['title'] ?> | Pressing Manager</title>
    
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    
    <style>
        :root {
            --primary-color: #800020; /* Bordeaux */
            --accent-color: #6a001b;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        body {
            background: var(--bg-gradient);
            font-family: 'Segoe UI', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .reset-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            border: none;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
            border: none;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .card-body {
            padding: 30px;
        }

        .alert-info {
            background-color: #f0f7ff;
            border-left: 4px solid #3498db;
            color: #2c3e50;
            font-weight: 500;
            margin-bottom: 25px;
        }

        .step-list {
            list-style: none;
            padding: 0;
            margin-bottom: 30px;
        }

        .step-list li {
            position: relative;
            padding-left: 35px;
            margin-bottom: 15px;
            line-height: 1.4;
            color: #444;
        }

        .step-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            top: 0;
            width: 24px;
            height: 24px;
            background: #e8f5e9;
            color: #2e7d32;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        .contact-box {
            background-color: #f8f9fa;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .contact-box h4 {
            font-size: 1.1rem;
            color: var(--primary-color);
            margin-bottom: 15px;
            font-weight: 600;
            border-bottom: 1px solid #ddd;
            padding-bottom: 8px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: #555;
        }

        .contact-item i {
            margin-right: 12px;
            color: var(--primary-color);
            width: 16px;
            text-align: center;
        }

        .btn-back {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-weight: 600;
            width: 100%;
            text-align: center;
        }

        .btn-back:hover {
            background-color: var(--accent-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .icon-small {
            font-size: 1.2rem;
            margin-right: 8px;
        }
    </style>
</head>
<body>

    <div class="reset-card">
        <div class="card-header">
            <h3><?= $text['title'] ?></h3>
        </div>
        
        <div class="card-body">
            <div class="alert alert-info">
                <strong><i class="icon-small">ℹ️</i> <?= $text['info'] ?></strong>
            </div>
            
            <p class="text-muted"><?= $text['instruction'] ?></p>
            
            <ul class="step-list">
                <?php foreach ($text['steps'] as $step): ?>
                    <li><?= htmlspecialchars($step) ?></li>
                <?php endforeach; ?>
            </ul>
            
            <div class="contact-box">
                <h4><?= $text['contact_title'] ?></h4>
                <div class="contact-item">
                    <i>📞</i> <strong><?= $text['phone'] ?> :</strong> +XXX XX XXX XXX
                </div>
                <div class="contact-item">
                    <i>✉️</i> <strong><?= $text['email'] ?> :</strong> admin@votresociete.com
                </div>
            </div>
            
            <div class="text-center">
                <a href="index.php" class="btn-back">
                    ← <?= $text['back'] ?>
                </a>
            </div>
        </div>
    </div>

</body>
</html>