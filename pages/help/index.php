<?php
// help/index.php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../index.php');
    exit;
}

// Récupérer le rôle de l'utilisateur
$role = $_SESSION['role'] ?? 'Réceptionniste';

// Fonction pour vérifier les permissions
function hasPermission($role, $permission) {
    $permissions = [
        'patron' => ['all'],
        'Responsable' => ['dashboard', 'tickets', 'clients', 'stock', 'caisse', 'rapports', 'comptes', 'factures'],
        'Réceptionniste' => ['tickets', 'clients', 'caisse'],
        'Technicien' => ['tickets', 'stock'],
        'Caissier' => ['caisse', 'tickets'],
        'gestionnaire_stock' => ['stock', 'dashboard'],
        'employe_pressing' => ['tickets']
    ];
    
    return isset($permissions[$role]) && 
           (in_array('all', $permissions[$role]) || in_array($permission, $permissions[$role]));
}

// Générer le contenu en fonction du rôle
function generateHelpContent($role) {
    $content = [];
    
    // Section pour tous les rôles
    $content[] = "<h4><i class='fas fa-home'></i> Tableau de Bord</h4>";
    $content[] = "<ul>";
    $content[] = "<li><strong>Accueil :</strong> Vue d'ensemble des activités en cours</li>";
    $content[] = "<li><strong>Statistiques :</strong> Chiffres clés de l'activité</li>";
    $content[] = "<li><strong>Alertes :</strong> Notifications importantes</li>";
    $content[] = "</ul>";
    
    // Section Tickets
    if (hasPermission($role, 'tickets')) {
        $content[] = "<h4><i class='fas fa-ticket-alt'></i> Gestion des Tickets</h4>";
        $content[] = "<ul>";
        $content[] = "<li><strong>Création :</strong> Cliquez sur 'Nouveau Ticket' → Sélectionnez le client → Ajoutez les articles</li>";
        $content[] = "<li><strong>Numérotation :</strong> Automatique (Ex: T2025-001)</li>";
        $content[] = "<li><strong>Statuts :</strong>
            <span class='badge bg-secondary'>En attente</span> - Non traité
            <span class='badge bg-primary'>Traitement</span> - Au lavage/repassage
            <span class='badge bg-success'>Prêt</span> - Disponible en agence
            <span class='badge bg-dark'>Récupéré</span> - Déjà rendu</li>";
        $content[] = "<li><strong>Flux de travail :</strong> Dépôt → Traitement → Prêt → Retrait</li>";
        $content[] = "</ul>";
    }
    
   // Section Clients
if (hasPermission($role, 'clients')) {
    $content[] = "<div class='menu-section'>";
    $content[] = "<h4><i class='fas fa-users'></i> Gestion Clients</h4>";
    $content[] = "<ul class='list-unstyled'>";
    
    // Basé sur ajouterNouveauClient()
    $content[] = "<li><i class='fas fa-plus-circle'></i> <strong>Inscription :</strong> Le système formate automatiquement le NOM en majuscules.</li>";
    
    // Basé sur rechercherClients()
    $content[] = "<li><i class='fas fa-search'></i> <strong>Recherche rapide :</strong> Trouvez un client instantanément par son Nom ou son Téléphone.</li>";
    
    // Basé sur ajouterPointsFidelite()
    $content[] = "<li><i class='fas fa-star'></i> <strong>Fidélité :</strong> Cumul de points automatique lors de chaque dépôt.</li>";
    
    // Basé sur modifierClient()
    $content[] = "<li><i class='fas fa-edit'></i> <strong>Mise à jour :</strong> Modifiez les infos ou gérez les remises spéciales de 0 à 100%.</li>";
    
    // Basé sur l'aspect "est_actif" du modèle
    $content[] = "<li><i class='fas fa-user-shield'></i> <strong>Statut :</strong> Désactivez un compte client pour bloquer ses transactions.</li>";
    
    // Basé sur trouverClientParId()
    $content[] = "<li><i class='fas fa-file-invoice'></i> <strong>Fiche Client :</strong> Vue globale incluant l'agence de rattachement.</li>";
    
    $content[] = "</ul>";
    $content[] = "</div>";
}
    
    // Section Stock
    if (hasPermission($role, 'stock')) {
        $content[] = "<h4><i class='fas fa-boxes'></i> Gestion du Stock</h4>";
        $content[] = "<ul>";
        $content[] = "<li><strong>Catégories :</strong> Lessive, Détachant, Cintres, Sacs, Étiquettes</li>";
        $content[] = "<li><strong>Alertes :</strong> Notification automatique quand stock < seuil</li>";
        $content[] = "<li><strong>Mouvements :</strong> Toute entrée/sortie tracée</li>";
        $content[] = "<li><strong>Inventaire :</strong> Comptage physique périodique</li>";
        $content[] = "<li><strong>Fournisseurs :</strong> Gestion des contacts et commandes</li>";
        $content[] = "<li><strong>Rapports :</strong> Consommation mensuelle par produit</li>";
        $content[] = "</ul>";
    }
    
    // Section Caisse
    if (hasPermission($role, 'caisse')) {
        $content[] = "<h4><i class='fas fa-cash-register'></i> Gestion Caisse</h4>";
        $content[] = "<ul>";
        $content[] = "<li><strong>Ouverture :</strong> Vérifiez le fond de caisse chaque matin</li>";
        $content[] = "<li><strong>Encaissement :</strong> Sélectionnez ticket → Choisissez mode paiement</li>";
        $content[] = "<li><strong>Modes :</strong> Espèces, Carte, Mobile Money, Chèque</li>";
        $content[] = "<li><strong>Acompte :</strong> Possibilité de paiement partiel</li>";
        $content[] = "<li><strong>Clôture :</strong> Tous soirs, génération du rapport de caisse</li>";
        $content[] = "<li><strong>Rapprochement :</strong> Vérification espèces/théorique</li>";
        $content[] = "</ul>";
    }
    
    // Section Rapports
    if (hasPermission($role, 'rapports')) {
        $content[] = "<h4><i class='fas fa-chart-bar'></i> Rapports & Statistiques</h4>";
        $content[] = "<ul>";
        $content[] = "<li><strong>Journalier :</strong> Chiffre d'affaires du jour</li>";
        $content[] = "<li><strong>Mensuel :</strong> Bilan complet par mois</li>";
        $content[] = "<li><strong>Par service :</strong> Performance de chaque catégorie</li>";
        $content[] = "<li><strong>Par employé :</strong> Productivité du personnel</li>";
        $content[] = "<li><strong>Export :</strong> PDF, Excel, CSV disponibles</li>";
        $content[] = "</ul>";
    }
    
    // Section pour patrons seulement
    if ($role === 'patron') {
        $content[] = "<h4><i class='fas fa-crown'></i> Administration (Patron)</h4>";
        $content[] = "<ul>";
        $content[] = "<li><strong>Agences :</strong> Gestion multi-sites</li>";
        $content[] = "<li><strong>Utilisateurs :</strong> Création/compte avec rôles</li>";
        $content[] = "<li><strong>Permissions :</strong> Configuration fine des accès</li>";
        $content[] = "<li><strong>Audit :</strong> Consultation de tous les logs</li>";
        $content[] = "<li><strong>Sauvegarde :</strong> Planification automatique</li>";
        $content[] = "<li><strong>Paramètres :</strong> Personnalisation du système</li>";
        $content[] = "</ul>";
    }
    
    // Section conseils généraux
    $content[] = "<h4><i class='fas fa-lightbulb'></i> Conseils & Bonnes Pratiques</h4>";
    $content[] = "<ul>";
    $content[] = "<li><strong>Étiquettes :</strong> Toujours vérifier le numéro avant remise</li>";
    $content[] = "<li><strong>Clients :</strong> Demander confirmation téléphone pour les retraits</li>";
    $content[] = "<li><strong>Stock :</strong> Commander avant d'atteindre le seuil critique</li>";
    $content[] = "<li><strong>Caisse :</strong> Faire les comptes en présence d'un collègue</li>";
    $content[] = "<li><strong>Sécurité :</strong> Déconnectez-vous toujours en quittant le poste</li>";
    $content[] = "</ul>";
    
    // Section problèmes fréquents
    $content[] = "<h4><i class='fas fa-exclamation-triangle'></i> Dépannage</h4>";
    $content[] = "<ul>";
    $content[] = "<li><strong>Ticket introuvable :</strong> Vérifiez les archives ou contactez le patron</li>";
    $content[] = "<strong>Erreur d'impression :</strong>
        1. Vérifiez la connexion imprimante
        2. Redémarrez l'ordinateur
        3. Contactez le support technique</li>";
    $content[] = "<li><strong>Client mécontent :</strong> Écoutez → Excusez → Proposez solution → Notez l'incident</li>";
  
    $content[] = "</ul>";
    
    return implode("\n", $content);
}


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centre d'Aide - Pressing Manager</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../css/font-awesome.min.css">
    
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #ecf0f1;
            --dark: #34495e;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .help-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .role-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        
        .search-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .search-input {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 20px;
            width: 100%;
            transition: all 0.3s;
        }
        
        .search-input:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
        }
        
        .category-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .category-tickets {
            border-left-color: #3498db;
        }
        
        .category-clients {
            border-left-color: #27ae60;
        }
        
        .category-stock {
            border-left-color: #f39c12;
        }
        
        .category-caisse {
            border-left-color: #9b59b6;
        }
        
        .category-reports {
            border-left-color: #e74c3c;
        }
        
        .category-admin {
            border-left-color: #2c3e50;
        }
        
        .category-card h4 {
            color: var(--dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .category-card h4 i {
            font-size: 1.2rem;
        }
        
        .category-card ul {
            list-style: none;
            padding-left: 0;
        }
        
        .category-card li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .category-card li:last-child {
            border-bottom: none;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            margin: 0 5px;
        }
        
        .quick-links {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .quick-links h5 {
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .link-item {
            display: flex;
            align-items: center;
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 8px;
            transition: background 0.3s;
            cursor: pointer;
        }
        
        .link-item:hover {
            background: #f8f9fa;
        }
        
        .link-item i {
            width: 30px;
            color: var(--secondary);
        }
        
        .hotline-box {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .hotline-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .contact-info {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
            border: 2px solid var(--secondary);
        }
        
        .keyboard-shortcuts {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .shortcut-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #ddd;
        }
        
        .shortcut-key {
            background: var(--dark);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
        }
        
        @media (max-width: 768px) {
            .help-container {
                padding: 10px;
            }
            
            .header-card {
                padding: 20px;
            }
            
            .category-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="help-container">
        <!-- Header -->
        <div class="header-card">
            <h1><i class="fas fa-life-ring"></i> Centre d'Aide & Documentation</h1>
            <p class="lead">Guide complet d'utilisation du Pressing Manager</p>
            <div class="role-badge">
                <i class="fas fa-user-tag"></i> Vous êtes connecté en tant que : <strong><?php echo htmlspecialchars($role); ?></strong>
            </div>
        </div>
        
        <!-- Barre de recherche -->
        <div class="search-box">
            <h5><i class="fas fa-search"></i> Rechercher dans l'aide</h5>
            <input type="text" class="search-input" placeholder="Tapez votre question (ex: 'créer ticket', 'gérer stock', 'imprimer facture')...">
            <div style="color: #666; font-size: 0.9rem; margin-top: 10px;">
                <i class="fas fa-info-circle"></i> Recherchez par mot-clé ou consultez les catégories ci-dessous
            </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="row">
            <div class="col-lg-8">
                <!-- Table des matières -->
                <div class="alert alert-info">
                    <h6><i class="fas fa-list"></i> Table des matières</h6>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                        <?php if (hasPermission($role, 'tickets')): ?>
                            <a href="#tickets" class="btn btn-sm btn-outline-primary">Tickets</a>
                        <?php endif; ?>
                        <?php if (hasPermission($role, 'clients')): ?>
                            <a href="#clients" class="btn btn-sm btn-outline-success">Clients</a>
                        <?php endif; ?>
                        <?php if (hasPermission($role, 'stock')): ?>
                            <a href="#stock" class="btn btn-sm btn-outline-warning">Stock</a>
                        <?php endif; ?>
                        <?php if (hasPermission($role, 'caisse')): ?>
                            <a href="#caisse" class="btn btn-sm btn-outline-purple">Caisse</a>
                        <?php endif; ?>
                        <?php if (hasPermission($role, 'rapports')): ?>
                            <a href="#rapports" class="btn btn-sm btn-outline-danger">Rapports</a>
                        <?php endif; ?>
                        <?php if ($role === 'patron'): ?>
                            <a href="#admin" class="btn btn-sm btn-outline-dark">Administration</a>
                        <?php endif; ?>
                        <a href="#conseils" class="btn btn-sm btn-outline-secondary">Conseils</a>
                        <a href="#depannage" class="btn btn-sm btn-outline-danger">Dépannage</a>
                    </div>
                </div>
                
                <!-- Contenu dynamique par rôle -->
                <div id="help-content">
                    <?php echo generateHelpContent($role); ?>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Liens rapides -->
            
                
                <!-- Hotline -->
                <div class="hotline-box">
                    <h5><i class="fas fa-phone-alt"></i> Support Technique</h5>
                    <p>Problème urgent ? Contactez notre support :</p>
                    <div class="hotline-number">
                        <i class="fas fa-phone"></i> 237 6XX XX XX XX
                    </div>
                    <p style="font-size: 0.9rem; opacity: 0.9;">
                        <i class="fas fa-clock"></i> Lundi - Samedi : 8h - 18h<br>
                        <i class="fas fa-envelope"></i> support@pressing-manager.cm
                    </p>
                </div>
                
                <!-- Raccourcis clavier -->
                <div class="keyboard-shortcuts">
                    <h5><i class="fas fa-keyboard"></i> Raccourcis Clavier</h5>
                    <div class="shortcut-item">
                        <span>Nouveau ticket</span>
                        <kbd class="shortcut-key">Ctrl + T</kbd>
                    </div>
                    <div class="shortcut-item">
                        <span>Rechercher client</span>
                        <kbd class="shortcut-key">Ctrl + F</kbd>
                    </div>
                    <div class="shortcut-item">
                        <span>Encaisser</span>
                        <kbd class="shortcut-key">Ctrl + P</kbd>
                    </div>
                    <div class="shortcut-item">
                        <span>Tableau de bord</span>
                        <kbd class="shortcut-key">Ctrl + D</kbd>
                    </div>
                    <div class="shortcut-item">
                        <span>Imprimer</span>
                        <kbd class="shortcut-key">Ctrl + I</kbd>
                    </div>
                    <div class="shortcut-item">
                        <span>Aide</span>
                        <kbd class="shortcut-key">F1</kbd>
                    </div>
                </div>
                
                <!-- Glossaire -->
                <div class="contact-info">
                    <h5><i class="fas fa-book"></i> Glossaire</h5>
                    <div style="font-size: 0.9rem;">
                        <p><strong>Ticket :</strong> Bon de prise en charge d'un vêtement</p>
                        <p><strong>Acompte :</strong> Paiement partiel à la dépose</p>
                        <p><strong>Solde :</strong> Reste à payer au retrait</p>
                        <p><strong>KPI :</strong> Indicateur de performance</p>
                        <p><strong>CA :</strong> Chiffre d'Affaires</p>
                        <p><strong>CMUP :</strong> Coût Moyen Unit. Pondéré (stock)</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pied de page -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-light text-center">
                    <p class="mb-1">
                        <strong>Pressing Manager Pro v1.0</strong> | 
                        <i class="fas fa-code"></i> Développé par votre équipe technique |
                        <i class="fas fa-calendar-alt"></i> Dernière mise à jour : <?php echo date('d/m/Y'); ?>
                    </p>
                    <p class="mb-0 text-muted" style="font-size: 0.85rem;">
                        <i class="fas fa-exclamation-circle"></i> Ce système est la propriété de Tamboug Cyrille Steve. 
                        Toute copie ou diffusion non autorisée est interdite.
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Fonction de recherche
            $('.search-input').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                const sections = $('#help-content').find('h4');
                
                sections.each(function() {
                    const section = $(this);
                    const sectionContent = section.next('ul');
                    const sectionText = section.text().toLowerCase() + ' ' + sectionContent.text().toLowerCase();
                    
                    if (sectionText.includes(searchTerm)) {
                        section.show();
                        sectionContent.show();
                    } else {
                        section.hide();
                        sectionContent.hide();
                    }
                });
            });
            
            // Navigation par ancres
            $('a[href^="#"]').on('click', function(e) {
                e.preventDefault();
                const target = $(this).attr('href');
                if (target !== '#') {
                    $('html, body').animate({
                        scrollTop: $(target).offset().top - 20
                    }, 500);
                }
            });
            
            // Simuler des sections
            window.showSection = function(section) {
                let message = '';
                switch(section) {
                    case 'tickets':
                        message = "Pour créer un ticket :\n1. Cliquez sur 'Tickets' dans le menu\n2. Sélectionnez 'Nouveau Ticket'\n3. Choisissez un client existant ou créez-en un nouveau\n4. Ajoutez les articles avec leurs quantités\n5. Validez pour générer le ticket";
                        break;
                    case 'clients':
                        message = "Pour ajouter un client :\n1. Allez dans 'Clients' → 'Nouveau Client'\n2. Remplissez au minimum le nom et téléphone\n3. Optionnel : ajoutez email et adresse\n4. Enregistrez pour créer la fiche";
                        break;
                    case 'caisse':
                        message = "Pour encaisser :\n1. Recherchez le ticket par numéro ou client\n2. Cliquez sur 'Paiement'\n3. Choisissez le mode de paiement\n4. Saisissez le montant (total ou acompte)\n5. Confirmez l'encaissement";
                        break;
                    case 'stock':
                        message = "Alertes stock :\nLes produits en rupture apparaissent en rouge\nCommander avant d'atteindre le seuil critique\nVérifiez les dates de péremption régulièrement";
                        break;
                    case 'admin':
                        message = "Administration :\nAccès réservé au patron\nConfigurez les agences, utilisateurs, permissions\nConsultez les logs et sauvegardes";
                        break;
                }
                
                if (message) {
                    alert(message);
                }
            };
            
            // Afficher message de bienvenue selon le rôle
            const role = "<?php echo $role; ?>";
            const welcomeMessages = {
                'patron': "Bienvenue Patron ! Accès complet au système.",
                'Responsable': "Bienvenue Responsable ! Gestion complète de l'agence.",
                'Réceptionniste': "Bienvenue Réceptionniste ! Focus sur tickets et clients.",
                'Technicien': "Bienvenue Technicien ! Suivi des traitements et stock.",
                'Caissier': "Bienvenue Caissier ! Gestion des paiements et caisse.",
                'gestionnaire_stock': "Bienvenue Gestionnaire Stock ! Surveillance des produits.",
                'employe_pressing': "Bienvenue Employé ! Mise à jour des statuts."
            };
            
            if (welcomeMessages[role]) {
                console.log(welcomeMessages[role]);
            }
        });
    </script>
    
    <!-- Style pour badge personnalisé -->
    <style>
        .btn-outline-purple {
            color: #9b59b6;
            border-color: #9b59b6;
        }
        .btn-outline-purple:hover {
            color: white;
            background-color: #9b59b6;
            border-color: #9b59b6;
        }
    </style>
</body>
</html>
