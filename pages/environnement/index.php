<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Management Environnemental";

try {
    // 2. Statistiques rapides (Simulées ou basées sur vos tables)
    
    // Volume de déchets collectés (Ex: Somme de la table gestion_dechets)
    $sql_dechets = "SELECT SUM(quantite) FROM consommables WHERE categorie = 'Déchets Chimiques'";
    // Si la table n'existe pas encore, on initialise à 0
    $total_dechets = 0; 

    // Nombre de produits chimiques en stock
    $sql_chimie = "SELECT COUNT(*) FROM produits WHERE categorie = 'Chimique'";
    $nb_chimiques = $pdo->query($sql_chimie)->fetchColumn() ?: 0;

    // Statut des certifications actives
    $nb_certifs = 2; // Exemple: Eco-Label, ISO 14001

} catch (PDOException $e) {
    $error = "Erreur : " . $e->getMessage();
}

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-5 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-success"><?= $titre ?></h2>
            <p class="text-muted">Suivi de l'empreinte écologique et conformité réglementaire.</p>
        </div>
        <div class="bg-success-soft p-3 rounded-4 border border-success border-opacity-25">
            <span class="text-success fw-bold">Pressing Éco-Responsable</span>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-muted fw-bold text-uppercase">Produits Chimiques</small>
                        <h2 class="fw-bold mb-0 mt-1"><?= $nb_chimiques ?></h2>
                        <p class="text-muted small mb-0">Substances répertoriées</p>
                    </div>
                    <div class="text-warning"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-muted fw-bold text-uppercase">Déchets Collectés</small>
                        <h2 class="fw-bold mb-0 mt-1">124 <small class="fs-6 text-muted">Kg</small></h2>
                        <p class="text-muted small mb-0">Total sur l'année en cours</p>
                    </div>
                    <div class="text-success"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-dark text-white">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-white-50 fw-bold text-uppercase">Certifications</small>
                        <h2 class="fw-bold mb-0 mt-1"><?= $nb_certifs ?></h2>
                        <p class="text-info small mb-0">Normes à jour</p>
                    </div>
                    <div class="text-info"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-6 col-lg-3">
            <a href="tracabilite_chimiques.php" class="card border-0 shadow-sm rounded-4 p-4 text-center text-decoration-none eco-menu h-100">
                <div class="icon-circle bg-warning-soft text-warning mb-3 mx-auto">
                  
                </div>
                <h5 class="fw-bold text-dark">Traçabilité Chimique</h5>
                <p class="text-muted small">Suivi des solvants, détergents et FDS (Fiches de Données de Sécurité).</p>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="gestion_dechets.php" class="card border-0 shadow-sm rounded-4 p-4 text-center text-decoration-none eco-menu h-100">
                <div class="icon-circle bg-success-soft text-success mb-3 mx-auto">
                   
                </div>
                <h5 class="fw-bold text-dark">Gestion des Déchets</h5>
                <p class="text-muted small">Bordereaux de suivi des déchets (BSD) et recyclage des cintres/plastiques.</p>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="certifications.php" class="card border-0 shadow-sm rounded-4 p-4 text-center text-decoration-none eco-menu h-100">
                <div class="icon-circle bg-primary-soft text-primary mb-3 mx-auto">
                   
                </div>
                <h5 class="fw-bold text-dark">Certifications</h5>
                <p class="text-muted small">Renouvellement des labels écologiques et audits de conformité.</p>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="rapports_rse.php" class="card border-0 shadow-sm rounded-4 p-4 text-center text-decoration-none eco-menu h-100">
                <div class="icon-circle bg-info-soft text-info mb-3 mx-auto">
                  
                </div>
                <h5 class="fw-bold text-dark">Rapports RSE</h5>
                <p class="text-muted small">Bilans annuels d'impact social et environnemental pour les partenaires.</p>
            </a>
        </div>
    </div>

    <div class="alert bg-light border-0 rounded-4 p-4 d-flex align-items-start shadow-sm">
       
        <div>
            <h6 class="fw-bold">Pourquoi ce module ?</h6>
            <p class="mb-0 text-muted small">Le secteur du pressing est soumis à des normes strictes (ex: Directive Solvants). Ce module vous permet de centraliser vos preuves de conformité en cas de contrôle des autorités environnementales et valorise votre image auprès des clients B2B (Hôtels, Entreprises).</p>
        </div>
    </div>
</div>

<style>
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
    
    .icon-circle {
        width: 70px;
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }
    
    .eco-menu {
        transition: all 0.3s ease;
        border: 1px solid transparent !important;
    }
    
    .eco-menu:hover {
        transform: translateY(-10px);
        background-color: #ffffff;
        border-color: #198754 !important;
        box-shadow: 0 1rem 3rem rgba(0,0,0,.1) !important;
    }
</style>

<?php require_once '../../templates/footer.php'; ?>