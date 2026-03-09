<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once '../../fonctions/database.php';

// Récupération sécurisée du PDO
$pdo = getConnection(); 

$id_utilisateur = $_SESSION['id_utilisateur'] ?? null;
$id_agence = $_SESSION['id_agence'] ?? 1;

if (!$id_utilisateur) {
    header('Location: index.php?cloture=error&message=' . urlencode("Session expirée.")); 
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
    header('Location: index.php'); 
    exit(); 
}

$aujourdhui = date('Y-m-d');
$notes = trim($_POST['notes'] ?? '');

try {
    $pdo->beginTransaction();

    // 1. Calcul des totaux depuis la table 'paiements'
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN LOWER(mode_paiement) LIKE 'espece%' THEN montant ELSE 0 END) as total_especes,
            SUM(CASE WHEN LOWER(mode_paiement) LIKE 'carte%' THEN montant ELSE 0 END) as total_carte,
            SUM(CASE WHEN LOWER(mode_paiement) LIKE 'cheque%' THEN montant ELSE 0 END) as total_cheque,
            SUM(CASE WHEN LOWER(mode_paiement) LIKE 'mobile%' OR LOWER(mode_paiement) LIKE 'momo%' THEN montant ELSE 0 END) as total_mobile,
            COUNT(DISTINCT id_ticket) as nb_tickets,
            SUM(montant) as total_general
        FROM paiements 
        WHERE DATE(date_paiement) = ? AND id_agence = ?
    ");
    $stmt->execute([$aujourdhui, $id_agence]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    $total_general = $data['total_general'] ?? 0;
    $nb_tickets = $data['nb_tickets'] ?? 0;

    // 2. Vérifier si une clôture existe déjà dans 'recettes'
    $stmtCheck = $pdo->prepare("SELECT id_recette FROM recettes WHERE date_recette = ? AND id_agence = ?");
    $stmtCheck->execute([$aujourdhui, $id_agence]);
    if ($stmtCheck->fetch()) {
        throw new Exception("La caisse a déjà été clôturée pour aujourd'hui.");
    }

    // 3. Insertion dans la table 'recettes' (Correction du nom de la table ici)
    $sqlInsert = "INSERT INTO recettes (
                    id_agence, date_recette, montant_total, 
                    montant_especes, montant_carte, montant_cheque, 
                    montant_mobile, nombre_tickets, id_utilisateur, notes, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([
        $id_agence, 
        $aujourdhui, 
        $total_general,
        $data['total_especes'] ?? 0, 
        $data['total_carte'] ?? 0,
        $data['total_cheque'] ?? 0, 
        $data['total_mobile'] ?? 0,
        $nb_tickets, 
        $id_utilisateur, 
        $notes
    ]);
    
    $id_recette = $pdo->lastInsertId();
    
    // 4. Mise à jour de la référence
    $ref = "CLOT-" . date('Ymd') . "-" . str_pad($id_recette, 4, "0", STR_PAD_LEFT);
    $pdo->prepare("UPDATE recettes SET reference = ? WHERE id_recette = ?")->execute([$ref, $id_recette]);

    // 5. Log d'activité
    $logMsg = "Clôture réalisée (#$ref). Total: $total_general XAF.";
    $stmtLog = $pdo->prepare("INSERT INTO logs_activite (id_utilisateur, action, table_concernée, id_enregistrement) VALUES (?, ?, ?, ?)");
    $stmtLog->execute([$id_utilisateur, $logMsg, 'recettes', $id_recette]);

    $pdo->commit();
    header("Location: index.php?cloture=success&ref=$ref&total=$total_general");

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header('Location: index.php?cloture=error&message=' . urlencode($e->getMessage()));
}