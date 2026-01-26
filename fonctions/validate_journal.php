
<?php
require_once 'database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    $journalCode = $_POST['journal_code'] ?? '';
    
    if (empty($journalCode)) {
        throw new Exception('Code journal manquant');
    }
    
    // 1. Vérifier l'existence du journal dans la table 'jal'
    // Note : Votre structure montre la colonne 'Cde'
    $sqlCheck = "SELECT Cde, Lib FROM jal WHERE Cde = :journal_code";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([':journal_code' => $journalCode]);
    $journal = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$journal) {
        throw new Exception("Le journal code $journalCode n'existe pas dans la configuration (table jal)");
    }
    
    // 2. Vérifier l'équilibre dans le brouillard (table 'brl')
    $sqlBalance = "SELECT 
                    COALESCE(SUM(Deb), 0) as total_debit,
                    COALESCE(SUM(Cre), 0) as total_credit,
                    COUNT(*) as count_entries
                   FROM brl 
                   WHERE Jal = :journal_code";
    
    $stmtBalance = $pdo->prepare($sqlBalance);
    $stmtBalance->execute([':journal_code' => $journalCode]);
    $balance = $stmtBalance->fetch(PDO::FETCH_ASSOC);
    
    // Calcul de la différence
    $diff = abs($balance['total_debit'] - $balance['total_credit']);
    
    if ($balance['count_entries'] == 0) {
        throw new Exception("Le brouillard pour le journal $journalCode est vide.");
    }

    if ($diff > 0.01) {
        throw new Exception("Déséquilibre détecté ! Différence : " . number_format($diff, 2) . " XAF");
    }
    
    // 3. Appel de la Procédure Stockée
    // On suppose que la procédure s'appelle 'SP_TransfereEcrituresVersDef' 
    // et qu'elle prend le code journal en paramètre.
    $pdo->beginTransaction();
    try {
        $stmtProc = $pdo->prepare("CALL SP_TransfereEcrituresVersDef(:journal_code)");
        $stmtProc->execute([':journal_code' => $journalCode]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Le journal " . $journal['Lib'] . " a été validé avec succès via procédure stockée."
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception("Erreur lors de l'exécution de la procédure : " . $e->getMessage());
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}