<?php
// fonctions/gestion_releves.php

// Ce fichier gère les fonctions liées à la consultation et à la génération des relevés.

// Assurez-vous que le fichier database.php est bien inclus dans le script appelant,
// ou décommenter l'inclusion si ce fichier est le point d'entrée
// require_once("database.php"); 

/**
 * Récupère l'historique des transactions pour un compte donné, sur une période spécifique.
 * * @param PDO    $pdo              L'objet de connexion à la base de données.
 * @param int    $id_compte        L'ID du compte client (table 'comptes').
 * @param string $date_debut       Date de début de la période (format 'YYYY-MM-DD').
 * @param string $date_fin         Date de fin de la période (format 'YYYY-MM-DD').
 * @return array Un tableau d'enregistrements de transactions.
 */
function getTransactionsPourReleve(PDO $pdo, int $id_compte, string $date_debut, string $date_fin): array
{
    try {
        // Préparer la requête pour sélectionner les transactions dans la table transactions_comptes.
        // La date de fin doit inclure toutes les transactions de ce jour-là,
        // donc nous utilisons une condition jusqu'à minuit le jour suivant ($date_fin + 1 jour).
        // Cependant, le plus simple est d'utiliser le signe <= sur la colonne date_transaction
        // si elle inclut l'heure, ou d'ajouter ' 23:59:59' à la date de fin.
        
        $sql = "SELECT 
                    id_transaction, 
                    type_transaction, 
                    montant, 
                    date_transaction, 
                    solde_apres,
                    commentaire
                FROM 
                    transactions_comptes
                WHERE 
                    id_compte = :id_compte AND 
                    DATE(date_transaction) BETWEEN :date_debut AND :date_fin
                ORDER BY 
                    date_transaction ASC";

        $stmt = $pdo->prepare($sql);
        
        // Exécuter la requête
        $stmt->execute([
            ':id_compte' => $id_compte,
            ':date_debut' => $date_debut,
            ':date_fin' => $date_fin // DATE() permet de ne comparer que la partie date
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Erreur PDO lors de la récupération des transactions pour relevé : " . $e->getMessage());
        // Lever une exception pour que le script appelant puisse afficher l'erreur
        throw new Exception("Erreur de base de données lors de la récupération du relevé.");
    }
}

/**
 * Récupère le solde du compte à la fin du jour précédant la date de début.
 * Ce solde est utilisé comme "Solde initial" pour le relevé.
 * * @param PDO    $pdo              L'objet de connexion à la base de données.
 * @param int    $id_compte        L'ID du compte client (table 'comptes').
 * @param string $date_debut       Date de début de la période (format 'YYYY-MM-DD').
 * @return float Le solde initial.
 */
function getSoldeInitial(PDO $pdo, int $id_compte, string $date_debut): float
{
    try {
        // Calcule la date de la veille
        $date_veille = date('Y-m-d', strtotime($date_debut . ' -1 day'));

        // Trouver la dernière transaction avant ou égale à la date de la veille
        $sql = "SELECT 
                    solde_apres 
                FROM 
                    transactions_comptes 
                WHERE 
                    id_compte = :id_compte AND 
                    DATE(date_transaction) <= :date_veille
                ORDER BY 
                    date_transaction DESC, id_transaction DESC
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_compte' => $id_compte,
            ':date_veille' => $date_veille
        ]);
        
        $solde = $stmt->fetchColumn();

        // Si aucune transaction n'est trouvée avant la date de début, 
        // on prend le solde d'ouverture du compte (0.00 par défaut ou valeur initiale réelle si disponible)
        return $solde !== false ? (float)$solde : 0.00;

    } catch (PDOException $e) {
        error_log("Erreur PDO lors de la récupération du solde initial : " . $e->getMessage());
        throw new Exception("Erreur de base de données lors de la détermination du solde initial.");
    }
}


// --- Fonctions de génération de PDF (Placeholder pour le fichier releve_pdf.php) ---
// La fonction réelle de génération de PDF (ex: avec FPDF ou TCPDF) doit être placée 
// dans le fichier releve_pdf.php ou dans une classe dédiée. 
// Le fichier index.php n'a besoin que de l'historique des transactions.


// /**
//  * Génère le fichier PDF du relevé de compte.
//  * (Ceci est une fonction placeholder pour la logique de releve_pdf.php)
//  * //  * @param PDO    $pdo             
//  * @param int    $id_compte       
//  * @param string $date_debut      
//  * @param string $date_fin        
//  * @return void
//  */
// function genererRelevePDF(PDO $pdo, int $id_compte, string $date_debut, string $date_fin): void
// {
//     $transactions = getTransactionsPourReleve($pdo, $id_compte, $date_debut, $date_fin);
//     $solde_initial = getSoldeInitial($pdo, $id_compte, $date_debut);

//     if (empty($transactions)) {
//         throw new Exception("Aucune transaction trouvée pour la période sélectionnée.");
//     }
    
//     // Logique de génération de PDF ici (instanciation de la librairie, ajout des pages, affichage...)
//     // Exemple : $pdf = new FPDF(); ... $pdf->Output('I', 'Releve_' . $id_compte . '.pdf');

// }

// Fin du fichier gestion_releves.php
?>