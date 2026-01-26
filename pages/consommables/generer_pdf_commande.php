<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['utilisateur_id'])) exit;

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

// Récupération des données postées
$items = $_POST['items'] ?? [];
if (empty($items)) {
    die("Aucun article sélectionné.");
}

$date_commande = date('d/m/Y');
$numero_bc = "BC-" . date('Ymd-His');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bon de Commande - <?= $numero_bc ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f0f0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .page {
            background: white;
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 10px auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        @media print {
            body { background: none; }
            .page { margin: 0; box-shadow: none; width: 100%; }
            .no-print { display: none; }
        }

        .logo-placeholder {
            width: 60px;
            height: 60px;
            background: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            border-radius: 8px;
        }
        .table thead { background-color: #f8f9fa !important; }
        .total-section { background: #f8f9fa; padding: 15px; border-radius: 8px; }
    </style>
</head>
<body>

<div class="container no-print py-3 text-center">
    <button onclick="window.print()" class="btn btn-primary btn-lg px-5 shadow">
        <i class="fas fa-print"></i> Imprimer ou Enregistrer en PDF
    </button>
</div>



<div class="page">
    <div class="row mb-5">
        <div class="col-6">
            <div class="d-flex align-items-center mb-3">
                <div class="logo-placeholder me-3">PM</div>
                <div>
                    <h4 class="fw-bold mb-0">PRESSING MANAGER</h4>
                    <small class="text-muted">Logiciel de Gestion Professionnel</small>
                </div>
            </div>
            <p class="small">
                <strong>Atelier Central</strong><br>
                Avenue de l'Indépendance, Douala<br>
                Contact : +237 600 000 000<br>
                Email : atelier@pressing-manager.com
            </p>
        </div>
        <div class="col-6 text-end">
            <h2 class="text-uppercase fw-light text-muted">Bon de Commande</h2>
            <p class="mb-0">Référence : <strong><?= $numero_bc ?></strong></p>
            <p>Date : <strong><?= $date_commande ?></strong></p>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-6">
            <h6 class="text-muted text-uppercase small fw-bold">Émetteur :</h6>
            <p>Responsable des Stocks<br>Dépôt Technique Atelier</p>
        </div>
        <div class="col-6 text-end">
            <h6 class="text-muted text-uppercase small fw-bold">Destinataire :</h6>
            <p><em>Veuillez vous référer au fournisseur habituel mentionné par article.</em></p>
        </div>
    </div>

    <table class="table table-bordered align-middle">
        <thead>
            <tr class="table-light">
                <th>Désignation de l'article</th>
                <th class="text-center" style="width: 15%;">Qté</th>
                <th class="text-center" style="width: 15%;">Unité</th>
                <th class="text-end" style="width: 20%;">Prix Unit. Est.</th>
                <th class="text-end" style="width: 20%;">Total HT</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $grand_total = 0;
            foreach ($items as $item): 
                if ($item['qty'] <= 0) continue;
                
                $stmt = $pdo->prepare("SELECT * FROM consommables WHERE id_consommable = ?");
                $stmt->execute([$item['id']]);
                $art = $stmt->fetch();
                
                $sous_total = $item['qty'] * $art['dernier_prix_achat'];
                $grand_total += $sous_total;
            ?>
            <tr>
                <td>
                    <div class="fw-bold"><?= htmlspecialchars($art['nom_article']) ?></div>
                    <small class="text-muted">Réf: #<?= $art['id_consommable'] ?> - <?= $art['conditionnement'] ?></small>
                </td>
                <td class="text-center"><?= $item['qty'] ?></td>
                <td class="text-center text-muted"><?= $art['unite_mesure'] ?></td>
                <td class="text-end"><?= number_format($art['dernier_prix_achat'], 0, ',', ' ') ?></td>
                <td class="text-end fw-bold"><?= number_format($sous_total, 0, ',', ' ') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="row justify-content-end mt-4">
        <div class="col-5">
            <div class="total-section">
                <div class="d-flex justify-content-between mb-2">
                    <span>Total de la commande :</span>
                    <span class="fw-bold fs-5 text-primary"><?= number_format($grand_total, 0, ',', ' ') ?> FCFA</span>
                </div>
                <div class="small text-muted text-end">Arrêté à la somme de : <br><strong><?= ucfirst($grand_total) ?> FCFA</strong></div>
            </div>
        </div>
    </div>

    <div class="row mt-5 pt-5">
        <div class="col-6 text-center">
            <p class="small text-muted mb-5">Signature Responsable Atelier</p>
            <div style="border-bottom: 1px solid #dee2e6; width: 60%; margin: 0 auto;"></div>
        </div>
        <div class="col-6 text-center">
            <p class="small text-muted mb-5">Cachet Direction / Validation</p>
            <div style="border-bottom: 1px solid #dee2e6; width: 60%; margin: 0 auto;"></div>
        </div>
    </div>

    <div class="mt-5 pt-5 text-center text-muted small">
        <p>Ce document est généré informatiquement par le système Pressing Manager.<br>
        Merci de confirmer la réception de cette commande.</p>
    </div>
</div>

<script src="https://kit.fontawesome.com/your-code.js"></script>
</body>
</html>