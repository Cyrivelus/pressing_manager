<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Configuration des Notifications";

// Simulation des paramètres actuels en base de données
$config = [
    'sms_enabled' => true,
    'email_enabled' => true,
    'whatsapp_enabled' => false,
    'delai_rappel_auto' => 5, // jours
    'heure_envoi_massif' => '09:00'
];

require_once $root . '/templates/header.php';
require_once $root . '/templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-dark"><i class="fas fa-cogs text-muted me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Définissez vos règles de communication et vos seuils d'alerte</p>
        </div>
        <button class="btn btn-primary shadow-sm">
            <i class="fas fa-save me-2"></i> Enregistrer les réglages
        </button>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Canaux Activés</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <h6 class="mb-0 fw-bold">Passerelle SMS</h6>
                                <small class="text-muted">Utilisé pour les alertes "Commande Prête"</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" checked>
                            </div>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <h6 class="mb-0 fw-bold">Serveur Email (SMTP)</h6>
                                <small class="text-muted">Utilisé pour les factures et newsletters</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" checked>
                            </div>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <h6 class="mb-0 fw-bold">API WhatsApp Business</h6>
                                <small class="text-muted">Envoi de photos et de PDF (Désactivé)</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Règles d'Automatisme</h6>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="form-label fw-bold small">Délai avant premier rappel (Jours)</label>
                        <input type="range" class="form-range" min="1" max="30" value="<?= $config['delai_rappel_auto'] ?>">
                        <div class="d-flex justify-content-between small text-muted">
                            <span>1 jour</span>
                            <span class="text-primary fw-bold">Actuel : <?= $config['delai_rappel_auto'] ?> jours</span>
                            <span>30 jours</span>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small">Heure d'envoi des rappels massifs</label>
                        <input type="time" class="form-control" value="<?= $config['heure_envoi_massif'] ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100 p-4">
                <h6 class="fw-bold mb-4">Répartition de la Communication</h6>
                
                
                <div class="mt-4 p-3 bg-light rounded-3">
                    <h6 class="fw-bold small text-uppercase">Signature par défaut</h6>
                    <textarea class="form-control form-control-sm mt-2" rows="3">Cordialement, l'équipe du Pressing Pro. 
Localisation : Akwa, Douala. 
Tél : +237 6xx xx xx xx</textarea>
                </div>
                
                <div class="mt-4">
                    <div class="alert alert-info border-0 mb-0">
                        <i class="fas fa-lightbulb me-2"></i> <strong>Conseil :</strong> Évitez d'envoyer des SMS entre 21h et 07h pour respecter la tranquillité de vos clients.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $root . '/templates/footer.php'; ?>