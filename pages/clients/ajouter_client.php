<?php
// pages/clients/ajouter_client.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../fonctions/database.php';
require_once '../../fonctions/gestion_clients.php';

$message = '';
$message_type = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer_client'])) {
    try {
        // Récupération et nettoyage des données
        $donnees = [
            'nom_client'      => trim($_POST['nom_client']),
            'prenom_client'   => trim($_POST['prenom_client']),
            'telephone'       => trim($_POST['telephone']),
            'email'           => trim($_POST['email']),
            'adresse'         => trim($_POST['adresse']),
            'remise_speciale' => floatval($_POST['remise_speciale'] ?? 0),
            'notes'           => trim($_POST['notes']),
            'id_agence'       => $_SESSION['id_agence'] ?? null // Agence de l'utilisateur connecté
        ];

        // Validation minimale
        if (empty($donnees['nom_client']) || empty($donnees['telephone'])) {
            throw new Exception("Le nom et le téléphone sont obligatoires.");
        }

        // Appel de la fonction d'insertion (à créer dans gestion_clients.php)
        $resultat = ajouterNouveauClient($pdo, $donnees);

        if ($resultat) {
            header('Location: liste_clients.php?msg=success');
            exit();
        }
    } catch (Exception $e) {
        $message = "Erreur lors de l'ajout : " . $e->getMessage();
        $message_type = 'danger';
    }
}

include '../../templates/header.php';
include '../../templates/navigation.php';

?>
</BR> </BR>
<div class="container-fluid" style="margin-top: 20px;">
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default shadow-sm">
                <div class="panel-heading" style="background-color: #2c3e50; color: white;">
                    <h3 class="panel-title"> Enregistrer un Nouveau Client</h3>
                </div>
                <div class="panel-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <legend style="font-size: 1.2em; color: #3498db;">Identité</legend>
                                <div class="form-group">
                                    <label>Nom du Client *</label>
                                    <input type="text" name="nom_client" class="form-control" placeholder="Ex: DUPONT" required>
                                </div>
                                <div class="form-group">
                                    <label>Prénom(s)</label>
                                    <input type="text" name="prenom_client" class="form-control" placeholder="Ex: Jean Luc">
                                </div>
                                <div class="form-group">
                                    <label>Remise Spéciale (%)</label>
                                    <input type="number" name="remise_speciale" class="form-control" step="0.01" value="0">
                                    <small class="text-muted">S'appliquera automatiquement sur ses tickets.</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <legend style="font-size: 1.2em; color: #3498db;">Contact & Localisation</legend>
                                <div class="form-group">
                                    <label>Téléphone *</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"></span>
                                        <input type="text" name="telephone" class="form-control" placeholder="Ex: 06000000" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" placeholder="client@exemple.com">
                                </div>
                                <div class="form-group">
                                    <label>Adresse Résidentielle</label>
                                    <textarea name="adresse" class="form-control" rows="1" placeholder="Quartier, Rue..."></textarea>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group">
                            <label>Notes / Observations (Allergies, Préférences...)</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Ex: Fragile avec le lin, demande toujours un pliage spécifique..."></textarea>
                        </div>

                        <div class="text-right">
                            <a href="liste_clients.php" class="btn btn-default">Annuler</a>
                            <button type="submit" name="enregistrer_client" class="btn btn-primary">
                                 Enregistrer le Client
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="panel panel-info">
                <div class="panel-heading">Astuces Pressing</div>
                <div class="panel-body">
                    <h4>Programme Fidélité</h4>
                    <p>Par défaut, chaque nouveau client commence avec <strong>0 point</strong>.</p>
                    <ul class="list-unstyled">
                        <li> Attribution automatique de points.</li>
                        <li> Notification SMS si option activée.</li>
                    </ul>
                    <hr>
                    <div class="alert alert-warning">
                        <small>Les champs marqués d'une <strong>*</strong> sont obligatoires pour la facturation.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>