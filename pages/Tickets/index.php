<?php
// Inclusion de la navigation (qui contient déjà la session, la DB et ob_start)
require_once(__DIR__ . '/../../templates/navigation.php');

// 1. Logique de filtrage
$statut_filtre = $_GET['statut'] ?? '';
$recherche = $_GET['search'] ?? '';

// 2. Construction de la requête SQL
$sql = "SELECT t.*, c.nom as client_nom, c.telephone as client_tel 
        FROM tickets t 
        LEFT JOIN clients c ON t.client_id = c.id 
        WHERE 1=1";

if ($statut_filtre) {
    $sql .= " AND t.statut = :statut";
}
if ($recherche) {
    $sql .= " AND (c.nom LIKE :search OR t.code_ticket LIKE :search)";
}

$sql .= " ORDER BY t.date_creation DESC";

$stmt = $pdo->prepare($sql);

if ($statut_filtre) $stmt->bindValue(':statut', $statut_filtre);
if ($recherche) $stmt->bindValue(':search', "%$recherche%");

$stmt->execute();
$tickets = $stmt->fetchAll();

// Fonction utilitaire pour le badge de couleur
function getStatutBadge($statut) {
    $badges = [
        'En attente' => 'badge-warning',
        'En cours'   => 'badge-primary',
        'Prêt'       => 'badge-success',
        'Livré'      => 'badge-secondary',
        'Annulé'     => 'badge-danger'
    ];
    return $badges[$statut] ?? 'badge-dark';
}
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3"><i class="fas fa-ticket-alt text-primary"></i> Gestion des Tickets</h2>
        <a href="<?= generateUrl('pages/tickets/create.php') ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouveau Ticket
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher par client ou code..." value="<?= htmlspecialchars($recherche) ?>">
                </div>
                <div class="col-md-3">
                    <select name="statut" class="form-control">
                        <option value="">Tous les statuts</option>
                        <option value="En attente" <?= $statut_filtre == 'En attente' ? 'selected' : '' ?>>En attente</option>
                        <option value="En cours" <?= $statut_filtre == 'En cours' ? 'selected' : '' ?>>En cours</option>
                        <option value="Prêt" <?= $statut_filtre == 'Prêt' ? 'selected' : '' ?>>Prêt</option>
                        <option value="Livré" <?= $statut_filtre == 'Livré' ? 'selected' : '' ?>>Livré</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary btn-block">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Code</th>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Articles</th>
                            <th>Total</th>
                            <th>Statut</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Aucun ticket trouvé.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($ticket['code_ticket']) ?></strong></td>
                                    <td><?= date('d/m/Y H:i', strtotime($ticket['date_creation'])) ?></td>
                                    <td>
                                        <?= htmlspecialchars($ticket['client_nom']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($ticket['client_tel']) ?></small>
                                    </td>
                                    <td><?= $ticket['nombre_articles'] ?> article(s)</td>
                                    <td><?= number_format($ticket['montant_total'], 0, ',', ' ') ?> FCFA</td>
                                    <td>
                                        <span class="badge <?= getStatutBadge($ticket['statut']) ?>">
                                            <?= htmlspecialchars($ticket['statut']) ?>
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <div class="btn-group">
                                            <a href="view.php?id=<?= $ticket['id'] ?>" class="btn btn-sm btn-outline-info" title="Voir">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="print.php?id=<?= $ticket['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Imprimer">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <?php if (hasPermission($roleUtilisateur, 'all')): ?>
                                                <a href="edit.php?id=<?= $ticket['id'] ?>" class="btn btn-sm btn-outline-warning" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
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

<style>
    /* Styles spécifiques pour le tableau */
    .table td { vertical-align: middle; }
    .badge { padding: 8px 12px; font-weight: 500; text-transform: uppercase; font-size: 0.75rem; }
    .thead-light th { background-color: #f8f9fa; border-top: none; }
</style>

<?php
// Fin du fichier (navigation.php a déjà ouvert le body, on pourrait inclure un footer ici)
?>