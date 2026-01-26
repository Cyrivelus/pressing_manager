<?php
// Vérification avant sortie
function validerQualiteArticle($pdo, $id_article_ticket, $id_inspecteur) {
    $stmt = $pdo->prepare("UPDATE ticket_details SET etat_proprete = 'valide', date_controle = NOW(), inspecteur_id = ? WHERE id = ?");
    return $stmt->execute([$id_inspecteur, $id_article_ticket]);
}