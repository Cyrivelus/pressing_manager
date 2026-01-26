<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titre) ? $titre . " - Portails Client" : "Mon Espace Pressing" ?></title>
    
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --client-primary: #3498db;
            --client-secondary: #2c3e50;
            --client-accent: #f1c40f;
            --client-bg: #f8f9fa;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--client-bg);
            color: var(--client-secondary);
            margin: 0;
            padding: 0;
        }

        /* Navbar Portails Client */
        .navbar-client {
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 15px 0;
        }

        .navbar-brand-client {
            font-weight: 700;
            color: var(--client-secondary);
            text-decoration: none;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
        }

        .navbar-brand-client i {
            color: var(--client-primary);
            margin-right: 10px;
        }

        .user-badge {
            background: var(--client-bg);
            padding: 5px 15px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dot-online {
            height: 8px;
            width: 8px;
            background-color: #2ecc71;
            border-radius: 50%;
            display: inline-block;
        }

        /* Utilitaires de couleurs personnalisés */
        .bg-soft-primary { background-color: rgba(52, 152, 219, 0.1); }
        .text-primary { color: var(--client-primary) !important; }
        
        /* Animation simple */
        .fade-in { animation: fadeIn 0.5s ease-in; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<nav class="navbar-client">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="index.php" class="navbar-brand-client">
            <i class="fas fa-mops"></i> KAYADE <span class="text-primary">PORTAL</span>
        </a>

        <?php if(isset($_SESSION['client_nom'])): ?>
        <div class="user-badge d-none d-md-flex">
            <span class="dot-online"></span>
            <small class="fw-bold"><?= htmlspecialchars($_SESSION['client_prenom'] . ' ' . $_SESSION['client_nom']) ?></small>
            <a href="logout.php" class="text-danger ms-2" title="Déconnexion">
                <i class="fas fa-power-off"></i>
            </a>
        </div>
        <div class="d-md-none">
            <a href="profil.php" class="text-secondary me-3"><i class="fas fa-user-circle fa-lg"></i></a>
            <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt fa-lg"></i></a>
        </div>
        <?php endif; ?>
    </div>
</nav>

<main class="fade-in">
</main>