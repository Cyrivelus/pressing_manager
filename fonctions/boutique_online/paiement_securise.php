<?php
/**
 * Initialise une transaction de paiement
 * @param float $montant
 * @param string $methode (CARD, MOMO, OM)
 */
function initierPaiementBoutique($commande_id, $montant, $methode) {
    // 1. Création d'une empreinte de transaction en base (statut 'pending')
    // 2. Appel API du fournisseur de paiement
    // 3. Récupération de l'URL de redirection ou du code USSD
    
    $transaction_ref = "TXN-" . strtoupper(uniqid());
    
    // Exemple de structure de retour vers le front-end
    return [
        'transaction_ref' => $transaction_ref,
        'status' => 'redirect_required',
        'payment_url' => "https://api.provider.com/pay/" . $transaction_ref
    ];
}