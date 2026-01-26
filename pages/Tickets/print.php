<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once '../../fonctions/database.php';

$id_ticket = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // Requête conforme à votre structure de tables
    $query = "SELECT t.*, c.nom_client, c.prenom_client, c.telephone as client_tel,
                     a.nom_agence, a.adresse as agence_adresse, a.telephone as agence_tel,
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

    $stmt_lignes = $pdo->prepare("SELECT l.*, s.nom_service 
                                  FROM lignes_ticket l 
                                  JOIN services s ON l.id_service = s.id_service 
                                  WHERE l.id_ticket = ?");
    $stmt_lignes->execute([$id_ticket]);
    $lignes = $stmt_lignes->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ticket #<?= $ticket['numero_ticket'] ?></title>
    <style>
        /* Configuration de la page pour rouleau thermique standard */
        @page { margin: 0; }
        body { 
            font-family: 'Courier New', Courier, monospace; 
            font-size: 12px; 
            line-height: 1.2; 
            margin: 0; 
            padding: 2mm; 
            width: 72mm; /* Largeur de sécurité pour rouleau 80mm */
            color: #000;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 5px 0; }
        
        .header h2 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 1px 0; font-size: 10px; }

        .ticket-info { margin: 8px 0; font-size: 11px; }
        .retrait-box { 
            border: 1px solid #000; 
            padding: 4px; 
            margin: 5px 0; 
            text-align: center; 
            font-size: 14px;
            background-color: #f0f0f0; /* Visible même sur thermique */
        }

        table { width: 100%; border-collapse: collapse; margin: 5px 0; }
        th { border-bottom: 1px solid #000; text-align: left; font-size: 11px; padding-bottom: 2px; }
        td { padding: 3px 0; vertical-align: top; font-size: 11px; }

        .totals { margin-top: 5px; }
        .totals div { margin-bottom: 2px; }
        .net-a-payer { 
            font-size: 15px; 
            margin: 4px 0; 
            padding: 2px 0;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000; 
        }
        
        .footer { margin-top: 15px; font-size: 9px; line-height: 1.3; }
        .barcode { 
            margin-top: 10px; 
            font-size: 16px; 
            letter-spacing: 5px; 
            font-weight: bold;
        }

        @media print {
            .no-print { display: none; }
            body { width: 97%; } /* Laisse l'imprimante gérer la découpe */
        }
    </style>
</head>
<body onload="window.print();">

    <div class="no-print" style="background: #444; padding: 15px; text-align:center; color: white; font-family: sans-serif;">
        <button onclick="window.print();" style="padding: 10px 20px; font-weight:bold; cursor:pointer; background: #28a745; color:white; border:none; border-radius:5px;">🖨️ IMPRIMER LE TICKET</button>
        <button onclick="window.history.back();" style="padding: 10px 20px; cursor:pointer; background: #dc3545; color:white; border:none; border-radius:5px; margin-left:10px;">RETOUR</button>
    </div>

    <div class="header text-center">
        <h2><?= htmlspecialchars($ticket['nom_agence']) ?></h2>
        <p><?= htmlspecialchars($ticket['agence_adresse']) ?></p>
        <p>Tél: <?= htmlspecialchars($ticket['agence_tel']) ?></p>
    </div>

    <div class="divider"></div>

    <div class="ticket-info">
        <div class="bold">TICKET : #<?= $ticket['numero_ticket'] ?></div>
         
        <div>Client : <?= strtoupper(htmlspecialchars($ticket['nom_client'])) ?> <?= htmlspecialchars($ticket['prenom_client']) ?></div>
       
        <div> NIU : </div>
        <div> RC : </div>
        <div> Bienvenu à </div>
        <div class="retrait-box bold">
            PRÊT LE : <?= date('d/m/Y', strtotime($ticket['date_retrait_prevue'])) ?>
        </div>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th>Désignation</th>
                <th class="text-right">Qté</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lignes as $l): ?>
            <tr>
                <td><?= htmlspecialchars($l['nom_service']) ?></td>
                <td class="text-right"><?= (int)$l['quantite'] ?></td>
                <td class="text-right"><?= number_format($l['sous_total'], 0, ',', ' ') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="totals">
        <div class="text-right">Total Brut : <?= number_format($ticket['montant_total'] + $ticket['montant_remise'], 0, ',', ' ') ?></div>
        
        <?php if($ticket['montant_remise'] > 0): ?>
            <div class="text-right">Remise : -<?= number_format($ticket['montant_remise'], 0, ',', ' ') ?></div>
        <?php endif; ?>

        <div class="text-right bold net-a-payer">NET À PAYER : <?= number_format($ticket['montant_total'], 0, ',', ' ') ?></div>
        
        <div class="text-right">Versé : <?= number_format($ticket['montant_verse'], 0, ',', ' ') ?></div>
        
        <?php 
        $reste = $ticket['montant_total'] - $ticket['montant_verse'];
        if ($reste > 0): ?>
            <div class="text-right bold">RESTE À PAYER : <?= number_format($reste, 0, ',', ' ') ?></div>
        <?php else: ?>
            <div class="text-right bold">SOLDE : PAYÉ</div>
        <?php endif; ?>
    </div>

    <div class="footer text-center">
        <div class="divider"></div>
        <p>Encaissé par : <?= htmlspecialchars($ticket['caissier']) ?></p> 
        <p class="bold">CONDITIONS GÉNÉRALES</p>
        <p>1. Ce ticket est obligatoire pour le retrait.<br>
           2. Délai de garde maximum : 3 mois.<br>
           3. Responsabilité limitée selon les tarifs en vigueur.</p>
           
  <div class="ticket-meta" style="display: flex; align-items: center; gap: 15px; margin-top: 5px;">
    <span><strong></strong> <?= date('d/m/Y H:i', strtotime($ticket['date_depot'])) ?></span>
    <div class="barcode" style="border: 1px solid #000; padding: 2px 5px; font-family: 'Libre Barcode 39', cursive; font-size: 25px;">
        <?= htmlspecialchars($ticket['numero_ticket']) ?>
    </div>
</div>
        <p>Merci de votre confiance !</p>
        <div>TEL : <?= htmlspecialchars($ticket['client_tel'] ?? 'N/A') ?></div>
    </div>

</body>
</html>
