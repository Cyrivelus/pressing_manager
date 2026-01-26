<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Social Media & Influence";

// 1. Récupération des photos "Avant/Après" pour suggestions de posts
$sql_photos = "SELECT t.numero_ticket, tr.photo_avant, tr.photo_apres, c.nom_categorie 
               FROM tracabilite_tickets tr
               JOIN tickets tk ON tr.id_ticket = tk.id_ticket
               JOIN lignes_ticket lt ON tk.id_ticket = lt.id_ticket
               JOIN services s ON lt.id_service = s.id_service
               JOIN categories_service c ON s.id_categorie = c.id_categorie
               WHERE tr.photo_apres IS NOT NULL 
               ORDER BY tr.date_heure DESC LIMIT 4";
$suggestions = $pdo->query($sql_photos)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-gradient-social"><i class="fas fa-share-alt me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Gérez votre présence sur Facebook, Instagram et WhatsApp Business</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary"><i class="fab fa-facebook-f"></i></button>
            <button class="btn btn-outline-danger"><i class="fab fa-instagram"></i></button>
            <button class="btn btn-outline-success"><i class="fab fa-whatsapp"></i></button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-primary text-white">
                <small class="opacity-75 fw-bold">PORTÉE TOTALE (30j)</small>
                <h2 class="fw-bold m-0">12.4K</h2>
                <small><i class="fas fa-arrow-up"></i> +5.2% vs mois dernier</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <small class="text-muted fw-bold">RESERVATIONS VIA SOCIAL</small>
                <h2 class="fw-bold m-0 text-dark">84</h2>
                <small class="text-primary">Source : Instagram Bio</small>
            </div>
        </div>
        
        

        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 bg-light">
                <h6 class="fw-bold mb-3"><i class="fas fa-magic text-warning me-2"></i>Smart Post Suggestion</h6>
                <p class="small text-muted">L'IA a détecté une transformation incroyable sur une <strong>Robe de Mariée</strong> hier. Voulez-vous la publier ?</p>
                <button class="btn btn-sm btn-dark">Générer le post Instagram</button>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Bibliothèque "Efficacité Prouvée"</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach($suggestions as $s): ?>
                        <div class="col-md-6">
                            <div class="border rounded-3 overflow-hidden shadow-sm">
                                <div class="d-flex" style="height: 150px;">
                                    <div class="w-50 bg-secondary" style="background: url('<?= $s['photo_avant'] ?>') center/cover;">
                                        <span class="badge bg-dark m-2">AVANT</span>
                                    </div>
                                    <div class="w-50 bg-info" style="background: url('<?= $s['photo_apres'] ?>') center/cover;">
                                        <span class="badge bg-primary m-2">APRÈS</span>
                                    </div>
                                </div>
                                <div class="p-3 d-flex justify-content-between align-items-center">
                                    <small class="fw-bold"><?= $s['nom_categorie'] ?> #<?= $s['numero_ticket'] ?></small>
                                    <button class="btn btn-xs btn-outline-primary"><i class="fas fa-upload"></i> Publier</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0">Publications Planifiées</h6>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item p-3 border-start border-4 border-success">
                        <small class="text-muted">Demain, 10:00 - Facebook</small>
                        <p class="mb-0 small fw-bold">"Astuce : Comment conserver vos costumes entre deux lavages..."</p>
                    </div>
                    <div class="list-group-item p-3 border-start border-4 border-info">
                        <small class="text-muted">Samedi, 09:00 - Instagram</small>
                        <p class="mb-0 small fw-bold">Réel : Dans les coulisses de notre atelier de repassage.</p>
                    </div>
                </div>
                <div class="card-footer bg-light text-center">
                    <button class="btn btn-sm btn-link text-decoration-none">Voir tout le calendrier</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .text-gradient-social {
        background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .btn-xs { padding: .25rem .5rem; font-size: .75rem; }
</style>

<?php require_once $root . '/templates/footer.php'; ?>