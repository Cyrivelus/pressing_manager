<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$root = realpath(__DIR__ . '/../../');
require_once $root . '/fonctions/database.php';

$titre = "Passerelle SMS & Alertes Directes";

// 1. État du crédit SMS (Simulation via une API externe type Twilio ou Orange SMS)
$solde_sms = 1250; // Nombre de SMS restants

// 2. Récupération des SMS envoyés récemment
$sql = "SELECT n.*, c.nom_client, c.telephone 
        FROM notifications_clients n
        JOIN clients c ON n.id_client = c.id_client
        WHERE n.canal = 'SMS'
        ORDER BY n.date_envoi DESC LIMIT 15";
$historique = $pdo->query($sql)->fetchAll();

require_once  '../../templates/header.php';
require_once  '../../templates/navigation.php';
?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold m-0 text-success"><i class="fas fa-comment-alt me-2"></i><?= $titre ?></h2>
            <p class="text-muted">Communication instantanée avec vos clients</p>
        </div>
        <div class="card border-0 shadow-sm px-3 py-2 bg-light">
            <small class="text-muted fw-bold">SOLDE CRÉDIT :</small>
            <span class="h5 fw-bold mb-0 text-success"><?= $solde_sms ?> SMS</span>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Envoyer un message flash</h6>
                </div>
                <div class="card-body">
                    <form id="smsForm">
                        <div class="mb-3">
                            <label class="form-label">Destinataire</label>
                            <input type="text" class="form-control" placeholder="Nom du client ou numéro...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" rows="4" maxlength="160" id="smsBody" placeholder="Tapez votre message ici..."></textarea>
                            <div class="text-end">
                                <small class="text-muted" id="charCount">0 / 160 (1 SMS)</small>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold">
                            <i class="fas fa-paper-plane me-2"></i>Envoyer maintenant
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100 p-4">
                <h6 class="fw-bold mb-3">Statistiques de délivrabilité</h6>
                
                <div class="row text-center mt-4">
                    <div class="col-4 border-end">
                        <h4 class="fw-bold text-success">99.2%</h4>
                        <small class="text-muted">Délivrés</small>
                    </div>
                    <div class="col-4 border-end">
                        <h4 class="fw-bold text-warning">0.5%</h4>
                        <small class="text-muted">En attente</small>
                    </div>
                    <div class="col-4">
                        <h4 class="fw-bold text-danger">0.3%</h4>
                        <small class="text-muted">Échecs</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Derniers messages envoyés</h6>
            <input type="text" class="form-control form-control-sm w-25" placeholder="Filtrer...">
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th class="ps-4">Client / Numéro</th>
                        <th>Contenu du message</th>
                        <th>Date & Heure</th>
                        <th class="text-center">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($historique as $h): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold"><?= htmlspecialchars($h['nom_client']) ?></div>
                            <small class="text-muted"><?= $h['telephone'] ?></small>
                        </td>
                        <td class="small" style="max-width: 300px;"><?= htmlspecialchars($h['contenu']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($h['date_envoi'])) ?></td>
                        <td class="text-center">
                            <span class="badge bg-success-soft text-success"><i class="fas fa-check-double me-1"></i> Reçu</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Dynamisme du compteur de caractères
    const smsBody = document.getElementById('smsBody');
    const charCount = document.getElementById('charCount');
    
    smsBody.addEventListener('input', function() {
        const len = this.value.length;
        const nbSms = Math.ceil(len / 160) || 1;
        charCount.innerText = `${len} / 160 (${nbSms} SMS)`;
        charCount.className = len > 160 ? 'text-danger' : 'text-muted';
    });
</script>

<?php require_once  '../../templates/footer.php'; ?>