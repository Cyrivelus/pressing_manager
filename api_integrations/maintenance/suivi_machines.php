<?php
// Gestion du parc machine
function getEtatMachines($pdo) {
    return $pdo->query("SELECT * FROM machines_pressing ORDER BY derniere_maintenance DESC")->fetchAll();
}

function enregistrerPanne($pdo, $id_machine, $description) {
    $stmt = $pdo->prepare("UPDATE machines_pressing SET statut = 'en_panne', notes = ? WHERE id = ?");
    return $stmt->execute([$description, $id_machine]);
}