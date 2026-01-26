<?php
// Logique simplifiée pour noter une relance/promesse de paiement
if(isset($_POST['id_dette'])) {
    $note = "Relance effectuée le " . date('d/m/Y') . " : " . $_POST['commentaire'];
    $stmt = $pdo->prepare("UPDATE dettes_fournisseur SET historique_relance = CONCAT(IFNULL(historique_relance,''), ?) WHERE id_dette = ?");
    $stmt->execute([$note . "\n", $_POST['id_dette']]);
}
?>

<div class="container py-5">
    <div class="alert alert-info border-0 shadow-sm">
        <i class="fas fa-info-circle me-2"></i> 
        <strong>Astuce :</strong> Communiquer avec vos fournisseurs avant l'échéance renforce votre crédibilité.
    </div>
    </div>