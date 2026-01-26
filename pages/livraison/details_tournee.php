<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$id_tournee = $_GET['id'] ?? null;
if (!$id_tournee) {
    header('Location: tournees.php');
    exit;
}

try {
    // 1. Récupération des infos de la tournée (Sécurisé)
    $sql_t = "SELECT tr.*, u.nom_complet as livreur_nom 
              FROM tournees tr
              JOIN utilisateurs u ON tr.id_livreur = u.id_utilisateur
              WHERE tr.id_tournee = ?";
    $stmt_t = $pdo->prepare($sql_t);
    $stmt_t->execute([$id_tournee]);
    $tournee = $stmt_t->fetch();

    if (!$tournee) {
        die("Tournée introuvable.");
    }

    // 2. Récupération des détails (On ajuste t.total_ttc si votre colonne porte un autre nom)
    $sql_d = "SELECT td.*, t.numero_ticket, t.total_ttc, c.nom_client, c.adresse, c.telephone 
              FROM tournees_details td
              JOIN tickets t ON td.id_ticket = t.id_ticket
              JOIN clients c ON t.id_client = c.id_client
              WHERE td.id_tournee = ?
              ORDER BY td.ordre_passage ASC";
    $stmt_d = $pdo->prepare($sql_d);
    $stmt_d->execute([$id_tournee]);
    $details = $stmt_d->fetchAll();

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

$titre = "Détails Tournée #" . $id_tournee;
require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="mb-4 mt-4">
        <a href="tournees.php" class="btn btn-link text-decoration-none text-muted p-0 mb-2">
            <i class="fas fa-arrow-left"></i> Retour aux tournées
        </a>
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-bold m-0">Feuille de Route : <span class="text-primary"><?= htmlspecialchars($tournee['livreur_nom']) ?></span></h2>
            <div class="badge bg-dark fs-6"><?= date('d/m/Y', strtotime($tournee['date_tournee'])) ?> - <?= $tournee['heure_depart'] ?></div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-bold text-uppercase small text-muted mb-3">Véhicule & Zone</h6>
                    <p class="mb-1"><i class="fas fa-car me-2 text-primary"></i> <?= htmlspecialchars($tournee['vehicule_immatriculation'] ?? 'Non défini') ?></p>
                    <p class="mb-0"><i class="fas fa-map-marker-alt me-2 text-danger"></i> <?= htmlspecialchars($tournee['zone_livraison'] ?? 'Zone non spécifiée') ?></p>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm bg-primary text-white text-center p-3" style="border-radius: 15px;">
                <h2 class="fw-bold m-0"><?= count($details) ?></h2>
                <small class="opacity-75 text-uppercase fw-bold">Arrêts prévus</small>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Ordre</th>
                                <th>Client & Adresse</th>
                                <th>Ticket / Montant</th>
                                <th class="text-center">Statut</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($details as $index => $d): 
                                $status_map = [
                                    'attente' => ['class' => 'bg-warning-soft text-warning', 'label' => 'À livrer'],
                                    'livre' => ['class' => 'bg-success-soft text-success', 'label' => 'Livré'],
                                    'absent' => ['class' => 'bg-danger-soft text-danger', 'label' => 'Absent'],
                                    'annule' => ['class' => 'bg-secondary-soft text-secondary', 'label' => 'Annulé']
                                ];
                                $s = $status_map[$d['statut_livraison']] ?? ['class' => 'bg-light', 'label' => 'Inconnu'];
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="avatar-order shadow-sm"><?= $index + 1 ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($d['nom_client']) ?></div>
                                    <div class="small text-muted text-truncate" style="max-width: 250px;">
                                        <i class="fas fa-location-arrow me-1"></i> <?= htmlspecialchars($d['adresse']) ?>
                                    </div>
                                    <a href="tel:<?= $d['telephone'] ?>" class="text-decoration-none small fw-bold">
                                        <i class="fas fa-phone-alt me-1"></i> <?= $d['telephone'] ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">#<?= $d['numero_ticket'] ?></span>
                                    <div class="small fw-bold text-success mt-1"><?= number_format($d['total_ttc'], 0, ',', ' ') ?> FCFA</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $s['class'] ?> px-3 py-2"><?= $s['label'] ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-white border dropdown-toggle" data-bs-toggle="dropdown">
                                            Action
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            <li><a class="dropdown-item py-2" href="update_statut_livraison.php?id=<?= $d['id_detail'] ?>&status=livre&tournee=<?= $id_tournee ?>"><i class="fas fa-check-circle text-success me-2"></i> Marquer comme Livré</a></li>
                                            <li><a class="dropdown-item py-2" href="update_statut_livraison.php?id=<?= $d['id_detail'] ?>&status=absent&tournee=<?= $id_tournee ?>"><i class="fas fa-user-slash text-warning me-2"></i> Client Absent</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item py-2 text-danger" href="update_statut_livraison.php?id=<?= $d['id_detail'] ?>&status=annule&tournee=<?= $id_tournee ?>"><i class="fas fa-times-circle me-2"></i> Annuler</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-success-soft { background-color: rgba(0, 217, 126, 0.1); color: #00d97e; }
    .bg-warning-soft { background-color: rgba(246, 195, 68, 0.1); color: #f6c344; }
    .bg-danger-soft { background-color: rgba(230, 55, 87, 0.1); color: #e63757; }
    .bg-secondary-soft { background-color: rgba(110, 132, 163, 0.1); color: #6e84a3; }
    .avatar-order {
        width: 32px; height: 32px; background: #212529; color: white;
        border-radius: 8px; display: flex; align-items: center; justify-content: center;
        font-weight: bold; font-size: 0.85rem;
    }
    .btn-white { background: white; color: #333; }
</style>

<?php require_once '../../templates/footer.php'; ?>