<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once '../../fonctions/database.php';

$id_ticket = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
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
        /* Configuration de l'imprimante thermique */
        @page {
            margin: 0;
        }
        body { 
            font-family: 'Courier New', Courier, monospace; 
            font-size: 12px; 
            line-height: 1.3; 
            margin: 0; 
            padding: 5mm; /* Marge interne pour le rouleau */
            width: 70mm;  /* Largeur réelle d'impression pour un rouleau 80mm */
            color: #000;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 5px 0; }
        
        .header h2 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 11px; }

        .info-section { margin: 10px 0; font-size: 11px; }

        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th { border-bottom: 1px solid #000; text-align: left; font-size: 11px; }
        td { padding: 3px 0; vertical-align: top; font-size: 11px; }

        .totals-section { margin-top: 10px; }
        .net-payer { font-size: 15px; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 3px 0; margin: 5px 0; }
        
        .footer { margin-top: 15px; font-size: 10px; line-height: 1.2; }
        .barcode { margin-top: 10px; font-size: 14px; letter-spacing: 3px; }

        /* Masquage des boutons lors de l'impression */
        @media print {
            .no-print { display: none; }
            body { padding: 2mm; width: 97%; }
        }
    </style>
</head>
<body onload="window.print();">

    <div class="no-print" style="background: #333; padding: 10px; text-align: center; color: white;">
        <button onclick="window.print();" style="padding: 8px 15px; cursor: pointer;">Imprimer</button>
        <button onclick="window.history.back();" style="padding: 8px 15px; cursor: pointer;">Retour</button>
    </div>

    <div class="header text-center">
        <h2><?= htmlspecialchars($ticket['nom_agence']) ?></h2>
        <p><?= htmlspecialchars($ticket['agence_adresse']) ?></p>
        
        <div> NIU : </div>
        <div> RC : </div>
        <div> Bienvenu à </div>
    </div>

    <div class="divider"></div>

    <div class="info-section">
        <div class="bold">TICKET : #<?= $ticket['numero_ticket'] ?></div>
        <div class="ticket-info">
    <?php 
    // On prépare les variables pour éviter les erreurs de variables indéfinies
    $nom = !empty($ticket['nom_client']) ? strtoupper(htmlspecialchars($ticket['nom_client'])) : '';
    $prenom = !empty($ticket['prenom_client']) ? htmlspecialchars($ticket['prenom_client']) : '';
    
    // Logique d'affichage : si c'est le client par défaut ou si le nom est vide
    if ($nom === 'PASSAGER' || empty($nom)) {
        echo "<strong>Client :</strong> PASSAGER";
    } else {
        echo "<strong>Client :</strong> " . $nom . " " . $prenom;
    }
    ?>
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

    <div class="totals-section">
        <div class="text-right">Total Brut : <?= number_format($ticket['montant_total'] + $ticket['montant_remise'], 0, ',', ' ') ?></div>
        
        <?php if($ticket['montant_remise'] > 0): ?>
            <div class="text-right">Remise : -<?= number_format($ticket['montant_remise'], 0, ',', ' ') ?></div>
        <?php endif; ?>

        <div class="text-right bold net-payer">NET A PAYER : <?= number_format($ticket['montant_total'], 0, ',', ' ') ?></div>
        
        <div class="text-right">Versé : <?= number_format($ticket['montant_verse'], 0, ',', ' ') ?></div>
        
        <?php $reste = $ticket['montant_total'] - $ticket['montant_verse']; ?>
        <div class="text-right bold">
            <?= ($reste <= 0) ? "SOLDE : PAYÉ" : "RESTE À PAYER : " . number_format($reste, 0, ',', ' ') ?>
        </div>
    </div>
     
 
        <p>Caissier: <?= htmlspecialchars($ticket['caissier']) ?></p>
        <div class="footer text-center">
            <div class="d-flex justify-content-between align-items-center">
   <div class="ticket-meta" style="display: flex; align-items: center; gap: 15px; margin-top: 5px;">
    <span><strong></strong> <?= date('d/m/Y H:i', strtotime($ticket['date_depot'])) ?></span>
    <div class="barcode" style="border: 1px solid #000; padding: 2px 5px; font-family: 'Libre Barcode 39', cursive; font-size: 25px;">
        <?= htmlspecialchars($ticket['numero_ticket']) ?>
    </div>
</div>
</div>
        <p class="bold">Merci de votre confiance !</p>
        <p>Les articles non retirés après 3 mois seront disposés. Conservez ce ticket pour toute réclamation.</p>
        <p>TEL: <?= htmlspecialchars($ticket['agence_tel']) ?></p>
       <div class="d-flex justify-content-between align-items-center">
    
</div>
    </div>

</body>
</html>
