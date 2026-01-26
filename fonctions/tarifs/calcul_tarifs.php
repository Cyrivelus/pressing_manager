<?php
class CalculTarifs {
    private $db;
    public function __construct($db) { $this->db = $db; }

    public function calculerPrixTicket($services, $quantites, $remisePourcentage = 0) {
        $total = 0;
        foreach ($services as $index => $id_service) {
            $stmt = $this->db->prepare("SELECT prix_unitaire FROM services WHERE id_service = ?");
            $stmt->execute([$id_service]);
            $prix = $stmt->fetchColumn();
            $total += $prix * ($quantites[$index] ?? 1);
        }
        $montantRemise = $total * ($remisePourcentage / 100);
        return $total - $montantRemise;
    }
}