<?php
// pages/caisse/cloturer.php
if (session_status() == PHP_SESSION_NONE) session_start();

require_once '../../fonctions/database.php';

// Vérification de l'ID utilisateur (caissier) et de l'agence
$id_utilisateur = $_SESSION['id_utilisateur'] ?? null;
$id_agence = $_SESSION['id_agence'] ?? 1;
$username = $_SESSION['username'] ?? 'Caissier';

if (!$id_utilisateur) {
    // Stocker le message d'erreur en session
    $_SESSION['cloture_error'] = 'Identifiant utilisateur manquant. Veuillez vous reconnecter.';
    header('Location: index.php#cloturer');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// Récupération des données du formulaire
$aujourdhui = date('Y-m-d');
$notes = trim($_POST['notes'] ?? '');

try {
    // Vérifier la connexion PDO
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    $pdo->beginTransaction();

    // 1. Calcul des totaux par mode de paiement pour aujourd'hui
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN mode_paiement = 'especes' THEN montant ELSE 0 END), 0) as total_especes,
            COALESCE(SUM(CASE WHEN mode_paiement = 'carte' THEN montant ELSE 0 END), 0) as total_carte,
            COALESCE(SUM(CASE WHEN mode_paiement = 'cheque' THEN montant ELSE 0 END), 0) as total_cheque,
            COALESCE(SUM(CASE WHEN mode_paiement = 'mobile' THEN montant ELSE 0 END), 0) as total_mobile,
            COUNT(DISTINCT id_ticket) as nb_tickets,
            COALESCE(SUM(montant), 0) as total_general
        FROM paiements 
        WHERE DATE(date_paiement) = ? AND id_utilisateur = ?
    ");
    $stmt->execute([$aujourdhui, $id_utilisateur]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        throw new Exception("Erreur lors du calcul des totaux.");
    }

    // 2. Vérifier si une clôture existe déjà pour aujourd'hui
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) as count FROM recettes 
                               WHERE date_recette = ? AND id_utilisateur = ?");
    $stmtCheck->execute([$aujourdhui, $id_utilisateur]);
    $cloture_existante = $stmtCheck->fetch()['count'];
    
    if ($cloture_existante > 0) {
        throw new Exception("Une clôture a déjà été effectuée pour cette journée.");
    }

    // 3. Insertion dans la table 'recettes' si total > 0
    if ($data['total_general'] > 0) {
        $sqlInsert = "INSERT INTO recettes (
                        id_agence, date_recette, montant_total, 
                        montant_especes, montant_carte, montant_cheque, 
                        montant_mobile, nombre_tickets, id_utilisateur, notes,
                        created_at
                    ) VALUES (
                        :id_agence, :date_r, :total, 
                        :especes, :carte, :cheque, 
                        :mobile, :nb, :id_u, :notes,
                        NOW()
                    )";
        
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            ':id_agence'    => $id_agence,
            ':date_r'       => $aujourdhui,
            ':total'        => $data['total_general'],
            ':especes'      => $data['total_especes'],
            ':carte'        => $data['total_carte'],
            ':cheque'       => $data['total_cheque'],
            ':mobile'       => $data['total_mobile'],
            ':nb'           => $data['nb_tickets'],
            ':id_u'         => $id_utilisateur,
            ':notes'        => $notes
        ]);
        
        $id_recette = $pdo->lastInsertId();
        
        // 4. Logger l'action dans logs_activite
        try {
            $action = sprintf(
                "Clôture de caisse #%d - Total: %s XAF - Espèces: %s - Mobile: %s - Carte: %s - Chèque: %s",
                $id_recette,
                number_format($data['total_general'], 0),
                number_format($data['total_especes'], 0),
                number_format($data['total_mobile'], 0),
                number_format($data['total_carte'], 0),
                number_format($data['total_cheque'], 0)
            );
            
            $stmtLog = $pdo->prepare("
                INSERT INTO logs_activite 
                (id_utilisateur, action, table_concernée, id_enregistrement, anciennes_valeurs, nouvelles_valeurs)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtLog->execute([
                $id_utilisateur,
                $action,
                'recettes',
                $id_recette,
                json_encode(['date' => $aujourdhui, 'utilisateur' => $username]),
                json_encode($data)
            ]);
        } catch (Exception $e) {
            // Ne pas bloquer la clôture si le log échoue
            error_log("Erreur de log lors de la clôture: " . $e->getMessage());
        }
        
        // 5. Générer un numéro de reçu (optionnel)
        $numero_recu = sprintf("RC-%s-%06d", date('Ymd'), $id_recette);
        $stmtUpdate = $pdo->prepare("UPDATE recettes SET reference = ? WHERE id_recette = ?");
        $stmtUpdate->execute([$numero_recu, $id_recette]);
    } else {
        // Cas où il n'y a pas eu de transactions mais on veut quand même enregistrer une clôture à zéro
        $sqlInsert = "INSERT INTO recettes (
                        id_agence, date_recette, montant_total, 
                        montant_especes, montant_carte, montant_cheque, 
                        montant_mobile, nombre_tickets, id_utilisateur, notes,
                        created_at, reference
                    ) VALUES (
                        :id_agence, :date_r, 0, 
                        0, 0, 0, 
                        0, 0, :id_u, :notes,
                        NOW(), :ref
                    )";
        
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            ':id_agence'    => $id_agence,
            ':date_r'       => $aujourdhui,
            ':id_u'         => $id_utilisateur,
            ':notes'        => $notes,
            ':ref'          => 'RC-' . date('Ymd') . '-000000'
        ]);
        
        $id_recette = $pdo->lastInsertId();
        
        // Logger l'action pour une clôture à zéro
        try {
            $stmtLog = $pdo->prepare("
                INSERT INTO logs_activite 
                (id_utilisateur, action, table_concernée, id_enregistrement)
                VALUES (?, ?, ?, ?)
            ");
            $stmtLog->execute([
                $id_utilisateur,
                "Clôture de caisse #{$id_recette} - Aucune transaction aujourd'hui",
                'recettes',
                $id_recette
            ]);
        } catch (Exception $e) {
            error_log("Erreur de log: " . $e->getMessage());
        }
    }

    $pdo->commit();
    
    // Redirection avec succès
    $redirect_url = "index.php?cloture=success&total=" . $data['total_general'];
    if ($data['total_general'] == 0) {
        $redirect_url .= "&message=" . urlencode("Clôture enregistrée (0 transaction)");
    }
    
    header('Location: ' . $redirect_url);
    exit();

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Redirection avec erreur
    $error_message = urlencode($e->getMessage());
    header('Location: index.php?cloture=error&message=' . $error_message);
    exit();
}