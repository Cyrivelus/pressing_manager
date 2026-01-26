<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Analyse de la Satisfaction Client";

// 1. Calcul du score moyen (Simulation basée sur les avis récents)
$stats_avis = [
    'moyenne' => 4.7,
    'total_avis' => 128,
    'nps' => 65, // Net Promoter Score
    'recommandation' => 92 // % de clients prêts à recommander
];

// 2. Récupération des derniers avis (peut être lié à une table 'avis_clients' ou 'notes')
$sql = "SELECT c.nom_client, t.numero_ticket, n.message, n.date_notification as date_avis
        FROM notifications_clients n
        JOIN clients c ON n.id_client = c.id_client
        JOIN tickets t ON c.id_client = t.id_client
        WHERE n.type_notification = 'AVIS_CLIENT'
        ORDER BY n.date_envoi DESC LIMIT 10";
// Note: On adapte ici selon vos tables existantes
?>

<?php require_once  '../../templates/header.php'; ?>
<?php require_once  '../../templates/navigation.php'; ?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-teal"><i class="fas fa-smile me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Mesurez le bonheur de vos clients et identifiez vos points forts</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-teal"><i class="fas fa-envelope-open-text"></i> Envoyer Enquête</button>
            <button class="btn btn-teal text-white shadow-sm"><i class="fas fa-chart-pie"></i> Rapport Détaillé</button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-center">
                <small class="text-muted fw-bold">NOTE MOYENNE</small>
                <h1 class="fw-bold text-warning m-0"><?= $stats_avis['moyenne'] ?> <small class="fs-4 text-muted">/ 5</small></h1>
                <div class="text-warning small">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-center bg-teal text-white">
                <small class="opacity-75 fw-bold">NET PROMOTER SCORE (NPS)</small>
                <h1 class="fw-bold m-0"><?= $stats_avis['nps'] ?></h1>
                <small>Excellent (Bench: 50+)</small>
            </div>
        </div>

        

        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-center">
                <small class="text-muted fw-bold">TAUX DE RÉPONSE</small>
                <h1 class="fw-bold m-0 text-info">24%</h1>
                <small class="text-muted">Enquêtes remplies</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-center">
                <small class="text-muted fw-bold">RECOMMANDATION</small>
                <h1 class="fw-bold m-0 text-success"><?= $stats_avis['recommandation'] ?>%</h1>
                <small class="text-muted">Bouche-à-oreille positif</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Sentiments par catégorie</h6>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span>Qualité du nettoyage</span>
                            <span class="fw-bold text-success">98%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: 98%"></div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span>Délais de livraison</span>
                            <span class="fw-bold text-warning">75%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: 75%"></div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span>Accueil en boutique</span>
                            <span class="fw-bold text-info">88%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-info" style="width: 88%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Derniers témoignages</h6>
                </div>
                <div class="list-group list-group-flush scroll-avis" style="max-height: 350px; overflow-y: auto;">
                    <div class="list-group-item p-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-bold small text-primary">Mme Kouamé</span>
                            <small class="text-muted">Il y a 2h</small>
                        </div>
                        <p class="small mb-1 italic">"Traitement parfait de ma robe de mariée. Je recommande vivement !"</p>
                        <div class="text-warning extra-small">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="list-group-item p-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-bold small text-primary">M. Dupont</span>
                            <small class="text-muted">Hier</small>
                        </div>
                        <p class="small mb-1 italic">"Un peu de retard sur la livraison, mais le repassage est impeccable."</p>
                        <div class="text-warning extra-small">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .text-teal { color: #20c997; }
    .bg-teal { background-color: #20c997; }
    .btn-teal { background-color: #20c997; border-color: #20c997; }
    .extra-small { font-size: 0.7rem; }
    .italic { font-style: italic; }
</style>

<?php require_once  '../../templates/footer.php'; ?>