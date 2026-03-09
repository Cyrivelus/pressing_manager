<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once '../../fonctions/database.php';

$id_ticket = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // Requête corrigée selon votre structure de table réelle
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

    // Récupération des lignes (Services ou Produits)
    $stmt_lignes = $pdo->prepare("SELECT l.*, s.nom_service 
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
    <title>Ticket_<?= $ticket['numero_ticket'] ?></title>
    <style>
        /* Style Thermique Professionnel */
        @page { margin: 0; }
        body { 
            font-family: 'Courier New', monospace; 
            font-size: 11px; 
            width: 72mm; /* Standard 80mm moins marges */
            margin: 0 auto; 
            padding: 5mm 2mm;
            color: #000;
            background: #fff;
        }

        .no-print { 
            background: #333; padding: 10px; text-align: center; margin-bottom: 20px; 
            border-radius: 4px;
        }
        
        .header { text-align: center; margin-bottom: 10px; }
        .header h2 { margin: 0 0 5px 0; font-size: 15px; border-bottom: 1px double #000; display: inline-block; }
        .header p { margin: 2px 0; font-size: 10px; }

        .info-section { margin: 10px 0; font-size: 11px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 2px; }
        
        .type-badge { 
            border: 1px solid #000; padding: 5px; text-align: center; 
            font-weight: bold; margin: 10px 0; font-size: 13px;
            text-transform: uppercase;
        }

        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th { border-bottom: 1px dashed #000; text-align: left; padding: 5px 0; font-size: 10px; }
        td { padding: 5px 0; vertical-align: top; }

        .totals { border-top: 1px solid #000; padding-top: 5px; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 3px; }
        .grand-total { font-size: 14px; font-weight: bold; border-top: 1px double #000; padding-top: 5px; margin-top: 5px; }

        .footer { text-align: center; margin-top: 15px; font-size: 9px; }
        .barcode { font-family: 'Libre Barcode 39', cursive; font-size: 30px; margin: 10px 0; }
        
        @media print {
            .no-print { display: none !important; }
            body { width: 100%; padding: 0; margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print">
        <button onclick="window.print()" style="cursor:pointer; padding: 8px 15px; background: #28a745; color: white; border:none; border-radius:3px;">Imprimer</button>
        <button onclick="window.history.back()" style="cursor:pointer; padding: 8px 15px; background: #666; color: white; border:none; border-radius:3px;">Retour</button>
    </div>

    <div class="header">
        <h2><?= strtoupper(htmlspecialchars($ticket['nom_agence'])) ?></h2>
        <p><?= htmlspecialchars($ticket['agence_adresse']) ?></p>
        <p>Tél: <?= htmlspecialchars($ticket['agence_tel']) ?></p>
        <?php if($ticket['agence_email']): ?><p><?= $ticket['agence_email'] ?></p><?php endif; ?>
    </div>

    <div class="info-section">
        <div class="info-row"><span>Ticket No:</span> <strong>#<?= $ticket['numero_ticket'] ?></strong></div>
        <div class="info-row"><span>Date:</span> <span><?= date('d/m/Y H:i', strtotime($ticket['date_depot'])) ?></span></div>
        <div class="info-row"><span>Client:</span> <span><?= strtoupper($ticket['nom_client']) ?></span></div>
        <div class="info-row"><span>Caissier:</span> <span><?= $ticket['caissier'] ?></span></div>
    </div>

    <?php if (!empty($ticket['date_retrait_prevue'])): ?>
   <?php 
// On définit explicitement que seul le pressing (ou une session vide par défaut) affiche la date
$activite = $_SESSION['user_activity'] ?? 'pressing'; 

if ($activite === 'pressing'): 
?>
    <div class="type-badge" style="background: #f1f5f9; padding: 10px; border-radius: 4px; border-left: 4px solid #2563eb; margin-bottom: 15px;">
        <small style="color: #64748b; font-weight: bold; font-size: 0.7rem;">SORTIE PRÉVUE LE :</small><br>
        <strong style="color: #1e293b;">
            <?= isset($ticket['date_retrait_prevue']) ? date('d/m/Y', strtotime($ticket['date_retrait_prevue'])) : 'Non définie' ?>
        </strong>
    </div>
<?php endif; ?>
    <?php endif; ?>

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
                <td><?= htmlspecialchars($l['nom_service']) ?></td>
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
            <span><?= number_format($ticket['montant_total'], 0, ',', ' ') ?></span>
        </div>

        <div class="total-row" style="margin-top:5px;">
            <span>Acompte Versé:</span>
            <span><?= number_format($ticket['montant_verse'], 0, ',', ' ') ?></span>
        </div>

        <?php $reste = $ticket['montant_total'] - $ticket['montant_verse']; ?>
        <div class="total-row" style="font-weight:bold;">
            <span><?= $reste > 0 ? 'RESTE À PAYER:' : 'SOLDE:' ?></span>
            <span><?= $reste > 0 ? number_format($reste, 0, ',', ' ') : 'PAYÉ' ?></span>
        </div>
    </div>

    <div class="footer">
        <p>*** Merci de votre confiance ***</p>
        <p>Les articles non retirés après 3 mois<br>seront considérés comme abandonnés.</p>
        
        <div class="barcode">
            *<?= $ticket['numero_ticket'] ?>*
        </div>
        <p><?= date('d/m/Y H:i:s') ?></p>
    </div>

</body>
</html>