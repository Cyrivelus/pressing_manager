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
                    <i class="fas fa-file-contract text-white" style="font-size: 1.5rem;"></i>
                </div>
                <h1 class="text-primary m-0">Conditions d'Utilisation</h1>
            </div>

            <p class="lead text-muted">
                Les présentes conditions régissent l'utilisation de l'application <strong>Kayade PRESSING/COMMERCE Manager</strong> et les prestations de services fournies par l'établissement.
            </p>
            <hr class="my-4">

            <section class="mb-5">
                <h4 class="section-title">1. Objet du Service</h4>
                <p>
                    L'application fournit un support de gestion pour les opérations de dépôt, de traitement, de facturation et de retrait des articles confiés au pressing. Toute utilisation de l'application implique l'adhésion du personnel et des clients aux présentes conditions.
                </p>
            </section>

            <section class="mb-5">
                <h4 class="section-title">2. Responsabilité du Dépôt</h4>
                <p>Lors du dépôt, le client et le réceptionniste s'engagent à :</p>
                <ul>
                    <li>Signaler tout défaut, usure ou fragilité particulière sur les articles.</li>
                    <li>Vérifier l'exactitude des informations saisies sur le ticket de réception (nombre d'articles, nature, prix).</li>
                    <li>Vider les poches des vêtements. L'établissement décline toute responsabilité en cas de perte d'objets oubliés ou de dommages causés par ceux-ci.</li>
                </ul>
            </section>

            <section class="mb-5">
                <h4 class="section-title">3. Traitement et Délais</h4>
                <p>
                    Les délais de livraison sont donnés à titre indicatif. <strong>Kayade PRESSING</strong> s'efforce de respecter les dates indiquées sur le ticket. En cas de force majeure ou de traitement spécial nécessitant plus de temps, le client sera prévenu par les moyens de contact enregistrés.
                </p>
            </section>

            

            <section class="mb-5">
                <h4 class="section-title">4. Indemnisation en cas de Dommage ou Perte</h4>
                <p>En cas de problème avéré lors du traitement de l'article :</p>
                <ul>
                    <li><strong>Dommage :</strong> Toute réclamation doit être faite au moment du retrait. Aucun recours n'est possible après la sortie de l'article de l'établissement.</li>
                    <li><strong>Indemnisation :</strong> En cas de perte ou de dégradation sous la responsabilité du pressing, l'indemnisation se basera sur la valeur d'achat de l'article après dépréciation (vétusté), conformément au barème professionnel en vigueur.</li>
                </ul>
            </section>

            <section class="mb-5">
                <h4 class="section-title">5. Articles non retirés</h4>
                <p>
                    Les articles non retirés dans un délai de <strong>90 jours</strong> après la date de dépôt pourront être considérés comme abandonnés. L'établissement se réserve le droit de les remettre à des œuvres de bienfaisance ou de les détruire après cette période.
                </p>
            </section>

            <section class="mb-5">
                <h4 class="section-title">6. Sécurité de l'Application</h4>
                <p>
                    L'utilisation des comptes utilisateurs est strictement personnelle. L'administrateur système (<strong>Tamboug Cyrille Steve</strong>) se réserve le droit de suspendre tout accès suspect pour garantir l'intégrité des données financières et commerciales.
                </p>
            </section>

            <div class="mt-5 d-flex justify-content-between align-items-center">
                <p class="small text-muted m-0">Version applicable au : <?php echo date("d/m/Y"); ?></p>
                <a href="javascript:history.back()" class="btn btn-primary px-4 shadow-sm">
                    Accepter et Fermer
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
        margin-bottom: 10px;
        color: #555;
        line-height: 1.6;
    }
    .card {
        animation: slideIn 0.5s ease-out;
    }
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }
</style>

<?php 
include_once "../../templates/footer.php"; 
?>