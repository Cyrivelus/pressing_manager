<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Nouvelle Facture Vente";

// Récupération des clients pour la liste déroulante
$clients = $pdo->query("SELECT id_client, nom_client FROM clients ORDER BY nom_client")->fetchAll();

// RÉPARATION : On utilise 'prix_unitaire' au lieu de 'prix' conformément à votre base de données
$services = $pdo->query("SELECT id_service, nom_service, prix_unitaire FROM services ORDER BY nom_service")->fetchAll();

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>
<br><br><br>
<div class="container-fluid py-4">
    <form id="factureForm" action="enregistrer_facture.php" method="POST">
        <div class="row g-4">
            
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-4 text-primary">Détails de la Facture</h5>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Sélectionner un Client</label>
                                <select name="id_client" class="form-select select2" required>
                                    <option value="">-- Choisir un client --</option>
                                    <?php foreach($clients as $c): ?>
                                        <option value="<?= $c['id_client'] ?>"><?= htmlspecialchars($c['nom_client']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Date d'échéance</label>
                                <input type="date" name="date_echeance" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <hr>

                        <div class="table-responsive">
                            <table class="table table-borderless align-middle" id="tableArticles">
                                <thead class="text-muted small text-uppercase">
                                    <tr>
                                        <th style="width: 40%;">Service / Article</th>
                                        <th style="width: 15%;">Prix Unitaire</th>
                                        <th style="width: 15%;">Quantité</th>
                                        <th style="width: 20%;">Total</th>
                                        <th style="width: 10%;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="ligne-article">
                                        <td>
                                            <select name="articles[]" class="form-select select-service" required>
                                                <option value="" data-prix="0">-- Choisir --</option>
                                                <?php foreach($services as $s): ?>
                                                    <option value="<?= $s['id_service'] ?>" data-prix="<?= $s['prix_unitaire'] ?>">
                                                        <?= htmlspecialchars($s['nom_service']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><input type="number" name="prix[]" class="form-control input-prix" step="0.01" readonly></td>
                                        <td><input type="number" name="qte[]" class="form-control input-qte" value="1" min="1"></td>
                                        <td><input type="text" class="form-control input-total border-0 bg-light fw-bold" readonly value="0"></td>
                                        <td><button type="button" class="btn btn-outline-danger btn-sm btn-remove">Retirer</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" id="addBtn" class="btn btn-outline-primary btn-sm mt-2">
                            +Ajouter une ligne
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm bg-white p-4">
                    <h6 class="fw-bold mb-4">RÉSUMÉ FINANCIER</h6>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Sous-Total HT</span>
                        <span id="labelHT" class="fw-bold">0 FCFA</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">TVA (19.25%)</span>
                        <span id="labelTVA" class="fw-bold">0 FCFA</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="h5 fw-bold">TOTAL TTC</span>
                        <span id="labelTTC" class="h5 fw-bold text-primary">0 FCFA</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Mode de paiement</label>
                        <select name="mode_paiement" class="form-select">
                            <option value="Espèces">Espèces</option>
                            <option value="Mobile Money">Mobile Money</option>
                            <option value="Carte Bancaire">Carte Bancaire</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow">
                        VALIDER LA FACTURE
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.querySelector('#tableArticles tbody');
    const addBtn = document.querySelector('#addBtn');

    function calculerTotaux() {
        let totalHT = 0;
        document.querySelectorAll('.ligne-article').forEach(row => {
            const prix = parseFloat(row.querySelector('.input-prix').value) || 0;
            const qte = parseFloat(row.querySelector('.input-qte').value) || 0;
            const totalLigne = prix * qte;
            row.querySelector('.input-total').value = totalLigne.toLocaleString('fr-FR') + ' FCFA';
            totalHT += totalLigne;
        });

        const tva = totalHT * 0.1925;
        const ttc = totalHT + tva;

        document.getElementById('labelHT').innerText = totalHT.toLocaleString('fr-FR') + ' FCFA';
        document.getElementById('labelTVA').innerText = Math.round(tva).toLocaleString('fr-FR') + ' FCFA';
        document.getElementById('labelTTC').innerText = Math.round(ttc).toLocaleString('fr-FR') + ' FCFA';
    }

    addBtn.addEventListener('click', () => {
        const row = document.querySelector('.ligne-article').cloneNode(true);
        row.querySelectorAll('input').forEach(i => {
            if (i.classList.contains('input-qte')) i.value = 1;
            else i.value = "";
        });
        row.querySelector('.select-service').selectedIndex = 0;
        tableBody.appendChild(row);
    });

    tableBody.addEventListener('change', (e) => {
        if (e.target.classList.contains('select-service')) {
            const prix = e.target.options[e.target.selectedIndex].dataset.prix;
            e.target.closest('tr').querySelector('.input-prix').value = prix;
            calculerTotaux();
        }
    });

    tableBody.addEventListener('input', (e) => {
        if (e.target.classList.contains('input-qte')) {
            calculerTotaux();
        }
    });

    tableBody.addEventListener('click', (e) => {
        if (e.target.closest('.btn-remove')) {
            if (document.querySelectorAll('.ligne-article').length > 1) {
                e.target.closest('tr').remove();
                calculerTotaux();
            }
        }
    });
});
</script>

<?php require_once  '../../templates/footer.php'; ?>