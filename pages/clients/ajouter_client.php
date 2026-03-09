<?php
// pages/clients/ajouter_client.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../fonctions/database.php';
require_once '../../fonctions/gestion_clients.php';

$message = '';
$message_type = '';

// Récupération de l'activité (Simulé ici, adapter à votre système de session)
$userActivity = $_SESSION['user_activity'] ?? 'pressing';

// Simulation des activités disponibles
$activitesDisponibles = [
    ['type_activite' => 'pressing', 'nom_activite' => 'Pressing'],
    ['type_activite' => 'commerce', 'nom_activite' => 'Commerce'],
    ['type_activite' => 'hotel', 'nom_activite' => 'Hôtel']
];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer_client'])) {
    try {
        $db = new PDO('mysql:host=localhost;dbname=pressing_manager;charset=utf8', 'root', '');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $donnees = [
            'nom_client'      => strtoupper(trim($_POST['nom_client'])),
            'prenom_client'   => ucwords(trim($_POST['prenom_client'])),
            'telephone'       => trim($_POST['telephone']),
            'email'           => trim($_POST['email']),
            'adresse'         => trim($_POST['adresse']),
            'remise_speciale' => floatval($_POST['remise_speciale'] ?? 0),
            'notes'           => trim($_POST['notes']),
            'type_client'     => trim($_POST['type_client'] ?? 'particulier'),
            'activites_client'=> $_POST['activites_client'] ?? []
        ];

        if (empty($donnees['nom_client']) || empty($donnees['telephone'])) {
            throw new Exception("Champs obligatoires manquants.");
        }

        $db->beginTransaction();
        
        $stmt = $db->prepare("INSERT INTO clients (nom_client, prenom_client, telephone, email, adresse, type_client, remise_speciale, notes, date_creation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$donnees['nom_client'], $donnees['prenom_client'], $donnees['telephone'], $donnees['email'], $donnees['adresse'], $donnees['type_client'], $donnees['remise_speciale'], $donnees['notes']]);
        
        $id_client = $db->lastInsertId();
        $db->commit();

        header('Location: liste.php?msg=success');
        exit();

    } catch (Exception $e) {
        if(isset($db)) $db->rollBack();
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

include '../../templates/header.php';
include '../../templates/navigation.php';
?>

<style>
    :root {
        --primary-color: #2c3e50;
        --accent-color: #3498db;
        --border-color: #dee2e6;
        --bg-light: #f8f9fa;
    }

    body { background-color: #f4f7f6; color: #333; }
    
    .client-card {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        margin-bottom: 20px;
    }

    .card-header-pro {
        background-color: var(--primary-color);
        color: white;
        padding: 15px 20px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.9rem;
    }

    .form-section {
        padding: 20px;
        border-bottom: 1px solid var(--border-color);
    }

    .section-title {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--accent-color);
        text-transform: uppercase;
        margin-bottom: 20px;
        display: block;
        border-left: 3px solid var(--accent-color);
        padding-left: 10px;
    }

    .form-control {
        border-radius: 2px;
        border: 1px solid var(--border-color);
        box-shadow: none;
        height: 40px;
    }

    .form-control:focus {
        border-color: var(--accent-color);
        box-shadow: none;
    }

    .activity-selector {
        background: var(--bg-light);
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }

    .badge-outline {
        padding: 4px 10px;
        border: 1px solid var(--border-color);
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--primary-color);
    }

    .btn-pro {
        border-radius: 2px;
        font-weight: 600;
        text-transform: uppercase;
        padding: 10px 25px;
        transition: all 0.3s;
    }

    .btn-save { background-color: var(--accent-color); color: white; border: none; }
    .btn-save:hover { background-color: #2980b9; color: white; }

    .btn-cancel { background-color: transparent; border: 1px solid var(--border-color); color: var(--primary-color); margin-right: 10px; }

    .sidebar-info {
        background: white;
        border: 1px solid var(--border-color);
        padding: 20px;
        border-radius: 4px;
    }

    .mandatory { color: #e74c3c; font-weight: bold; }
</style>

<div class="container" style="margin-top: 40px;">
    <div class="row">
        <div class="col-md-8">
            <div class="client-card">
                <div class="card-header-pro">
                    ENREGISTREMENT CLIENT
                    <span style="float: right; opacity: 0.7; font-size: 0.7rem;">MODULE : <?= strtoupper($userActivity) ?></span>
                </div>
                
                <form method="POST" action="">
                    <div class="panel-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $message_type ?>" style="border-radius: 0;">
                                <?= $message ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-section">
                            <span class="section-title">Domaines d'activité</span>
                            <div class="row">
                                <?php foreach ($activitesDisponibles as $activite): ?>
                                <div class="col-md-4">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="activites_client[]" value="<?= $activite['type_activite'] ?>" 
                                            <?= ($userActivity == $activite['type_activite']) ? 'checked' : '' ?>>
                                            <span class="badge-outline"><?= strtoupper($activite['nom_activite']) ?></span>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-section">
                            <span class="section-title">Identité du client</span>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="small">TYPE <span class="mandatory">*</span></label>
                                    <select name="type_client" class="form-control" required>
                                        <option value="particulier">PARTICULIER</option>
                                        <option value="entreprise">ENTREPRISE</option>
                                        <option value="hotel">CLIENT HÔTEL</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="small">NOM <span class="mandatory">*</span></label>
                                    <input type="text" name="nom_client" class="form-control" placeholder="NOM" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="small">PRÉNOM</label>
                                    <input type="text" name="prenom_client" class="form-control" placeholder="PRÉNOM">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <span class="section-title">Coordonnées</span>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="small">TÉLÉPHONE <span class="mandatory">*</span></label>
                                    <input type="text" name="telephone" class="form-control" placeholder="00 00 00 00" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="small">EMAIL</label>
                                    <input type="email" name="email" class="form-control" placeholder="adresse@mail.com">
                                </div>
                                <div class="col-md-12" style="margin-top: 15px;">
                                    <label class="small">ADRESSE PHYSIQUE</label>
                                    <textarea name="adresse" class="form-control" rows="1" placeholder="Localisation..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <span class="section-title">Paramètres Spéciaux</span>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="small">REMISE (%)</label>
                                    <input type="number" name="remise_speciale" class="form-control" value="0" step="0.01">
                                </div>
                                <div class="col-md-8">
                                    <label class="small">OBSERVATIONS</label>
                                    <input type="text" name="notes" class="form-control" placeholder="Commentaires ou notes internes">
                                </div>
                            </div>
                        </div>

                        <div class="form-section text-right" style="border: none;">
                           <a href="javascript:history.back()" class="btn btn-pro btn-cancel">Retour</a>
                            <button type="submit" name="enregistrer_client" class="btn btn-pro btn-save">Enregistrer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            <div class="sidebar-info">
                <h4 style="font-size: 0.9rem; font-weight: 700; color: var(--primary-color);">GUIDE DE SAISIE</h4>
                <hr>
                <p class="small text-muted">Veuillez renseigner les informations avec précision pour garantir le bon fonctionnement du module de fidélité.</p>
                <div style="background: var(--bg-light); padding: 10px; margin-top: 15px;">
                    <ul class="list-unstyled small">
                        <li style="margin-bottom: 8px;">• Les champs avec <span class="mandatory">*</span> sont requis.</li>
                        <li style="margin-bottom: 8px;">• Le numéro de téléphone doit être unique.</li>
                        <li style="margin-bottom: 8px;">• La remise s'appliquera sur chaque ticket.</li>
                    </ul>
                </div>
                <hr>
                <p class="text-center small">PROTECTION DES DONNÉES CONFORME</p>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>