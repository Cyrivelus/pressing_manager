<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion des Comptes & Trésorerie";

try {
    // 2. Récupération des soldes
    $sql_comptes = "SELECT * FROM comptes ORDER BY nom_compte ASC";
    $stmt_comptes = $pdo->query($sql_comptes);
    $comptes = $stmt_comptes->fetchAll();

    // 3. Récupération des 20 dernières transactions financières
    // Correction : Utilisation de u.nom_complet selon votre structure de table
    $sql_mouvements = "SELECT m.*, c.nom_compte, u.nom_complet as auteur
                       FROM mouvements_caisse m
                       JOIN comptes c ON m.id_compte = c.id_compte
                       LEFT JOIN utilisateurs u ON m.id_utilisateur = u.id_utilisateur
                       ORDER BY m.date_mouvement DESC LIMIT 20";
    $mouvements = $pdo->query($sql_mouvements)->fetchAll();

} catch (PDOException $e) {
    $error = "Erreur de base de données : " . $e->getMessage();
    $comptes = [];
    $mouvements = [];
}

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<br><br><br>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0 text-primary"><?= $titre ?></h2>
            <p class="text-muted">Suivi des flux monétaires en temps réel</p>
        </div>
        <div>
            <button class="btn btn-success fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAjustement">
                 Nouveau Mouvement
            </button>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger shadow-sm">
           <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-5">
        <?php if(empty($comptes)): ?>
            <div class="col-12 text-center text-muted">Aucun compte configuré. Exécutez le script SQL de création.</div>
        <?php else: ?>
            <?php foreach ($comptes as $compte): ?>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 border-start border-4 <?= $compte['solde'] < 0 ? 'border-danger' : 'border-primary' ?>">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <small class="text-muted fw-bold"><?= htmlspecialchars($compte['nom_compte']) ?></small>
                                <h2 class="fw-bold m-0 mt-1"><?= number_format($compte['solde'], 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h2>
                            </div>
                            <div class="bg-light p-3 rounded-circle">
                               
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="fw-bold mb-0">Derniers flux de trésorerie</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Compte</th>
                        <th>Type</th>
                        <th>Motif</th>
                        <th>Montant</th>
                        <th>Auteur</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mouvements)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">Aucune transaction enregistrée.</td></tr>
                    <?php else: ?>
                        <?php foreach ($mouvements as $m): ?>
                            <tr>
                                <td class="ps-4 small">
                                    <strong><?= date('d/m/Y', strtotime($m['date_mouvement'])) ?></strong><br>
                                    <span class="text-muted"><?= date('H:i', strtotime($m['date_mouvement'])) ?></span>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($m['nom_compte']) ?></span></td>
                                <td>
                                    <span class="badge <?= $m['type_mouvement'] == 'Entrée' ? 'bg-success' : 'bg-danger' ?>">
                                        <?= $m['type_mouvement'] ?>
                                    </span>
                                </td>
                                <td class="small fw-bold"><?= htmlspecialchars($m['libelle']) ?></td>
                                <td class="fw-bold <?= $m['type_mouvement'] == 'Entrée' ? 'text-success' : 'text-danger' ?>">
                                    <?= ($m['type_mouvement'] == 'Entrée' ? '+' : '-') . number_format($m['montant'], 0, ',', ' ') ?>
                                </td>
                                <td class="small"><?= htmlspecialchars($m['auteur']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAjustement" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" action="ajax_mouvement.php" method="POST">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Nouveau mouvement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Compte</label>
                    <select name="id_compte" class="form-select" required>
                        <?php foreach ($comptes as $c): ?>
                            <option value="<?= $c['id_compte'] ?>"><?= $c['nom_compte'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="Entrée">Entrée (Recette / Dépôt)</option>
                        <option value="Sortie">Sortie (Dépense / Retrait)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Montant (FCFA)</label>
                    <input type="number" name="montant" class="form-control" required min="1">
                </div>
                <div class="mb-0">
                    <label class="form-label">Motif</label>
                    <textarea name="libelle" class="form-control" placeholder="Détails de l'opération..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary w-100">Enregistrer l'opération</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>