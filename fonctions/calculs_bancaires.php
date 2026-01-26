<?php
/**
 * Bibliothèque de fonctions bancaires - BailCompta360
 * Centralise les calculs financiers pour les comptes et crédits.
 */

/**
 * Calcule la clé RIB française/OHADA (2 chiffres)
 * Formule : 97 - ( (89 * CodeBanque + 15 * CodeGuichet + 3 * NumCompte) % 97 )
 */
function calculerCleRIB($banque, $guichet, $compte) {
    // Nettoyage du compte (remplacer les lettres selon la norme bancaire si nécessaire)
    $tabLettres = ["AJ"=>"1", "BKS"=>"2", "CLT"=>"3", "DMU"=>"4", "ENV"=>"5", "FOW"=>"6", "GPX"=>"7", "HQY"=>"8", "IRZ"=>"9"];
    $compte_num = strtr(strtoupper($compte), $tabLettres);
    
    $val = $banque . $guichet . $compte_num . "00";
    $reste = bcmod($val, '97');
    $cle = 97 - $reste;
    return str_pad($cle, 2, "0", STR_PAD_LEFT);
}

/**
 * Calcul automatique des agios (Débiteurs)
 * Formule simplifiée : (Montant x Jours x Taux) / 360
 */
function calculerAgios($montant_decouvert, $jours, $taux_annuel) {
    if ($montant_decouvert >= 0) return 0;
    
    $decouvert_absolu = abs($montant_decouvert);
    $agios = ($decouvert_absolu * $jours * ($taux_annuel / 100)) / 360;
    
    return round($agios, 2);
}

/**
 * Calcul de la mensualité d'un crédit (Amortissement constant)
 * $M = P * (i / (1 - (1 + i)^-n))
 */
function calculerMensualite($capital, $taux_annuel, $duree_mois) {
    if ($taux_annuel == 0) return $capital / $duree_mois;
    
    $taux_mensuel = ($taux_annuel / 100) / 12;
    $mensualite = $capital * ($taux_mensuel / (1 - pow(1 + $taux_mensuel, -$duree_mois)));
    
    return round($mensualite, 2);
}

/**
 * Vérifie si une opération est autorisée selon le solde et le plafond
 */
function estOperationAutorisee($solde_actuel, $montant_debit, $autorisation_decouvert) {
    $nouveau_solde = $solde_actuel - $montant_debit;
    // Si le nouveau solde est plus bas que le découvert autorisé (ex: -500 < -1000 est vrai, mais ici on parle en valeur absolue ou algébrique)
    // Algébriquement : Nouveau solde doit être >= -Plafond
    return $nouveau_solde >= (-1 * abs($autorisation_decouvert));
}

/**
 * Calcule les pénalités de retard (Urgent)
 * @param float $montant_echeance
 * @param int $jours_retard
 * @param float $taux_penalite (ex: 5% par mois)
 */
function calculerPenaliteRetard($montant, $jours_retard, $taux_penalite) {
    if ($jours_retard <= 0) return 0;
    // Calcul prorata temporis (journalier)
    $penalite = ($montant * ($taux_penalite / 100) * $jours_retard) / 30;
    return round($penalite, 0);
}