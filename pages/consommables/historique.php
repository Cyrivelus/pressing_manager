<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';


// 2. Filtrage par produit (optionnel)
$id_produit = isset($_GET['id']) ? intval($_GET['id']) : null;

try {
    // CORRECTION : Changement de u.nom en u.nom_complet
    $sql = "SELECT m.*, c.nom_article, c.unite_mesure, u.nom_complet as nom_utilisateur 
            FROM mouvements_stock m
            JOIN consommables c ON m.id_produit = c.id_consommable
            LEFT JOIN utilisateurs u ON m.id_utilisateur = u.id_utilisateur";
    
    if ($id_produit) {
        $sql .= " WHERE m.id_produit = :id";
    }
    
    $sql .= " ORDER BY m.date_mouvement DESC LIMIT 100";
    
    $stmt = $pdo->prepare($sql);
    if ($id_produit) $stmt->bindParam(':id', $id_produit);
    $stmt->execute();
    $mouvements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer le nom du produit si on filtre
    $nom_page = "Historique Global des Mouvements";
    if ($id_produit && !empty($mouvements)) {
        $nom_page = "Historique : " . $mouvements[0]['nom_article'];
    }

} catch (PDOException $e) {
    die("<div class='alert alert-danger'>Erreur SQL : " . $e->getMessage() . "</div>");
}

$titre = "Historique Stock";
require_once  '../../templates/header.php';
require_once '../../templates/navigation.php';
?>
<br><br><br>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0"><?= $nom_page ?></h2>
            <p class="text-muted small">Traçabilité complète des flux de stock</p>
        </div>
        <a href="javascript:history.back()" class="btn btn-outline-secondary shadow-sm">
    <-Retour 
</a>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 15px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Date & Heure</th>
                            <?php if (!$id_produit): ?><th>Article</th><?php endif; ?>
                            <th>Type</th>
                            <th class="text-center">Quantité</th>
                            <th class="text-center">Stock après</th>
                            <th>Référence</th>
                            <th>Utilisateur</th>
                            <th class="pe-4">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mouvements)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    Aucun mouvement enregistré pour le moment.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mouvements as $m): ?>
                                <tr>
                                    <td class="ps-4">
                                        <small class="d-block fw-bold"><?= date('d/m/Y', strtotime($m['date_mouvement'])) ?></small>
                                        <small class="text-muted"><?= date('H:i', strtotime($m['date_mouvement'])) ?></small>
                                    </td>
                                    <?php if (!$id_produit): ?>
                                        <td class="fw-bold"><?= htmlspecialchars($m['nom_article']) ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <?php 
                                        $badge_class = 'bg-secondary';
                                        $icon = 'fa-exchange-alt';
                                        if($m['type_mouvement'] == 'entree') { $badge_class = 'bg-success'; $icon = 'fa-arrow-down'; }
                                        elseif($m['type_mouvement'] == 'sortie') { $badge_class = 'bg-danger'; $icon = 'fa-arrow-up'; }
                                        elseif($m['type_mouvement'] == 'ajustement') { $badge_class = 'bg-info'; $icon = 'fa-sync-alt'; }
                                        ?>
                                        <span class="badge <?= $badge_class ?> text-uppercase p-2" style="min-width: 90px;">
                                            <i class="fas <?= $icon ?> me-1"></i> <?= $m['type_mouvement'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold">
                                        <span class="<?= $m['quantite'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $m['quantite'] > 0 ? '+' : '' ?><?= number_format($m['quantite'], 2) ?>
                                        </span>
                                        <small class="text-muted"><?= htmlspecialchars($m['unite_mesure']) ?></small>
                                    </td>
                                    <td class="text-center border-start">
                                        <span class="fw-bold"><?= number_format($m['quantite_apres'], 2) ?></span>
                                    </td>
                                    <td><code class="text-dark small"><?= htmlspecialchars($m['reference'] ?? 'N/A') ?></code></td>
                                    <td>
                                        
                                        <?= htmlspecialchars($m['nom_utilisateur'] ?? 'Système') ?>
                                    </td>
                                    <td class="pe-4 small text-muted italic">
                                        <?= htmlspecialchars($m['notes'] ?? '-') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>