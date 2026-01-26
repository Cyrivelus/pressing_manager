<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$id_ticket = $_GET['id'] ?? null;
$utilisateur_id = $_SESSION['utilisateur_id'];

// 2. Récupération des données complètes
$stmt = $pdo->prepare("
    SELECT t.*, c.nom_client, c.prenom_client, c.adresse, c.telephone
    FROM tickets t 
    JOIN clients c ON t.id_client = c.id_client
    WHERE t.id_ticket = ? AND t.id_client = ?
");
$stmt->execute([$id_ticket, $utilisateur_id]);
$facture = $stmt->fetch();

if (!$facture) {
    die("Facture introuvable ou accès non autorisé.");
}

$items = json_decode($facture['details_articles'], true) ?? [];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture_<?= $facture['code_ticket'] ?></title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    <style>
        body { background: #f0f0f0; padding: 50px 0; }
        .invoice-box {
            max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee;
            background: #fff; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            font-size: 14px; line-height: 24px; color: #555;
        }
        .invoice-header { border-bottom: 2px solid #3498db; padding-bottom: 20px; margin-bottom: 20px; }
        @media print {
            body { background: none; padding: 0; }
            .invoice-box { box-shadow: none; border: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="container no-print mb-4 text-center">
    <button onclick="window.print()" class="btn btn-primary px-4">
        <i class="fas fa-print me-2"></i> Imprimer / Enregistrer en PDF
    </button>
    <a href="historique.php" class="btn btn-outline-secondary px-4">Retour</a>
</div>

<div class="invoice-box">
    <div class="invoice-header d-flex justify-content-between align-items-center">
        <div>
            <img src="../../assets/images/logo.png" style="width: 150px;" alt="Logo Pressing">
            <p class="mt-2 mb-0"><strong>Pressing Manager Pro</strong><br>
            Avenue de l'Indépendance, Yaoundé<br>
            Contact: +237 600 000 000</p>
        </div>
        <div class="text-end">
            <h2 class="text-primary fw-bold">FACTURE</h2>
            <p class="mb-0 text-muted">Référence : #<?= $facture['code_ticket'] ?></p>
            <p class="mb-0 text-muted">Date : <?= date('d/m/Y', strtotime($facture['date_reception'])) ?></p>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-6">
            <p class="text-muted mb-1">Facturé à :</p>
            <h5 class="fw-bold"><?= htmlspecialchars($facture['prenom_client'] . ' ' . $facture['nom_client']) ?></h5>
            <p><?= htmlspecialchars($facture['adresse']) ?><br>
            Tél: <?= htmlspecialchars($facture['telephone']) ?></p>
        </div>
        <div class="col-6 text-end">
            <p class="text-muted mb-1">Détails de paiement :</p>
            <p>Statut : <span class="badge bg-success">PAYÉ</span><br>
            Mode : <?= strtoupper($facture['mode_paiement'] ?? 'Espèces') ?></p>
        </div>
    </div>

    

    <table class="table table-bordered">
        <thead class="table-light text-center">
            <tr>
                <th>Désignation</th>
                <th style="width: 100px;">Qté</th>
                <th style="width: 150px;">Prix Unit.</th>
                <th style="width: 150px;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['libelle']) ?></td>
                <td class="text-center"><?= $item['qte'] ?></td>
                <td class="text-end"><?= number_format($item['prix_u'] ?? ($item['prix']/$item['qte']), 0, ',', ' ') ?></td>
                <td class="text-end fw-bold"><?= number_format($item['prix'], 0, ',', ' ') ?> FCFA</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-end fw-bold">TOTAL TTC</td>
                <td class="text-end fw-bold text-primary h4"><?= number_format($facture['montant_total'], 0, ',', ' ') ?> FCFA</td>
            </tr>
        </tfoot>
    </table>

    <div class="mt-5 pt-4 border-top text-center text-muted small">
        <p>Merci de votre confiance. Le linge non réclamé après 3 mois sera remis à des œuvres caritatives.<br>
        <strong>Pressing Manager Pro - La qualité au service de votre image.</strong></p>
    </div>
</div>

</body>
</html>