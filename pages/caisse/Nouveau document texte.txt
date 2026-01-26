<?php
session_start();
require_once __DIR__ . "/../../fonctions/database.php";

// Sécurité : Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Récupération des données pour les listes déroulantes
$clients = $pdo->query("SELECT id_client, nom_client, telephone FROM clients WHERE est_actif = 1 ORDER BY nom_client ASC")->fetchAll();
$services = $pdo->query("SELECT id_service, nom_service, prix_unitaire FROM services WHERE est_disponible = 1")->fetchAll();

include "../../includes/header.php";
include "../../includes/navigation.php";
?>

<div class="container-fluid">
    <div class="row">
        <form action="enregistrer_ticket.php" method="POST" id="ticketForm">
            <div class="col-md-4">
                <div class="card">
                    <h4 class="page-header">Informations Client</h4>
                    <div class="form-group">
                        <label>Sélectionner un Client</label>
                        <select name="id_client" class="form-control" required>
                            <option value="">-- Choisir un client --</option>
                            <?php foreach($clients as $c): ?>
                                <option value="<?= $c['id_client'] ?>"><?= $c['nom_client'] ?> (<?= $c['telephone'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date de retrait prévue</label>
                        <input type="datetime-local" name="date_retrait_prevue" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Notes Client (Tâches, fragilité...)</label>
                        <textarea name="notes_client" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="card">
                    <h4 class="page-header">Paiement</h4>
                    <div class="form-group">
                        <label>Mode de règlement</label>
                        <select name="mode_paiement" class="form-control">
                            <option value="especes">Espèces</option>
                            <option value="mobile">Mobile Money</option>
                            <option value="carte">Carte</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Montant Versé (Acompte)</label>
                        <input type="number" name="montant_verse" class="form-control" value="0">
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <h4 class="page-header">Articles & Services</h4>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <select id="selectService" class="form-control">
                                <option value="">-- Ajouter un article --</option>
                                <?php foreach($services as $s): ?>
                                    <option value="<?= $s['id_service'] ?>" data-prix="<?= $s['prix_unitaire'] ?>" data-nom="<?= $s['nom_service'] ?>">
                                        <?= $s['nom_service'] ?> (<?= number_format($s['prix_unitaire'], 0, ',', ' ') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="number" id="inputQte" class="form-control" value="1" min="1">
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-success btn-block" onclick="ajouterArticle()">Ajouter</button>
                        </div>
                    </div>

                    <table class="table table-bordered mt-3" id="tableArticles">
                        <thead>
                            <tr>
                                <th>Désignation</th>
                                <th>Prix Unitaire</th>
                                <th>Quantité</th>
                                <th>Sous-total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            </tbody>
                    </table>

                    <div class="total-display">
                        TOTAL : <span id="totalAffichage">0</span> FCFA
                        <input type="hidden" name="montant_total" id="totalInput" value="0">
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-primary btn-lg btn-block">VALIDER LE DEPOT ET IMPRIMER</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include "../../includes/footer.php"; ?>