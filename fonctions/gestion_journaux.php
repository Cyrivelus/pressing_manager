<?php
// gestion_journaux.php
require_once 'database.php';

/**
 * Récupère le nombre total de journaux depuis la base de données.
 */
function getNombreTotalJournaux(PDO $pdo, string $recherche = ''): int
{
    try {
        $sql = "SELECT COUNT(*) FROM jal WHERE 1=1";
        
        if (!empty($recherche)) {
            // Recherche par Lib (texte) ou Cde (numérique)
            $sql .= " AND (Lib LIKE :recherche";
            
            // Si la recherche est numérique, on cherche aussi par Cde
            if (is_numeric($recherche)) {
                $sql .= " OR Cde = :cde_recherche";
            }
            
            $sql .= ")";
        }

        $stmt = $pdo->prepare($sql);
        
        if (!empty($recherche)) {
            $stmt->bindValue(':recherche', '%' . $recherche . '%', PDO::PARAM_STR);
            
            // Si la recherche est numérique, on bind aussi le paramètre numérique
            if (is_numeric($recherche)) {
                $stmt->bindValue(':cde_recherche', (int)$recherche, PDO::PARAM_INT);
            }
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur dans getNombreTotalJournaux: " . $e->getMessage());
        return 0;
    }
}

/**
 * Récupère la liste des journaux avec pagination.
 */
function getJournaux(PDO $pdo, string $recherche = '', int $limit = 25, int $offset = 0): array
{
    try {
        $sql = "SELECT * FROM jal WHERE 1=1";
        
        if (!empty($recherche)) {
            // Recherche par Lib (texte) ou Cde (numérique)
            $sql .= " AND (Lib LIKE :recherche";
            
            // Si la recherche est numérique, on cherche aussi par Cde
            if (is_numeric($recherche)) {
                $sql .= " OR Cde = :cde_recherche";
            }
            
            $sql .= ")";
        }

        // Syntaxe MySQL pour la pagination
        $sql .= " ORDER BY Cde ASC LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        
        // Bind des paramètres de recherche
        if (!empty($recherche)) {
            $stmt->bindValue(':recherche', '%' . $recherche . '%', PDO::PARAM_STR);
            
            if (is_numeric($recherche)) {
                $stmt->bindValue(':cde_recherche', (int)$recherche, PDO::PARAM_INT);
            }
        }
        
        // Bind des paramètres de pagination
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur dans getJournaux: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère la liste de tous les journaux.
 */
function getListeJournaux(PDO $pdo): array
{
    try {
        $sql = "SELECT Cde, Lib, Typ, Cpt, NumeroAgenceSCE FROM jal ORDER BY Cde ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur dans getListeJournaux: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère la liste des fournisseurs.
 */
function getListeFournisseursPLN(PDO $pdo): array 
{
    try {
        // Vérifier si la constante existe
        if (!defined('PREFIXES_COMPTE_FOURNISSEUR')) {
            throw new Exception("La constante PREFIXES_COMPTE_FOURNISSEUR n'est pas définie.");
        }
        
        $prefixes = PREFIXES_COMPTE_FOURNISSEUR;
        if (empty($prefixes)) {
            return [];
        }
        
        // Créer les placeholders
        $placeholders = str_repeat('?,', count($prefixes) - 1) . '?';
        $query = "SELECT Cpt, Lib FROM pln WHERE LEFT(Cpt, 3) IN ($placeholders) ORDER BY Cpt";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($prefixes);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur dans getListeFournisseursPLN: " . $e->getMessage());
        return [];
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * Récupère les détails d'un journal spécifique par son code.
 */
function getJournal(PDO $pdo, int $codeJournal): ?array
{
    try {
        $sql = "SELECT Cde, Lib, Typ, Cpt, NumeroAgenceSCE FROM jal WHERE Cde = :codeJournal LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':codeJournal', $codeJournal, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    } catch (PDOException $e) {
        error_log("Erreur dans getJournal: " . $e->getMessage());
        return null;
    }
}

/**
 * Alias de getJournal pour compatibilité.
 */
function getJournalByCde(PDO $pdo, int $cde): ?array
{
    return getJournal($pdo, $cde);
}

/**
 * Insère un nouveau journal dans la base de données.
 */
function ajouterJournal(PDO $pdo, array $data): bool
{
    try {
        // Validation des données obligatoires
        if (empty($data['Cde']) || empty($data['Lib'])) {
            error_log("Données manquantes pour l'ajout d'un journal");
            return false;
        }
        
        $sql = "INSERT INTO jal (Cde, Lib, Typ, Cpt, NumeroAgenceSCE) 
                VALUES (:Cde, :Lib, :Typ, :Cpt, :NumeroAgenceSCE)";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind des paramètres
        $stmt->bindValue(':Cde', (int)$data['Cde'], PDO::PARAM_INT);
        $stmt->bindValue(':Lib', trim($data['Lib']), PDO::PARAM_STR);
        $stmt->bindValue(':Typ', !empty($data['Typ']) ? trim($data['Typ']) : null, PDO::PARAM_STR);
        $stmt->bindValue(':Cpt', !empty($data['Cpt']) ? trim($data['Cpt']) : null, PDO::PARAM_STR);
        $stmt->bindValue(':NumeroAgenceSCE', !empty($data['NumeroAgenceSCE']) ? trim($data['NumeroAgenceSCE']) : null, PDO::PARAM_STR);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erreur dans ajouterJournal: " . $e->getMessage());
        return false;
    }
}

/**
 * Modifie un journal existant.
 */
function modifierJournal(PDO $pdo, int $cde, array $data): bool
{
    try {
        // Validation des données
        if (empty($data['Lib'])) {
            error_log("Libellé manquant pour la modification du journal");
            return false;
        }
        
        $sql = "UPDATE jal 
                SET Lib = :Lib, 
                    Typ = :Typ, 
                    Cpt = :Cpt, 
                    NumeroAgenceSCE = :NumeroAgenceSCE 
                WHERE Cde = :cde";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind des paramètres
        $stmt->bindValue(':cde', $cde, PDO::PARAM_INT);
        $stmt->bindValue(':Lib', trim($data['Lib']), PDO::PARAM_STR);
        $stmt->bindValue(':Typ', !empty($data['Typ']) ? trim($data['Typ']) : null, PDO::PARAM_STR);
        $stmt->bindValue(':Cpt', !empty($data['Cpt']) ? trim($data['Cpt']) : null, PDO::PARAM_STR);
        $stmt->bindValue(':NumeroAgenceSCE', !empty($data['NumeroAgenceSCE']) ? trim($data['NumeroAgenceSCE']) : null, PDO::PARAM_STR);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erreur dans modifierJournal: " . $e->getMessage());
        return false;
    }
}

/**
 * Supprime un journal de la base de données.
 */
function supprimerJournal(PDO $pdo, int $codeJournal): bool
{
    try {
        // Vérifier d'abord si le journal existe
        $journal = getJournal($pdo, $codeJournal);
        if (!$journal) {
            error_log("Journal $codeJournal non trouvé pour suppression");
            return false;
        }
        
        $sql = "DELETE FROM jal WHERE Cde = :codeJournal";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':codeJournal', $codeJournal, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Vérifier si c'est une erreur de contrainte de clé étrangère (MySQL erreur 1451)
        $errorCode = $e->errorInfo[1] ?? 0;
        
        if ($errorCode == 1451) {
            error_log("Impossible de supprimer le journal $codeJournal : il est utilisé dans d'autres tables");
            throw new Exception("Ce journal ne peut pas être supprimé car il est utilisé dans des écritures comptables.");
        }
        
        error_log("Erreur dans supprimerJournal: " . $e->getMessage());
        return false;
    }
}

/**
 * Vérifie si un journal existe.
 */
function journalExiste(PDO $pdo, int $codeJournal): bool
{
    try {
        $sql = "SELECT COUNT(*) FROM jal WHERE Cde = :codeJournal";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':codeJournal', $codeJournal, PDO::PARAM_INT);
        $stmt->execute();
        
        return (int)$stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Erreur dans journalExiste: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les journaux pour un sélecteur HTML.
 */
function getJournauxPourSelect(PDO $pdo): array
{
    try {
        $sql = "SELECT Cde, CONCAT(Cde, ' - ', Lib) as affichage FROM jal ORDER BY Cde ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $journaux = [];
        
        foreach ($result as $row) {
            $journaux[$row['Cde']] = $row['affichage'];
        }
        
        return $journaux;
    } catch (PDOException $e) {
        error_log("Erreur dans getJournauxPourSelect: " . $e->getMessage());
        return [];
    }
}
?>