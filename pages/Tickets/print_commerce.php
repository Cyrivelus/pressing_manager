<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once '../../fonctions/database.php';

$id_ticket = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // Requête corrigée selon la structure réelle de votre table 'agences'
    $query = "SELECT t.*, c.nom_client, c.prenom_client, c.telephone as client_tel,
                     a.nom_agence, a.adresse as agence_adresse, a.telephone as agence_tel, a.email as agence_email,
                     u.nom_complet as caissier
              FROM tickets t
              LEFT JOIN clients c ON t.id_client = c.id_client
              LEFT JOIN agences a ON t.id_agence = a.id_agence
              LEFT JOIN utilisateurs u ON t.id_utilisateur = u.id_utilisateur
              WHERE t.id_ticket = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id_ticket]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) die("Ticket introuvable.");

    // Récupération des articles vendus
    $stmt_lignes = $pdo->prepare("SELECT l.*, s.nom_service as article 
                                  FROM lignes_ticket l 
                                  JOIN services s ON l.id_service = s.id_service 
                                  WHERE l.id_ticket = ?");
    $stmt_lignes->execute([$id_ticket]);
    $lignes = $stmt_lignes->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Erreur technique : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ticket_Vente_<?= $ticket['numero_ticket'] ?></title>
    <style>
        /* Style spécial imprimante thermique 80mm/58mm */
        @page { margin: 0; }
        body { 
            font-family: 'Courier New', Courier, monospace; 
            font-size: 11px; 
            width: 72mm; 
            margin: 0 auto; 
            padding: 4mm 2mm;
            color: #000;
            background: #fff;
        }

        .no-print { 
            background: #444; padding: 10px; text-align: center; margin-bottom: 20px; 
            border-radius: 4px;
        }
        
        .header { text-align: center; margin-bottom: 8px; }
        .header h2 { margin: 0 0 4px 0; font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 10px; line-height: 1.2; }

        .divider { border-top: 1px dashed #000; margin: 8px 0; }

        .info-section { font-size: 11px; margin-bottom: 8px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 2px; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; border-bottom: 1px solid #000; padding: 4px 0; font-size: 10px; }
        td { padding: 4px 0; vertical-align: top; }

        .totals { margin-top: 8px; border-top: 1px solid #000; padding-top: 5px; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 3px; }
        .grand-total { 
            font-size: 14px; 
            font-weight: bold; 
            border-top: 1px double #000; 
            border-bottom: 1px double #000;
            padding: 5px 0; 
            margin-top: 5px; 
        }

        .footer { text-align: center; margin-top: 20px; font-size: 10px; }
        .barcode { font-size: 12px; margin-top: 10px; font-weight: bold; letter-spacing: 2px; }

        @media print {
            .no-print { display: none !important; }
            body { width: 100%; padding: 2mm; margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print">
        <button onclick="window.print()" style="cursor:pointer; padding: 10px 20px; background: #28a745; color: white; border:none; border-radius:3px; font-weight:bold;">🖨️ IMPRIMER</button>
        <button onclick="window.history.back()" style="cursor:pointer; padding: 10px 20px; background: #666; color: white; border:none; border-radius:3px; margin-left:10px;">RETOUR</button>
    </div>

    <div class="header">
        <h2><?= htmlspecialchars($ticket['nom_agence']) ?></h2>
        <p><?= htmlspecialchars($ticket['agence_adresse']) ?></p>
        <p>Tél: <?= htmlspecialchars($ticket['agence_tel']) ?></p>
        <?php if(!empty($ticket['agence_email'])): ?><p><?= $ticket['agence_email'] ?></p><?php endif; ?>
    </div>

    <div class="divider"></div>

    <div class="info-section">
        <div class="info-row"><span>Ticket No:</span> <strong>#<?= $ticket['numero_ticket'] ?></strong></div>
        <div class="info-row"><span>Date:</span> <span><?= date('d/m/Y H:i', strtotime($ticket['date_depot'])) ?></span></div>
        <div class="info-row">
            <span>Client:</span> 
            <span><?= (!empty($ticket['nom_client']) && $ticket['nom_client'] !== 'PASSAGER') ? strtoupper($ticket['nom_client']) : 'PASSAGER' ?></span>
        </div>
        <div class="info-row"><span>Vendeur:</span> <span><?= $ticket['caissier'] ?></span></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Désignation</th>
                <th style="text-align:center">Qté</th>
                <th style="text-align:right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lignes as $l): ?>
            <tr>
                <td><?= htmlspecialchars($l['article']) ?></td>
                <td style="text-align:center"><?= (int)$l['quantite'] ?></td>
                <td style="text-align:right"><?= number_format($l['sous_total'], 0, ',', ' ') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <?php if($ticket['montant_remise'] > 0): ?>
            <div class="total-row"><span>Total Brut:</span> <span><?= number_format($ticket['montant_total'] + $ticket['montant_remise'], 0, ',', ' ') ?></span></div>
            <div class="total-row"><span>Remise:</span> <span>-<?= number_format($ticket['montant_remise'], 0, ',', ' ') ?></span></div>
        <?php endif; ?>

        <div class="total-row grand-total">
            <span>NET À PAYER:</span>
            <span><?= number_format($ticket['montant_total'], 0, ',', ' ') ?> FCFA</span>
        </div>

        <div class="total-row" style="margin-top:5px;">
            <span>Montant Reçu:</span>
            <span><?= number_format($ticket['montant_verse'], 0, ',', ' ') ?></span>
        </div>

        <?php $reste = $ticket['montant_total'] - $ticket['montant_verse']; ?>
        <div class="total-row">
            <span><?= $reste > 0 ? 'RESTE À PAYER:' : 'RENDU MONNAIE:' ?></span>
            <span><?= number_format(abs($reste), 0, ',', ' ') ?></span>
        </div>
    </div>

    <div class="footer">
        <div class="divider"></div>
        <p>Merci de votre visite !</p>
        <p>Les articles achetés ne sont ni repris,<br>ni échangés après 24 heures.</p>
        
        <div class="barcode">
            * <?= $ticket['numero_ticket'] ?> *
        </div>
        <p style="font-size: 8px; margin-top: 10px;">Imprimé le <?= date('d/m/Y à H:i:s') ?></p>
    </div>

</body>
</html>