<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Sécurité : On utilise bien 'client_id'
if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit;
}

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$client_id = $_SESSION['client_id'];
$titre = "Mes Préférences & Profil";

// 2. Traitement de la mise à jour réelle (Téléphone et Adresse)
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouveau_tel = $_POST['telephone'] ?? '';
    $nouvelle_adr = $_POST['adresse'] ?? '';

    $update = $pdo->prepare("UPDATE clients SET telephone = ?, adresse = ? WHERE id_client = ?");
    if ($update->execute([$nouveau_tel, $nouvelle_adr, $client_id])) {
        $message = "<div class='alert alert-success'>Profil mis à jour avec succès !</div>";
    } else {
        $message = "<div class='alert alert-danger'>Erreur lors de la mise à jour.</div>";
    }
}

// 3. Récupération des infos actuelles (Sécurisée)
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id_client = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

// Si le client n'existe pas en base (session expirée ou ID invalide)
if (!$client) {
    session_destroy();
    header('Location: login.php');
    exit;
}

require_once  '../../templates/header_client.php';
?>

<div class="container py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="index.php" class="btn btn-light rounded-circle me-3"><i class="fas fa-arrow-left"></i></a>
        <h2 class="fw-bold m-0"><?= $titre ?></h2>
    </div>

    <?= $message ?>

    <form method="POST" action="">
        <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 20px;">
            <h5 class="fw-bold mb-3"><i class="fas fa-user-edit text-primary me-2"></i>Mes Informations</h5>
            
            <div class="mb-3">
                <label class="form-label small fw-bold">Nom Complet</label>
                <input type="text" class="form-control bg-light" 
                       value="<?= htmlspecialchars(($client['prenom_client'] ?? '') . ' ' . ($client['nom_client'] ?? '')) ?>" readonly>
                <small class="text-muted">Contactez l'agence pour modifier votre nom.</small>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Numéro de Téléphone</label>
                <input type="tel" name="telephone" class="form-control" 
                       value="<?= htmlspecialchars($client['telephone'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Adresse de livraison par défaut</label>
                <textarea name="adresse" class="form-control" rows="2"><?= htmlspecialchars($client['adresse'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 20px;">
            <h5 class="fw-bold mb-3"><i class="fas fa-bell text-warning me-2"></i>Notifications</h5>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="smsNotify" checked disabled>
                <label class="form-check-label" for="smsNotify">SMS de suivi (Activé par défaut)</label>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="promoNotify">
                <label class="form-check-label" for="promoNotify">Recevoir les offres promotionnelles</label>
            </div>
        </div>

        <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 20px;">
            <h5 class="fw-bold mb-3"><i class="fas fa-magic text-info me-2"></i>Préférences de lavage</h5>
            
            <div class="mb-3">
                <label class="form-label small fw-bold">Amidon (Chemises)</label>
                <select name="pref_amidon" class="form-select">
                    <option value="aucun">Sans amidon</option>
                    <option value="leger">Léger</option>
                    <option value="normal" selected>Normal</option>
                    <option value="fort">Fort</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Finition souhaitée</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="finition" id="fin1" value="cintre" checked>
                    <label class="btn btn-outline-secondary" for="fin1">Sur Cintre</label>
                    
                    <input type="radio" class="btn-check" name="finition" id="fin2" value="plie">
                    <label class="btn btn-outline-secondary" for="fin2">Plié</label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100 py-3 shadow mb-5" style="border-radius: 15px;">
            Enregistrer mes modifications
        </button>
    </form>
</div>

<?php require_once  '../../templates/footer_client.php'; ?>