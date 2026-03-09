<?php
// pages/admin/tarifs/activites.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Connexion à la base de données (Assurez-vous que le chemin est correct)
require_once '../../../fonctions/database.php';

// Vérifier les permissions
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['patron', 'admin', 'gestionnaire_hotel', 'gestionnaire_commerce'])) {
    header('Location: ../../../pages/acces_refuse.php');
    exit();
}

$message = '';
$message_type = '';

/**
 * Fonction de création des tables avec correction des clés étrangères (InnoDB + Types identiques)
 */
function createTarifsTables($pdo) {
    try {
        // 1. Table des catégories
        $sql1 = "CREATE TABLE IF NOT EXISTS tarif_categories (
            id_categorie INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            code_categorie VARCHAR(50) NOT NULL,
            nom_categorie VARCHAR(100) NOT NULL,
            description TEXT,
            type_activite VARCHAR(20) DEFAULT 'all',
            ordre_affichage INT DEFAULT 0,
            actif BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_categorie (code_categorie, type_activite)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql1);

        // 2. Table des tarifs par activité
        $sql2 = "CREATE TABLE IF NOT EXISTS tarifs_activites (
            id_tarif INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            type_activite VARCHAR(20) NOT NULL,
            code_tarif VARCHAR(50) NOT NULL,
            nom_tarif VARCHAR(100) NOT NULL,
            description TEXT,
            id_categorie INT(11) UNSIGNED,
            prix_unitaire DECIMAL(10,2) NOT NULL,
            prix_membre DECIMAL(10,2),
            prix_urgent DECIMAL(10,2),
            tva_percent DECIMAL(5,2) DEFAULT 19.25,
            devise VARCHAR(3) DEFAULT 'XAF',
            unite_mesure VARCHAR(20) DEFAULT 'piece',
            delai_min INT DEFAULT 24,
            delai_max INT DEFAULT 72,
            actif BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_tarif_categorie FOREIGN KEY (id_categorie) REFERENCES tarif_categories(id_categorie) ON DELETE SET NULL,
            UNIQUE KEY unique_activite_tarif (type_activite, code_tarif)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql2);

        // 3. Table des historiques (C'est ici que l'erreur 150 se produisait)
        $sql3 = "CREATE TABLE IF NOT EXISTS tarif_historique (
            id_historique INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            id_tarif INT(11) UNSIGNED NOT NULL,
            ancien_prix DECIMAL(10,2),
            nouveau_prix DECIMAL(10,2),
            date_changement DATETIME DEFAULT CURRENT_TIMESTAMP,
            id_utilisateur INT(11),
            motif TEXT,
            CONSTRAINT fk_historique_tarif FOREIGN KEY (id_tarif) REFERENCES tarifs_activites(id_tarif) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql3);

        // Insertion des catégories par défaut
        $check = $pdo->query("SELECT COUNT(*) FROM tarif_categories")->fetchColumn();
        if ($check == 0) {
            $default_categories = [
                ['LAVAGE', 'Lavage', 'pressing', 1],
                ['REPASSAGE', 'Repassage', 'pressing', 2],
                ['NETTOYAGE', 'Nettoyage à sec', 'pressing', 3],
                ['TEXTILES', 'Textiles', 'commerce', 1],
                ['CHAMBRE', 'Services Chambre', 'hotel', 1]
            ];
            $stmt = $pdo->prepare("INSERT INTO tarif_categories (code_categorie, nom_categorie, type_activite, ordre_affichage) VALUES (?, ?, ?, ?)");
            foreach ($default_categories as $cat) { $stmt->execute($cat); }
        }
    } catch (PDOException $e) {
        throw new Exception("Erreur SQL lors de la création des tables : " . $e->getMessage());
    }
}

