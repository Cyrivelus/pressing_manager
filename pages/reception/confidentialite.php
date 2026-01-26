<?php
// On définit l'URL de base pour les liens du footer
$base_url = "../../"; 
include_once "../../templates/header.php"; 
?>

<div class="container mt-5 mb-5" style="min-height: 70vh;">
    <div class="card shadow-sm border-0" style="border-radius: 15px;">
        <div class="card-body p-5">
            <div class="d-flex align-items-center mb-4">
                <div style="width: 50px; height: 50px; background: #f8b500; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                    <i class="fas fa-shield-alt text-white" style="font-size: 1.5rem;"></i>
                </div>
                <h1 class="text-primary m-0">Politique de Confidentialité</h1>
            </div>
            
            <p class="lead text-muted">
                La protection de vos données est une priorité pour <strong>Kayade PRESSING/COMMERCE Manager</strong>. 
                Cette politique détaille comment nous traitons les informations collectées au sein de l'application.
            </p>
            <hr class="my-4">

            <section class="mb-5">
                <h4 class="section-title">1. Collecte des données</h4>
                <p>Dans le cadre de l'activité de pressing et de commerce, nous collectons uniquement les données strictement nécessaires à la prestation de service :</p>
                <ul>
                    <li><strong>Données clients :</strong> Nom, prénom, numéro de téléphone (pour le suivi des commandes et alertes SMS).</li>
                    <li><strong>Données de transaction :</strong> Historique des dépôts, détails des articles confiés, paiements et soldes.</li>
                    <li><strong>Données techniques :</strong> Logs de connexion des utilisateurs (employés/administrateurs) pour la sécurité du système.</li>
                </ul>
            </section>

            

            <section class="mb-5">
                <h4 class="section-title">2. Utilisation des données</h4>
                <p>Vos informations sont utilisées exclusivement pour :</p>
                <ul>
                    <li>La gestion des dépôts et des retraits de linge.</li>
                    <li>L'édition des factures et tickets de réception.</li>
                    <li>La gestion de la relation client (notifications de disponibilité du linge).</li>
                    <li>La production de statistiques internes de vente pour le propriétaire.</li>
                </ul>
            </section>

            <section class="mb-5">
                <h4 class="section-title">3. Stockage et Sécurité</h4>
                <p>
                    L'application utilise des mesures de sécurité rigoureuses pour prévenir l'accès non autorisé, la modification ou la divulgation de vos données :
                </p>
                <div class="alert alert-info border-0 shadow-sm">
                    <strong>Sécurité locale :</strong> Les données sont stockées sur une base de données sécurisée. L'accès aux fonctions sensibles est protégé par des identifiants uniques et des niveaux de privilèges (Admin vs Réception).
                </div>
            </section>

            <section class="mb-5">
                <h4 class="section-title">4. Partage des données</h4>
                <p>
                    <strong>Aucune donnée collectée n'est vendue, louée ou cédée à des tiers.</strong> <br>
                    L'accès aux données est strictement réservé au personnel autorisé du pressing dans l'exercice de leurs fonctions.
                </p>
            </section>

            <section class="mb-5">
                <h4 class="section-title">5. Vos Droits</h4>
                <p>
                    Conformément aux lois sur la protection des données, chaque client dispose d'un droit d'accès, de rectification et de suppression des informations le concernant. Pour exercer ce droit, le client peut s'adresser directement à la réception du pressing.
                </p>
            </section>

            <div class="mt-5 d-flex justify-content-between align-items-center">
                <p class="small text-muted m-0">Dernière mise à jour : <?php echo date("d/m/Y"); ?></p>
                <a href="javascript:history.back()" class="btn btn-outline-primary shadow-sm">
                    <i class="fas fa-arrow-left mr-2"></i> Retour
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    body {
        background-color: #f8f9fa;
    }
    .section-title {
        color: #2c3e50;
        font-weight: 700;
        margin-bottom: 1.2rem;
        position: relative;
        padding-bottom: 5px;
    }
    .section-title::after {
        content: "";
        position: absolute;
        left: 0;
        bottom: 0;
        width: 40px;
        height: 3px;
        background-color: #f8b500;
        border-radius: 2px;
    }
    ul li {
        margin-bottom: 8px;
        color: #555;
    }
    .card {
        animation: fadeIn 0.6s ease-in-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<?php 
include_once "../../templates/footer.php"; 
?>