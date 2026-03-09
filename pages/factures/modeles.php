<?php
/**
 * pages/admin/factures/modeles.php
 * Gestion des modèles de factures - CRUD complet
 */

// 1. Initialisation et sécurité
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['utilisateur_id']) || !isset($_SESSION['role'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérification des permissions (admin uniquement)
$allowed_roles = ['admin', 'directeur', 'patron'];
if (!in_array(strtolower($_SESSION['role']), $allowed_roles)) {
    header('Location: ../../tableau_bord.php');
    exit();
}

// 2. Configuration
$titre = "Gestion des Modèles de Factures";
$current_page = 'modeles_factures';
$user_id = $_SESSION['utilisateur_id'];
$user_name = $_SESSION['nom_complet'];

// 3. Inclusions
require_once realpath(__DIR__ . '/../../../fonctions/database.php');
require_once realpath(__DIR__ . '/../../../fonctions/facturation.php');

// 4. Gestion des actions
$action = $_GET['action'] ?? 'liste';
$modele_id = $_GET['id'] ?? 0;

// Traitement des formulaires POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once realpath(__DIR__ . '/../../../fonctions/gestion_modeles.php');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'creer_modele':
                $result = creerModeleFacture($_POST, $user_id);
                if ($result['success']) {
                    $_SESSION['success_message'] = "Modèle créé avec succès !";
                    header('Location: modeles.php?action=editer&id=' . $result['id_modele']);
                    exit();
                } else {
                    $error_message = $result['message'];
                }
                break;
                
            case 'modifier_modele':
                $result = modifierModeleFacture($_POST, $modele_id, $user_id);
                if ($result['success']) {
                    $_SESSION['success_message'] = "Modèle modifié avec succès !";
                    header('Location: modeles.php?action=editer&id=' . $modele_id);
                    exit();
                } else {
                    $error_message = $result['message'];
                }
                break;
                
            case 'dupliquer_modele':
                $result = dupliquerModeleFacture($modele_id, $user_id);
                if ($result['success']) {
                    $_SESSION['success_message'] = "Modèle dupliqué avec succès !";
                    header('Location: modeles.php');
                    exit();
                } else {
                    $error_message = $result['message'];
                }
                break;
                
            case 'supprimer_modele':
                $result = supprimerModeleFacture($modele_id);
                if ($result['success']) {
                    $_SESSION['success_message'] = "Modèle supprimé avec succès !";
                    header('Location: modeles.php');
                    exit();
                } else {
                    $error_message = $result['message'];
                }
                break;
        }
    }
}