// Initialisation des données
try {
    createTarifsTables($pdo);
    
    // Traitement POST (Ajout/Modification/Suppression)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'ajouter_tarif':
                $stmt = $pdo->prepare("INSERT INTO tarifs_activites (type_activite, code_tarif, nom_tarif, description, id_categorie, prix_unitaire, tva_percent, devise, unite_mesure, delai_min, delai_max, actif) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    $_POST['type_activite'], $_POST['code_tarif'], $_POST['nom_tarif'], $_POST['description'], 
                    !empty($_POST['id_categorie']) ? $_POST['id_categorie'] : null,
                    $_POST['prix_unitaire'], $_POST['tva_percent'], $_POST['devise'], $_POST['unite_mesure'],
                    $_POST['delai_min'], $_POST['delai_max'], isset($_POST['actif']) ? 1 : 0
                ]);
                $message = "Tarif créé avec succès.";
                $message_type = "success";
                break;
                
            case 'modifier_tarif':
                $id = intval($_POST['id_tarif']);
                // Logique d'historisation avant update
                $old = $pdo->prepare("SELECT prix_unitaire FROM tarifs_activites WHERE id_tarif = ?");
                $old->execute([$id]);
                $oldPrice = $old->fetchColumn();
                
                if ($oldPrice != $_POST['prix_unitaire']) {
                    $hist = $pdo->prepare("INSERT INTO tarif_historique (id_tarif, ancien_prix, nouveau_prix, id_utilisateur, motif) VALUES (?, ?, ?, ?, ?)");
                    $hist->execute([$id, $oldPrice, $_POST['prix_unitaire'], $_SESSION['utilisateur_id'] ?? null, $_POST['motif_changement'] ?? 'Mise à jour manuelle']);
                }

                $stmt = $pdo->prepare("UPDATE tarifs_activites SET nom_tarif=?, description=?, id_categorie=?, prix_unitaire=?, actif=? WHERE id_tarif=?");
                $stmt->execute([$_POST['nom_tarif'], $_POST['description'], $_POST['id_categorie'], $_POST['prix_unitaire'], isset($_POST['actif']) ? 1 : 0, $id]);
                $message = "Tarif mis à jour.";
                $message_type = "success";
                break;

            case 'supprimer_tarif':
                $stmt = $pdo->prepare("DELETE FROM tarifs_activites WHERE id_tarif = ?");
                $stmt->execute([$_POST['id_tarif']]);
                $message = "Tarif supprimé.";
                $message_type = "success";
                break;
        }
    }

    // Récupération des données pour l'affichage
    $activites = $pdo->query("SELECT * FROM activites_config WHERE actif = 1 AND type_activite != 'all' ORDER BY ordre_affichage")->fetchAll(PDO::FETCH_ASSOC);
    $categories = $pdo->query("SELECT * FROM tarif_categories WHERE actif = 1 ORDER BY ordre_affichage")->fetchAll(PDO::FETCH_ASSOC);
    $tarifs = $pdo->query("SELECT t.*, c.nom_categorie FROM tarifs_activites t LEFT JOIN tarif_categories c ON t.id_categorie = c.id_categorie ORDER BY t.type_activite, t.nom_tarif")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $message = $e->getMessage();
    $message_type = 'danger';
}

include '../../../templates/header.php';
include '../../../templates/navigation.php';
?>

