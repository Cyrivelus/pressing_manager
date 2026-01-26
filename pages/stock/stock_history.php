<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once(__DIR__ . '/../../fonctions/database.php');

// Récupération des mouvements avec jointures pour les noms
$query = "SELECT m.*, p.nom_produit, u.nom_complet as utilisateur 
          FROM mouvements_stock m
          JOIN produits p ON m.id_produit = p.id_produit
          JOIN utilisateurs u ON m.id_utilisateur = u.id_utilisateur
          ORDER BY m.date_mouvement DESC";

$mouvements = $pdo->query($query)->fetchAll();

include '../../templates/header.php';
include '../../templates/navigation.php';
?>
<br><br><br>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Historique des mouvements de stock</h4>
    <a href="javascript:history.back()" class="btn btn-outline-primary btn-sm">
        <- Retour
    </a>
</div>

    <div class="card shadow border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Produit</th>
                        <th>Type</th>
                        <th>Quantité</th>
                        <th>Stock Après</th>
                        <th>Par</th>
                        <th>Note/Réf</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($mouvements as $m): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($m['date_mouvement'])) ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($m['nom_produit']) ?></td>
                            <td>
                                <?php if($m['type_mouvement'] == 'entree'): ?>
                                    <span class="badge bg-success-soft text-success"> Entrée</span>
                                <?php elseif($m['type_mouvement'] == 'sortie'): ?>
                                    <span class="badge bg-danger-soft text-danger"> Sortie</span>
                                <?php else: ?>
                                    <span class="badge bg-info-soft text-info">Ajustement</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold <?= $m['type_mouvement'] == 'sortie' ? 'text-danger' : 'text-success' ?>">
                                <?= $m['type_mouvement'] == 'sortie' ? '-' : '+' ?> <?= $m['quantite'] ?>
                            </td>
                            <td><span class="text-muted"><?= $m['quantite_apres'] ?></span></td>
                            <td><small><?= htmlspecialchars($m['utilisateur']) ?></small></td>
                            <td><small class="text-muted"><?= htmlspecialchars($m['reference'] . ' ' . $m['notes']) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if(empty($mouvements)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Aucun mouvement enregistré.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .bg-success-soft { background-color: rgba(40, 167, 69, 0.1); }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
    .bg-info-soft { background-color: rgba(23, 162, 184, 0.1); }
</style>

<?php include '../../templates/footer.php'; ?>