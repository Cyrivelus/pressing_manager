<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once '../../fonctions/database.php';

// 1. Récupération des clients pour la liste déroulante
$clients = $pdo->query("SELECT id_client, nom_client, prenom_client, telephone FROM clients WHERE est_actif = 1 ORDER BY nom_client ASC")->fetchAll();

// 2. Traitement du formulaire
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_client = $_POST['id_client'];
    $type = $_POST['type_abonnement'];
    $montant = $_POST['forfait_mensuel'];
    $date_debut = $_POST['date_debut'];
    
    // Calcul automatique de la date de fin (+1 mois)
    $date_fin = date('Y-m-d', strtotime($date_debut . ' + 1 month'));
    $statut = 'actif';

    try {
        $sql = "INSERT INTO abonnements (id_client, type_abonnement, forfait_mensuel, date_debut, date_fin, statut) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$id_client, $type, $montant, $date_debut, $date_fin, $statut])) {
            header("Location: index.php?success=1");
            exit();
        }
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Erreur : " . $e->getMessage() . "</div>";
    }
}
require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouvel Abonnement - BailCompta360</title>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .form-container { max-width: 600px; margin: 30px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-label { font-weight: bold; color: #2c3e50; }
    </style>
</head>
<body class="bg-light">
<br> <br> <br>
<div class="container">
    <div class="form-container">
        <div class="d-flex align-items-center mb-4">
            <a href="index.php" class="btn btn-outline-secondary btn-sm me-3">← Retour</a>
            <h2 class="h4 mb-0">Créer un abonnement</h2>
        </div>

        <?= $message ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Client</label>
                <select name="id_client" id="select_client" class="form-control" required>
                    <option value="">Rechercher un client (Nom ou Tél)...</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id_client'] ?>">
                            <?= htmlspecialchars($c['nom_client'] . ' ' . $c['prenom_client'] . ' (' . $c['telephone'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Type de Forfait</label>
                    <select name="type_abonnement" class="form-select" id="type_forfait" onchange="updatePrice()" required>
                        <option value="">Choisir...</option>
                        <option value="Classique" data-price="15000">Classique (15 000)</option>
                        <option value="Premium" data-price="30000">Premium (30 000)</option>
                        <option value="Famille" data-price="50000">Famille (50 000)</option>
                        <option value="Business" data-price="100000">Business (100 000)</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Montant (FCFA)</label>
                    <input type="number" name="forfait_mensuel" id="montant_forfait" class="form-control" readonly>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Date de début</label>
                <input type="date" name="date_debut" class="form-control" value="<?= date('Y-m-d') ?>" required>
                <div class="form-text">La date de fin sera calculée automatiquement à +30 jours.</div>
            </div>

            <hr>

            <button type="submit" class="btn btn-primary w-100">Activer l'abonnement</button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialisation de la recherche client intelligente
        $('#select_client').select2({
            placeholder: "Sélectionnez un client",
            allowClear: true
        });
    });

    // Mise à jour automatique du prix selon le forfait choisi
    function updatePrice() {
        const select = document.getElementById('type_forfait');
        const priceInput = document.getElementById('montant_forfait');
        const selectedOption = select.options[select.selectedIndex];
        
        if (selectedOption.value !== "") {
            priceInput.value = selectedOption.getAttribute('data-price');
        } else {
            priceInput.value = "";
        }
    }
</script>

</body>
</html>
<?php require_once  '../../templates/footer.php'; ?>