<br><br><br>
<div class="container-fluid">
    <div class="panel panel-default border-0 shadow-sm">
        <div class="panel-heading bg-dark text-white d-flex justify-content-between align-items-center" style="padding: 15px;">
            <h3 class="m-0"> Tarification par Activité</h3>
            <a href="javascript:history.back()" class="btn btn-sm btn-outline-light"> Retour</a>
        </div>
        
        <div class="panel-body p-4">
            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <ul class="nav nav-tabs mb-4">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#liste">Liste des Tarifs</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#ajouter" onclick="resetForm()">Nouveau Tarif</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#inflation">Gestion des Prix</a></li>
            </ul>

            <div class="tab-content">
                <div id="liste" class="tab-pane fade show active">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Activité</th>
                                    <th>Code</th>
                                    <th>Désignation</th>
                                    <th>Catégorie</th>
                                    <th>Prix Unitaire</th>
                                    <th>Statut</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tarifs as $t): ?>
                                <tr>
                                    <td><span class="activity-badge badge-<?= $t['type_activite'] ?>"><?= $t['type_activite'] ?></span></td>
                                    <td class="fw-bold"><?= $t['code_tarif'] ?></td>
                                    <td><?= htmlspecialchars($t['nom_tarif'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($t['nom_categorie'] ?? 'N/A') ?></td>
                                    <td class="fw-bold text-primary"><?= number_format($t['prix_unitaire'], 0, ',', ' ') ?> XAF</td>
                                    <td><?= $t['actif'] ? '<span class="text-success">Actif</span>' : '<span class="text-danger">Inactif</span>' ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-info" onclick="editTarif(<?= htmlspecialchars(json_encode($t)) ?>)">
                                          editer
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $t['id_tarif'] ?>, '<?= addslashes($t['nom_tarif']) ?>')">
                                           Supprimer
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="ajouter" class="tab-pane fade">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" id="form_action" value="ajouter_tarif">
                        <input type="hidden" name="id_tarif" id="edit_id_tarif">
                        
                        <div class="col-md-4">
                            <label class="form-label">Type d'activité</label>
                            <select name="type_activite" id="edit_type_activite" class="form-select" required>
                                <?php foreach($activites as $a): ?>
                                    <option value="<?= $a['type_activite'] ?>"><?= $a['nom_activite'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Code Tarif</label>
                            <input type="text" name="code_tarif" id="edit_code_tarif" class="form-control" placeholder="Ex: CHEMISE_L" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nom du Tarif</label>
                            <input type="text" name="nom_tarif" id="edit_nom_tarif" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Catégorie</label>
                            <select name="id_categorie" id="edit_id_categorie" class="form-select">
                                <option value="">-- Aucune --</option>
                                <?php foreach($categories as $c): ?>
                                    <option value="<?= $c['id_categorie'] ?>"><?= $c['nom_categorie'] ?> (<?= $c['type_activite'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prix Unitaire (XAF)</label>
                            <input type="number" step="0.01" name="prix_unitaire" id="edit_prix_unitaire" class="form-control" required>
                        </div>
                        <div class="col-12" id="motif_changement_group" style="display:none;">
                            <label class="form-label text-warning fw-bold">Motif du changement de prix</label>
                            <textarea name="motif_changement" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="actif" id="edit_actif" checked>
                                <label class="form-check-label">Tarif actif</label>
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" id="submit_button" class="btn btn-primary px-5">Enregistrer</button>
                            <button type="button" class="btn btn-secondary" onclick="openTab('liste')">Annuler</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction pour basculer entre onglets (Version Bootstrap)
function openTab(id) {
    var triggerEl = document.querySelector('a[href="#' + id + '"]');
    if(triggerEl) bootstrap.Tab.getOrCreateInstance(triggerEl).show();
}

function editTarif(data) {
    document.getElementById('form_title').textContent = 'Modifier le Tarif';
    document.getElementById('form_action').value = 'modifier_tarif';
    document.getElementById('edit_id_tarif').value = data.id_tarif;
    document.getElementById('edit_type_activite').value = data.type_activite;
    document.getElementById('edit_code_tarif').value = data.code_tarif;
    document.getElementById('edit_nom_tarif').value = data.nom_tarif;
    document.getElementById('edit_id_categorie').value = data.id_categorie;
    document.getElementById('edit_prix_unitaire').value = data.prix_unitaire;
    document.getElementById('edit_actif').checked = data.actif == 1;
    
    document.getElementById('motif_changement_group').style.display = 'block';
    openTab('ajouter');
}

function resetForm() {
    document.getElementById('form_action').value = 'ajouter_tarif';
    document.getElementById('motif_changement_group').style.display = 'none';
    document.querySelector('#ajouter form').reset();
}

function confirmDelete(id, nom) {
    if (confirm("Supprimer définitivement le tarif : " + nom + " ?")) {
        const f = document.createElement('form');
        f.method = 'POST';
        f.innerHTML = `<input type="hidden" name="action" value="supprimer_tarif"><input type="hidden" name="id_tarif" value="${id}">`;
        document.body.appendChild(f);
        f.submit();
    }
}
</script>

<?php require_once '../../../templates/footer.php'; ?>