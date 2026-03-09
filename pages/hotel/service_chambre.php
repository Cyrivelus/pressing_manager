<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Service Linge de Chambre";

// 1. Traitement de l'enregistrement et redirection vers l'impression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer_linge'])) {
    try {
        $pdo->beginTransaction();

        $id_hotel = $_POST['id_hotel'];
        $num_chambre = $_POST['num_chambre'];
        $priorite = $_POST['priorite'];
        $total_general = 0;
        
        // Génération d'un numéro de ticket unique (ex: CH-302-1234)
        $numero_ticket = "CH-" . $num_chambre . "-" . strtoupper(substr(uniqid(), -4));

        // Insertion du Ticket Principal
        $sql_ticket = "INSERT INTO tickets (numero_ticket, id_client, id_agence, id_utilisateur, date_depot, statut, notes, montant_total, mode_retrait) 
                       VALUES (:num, :id_c, :id_a, :id_u, NOW(), 'en_traitement', :notes, :total, 'boutique')";
        
        $stmt_ticket = $pdo->prepare($sql_ticket);
        $stmt_ticket->execute([
            'num'   => $numero_ticket,
            'id_c'  => $id_hotel,
            'id_a'  => $_SESSION['id_agence'] ?? 1, // Ajuster selon votre session
            'id_u'  => $_SESSION['id_utilisateur'] ?? 1,
            'notes' => "Linge Chambre: " . $num_chambre . " | Priorité: " . $priorite,
            'total' => 0 // On mettra à jour après le calcul des lignes
        ]);
        
        $id_ticket = $pdo->lastInsertId();

        // Insertion des Lignes de Ticket (Articles)
        foreach ($_POST['qte'] as $id_service => $quantite) {
            $quantite = (int)$quantite;
            if ($quantite > 0) {
                // Récupérer le prix actuel en base pour sécurité
                $stmt_p = $pdo->prepare("SELECT prix_unitaire FROM services WHERE id_service = ?");
                $stmt_p->execute([$id_service]);
                $prix = $stmt_p->fetchColumn();
                
                $sous_total = $prix * $quantite;
                $total_general += $sous_total;

                $sql_ligne = "INSERT INTO lignes_ticket (id_ticket, id_service, quantite, prix_unitaire, sous_total) 
                              VALUES (?, ?, ?, ?, ?)";
                $pdo->prepare($sql_ligne)->execute([$id_ticket, $id_service, $quantite, $prix, $sous_total]);
            }
        }

        // Mise à jour du montant total du ticket
        $pdo->prepare("UPDATE tickets SET montant_total = ?, total_ttc = ? WHERE id_ticket = ?")
            ->execute([$total_general, $total_general, $id_ticket]);

        $pdo->commit();

        // REDIRECTION VERS L'IMPRESSION (Chemin corrigé selon votre dossier)
        header("Location: ../Tickets/print_hotel.php?id=" . $id_ticket);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur lors de l'enregistrement : " . $e->getMessage();
    }
}

// 2. Récupération des hôtels
$hotels = $pdo->query("SELECT id_client, nom_client FROM clients WHERE notes LIKE '%PARTENAIRE_HOTEL%'")->fetchAll();

// 3. Récupération des services de blanchisserie (Catégorie 1 et 2 selon vos données)
$articles_hotel = $pdo->query("SELECT id_service, nom_service, prix_unitaire FROM services WHERE est_disponible = 1 AND id_categorie IN (1,2)")->fetchAll();

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5 px-4">
    <br><br><br>
    <div class="mb-5 mt-4">
        <h2 style="font-weight: 900; color: #1a1a1a; letter-spacing: -1px;">🛎️ <?= $titre ?></h2>
        <p class="text-muted">Réception et comptage du linge par unité d'hébergement</p>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h5 class="fw-bold mb-4">1. Origine du Linge</h5>
                    
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">HÔTEL</label>
                        <select name="id_hotel" class="form-select border-0 bg-light" required>
                            <?php foreach($hotels as $h): ?>
                                <option value="<?= $h['id_client'] ?>"><?= htmlspecialchars($h['nom_client']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">NUMÉRO DE CHAMBRE / SUITE</label>
                        <input type="text" name="num_chambre" class="form-control border-0 bg-light fw-bold" placeholder="Ex: 302" required>
                    </div>

                    <div class="mb-0">
                        <label class="small fw-bold text-muted">PRIORITÉ</label>
                        <select name="priorite" class="form-select border-0 bg-light">
                            <option value="Normal">Standard (24h)</option>
                            <option value="Express">Express (Même jour)</option>
                            <option value="Urgent">VIP / Urgent</option>
                        </select>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 bg-primary text-white text-center">
                    <small class="opacity-75 fw-bold text-uppercase">Total de la note</small>
                    <h2 class="fw-bold mb-0" id="total_affichage">0 F</h2>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="fw-bold m-0">2. Inventaire des articles (Tarifs en vigueur)</h5>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr class="small text-muted">
                                    <th class="ps-4">ARTICLE</th>
                                    <th class="text-center">PU</th>
                                    <th class="text-center" style="width: 120px;">QUANTITÉ</th>
                                    <th class="text-end pe-4">SOUS-TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($articles_hotel as $art): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?= htmlspecialchars($art['nom_service']) ?></td>
                                    <td class="text-center"><?= number_format($art['prix_unitaire'], 0, ',', ' ') ?> F</td>
                                    <td class="text-center">
                                        <input type="number" 
                                               name="qte[<?= $art['id_service'] ?>]" 
                                               class="form-control form-control-sm text-center border-0 bg-light rounded-pill qte-input" 
                                               data-prix="<?= $art['prix_unitaire'] ?>"
                                               min="0" value="0">
                                    </td>
                                    <td class="text-end pe-4 fw-bold text-primary">
                                        <span class="sous-total">0</span> F
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer bg-white p-4 border-0 text-end">
                        <button type="reset" class="btn btn-light rounded-pill px-4 me-2">Vider</button>
                        <button type="submit" name="enregistrer_linge" class="btn btn-dark rounded-pill px-5 fw-bold">
                            🚀 Valider et Imprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Calcul dynamique
document.querySelectorAll('.qte-input').forEach(input => {
    input.addEventListener('input', function() {
        const prix = parseFloat(this.dataset.prix);
        const qte = parseInt(this.value) || 0;
        const sousTotal = prix * qte;
        
        this.closest('tr').querySelector('.sous-total').textContent = sousTotal.toLocaleString();
        
        let totalGeneral = 0;
        document.querySelectorAll('.qte-input').forEach(i => {
            totalGeneral += (parseFloat(i.dataset.prix) * (parseInt(i.value) || 0));
        });
        document.getElementById('total_affichage').textContent = totalGeneral.toLocaleString() + ' F';
    });
});
</script>

<?php require_once  '../../templates/footer.php'; ?>