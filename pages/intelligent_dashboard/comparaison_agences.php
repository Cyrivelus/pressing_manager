<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Benchmark & Comparaison des Agences";

// 1. Récupération des indicateurs de performance par agence (KPIs)
$query = "
    SELECT 
        a.nom_agence,
        a.code_agence,
        COUNT(t.id_ticket) as total_commandes,
        SUM(t.montant_total) as chiffre_affaires,
        AVG(t.montant_total) as panier_moyen,
        (SELECT COUNT(*) FROM clients c WHERE c.id_agence = a.id_agence) as nombre_clients
    FROM agences a
    LEFT JOIN tickets t ON a.id_agence = t.id_agence
    WHERE a.est_actif = TRUE
    GROUP BY a.id_agence
    ORDER BY chiffre_affaires DESC
";
$benchmarks = $pdo->query($query)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-balance-scale text-warning me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Analyse comparative de la productivité et de la rentabilité par site</p>
        </div>
        <button class="btn btn-outline-primary" onclick="window.print()">
            <i class="fas fa-file-export"></i> Exporter l'Audit
        </button>
    </div>

    <div class="row mb-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold mb-4">Répartition du CA par Agence</h5>
                <div class="d-flex align-items-end" style="height: 200px; gap: 40px;">
                    <?php foreach($benchmarks as $b): 
                        $max_ca = max(array_column($benchmarks, 'chiffre_affaires'));
                        $hauteur = ($b['chiffre_affaires'] / $max_ca) * 100;
                    ?>
                    <div class="flex-grow-1 d-flex flex-column align-items-center">
                        <small class="fw-bold mb-2"><?= number_format($b['chiffre_affaires'], 0, ',', ' ') ?> FCFA</small>
                        <div class="bg-primary opacity-75 rounded-top w-100" style="height: <?= $hauteur ?>%; min-height: 10px;"></div>
                        <span class="mt-2 small fw-bold text-uppercase"><?= $b['nom_agence'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold mb-0">Indicateurs de Performance (KPIs)</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Agence</th>
                        <th class="text-center">Volume Tickets</th>
                        <th class="text-center">Panier Moyen</th>
                        <th class="text-center">Fidélisation (Clients)</th>
                        <th class="text-center">CA Total</th>
                        <th class="text-end pe-4">Score Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($benchmarks as $b): 
                        $score = ($b['panier_moyen'] / 5000) * 10; // Score basé sur un panier cible de 5000
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold"><?= $b['nom_agence'] ?></div>
                            <small class="text-muted">Réf: <?= $b['code_agence'] ?></small>
                        </td>
                        <td class="text-center fw-bold"><?= $b['total_commandes'] ?></td>
                        <td class="text-center"><?= number_format($b['panier_moyen'], 0, ',', ' ') ?> FCFA</td>
                        <td class="text-center">
                            <i class="fas fa-users text-muted me-1"></i> <?= $b['nombre_clients'] ?>
                        </td>
                        <td class="text-center fw-bold text-primary"><?= number_format($b['chiffre_affaires'], 0, ',', ' ') ?> FCFA</td>
                        <td class="text-end pe-4">
                            <?php if($score >= 8): ?>
                                <span class="badge bg-success">Excellente</span>
                            <?php elseif($score >= 5): ?>
                                <span class="badge bg-warning text-dark">Stable</span>
                            <?php else: ?>
                                <span class="badge bg-danger">En Alerte</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>