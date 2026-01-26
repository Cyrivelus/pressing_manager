<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once '../../fonctions/database.php';

header('Content-Type: application/json');

// Récupération de l'ID, qu'il vienne de POST ou de GET pour éviter l'erreur de méthode
$id_ticket = isset($_REQUEST['id_ticket']) ? (int)$_REQUEST['id_ticket'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$id_user = $_SESSION['id_utilisateur'] ?? 1;

try {
    if ($id_ticket <= 0) {
        throw new Exception("Identifiant de ticket invalide.");
    }

    $pdo->beginTransaction();

    // 1. Vérifier si le ticket est totalement payé avant livraison
    $stmt = $pdo->prepare("SELECT numero_ticket, montant_total, montant_verse, statut FROM tickets WHERE id_ticket = ? FOR UPDATE");
    $stmt->execute([$id_ticket]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        throw new Exception("Le ticket n'existe pas.");
    }

    // Vérification du solde (Optionnel : vous pouvez autoriser la livraison même avec dette selon votre politique)
    $solde = $ticket['montant_total'] - $ticket['montant_verse'];
    if ($solde > 0) {
        throw new Exception("Impossible de livrer : il reste " . number_format($solde, 0) . " XAF à payer.");
    }

    // 2. Mise à jour du ticket : Statut 'recupere' et Date de retrait réelle
    $stmt_upd = $pdo->prepare("
        UPDATE tickets 
        SET statut = 'recupere', 
            date_retrait_reelle = NOW(),
            updated_at = NOW()
        WHERE id_ticket = ?
    ");
    $stmt_upd->execute([$id_ticket]);

    // 3. Mise à jour de tous les articles du ticket en statut 'livre'
    $stmt_art = $pdo->prepare("UPDATE lignes_ticket SET statut_article = 'livre' WHERE id_ticket = ?");
    $stmt_art->execute([$id_ticket]);

    // 4. Log de l'activité
    $stmt_log = $pdo->prepare("
        INSERT INTO logs_activite (id_utilisateur, action, table_concernée, id_enregistrement) 
        VALUES (?, ?, 'tickets', ?)
    ");
    $stmt_log->execute([$id_user, "Livraison effectuée pour le ticket #" . $ticket['numero_ticket'], $id_ticket]);

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => "Le ticket #" . $ticket['numero_ticket'] . " a été marqué comme livré.",
        'numero_ticket' => $ticket['numero_ticket']
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}