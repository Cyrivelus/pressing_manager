<?php
// On définit l'URL de base pour les liens du footer
$base_url = "../../"; 
include_once "../../templates/header.php"; // Ajustez le chemin selon votre structure
?>

<div class="container mt-5 mb-5" style="min-height: 70vh;">
    <div class="card shadow-sm">
        <div class="card-body p-5">
            <h1 class="text-primary mb-4">Mentions Légales</h1>
            <hr>

            <section class="mb-5">
                <h4 class="text-secondary">1. Présentation du site</h4>
                <p>
                    En vertu de l'article 6 de la loi n° 2004-575 du 21 juin 2004 pour la confiance dans l'économie numérique, 
                    il est précisé aux utilisateurs de l'application <strong>Kayade PRESSING/COMMERCE Manager</strong> 
                    l'identité des différents intervenants dans le cadre de sa réalisation et de son suivi :
                </p>
                <ul>
                    <li><strong>Propriétaire & Éditeur :</strong> Tamboug Cyrille Steve</li>
                    <li><strong>Statut :</strong> Développeur Indépendant / Spécialiste Sécurité</li>
                    <li><strong>Contact :</strong> cyrillestevetamboug@gmail.com | +237 696 19 75 25</li>
                    <li><strong>Responsable publication :</strong> Tamboug Cyrille Steve</li>
                </ul>
            </section>

            <section class="mb-5">
                <h4 class="text-secondary">2. Hébergement</h4>
                <p>
                    L'application est actuellement déployée en environnement local ou sur serveur privé. <br>
           
                </p>
            </section>

            <section class="mb-5">
                <h4 class="text-secondary">3. Propriété Intellectuelle</h4>
                <p>
                    <strong>Tamboug Cyrille Steve</strong> est propriétaire des droits de propriété intellectuelle ou détient les droits d’usage sur tous les éléments accessibles sur l’application, notamment : les textes, images, graphismes, logo, icônes, sons et logiciels.
                </p>
                <p>
                    Toute reproduction, représentation, modification, publication, adaptation de tout ou partie des éléments du site, quel que soit le moyen ou le procédé utilisé, est interdite, sauf autorisation écrite préalable de l'auteur.
                </p>
            </section>

            <section class="mb-5">
                <h4 class="text-secondary">4. Limitation de responsabilité</h4>
                <p>
                    L'éditeur ne pourra être tenu responsable des dommages directs et indirects causés au matériel de l’utilisateur, lors de l’accès à l’application, et résultant soit de l’utilisation d’un matériel ne répondant pas aux spécifications techniques, soit de l’apparition d’un bug ou d’une incompatibilité.
                </p>
            </section>

            <section class="mb-5">
                <h4 class="text-secondary">5. Gestion des données personnelles</h4>
                <p>
                    Dans le cadre de la gestion du pressing et du commerce, l'application collecte des informations relatives aux clients (Noms, Téléphones). Conformément aux réglementations sur la protection des données, vous disposez d'un droit d'accès, de rectification et d'opposition aux données personnelles vous concernant en contactant l'administrateur du système.
                </p>
            </section>

            <div class="mt-4">
                <a href="javascript:history.back()" class="btn btn-primary">Retour à l'accueil</a>
            </div>
        </div>
    </div>
</div>

<style>
    /* Styles spécifiques pour rendre les mentions légales élégantes */
    body {
        background-color: #f4f7f6;
    }
    h4 {
        border-left: 4px solid #f8b500;
        padding-left: 15px;
        margin-top: 25px;
    }
    .card {
        border: none;
        border-radius: 15px;
    }
</style>

<?php 
include_once "../../templates/footer.php"; 
?>