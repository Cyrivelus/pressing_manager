<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once '../../fonctions/database.php';

$id_ticket = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // Requête adaptée : on récupère les infos agence (Hôtel) et client
    $query = "SELECT t.*, c.nom_client, c.prenom_client, c.telephone as client_tel,
                     a.nom_agence as nom_hotel, a.adresse as hotel_adresse, a.telephone as hotel_tel, a.email as hotel_email,
                     u.nom_complet as receptionniste
              FROM tickets t
              LEFT JOIN clients c ON t.id_client = c.id_client
              LEFT JOIN agences a ON t.id_agence = a.id_agence
              LEFT JOIN utilisateurs u ON t.id_utilisateur = u.id_utilisateur
              WHERE t.id_ticket = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id_ticket]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) die("Note introuvable.");

    // Récupération des prestations (Chambres, Restauration, Blanchisserie, etc.)
    $stmt_lignes = $pdo->prepare("SELECT l.*, s.nom_service as prestation 
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
    <title>Note_Hotel_<?= $ticket['numero_ticket'] ?></title>
    <style>
        @page { margin: 0; }
        body { 
            font-family: 'Arial', sans-serif; /* Plus élégant pour l'hôtellerie */
            font-size: 11px; 
            width: 72mm; 
            margin: 0 auto; 
            padding: 5mm 3mm;
            color: #333;
            background: #fff;
        }

        .no-print { 
            background: #1e293b; padding: 10px; text-align: center; margin-bottom: 20px; 
        }
        
        .header { text-align: center; margin-bottom: 15px; }
        .header h2 { margin: 0; font-size: 17px; color: #000; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 10px; color: #666; }

        .divider { border-top: 1px solid #eee; margin: 10px 0; }
        .double-divider { border-top: 3px double #333; margin: 10px 0; }

        .stay-info { 
            background: #f8fafc; 
            padding: 8px; 
            border: 1px solid #e2e8f0;
            margin-bottom: 10px;
        }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 3px; }
        .bold { font-weight: bold; color: #000; }

        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th { text-align: left; border-bottom: 2px solid #333; padding: 5px 0; font-size: 10px; text-transform: uppercase; }
        td { padding: 7px 0; border-bottom: 1px solid #eee; }

        .totals { margin-top: 10px; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 11px; }
        .grand-total { 
            font-size: 15px; 
            font-weight: bold; 
            background: #000; 
            color: #fff; 
            padding: 6px; 
            margin-top: 7px;
            display: flex;
            justify-content: space-between;
        }

        .footer { text-align: center; margin-top: 25px; font-size: 9px; font-style: italic; }
        
        @media print {
            .no-print { display: none !important; }
            body { width: 100%; padding: 0; margin: 0; }
            .grand-total { border: 1px solid #000; color: #000; background: none; } /* Économie d'encre */
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print">
        <button onclick="window.print()" style="cursor:pointer; padding: 8px 15px; background: #fbbf24; color: #000; border:none; border-radius:3px; font-weight:bold;">🖨️ IMPRIMER LA NOTE</button>
        <button onclick="window.history.back()" style="cursor:pointer; padding: 8px 15px; background: #fff; color: #000; border:none; border-radius:3px; margin-left:10px;">RETOUR</button>
    </div>

    <div class="header">
        <h2><?= strtoupper(htmlspecialchars($ticket['nom_hotel'])) ?></h2>
        <p><?= htmlspecialchars($ticket['hotel_adresse']) ?></p>
        <p>Tél: <?= htmlspecialchars($ticket['hotel_tel']) ?></p>
        <p><?= htmlspecialchars($ticket['hotel_email']) ?></p>
    </div>

    <div class="double-divider"></div>

    <div class="stay-info">
        <div class="info-row"><span class="bold">NOTE N° :</span> <span class="bold">#<?= $ticket['numero_ticket'] ?></span></div>
        <div class="info-row"><span></span> <span><?= strtoupper($ticket['nom_client']) ?></span></div>
        <div class="info-row"><span>Date :</span> <span><?= date('d/m/Y H:i', strtotime($ticket['date_depot'])) ?></span></div>
        
        <?php if (!empty($ticket['date_retrait_prevue'])): ?>
        <div class="divider"></div>
        <div class="info-row"><span>Arrivée :</span> <span><?= date('d/m/Y', strtotime($ticket['date_depot'])) ?></span></div>
        <div class="info-row"><span class="bold">Départ prévu :</span> <span class="bold"><?= date('d/m/Y', strtotime($ticket['date_retrait_prevue'])) ?></span></div>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>Prestation</th>
                <th style="text-align:center">Qté</th>
                <th style="text-align:right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lignes as $l): ?>
            <tr>
                <td><?= htmlspecialchars($l['prestation']) ?></td>
                <td style="text-align:center"><?= (int)$l['quantite'] ?></td>
                <td style="text-align:right"><?= number_format($l['sous_total'], 0, ',', ' ') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="total-row"><span>Sous-total :</span> <span><?= number_format($ticket['montant_total'] + $ticket['montant_remise'], 0, ',', ' ') ?></span></div>
        
        <?php if($ticket['montant_remise'] > 0): ?>
            <div class="total-row"><span>Remise :</span> <span>-<?= number_format($ticket['montant_remise'], 0, ',', ' ') ?></span></div>
        <?php endif; ?>

        <div class="grand-total">
            <span>TOTAL À PAYER</span>
            <span><?= number_format($ticket['montant_total'], 0, ',', ' ') ?> FCFA</span>
        </div>

        <div class="total-row" style="margin-top:10px;">
            <span>Acomptes versés :</span>
            <span><?= number_format($ticket['montant_verse'], 0, ',', ' ') ?></span>
        </div>

        <?php $reste = $ticket['montant_total'] - $ticket['montant_verse']; ?>
        <div class="total-row <?= ($reste <= 0) ? 'bold' : '' ?>" style="color: <?= ($reste > 0) ? '#b91c1c' : '#15803d' ?>;">
            <span><?= ($reste > 0) ? 'RESTE À RÉGLER :' : 'ÉTAT : SOLDE PAYÉ' ?></span>
            <span><?= ($reste > 0) ? number_format($reste, 0, ',', ' ') : '' ?></span>
        </div>
    </div>

    <div class="footer">
        <p>Réceptionniste : <?= htmlspecialchars($ticket['receptionniste']) ?></p>
        <div class="divider"></div>
        <p>Nous espérons que vous avez passé<br>un agréable séjour parmi nous.</p>
        <p class="bold">A très bientôt !</p>
        <p style="margin-top:10px; font-size:7px;">Généré par Manager v2.0</p>
    </div>

</body>
</html>