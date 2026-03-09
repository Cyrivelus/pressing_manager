<?php
// actions/supprimer_cycle.php
session_start();
require_once '../../fonctions/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' || !isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
    exit;
}

try {
    // Vérifier si le cycle existe et n'est pas en cours
    $stmt = $pdo->prepare("SELECT * FROM cycles_production WHERE id_cycle = ? AND statut_cycle != 'en_cours'");
    $stmt->execute([$_GET['id']]);
    $cycle = $stmt->fetch();
    
    if (!$cycle) {
        throw new Exception("Impossible de supprimer un cycle en cours");
    }
    
    // Supprimer le cycle
    $stmt = $pdo->prepare("DELETE FROM cycles_production WHERE id_cycle = ?");
    $stmt->execute([$_GET['id']]);
    
    // Enregistrer dans l'historique
    $stmt = $pdo->prepare("
        INSERT INTO historique_cycles (
            id_cycle, action, id_utilisateur, details, date_action
        ) VALUES (?, 'suppression', ?, ?, NOW())
    ");
    $stmt->execute([
        $_GET['id'],
        $_SESSION['utilisateur_id'],
        json_encode(['raison' => 'suppression_manuelle'])
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Cycle supprimé avec succès']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}