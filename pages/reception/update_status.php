<?php
session_start();
require_once '../../fonctions/database.php';

// On définit le header pour répondre en JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_ligne'], $_POST['nouveau_statut'])) {
    $id_ligne = (int)$_POST['id_ligne'];
    $nouveau_statut = $_POST['nouveau_statut'];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT id_ticket FROM lignes_ticket WHERE id_ligne = ?");
        $stmt->execute([$id_ligne]);
        $id_ticket = $stmt->fetchColumn();
        
        if ($id_ticket) {
            $updLigne = $pdo->prepare("UPDATE lignes_ticket SET statut_article = ? WHERE id_ligne = ?");
            $updLigne->execute([$nouveau_statut, $id_ligne]);

            if ($nouveau_statut === 'livre') {
                $check = $pdo->prepare("SELECT COUNT(*) FROM lignes_ticket WHERE id_ticket = ? AND statut_article != 'livre'");
                $check->execute([$id_ticket]);
                if ($check->fetchColumn() == 0) {
                    $pdo->prepare("UPDATE tickets SET statut = 'recupere' WHERE id_ticket = ?")->execute([$id_ticket]);
                }
            } 
            elseif ($nouveau_statut === 'conditionne') {
                $checkPret = $pdo->prepare("SELECT COUNT(*) FROM lignes_ticket WHERE id_ticket = ? AND statut_article NOT IN ('conditionne', 'livre')");
                $checkPret->execute([$id_ticket]);
                if ($checkPret->fetchColumn() == 0) {
                    $pdo->prepare("UPDATE tickets SET statut = 'pret' WHERE id_ticket = ?")->execute([$id_ticket]);
                }
            }
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Mise à jour réussie']);
            exit;
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}
echo json_encode(['status' => 'error', 'message' => 'Requête invalide']);