// 5. Récupération des données selon l'action
try {
    switch ($action) {
        case 'creer':
            // Récupération des paramètres pour le nouveau modèle
            $types_facture = $pdo->query("SELECT * FROM types_facture WHERE actif = 1 ORDER BY nom")->fetchAll();
            $entreprises = $pdo->query("SELECT * FROM entreprises WHERE est_actif = 1 ORDER BY nom")->fetchAll();
            $devises = $pdo->query("SELECT * FROM devises WHERE actif = 1 ORDER BY code")->fetchAll();
            break;
            
        case 'editer':
            $modele_id = (int)$_GET['id'];
            
            // Récupération du modèle
            $stmt = $pdo->prepare("SELECT m.*, t.nom as type_facture_nom, e.nom as entreprise_nom 
                                  FROM modeles_facture m
                                  LEFT JOIN types_facture t ON m.id_type_facture = t.id_type
                                  LEFT JOIN entreprises e ON m.id_entreprise = e.id_entreprise
                                  WHERE m.id_modele = :id");
            $stmt->execute([':id' => $modele_id]);
            $modele = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$modele) {
                $error_message = "Modèle non trouvé";
                break;
            }
            
            // Récupération des sections du modèle
            $stmt = $pdo->prepare("SELECT * FROM sections_modele 
                                  WHERE id_modele = :id_modele 
                                  ORDER BY ordre ASC");
            $stmt->execute([':id_modele' => $modele_id]);
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupération des champs variables
            $stmt = $pdo->prepare("SELECT * FROM champs_modele 
                                  WHERE id_modele = :id_modele 
                                  ORDER BY position_section ASC, ordre ASC");
            $stmt->execute([':id_modele' => $modele_id]);
            $champs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $types_facture = $pdo->query("SELECT * FROM types_facture WHERE actif = 1 ORDER BY nom")->fetchAll();
            $entreprises = $pdo->query("SELECT * FROM entreprises WHERE est_actif = 1 ORDER BY nom")->fetchAll();
            $devises = $pdo->query("SELECT * FROM devises WHERE actif = 1 ORDER BY code")->fetchAll();
            break;
            
        case 'apercu':
            $modele_id = (int)$_GET['id'];
            
            // Récupération du modèle
            $stmt = $pdo->prepare("SELECT * FROM modeles_facture WHERE id_modele = :id");
            $stmt->execute([':id' => $modele_id]);
            $modele = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$modele) {
                $error_message = "Modèle non trouvé";
                break;
            }
            
            // Données de test pour l'aperçu
            $donnees_test = [
                'entreprise' => [
                    'nom' => 'MON ENTREPRISE SARL',
                    'adresse' => '123 Avenue des Champs-Élysées',
                    'ville' => '75008 Paris',
                    'telephone' => '01 23 45 67 89',
                    'email' => 'contact@monentreprise.com',
                    'site_web' => 'www.monentreprise.com',
                    'siret' => '123 456 789 00012',
                    'tva_intra' => 'FR12345678901'
                ],
                'client' => [
                    'nom' => 'DUPONT SA',
                    'adresse' => '456 Rue de la République',
                    'ville' => '69001 Lyon',
                    'telephone' => '04 12 34 56 78',
                    'email' => 'compta@dupont.fr'
                ],
                'facture' => [
                    'numero' => 'FAC-2024-001',
                    'date' => date('d/m/Y'),
                    'date_echeance' => date('d/m/Y', strtotime('+30 days')),
                    'reference_commande' => 'CMD-2024-123',
                    'conditions_paiement' => '30 jours net'
                ],
                'lignes' => [
                    [
                        'description' => 'Service de nettoyage pressing',
                        'quantite' => 10,
                        'prix_unitaire' => 125.50,
                        'taux_tva' => 20,
                        'total_ht' => 1255.00
                    ],
                    [
                        'description' => 'Produit détergent professionnel',
                        'quantite' => 5,
                        'prix_unitaire' => 89.90,
                        'taux_tva' => 20,
                        'total_ht' => 449.50
                    ],
                    [
                        'description' => 'Maintenance machine à laver',
                        'quantite' => 1,
                        'prix_unitaire' => 350.00,
                        'taux_tva' => 20,
                        'total_ht' => 350.00
                    ]
                ],
                'totaux' => [
                    'total_ht' => 2054.50,
                    'total_tva' => 410.90,
                    'total_ttc' => 2465.40,
                    'acompte' => 0,
                    'net_a_payer' => 2465.40
                ]
            ];
            break;
            
        case 'importer':
            // Récupération des modèles prédéfinis
            $modeles_predefinis = [
                ['nom' => 'Facture Standard', 'description' => 'Modèle basique avec en-tête et pied de page'],
                ['nom' => 'Facture Professionnelle', 'description' => 'Design moderne avec logo et détails'],
                ['nom' => 'Facture Simplifiée', 'description' => 'Version minimaliste pour les petites factures'],
                ['nom' => 'Facture avec TVA', 'description' => 'Inclut le détail des taux de TVA'],
                ['nom' => 'Facture Bilingue', 'description' => 'Français/Anglais pour les clients internationaux']
            ];
            break;
            
        default: // 'liste'
            // Récupération de tous les modèles
            $stmt = $pdo->prepare("SELECT m.*, 
                                  t.nom as type_facture_nom,
                                  e.nom as entreprise_nom,
                                  u.nom_complet as createur_nom,
                                  COUNT(f.id_facture) as nb_factures
                                  FROM modeles_facture m
                                  LEFT JOIN types_facture t ON m.id_type_facture = t.id_type
                                  LEFT JOIN entreprises e ON m.id_entreprise = e.id_entreprise
                                  LEFT JOIN utilisateurs u ON m.created_by = u.id_utilisateur
                                  LEFT JOIN factures f ON m.id_modele = f.id_modele
                                  GROUP BY m.id_modele
                                  ORDER BY m.est_par_defaut DESC, m.nom ASC");
            $stmt->execute();
            $modeles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Statistiques
            $stats = $pdo->query("SELECT 
                                 COUNT(*) as total_modeles,
                                 SUM(est_actif) as modeles_actifs,
                                 SUM(est_par_defaut) as modeles_defaut
                                 FROM modeles_facture")->fetch(PDO::FETCH_ASSOC);
            break;
    }
} catch (PDOException $e) {
    error_log("Erreur PDO dans modeles.php: " . $e->getMessage());
    $error_message = "Erreur de base de données. Veuillez réessayer.";
}

// 6. Inclusions des templates
require_once realpath(__DIR__ . '/../../../templates/header.php');
require_once realpath(__DIR__ . '/../../../templates/navigation.php');
?>

<style>
:root {
    --primary-color: #2c3e50;
    --secondary-color: #34495e;
    --accent-color: #3498db;
    --success-color: #27ae60;
    --warning-color: #f39c12;
    --danger-color: #e74c3c;
    --light-color: #f8f9fa;
    --sidebar-width: 250px;
}

.dashboard-wrapper {
    margin-left: var(--sidebar-width);
    padding: 2rem 1.5rem;
    min-height: 100vh;
    background-color: var(--light-color);
    transition: all 0.3s ease;
    width: calc(100% - var(--sidebar-width));
}

.page-header {
    border-bottom: 0.3rem solid var(--primary-color);
    padding-bottom: 1.2rem;
    margin-bottom: 2rem;
    color: var(--primary-color);
    font-weight: 600;
}

.card-modele {
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.3s;
    margin-bottom: 1.5rem;
}

.card-modele:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.card-header-modele {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border-radius: 0.75rem 0.75rem 0 0 !important;
    padding: 1rem 1.5rem;
    font-weight: 600;
}

.badge-modele {
    padding: 0.4rem 0.8rem;
    border-radius: 2rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-defaut { background-color: var(--success-color); color: white; }
.badge-actif { background-color: var(--accent-color); color: white; }
.badge-inactif { background-color: #95a5a6; color: white; }

.btn-modele {
    border-radius: 0.5rem;
    font-weight: 500;
    padding: 0.5rem 1.5rem;
    transition: all 0.3s;
}

.btn-modele-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border: none;
}

.btn-modele-primary:hover {
    background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.section-modele {
    border: 1px solid #e0e0e0;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
    background: white;
    cursor: move;
}

.section-modele:hover {
    border-color: var(--accent-color);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e0e0e0;
}

.champ-modele {
    background: #f8f9fa;
    border: 1px dashed #ced4da;
    border-radius: 0.25rem;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    cursor: move;
}

.champ-modele:hover {
    border-color: var(--accent-color);
    background: #e3f2fd;
}

.preview-container {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 0.5rem;
    padding: 2rem;
    min-height: 800px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.preview-header {
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 1rem;
    margin-bottom: 2rem;
}

.preview-footer {
    border-top: 2px solid var(--primary-color);
    padding-top: 1rem;
    margin-top: 2rem;
}

.parametre-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-bottom: 1px solid #e0e0e0;
}

.parametre-item:last-child {
    border-bottom: none;
}

.stats-card {
    text-align: center;
    padding: 1.5rem;
    border-radius: 0.75rem;
    background: white;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.stats-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stats-label {
    color: var(--secondary-color);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05rem;
}

@media (max-width: 992px) {
    .dashboard-wrapper {
        margin-left: 0;
        width: 100%;
        padding: 1rem;
    }
}
</style>

<div class="dashboard-wrapper">
    <div class="container-fluid">
        
        <!-- En-tête de page -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-header mb-2"><?= htmlspecialchars($titre) ?></h2>
                <p class="text-muted mb-0">
                    <i class="fas fa-file-invoice me-2"></i>Créez et gérez vos modèles de factures personnalisés
                </p>
            </div>
            <div class="d-flex gap-2">
                <?php if ($action === 'liste'): ?>
                    <a href="modeles.php?action=creer" class="btn btn-modele btn-modele-primary">
                        <i class="fas fa-plus-circle me-2"></i>Nouveau Modèle
                    </a>
                    <a href="modeles.php?action=importer" class="btn btn-modele btn-outline-primary">
                        <i class="fas fa-download me-2"></i>Importer un modèle
                    </a>
                <?php elseif ($action === 'creer' || $action === 'editer'): ?>
                    <button type="button" class="btn btn-modele btn-outline-primary" onclick="afficherApercu()">
                        <i class="fas fa-eye me-2"></i>Aperçu
                    </button>
                    <button type="button" class="btn btn-modele btn-success" onclick="sauvegarderModele()">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                    <a href="modeles.php" class="btn btn-modele btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Annuler
                    </a>
                <?php elseif ($action === 'apercu'): ?>
                    <button type="button" class="btn btn-modele btn-outline-primary" onclick="imprimerApercu()">
                        <i class="fas fa-print me-2"></i>Imprimer
                    </button>
                    <a href="modeles.php?action=editer&id=<?= $modele_id ?>" class="btn btn-modele btn-primary">
                        <i class="fas fa-edit me-2"></i>Modifier
                    </a>
                    <a href="modeles.php" class="btn btn-modele btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Messages de notification -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Contenu selon l'action -->
        <?php switch ($action): 
            case 'creer': ?>
                <!-- Création d'un nouveau modèle -->
                <?php include 'includes/modeles/form_creation.php'; ?>
                <?php break; ?>
                
            <?php case 'editer': ?>
                <!-- Édition d'un modèle existant -->
                <?php include 'includes/modeles/form_edition.php'; ?>
                <?php break; ?>
                
            <?php case 'apercu': ?>
                <!-- Aperçu du modèle -->
                <?php include 'includes/modeles/apercu_modele.php'; ?>
                <?php break; ?>
                
            <?php case 'importer': ?>
                <!-- Importation de modèles -->
                <?php include 'includes/modeles/import_modele.php'; ?>
                <?php break; ?>
                
            <?php default: ?>
                <!-- Liste des modèles (par défaut) -->
                <?php include 'includes/modeles/liste_modeles.php'; ?>
                <?php break; ?>
        <?php endswitch; ?>
        
    </div>
</div>

<!-- Scripts JavaScript -->
<script src="../../../js/jquery-3.7.1.js"></script>
<script src="../../../js/bootstrap.bundle.min.js"></script>
<script src="../../../js/sortable.min.js"></script>

<script>
$(document).ready(function() {
    // Auto-hide les alertes après 5 secondes
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
    
    // Initialisation des tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Initialisation de Sortable pour le drag & drop
    if (typeof Sortable !== 'undefined') {
        // Sections
        if (document.getElementById('listeSections')) {
            new Sortable(document.getElementById('listeSections'), {
                group: 'sections',
                animation: 150,
                handle: '.section-header',
                onEnd: function() {
                    mettreAJourOrdreSections();
                }
            });
        }
        
        // Champs
        const listesChamps = document.querySelectorAll('.liste-champs');
        listesChamps.forEach(liste => {
            new Sortable(liste, {
                group: 'champs',
                animation: 150,
                handle: '.champ-modele',
                onEnd: function() {
                    mettreAJourOrdreChamps();
                }
            });
        });
        
        // Palette de champs
        if (document.getElementById('paletteChamps')) {
            new Sortable(document.getElementById('paletteChamps'), {
                group: {
                    name: 'champs',
                    pull: 'clone',
                    put: false
                },
                animation: 150,
                sort: false
            });
        }
    }
});

// Fonction pour afficher un aperçu
function afficherApercu() {
    const modeleId = <?= $modele_id ?? 'null' ?>;
    if (modeleId) {
        window.open('modeles.php?action=apercu&id=' + modeleId, '_blank');
    } else {
        alert('Veuillez d\'abord sauvegarder le modèle');
    }
}

// Fonction pour imprimer l'aperçu
function imprimerApercu() {
    window.print();
}

// Mettre à jour l'ordre des sections
function mettreAJourOrdreSections() {
    const sections = [];
    $('#listeSections .section-modele').each(function(index) {
        const sectionId = $(this).data('section-id') || 'new-' + index;
        sections.push({
            id: sectionId,
            ordre: index + 1
        });
    });
    
    // Envoyer l'ordre au serveur
    $.ajax({
        url: 'ajax/update_ordre_sections.php',
        type: 'POST',
        data: { sections: sections },
        success: function(response) {
            if (!response.success) {
                console.error('Erreur mise à jour ordre sections:', response.message);
            }
        }
    });
}

// Mettre à jour l'ordre des champs
function mettreAJourOrdreChamps() {
    const champs = [];
    $('.liste-champs .champ-modele').each(function(index) {
        const champId = $(this).data('champ-id') || 'new-' + index;
        const sectionId = $(this).closest('.section-modele').data('section-id');
        champs.push({
            id: champId,
            section_id: sectionId,
            ordre: index + 1
        });
    });
    
    // Envoyer l'ordre au serveur
    $.ajax({
        url: 'ajax/update_ordre_champs.php',
        type: 'POST',
        data: { champs: champs },
        success: function(response) {
            if (!response.success) {
                console.error('Erreur mise à jour ordre champs:', response.message);
            }
        }
    });
}

// Ajouter une nouvelle section
function ajouterSection() {
    const sectionId = 'section-' + Date.now();
    const sectionHTML = `
        <div class="section-modele" data-section-id="${sectionId}">
            <div class="section-header">
                <div>
                    <input type="text" class="form-control form-control-sm d-inline-block w-auto" 
                           name="sections[${sectionId}][nom]" placeholder="Nom de la section" value="Nouvelle Section">
                    <select class="form-control form-control-sm d-inline-block w-auto ms-2" 
                            name="sections[${sectionId}][type]">
                        <option value="header">En-tête</option>
                        <option value="client">Client</option>
                        <option value="produits">Produits/Services</option>
                        <option value="totaux">Totaux</option>
                        <option value="footer">Pied de page</option>
                        <option value="notes">Notes</option>
                    </select>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="ajouterChamp('${sectionId}')">
                        <i class="fas fa-plus"></i> Champ
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="supprimerSection(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="liste-champs" data-section-id="${sectionId}">
                <!-- Les champs seront ajoutés ici -->
            </div>
        </div>
    `;
    
    $('#listeSections').append(sectionHTML);
    
    // Réinitialiser Sortable
    if (typeof Sortable !== 'undefined') {
        new Sortable(document.querySelector(`[data-section-id="${sectionId}"] .liste-champs`), {
            group: 'champs',
            animation: 150,
            handle: '.champ-modele',
            onEnd: function() {
                mettreAJourOrdreChamps();
            }
        });
    }
}

// Supprimer une section
function supprimerSection(button) {
    if (confirm('Supprimer cette section ?')) {
        $(button).closest('.section-modele').remove();
        mettreAJourOrdreSections();
    }
}

// Ajouter un champ
function ajouterChamp(sectionId) {
    const champId = 'champ-' + Date.now();
    const champHTML = `
        <div class="champ-modele" data-champ-id="${champId}">
            <div class="d-flex justify-content-between align-items-center">
                <div class="flex-grow-1 me-2">
                    <select class="form-control form-control-sm" name="champs[${champId}][type]">
                        <option value="texte">Texte</option>
                        <option value="nombre">Nombre</option>
                        <option value="date">Date</option>
                        <option value="montant">Montant</option>
                        <option value="tva">TVA</option>
                        <option value="total">Total</option>
                        <option value="separateur">Séparateur</option>
                        <option value="logo">Logo</option>
                        <option value="qrcode">QR Code</option>
                    </select>
                    <input type="text" class="form-control form-control-sm mt-1" 
                           name="champs[${champId}][label]" placeholder="Label">
                    <input type="text" class="form-control form-control-sm mt-1" 
                           name="champs[${champId}][valeur]" placeholder="Valeur ou variable">
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="supprimerChamp(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    $(`[data-section-id="${sectionId}"] .liste-champs`).append(champHTML);
}

// Supprimer un champ
function supprimerChamp(button) {
    $(button).closest('.champ-modele').remove();
    mettreAJourOrdreChamps();
}

// Sauvegarder le modèle
function sauvegarderModele() {
    const form = $('#formModele');
    const action = form.data('action');
    
    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }
    
    // Collecter les données
    const formData = new FormData(form[0]);
    
    // Ajouter les sections et champs
    const sections = [];
    $('.section-modele').each(function() {
        const sectionId = $(this).data('section-id');
        sections.push({
            id: sectionId,
            nom: $(this).find('[name*="[nom]"]').val(),
            type: $(this).find('[name*="[type]"]').val(),
            champs: []
        });
    });
    
    const champs = [];
    $('.champ-modele').each(function() {
        const champId = $(this).data('champ-id');
        const sectionId = $(this).closest('.section-modele').data('section-id');
        champs.push({
            id: champId,
            section_id: sectionId,
            type: $(this).find('[name*="[type]"]').val(),
            label: $(this).find('[name*="[label]"]').val(),
            valeur: $(this).find('[name*="[valeur]"]').val()
        });
    });
    
    formData.append('sections', JSON.stringify(sections));
    formData.append('champs', JSON.stringify(champs));
    
    // Afficher le chargement
    const btn = $('button[onclick="sauvegarderModele()"]');
    const originalHtml = btn.html();
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Sauvegarde...');
    
    // Envoyer la requête
    $.ajax({
        url: 'ajax/' + action + '_modele.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                window.location.href = 'modeles.php?action=editer&id=' + response.id_modele;
            } else {
                alert('Erreur: ' + response.message);
                btn.prop('disabled', false).html(originalHtml);
            }
        },
        error: function() {
            alert('Erreur de connexion au serveur');
            btn.prop('disabled', false).html(originalHtml);
        }
    });
}

// Définir comme modèle par défaut
function setModeleDefaut(modeleId) {
    if (confirm('Définir ce modèle comme modèle par défaut ?')) {
        $.ajax({
            url: 'ajax/set_modele_defaut.php',
            type: 'POST',
            data: { id_modele: modeleId },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + response.message);
                }
            }
        });
    }
}

// Tester le modèle
function testerModele(modeleId) {
    window.open('factures.php?action=creer&modele=' + modeleId + '&test=1', '_blank');
}

// Dupliquer le modèle
function dupliquerModele(modeleId) {
    if (confirm('Dupliquer ce modèle ?')) {
        $.ajax({
            url: 'ajax/dupliquer_modele.php',
            type: 'POST',
            data: { id_modele: modeleId },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + response.message);
                }
            }
        });
    }
}

// Supprimer le modèle
function supprimerModele(modeleId) {
    if (confirm('Supprimer définitivement ce modèle ?')) {
        $.ajax({
            url: 'ajax/supprimer_modele.php',
            type: 'POST',
            data: { id_modele: modeleId },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + response.message);
                }
            }
        });
    }
}
</script>

<?php 
require_once realpath(__DIR__ . '/../../../templates/footer.php');
?>