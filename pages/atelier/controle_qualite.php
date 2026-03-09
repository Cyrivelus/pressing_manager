<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../../index.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

// Récupération de l'ID du ticket
$id_ticket = $_GET['id'] ?? null;

if (!$id_ticket) {
    header('Location: suivi_production.php');
    exit;
}

// 2. Récupération des détails (Correction de numero_ticket)
$stmt = $pdo->prepare("SELECT t.*, c.nom_client, c.prenom_client 
                       FROM tickets t 
                       JOIN clients c ON t.id_client = c.id_client 
                       WHERE t.id_ticket = ?");
$stmt->execute([$id_ticket]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die("Ticket introuvable.");
}

// Correction du titre : on utilise numero_ticket
$titre = "Contrôle Qualité : Ticket #" . ($ticket['numero_ticket'] ?? 'Inconnu');

// 3. Traitement de la validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note_qualite = $_POST['note_qualite'] ?? '';
    $etat_final = $_POST['etat_final']; 

    try {
        $pdo->beginTransaction();

        // Gestion de l'upload photo (optionnel)
        $photo_path = null;
        if (!empty($_FILES['photo_apres']['name'])) {
            $upload_dir = $root . '/assets/uploads/qualite/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_extension = pathinfo($_FILES['photo_apres']['name'], PATHINFO_EXTENSION);
            $file_name = "ticket_" . $id_ticket . "_apres_" . time() . "." . $file_extension;
            move_uploaded_file($_FILES['photo_apres']['tmp_name'], $upload_dir . $file_name);
            $photo_path = 'assets/uploads/qualite/' . $file_name;
        }

        // Insertion dans la table de traçabilité
        // Vérifiez que la table existe ou utilisez 'etapes_production'
        $sql_trace = "INSERT INTO etapes_production (id_ticket, status_etape, progression, notes) 
                      VALUES (?, ?, ?, ?)";
        $pdo->prepare($sql_trace)->execute([
            $id_ticket, 
            ($etat_final == 'conforme') ? 'pret' : 'repassage', 
            ($etat_final == 'conforme') ? 100 : 50,
            "Contrôle Qualité: " . $note_qualite
        ]);

        // Mise à jour du statut du ticket et de la photo après dans lignes_ticket ou tickets
        $nouveau_statut = ($etat_final == 'conforme') ? 'prêt' : 'en_attente';
        $sql_update = "UPDATE tickets SET statut = ? WHERE id_ticket = ?";
        $pdo->prepare($sql_update)->execute([$nouveau_statut, $id_ticket]);

        $pdo->commit();
        header("Location: suivi_production.php?success=qualite_validee");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $erreur = "Erreur lors de la validation : " . $e->getMessage();
    }
}

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($TITRE_PAGE) ?> | BailCompta 360</title>
   
    <link rel="stylesheet" href="../../css/select2.min.css">
    <link rel="stylesheet" href="../../css/select2-bootstrap.min.css">

</head>
<body>
<style>
    .quality-card { max-width: 800px; margin: 40px auto; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
    .check-item { padding: 15px; border-bottom: 1px solid #eee; display: flex; align-items: center; transition: 0.2s; }
    .check-item:hover { background-color: #f8f9fa; }
    .form-check-input { width: 22px; height: 22px; margin-right: 15px; cursor: pointer; }
    .card-header-custom { background: #1a1a1a; color: white; }
</style>

<div class="container py-5">
    <div class="quality-card card border-0">
        <div class="card-header card-header-custom p-4">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0 fw-bold">Inspection Qualité</h3>
                <span class="badge bg-primary fs-6">Ticket #<?= $ticket['numero_ticket'] ?></span>
            </div>
            <p class="mb-0 mt-2 opacity-75">Client : <?= htmlspecialchars($ticket['nom_client'] . ' ' . ($ticket['prenom_client'] ?? '')) ?></p>
        </div>
        
        <form method="POST" action="" enctype="multipart/form-data" class="card-body p-4">
            <?php if(isset($erreur)): ?>
                <div class="alert alert-danger shadow-sm border-start border-4 border-danger"><?= $erreur ?></div>
            <?php endif; ?>

            <div class="mb-4">
                <h5 class="fw-bold mb-3 text-secondary text-uppercase small">Points de contrôle obligatoires</h5>
                <div class="bg-light rounded-3 overflow-hidden border">
                    <div class="check-item">
                        <input class="form-check-input" type="checkbox" required id="c1">
                        <label class="form-check-label" for="c1">Élimination totale des taches signalées</label>
                    </div>
                    <div class="check-item">
                        <input class="form-check-input" type="checkbox" required id="c2">
                        <label class="form-check-label" for="c2">Qualité du repassage et absence de faux plis</label>
                    </div>
                    <div class="check-item">
                        <input class="form-check-input" type="checkbox" required id="c3">
                        <label class="form-check-label" for="c3">Conformité du comptage (<?= $ticket['nombre_articles'] ?? '?' ?> articles)</label>
                    </div>
                </div>
            </div>

            

            <div class="row mt-4">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Preuve Visuelle (Photo Après)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"></span>
                        <input type="file" name="photo_apres" class="form-control" accept="image/*" capture="camera">
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Décision Finale</label>
                  
    <select name="etat_final" 
            id="etatFinalSelect" 
            class="form-control select2-enable border-primary fw-bold" 
            required>
        
        <option value="">-- Choisir l'état final --</option>
        <option value="conforme">✅ CONFORME - Prêt pour retrait</option>
        <option value="avec_reserve">❌ NON-CONFORME - Retour atelier</option>
    </select>

                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Commentaires de production</label>
                <textarea name="note_qualite" class="form-control" rows="3" placeholder="Ex: Bouton recousu, légère décoloration signalée..."></textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                <a href="suivi_production.php" class="text-decoration-none text-muted fw-bold">
                   Retour
                </a>
                <button type="submit" class="btn btn-success btn-lg px-5 shadow">
                    Enregistrer l'inspection
                </button>
            </div>
        </form>
    </div>
</div>
<script src="../../js/jquery.min.js"></script>
<script src="../../js/bootstrap.min.js"></script>
<script src="../../js/select2.min.js"></script>
<script>
    $(document).ready(function() {
    $('#etatFinalSelect').select2({
        theme: "bootstrap",
        placeholder: "Sélectionner l'état...",
        minimumResultsForSearch: Infinity, // Masque la barre de recherche (inutile pour 2 options)
        width: '100%'
    });
});
</script>
<?php require_once  '../../templates/footer.php'; ?>