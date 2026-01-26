<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$root = realpath(__DIR__ . '/../../../');
require_once $root . '/fonctions/database.php';

$titre = "Gestion des Dettes Fournisseurs";

$sql = "SELECT d.*, f.nom_fournisseur, f.telephone 
        FROM dettes_fournisseur d
        JOIN fournisseurs f ON d.id_fournisseur = f.id_fournisseur
        WHERE d.statut != 'SOLDE'
        ORDER BY d.date_echeance ASC";
$dettes = $pdo->query($sql)->fetchAll();

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-hand-holding-usd text-warning me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Suivez ce que vous devez à vos partenaires commerciaux</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalDette">
            <i class="fas fa-plus me-1"></i> Nouvelle Dette
        </button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="small text-uppercase">
                        <th class="ps-4">Fournisseur</th>
                        <th>Date Facture</th>
                        <th class="text-end">Montant Total</th>
                        <th class="text-end">Reste à Payer</th>
                        <th class="text-center">Échéance</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($dettes as $d): 
                        $retard = strtotime($d['date_echeance']) < time();
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold"><?= htmlspecialchars($d['nom_fournisseur']) ?></div>
                            <small class="text-muted"><?= $d['telephone'] ?></small>
                        </td>
                        <td><?= date('d/m/Y', strtotime($d['date_facture'])) ?></td>
                        <td class="text-end"><?= number_format($d['montant_total'], 0, ',', ' ') ?> F</td>
                        <td class="text-end fw-bold text-danger"><?= number_format($d['reste_a_payer'], 0, ',', ' ') ?> F</td>
                        <td class="text-center">
                            <span class="badge <?= $retard ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                <?= date('d/m/Y', strtotime($d['date_echeance'])) ?>
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <a href="paiement.php?id=<?= $d['id_dette'] ?>" class="btn btn-sm btn-success">Payer</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>