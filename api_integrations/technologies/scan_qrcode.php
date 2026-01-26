<?php
// Génération de l'URL pour le QR Code du ticket
function genererLienQR($ticket_ref) {
    $baseUrl = "https://votrepressing.com/suivi/";
    return $baseUrl . base64_encode($ticket_ref);
}