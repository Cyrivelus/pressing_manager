<?php
// pages/reporting/index.php

// 1. Correction de l'erreur session_start() : 
//    On ne démarre la session que si elle n'est pas déjà active.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclusions standard (ajustez les chemins si nécessaire)
// L'appel à session_start() dans header.php doit être supprimé ou conditionnel.
require_once('../../fonctions/database.php');
require_once('../../templates/header.php');
require_once('../../templates/navigation.php');

$titre = 'Tableau de bord des Rapports Financiers';
?>

<div class="reporting-wrapper">
    <div class="container-fluid px-3 px-md-4">
        <div class="row">
            <div class="col-12">
                
                <h2 class="page-title text-primary pt-4 pb-2 mb-4 animate__animated animate__fadeInDown">
                    <i class="bi bi-bar-chart-line-fill me-2"></i> <?= htmlspecialchars($titre) ?>
                </h2>
                <p class="lead mb-4 animate__animated animate__fadeIn" style="animation-delay: 0.2s;">
                    Sélectionnez le rapport financier que vous souhaitez consulter ou exporter.
                </p>
                
                <hr class="mb-5">

                <div class="card shadow-lg mb-5 animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                    <div class="card-header bg-gradient-primary text-white header-gradient">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-bar-graph me-2"></i> Rapports Standards</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                                <div class="report-card p-4 border rounded text-center bg-light-subtle shadow-sm">
                                    <h6 class="text-success">Bilan</h6>
                                    <p class="text-muted small">Situation financière à une date donnée.</p>
                                    <a href="balance.php" class="btn btn-success btn-sm w-100"><i class="bi bi-file-spreadsheet"></i> Consulter</a>
                                </div>
                            </div>
                            
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                                <div class="report-card p-4 border rounded text-center bg-light-subtle shadow-sm">
                                    <h6 class="text-danger">Compte de Résultat</h6>
                                    <p class="text-muted small">Performance sur une période donnée.</p>
                                    <a href="profit_loss.php" class="btn btn-danger btn-sm w-100"><i class="bi bi-graph-up-arrow"></i> Consulter</a>
                                </div>
                            </div>

                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                                <div class="report-card p-4 border rounded text-center bg-light-subtle shadow-sm">
                                    <h6 class="text-info">Flux de Trésorerie</h6>
                                    <p class="text-muted small">Mouvements d'encaisse sur la période.</p>
                                    <a href="cash_flow_statement.php" class="btn btn-info btn-sm w-100"><i class="bi bi-cash-stack"></i> Consulter</a>
                                </div>
                            </div>
                            
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                                <div class="report-card p-4 border rounded text-center bg-light-subtle shadow-sm">
                                    <h6 class="text-secondary">Balance Générale</h6>
                                    <p class="text-muted small">Synthèse des soldes de tous les comptes.</p>
                                    <a href="balance_generale.php" class="btn btn-secondary btn-sm w-100"><i class="bi bi-journal-check"></i> Consulter</a>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>

                <div class="card shadow-lg mb-5 animate__animated animate__fadeInUp" style="animation-delay: 0.5s;">
                    <div class="card-header bg-gradient-warning text-dark header-gradient-warning">
                        <h5 class="mb-0"><i class="bi bi-book-fill me-2"></i> Grands Livres et Journaux</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                                <div class="report-card p-4 border rounded text-center bg-light-subtle shadow-sm">
                                    <h6 class="text-warning">Journal Général</h6>
                                    <p class="text-muted small">Liste chronologique de toutes les écritures.</p>
                                    <a href="journal_general.php" class="btn btn-warning btn-sm w-100"><i class="bi bi-card-list"></i> Consulter</a>
                                </div>
                            </div>

                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                                <div class="report-card p-4 border rounded text-center bg-light-subtle shadow-sm">
                                    <h6 class="text-dark">Grand Livre</h6>
                                    <p class="text-muted small">Détail des mouvements par compte.</p>
                                    <a href="ledger_accounts.php" class="btn btn-dark btn-sm w-100"><i class="bi bi-archive-fill"></i> Consulter</a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="card shadow-lg mb-5 animate__animated animate__fadeInUp" style="animation-delay: 0.7s;">
                    <div class="card-header bg-gradient-info text-white header-gradient-info">
                        <h5 class="mb-0"><i class="bi bi-download me-2"></i> Exportations et Fiscalité</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">

                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                                <div class="report-card p-4 border rounded text-center bg-light-subtle shadow-sm">
                                    <h6 class="text-info">Liasse Fiscale</h6>
                                    <p class="text-muted small">Génération du document pour l'administration.</p>
                                    <a href="liasse_fiscale.php" class="btn btn-info btn-sm w-100"><i class="bi bi-file-text-fill"></i> Préparer</a>
                                </div>
                            </div>
                            
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                                <div class="report-card p-4 border rounded text-center bg-light-subtle shadow-sm">
                                    <h6 class="text-primary">Exportation de Données</h6>
                                    <p class="text-muted small">Exporter les données comptables (CSV/Excel).</p>
                                    <a href="export_reports.php" class="btn btn-primary btn-sm w-100"><i class="bi bi-file-earmark-arrow-down-fill"></i> Exporter</a>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<style>
    /* 3. MEDIA QUERIES & WRAPPER POUR LE RESPONSIVE 
    (Doit être adapté pour correspondre à la largeur réelle de votre sidebar)
    */
    :root {
        --sidebar-width: 250px; /* Largeur supposée de navigation.php */
        --card-bg-light: #f8f9fa; /* Couleur de fond des cartes internes */
    }

    .reporting-wrapper {
        margin-left: var(--sidebar-width);
        width: calc(100% - var(--sidebar-width));
        transition: margin-left 0.3s ease, width 0.3s ease;
    }

    /* Styles d'en-tête modernes avec dégradés */
    .header-gradient {
        background: linear-gradient(135deg, #0d6efd 0%, #0c4d9b 100%) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }
    .header-gradient-warning {
        background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%) !important;
    }
    .header-gradient-info {
        background: linear-gradient(135deg, #0dcaf0 0%, #0a8a9a 100%) !important;
    }
    
    /* Styles pour les cartes de rapport (Design moderne) */
    .report-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s;
        min-height: 180px; /* Augmenté pour l'esthétique */
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        background-color: var(--card-bg-light) !important;
        /* Ombre initiale subtile */
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .report-card:hover {
        transform: translateY(-5px); /* Animation au survol */
        box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.15) !important;
    }
    
    .page-title {
        /* Suppression des espaces en haut et style visuel */
        border-bottom: 3px solid var(--bs-primary);
        font-weight: 700;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
    }

    /* --- MEDIA QUERIES COMPLÈTES --- */

    /* 1. Desktop (1400px+) */
    @media (min-width: 1400px) {
        .col-xl-3 {
            flex: 0 0 auto;
            width: 25%;
        }
        .reporting-wrapper {
            /* Maintient le décalage standard */
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
        }
    }

    /* 2. Desktop moyen (1200px+) */
    @media (min-width: 1200px) and (max-width: 1399.98px) {
        .col-lg-4 {
            flex: 0 0 auto;
            width: 33.333333%;
        }
        .reporting-wrapper {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
        }
    }

    /* 3. Tablettes paysage (992px+) */
    @media (min-width: 992px) and (max-width: 1199.98px) {
        .col-lg-4 {
            flex: 0 0 auto;
            width: 33.333333%;
        }
        .reporting-wrapper {
            /* Sidebar peut être cachée/réduite sur tablettes */
            margin-left: var(--sidebar-width); 
            width: calc(100% - var(--sidebar-width));
        }
    }

    /* 4. Tablettes portrait (768px+) */
    @media (min-width: 768px) and (max-width: 991.98px) {
        .col-md-6 {
            flex: 0 0 auto;
            width: 50%;
        }
        .reporting-wrapper {
            /* Quand la sidebar est repliée ou masquée */
            margin-left: 0; 
            width: 100%;
        }
    }

    /* 5. Mobiles (576px+) */
    @media (max-width: 767.98px) {
        .col-sm-6 {
            flex: 0 0 auto;
            width: 50%;
        }
        .col-12 {
            width: 100%;
        }
        .reporting-wrapper {
            /* Pleine largeur sur mobile */
            margin-left: 0; 
            width: 100%;
        }
        .page-title {
            font-size: 1.75rem;
        }
    }
</style>
<?php
require_once('../../templates/footer.php');
?>