<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';


// Vérification de la méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupération et nettoyage des données
    $id_trigger = isset($_POST['trigger_id']) ? (int)$_POST['trigger_id'] : 0;
    $objet      = isset($_POST['objet']) ? trim($_POST['objet']) : '';
    $statut     = isset($_POST['statut']) ? $_POST['statut'] : 'Inactif';
    $contenu    = isset($_POST['contenu']) ? trim($_POST['contenu']) : '';

    if ($id_trigger > 0 && !empty($objet)) {
        try {
            /* 1. MISE À JOUR DU TEMPLATE 
               Note : On suppose ici l'existence d'une table 'email_templates'. 
               Si vous n'en avez pas encore, adaptez le nom de la table ci-dessous.
            */
            $sql = "UPDATE email_templates 
                    SET objet = :objet, 
                        corps_message = :contenu, 
                        statut = :statut,
                        date_modification = NOW()
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                ':objet'   => $objet,
                ':contenu' => $contenu,
                ':statut'  => $statut,
                ':id'      => $id_trigger
            ]);

            if ($success) {
                // Optionnel : Loguer l'action de modification dans notifications_clients (système)
                $log_sql = "INSERT INTO notifications_clients 
                            (type_notification, message, statut, canal, date_envoi) 
                            VALUES ('System_Log', :msg, 'envoye', 'Email', NOW())";
                $log_stmt = $pdo->prepare($log_sql);
                $log_stmt->execute([
                    ':msg' => "Mise à jour du template ID #$id_trigger par l'administrateur."
                ]);

                $_SESSION['message_success'] = "[SUCCÈS] Le scénario a été mis à jour avec succès.";
            } else {
                $_SESSION['message_error'] = "[ERREUR] Impossible de mettre à jour la base de données.";
            }

        } catch (PDOException $e) {
            $_SESSION['message_error'] = "[ERREUR SQL] " . $e->getMessage();
        }
    } else {
        $_SESSION['message_error'] = "[ERREUR] Données de formulaire incomplètes.";
    }
}

// Redirection vers la page de gestion
header("Location: index.php");
exit();