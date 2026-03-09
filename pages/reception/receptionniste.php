<?php

require_once '../../fonctions/database.php';

$agence_id = $_SESSION['id_agence'] ?? 1;
$today = date('Y-m-d');

// Statistiques du jour
$stats_query = $pdo->prepare("
    SELECT 
        COUNT(id_ticket) as total_tickets,
        SUM(CASE WHEN statut IN ('en_attente', 'en_traitement') THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN statut = 'pret' THEN 1 ELSE 0 END) as pret_retrait
    FROM tickets 
    WHERE id_agence = ? AND DATE(date_depot) = ?
");
$stats_query->execute([$agence_id, $today]);
$stats = $stats_query->fetch();

$articles_query = $pdo->prepare("
    SELECT l.*, s.nom_service, t.numero_ticket, t.statut as statut_ticket, t.date_depot, c.nom_client 
    FROM lignes_ticket l
    JOIN tickets t ON l.id_ticket = t.id_ticket
    JOIN services s ON l.id_service = s.id_service
    JOIN clients c ON t.id_client = c.id_client
    WHERE t.id_agence = ? AND t.statut NOT IN ('livre', 'recupere')
    ORDER BY t.date_depot DESC 
");
$articles_query->execute([$agence_id]);
$articles = $articles_query->fetchAll();

require_once '../../templates/header.php';
require_once '../../templates/navigation.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        :root { --dark-navy: #1e293b; --soft-grey: #f8fafc; --border-color: #e2e8f0; --accent: #2563eb; --success: #10b981; }
        body { background-color: var(--soft-grey); font-family: system-ui, sans-serif; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0; }
        .card-stat { background: white; padding: 20px; border-radius: 8px; border: 1px solid var(--border-color); }
        .main-card { background: white; border-radius: 8px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .search-input { width: 100%; padding: 12px 15px; border-radius: 8px; border: 2px solid var(--border-color); outline: none; margin-bottom: 15px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; padding: 12px 20px; text-align: left; font-size: 0.8rem; color: #475569; text-transform: uppercase; }
        td { padding: 12px 20px; border-bottom: 1px solid var(--border-color); }
        .status-select { padding: 5px; border-radius: 4px; border: 1px solid #cbd5e1; font-size: 0.8rem; }
        .badge-ticket { background: #e0e7ff; color: #4338ca; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }

        /* Style de la notification de succès */
        #toast {
            position: fixed; top: 20px; right: 20px; padding: 15px 25px;
            background: var(--success); color: white; border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: none;
            z-index: 1000; font-weight: 600; animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
    </style>
</head>
<body>
<br><br><br><br>

<div id="toast"><br><br><br><br>Statut mis à jour avec succès !</div>

<div class="container" style="max-width: 1100px; margin: 0 auto; padding: 20px;">
    
    <div class="stats-grid">
        <div class="card-stat"><small>REÇUS AUJOURD'HUI</small><h2><?= $stats['total_tickets'] ?? 0 ?></h2></div>
        <div class="card-stat" style="border-left: 4px solid #f59e0b;"><small>EN TRAITEMENT</small><h2 style="color: #d97706;"><?= $stats['en_attente'] ?? 0 ?></h2></div>
        <div class="card-stat" style="border-left: 4px solid #10b981;"><small>PRÊTS AU RETRAIT</small><h2 style="color: #059669;"><?= $stats['pret_retrait'] ?? 0 ?></h2></div>
    </div>

    <input type="text" id="smartSearch" class="search-input" placeholder="Rechercher...">

    <div class="main-card">
        <table>
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Client</th>
                    <th>Service</th>
                    <th>État</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach($articles as $art): ?>
                <tr class="item-row" id="row-<?= $art['id_ligne'] ?>">
                    <td><span class="badge-ticket"><?= $art['numero_ticket'] ?></span></td>
                    <td><strong><?= htmlspecialchars($art['nom_client']) ?></strong></td>
                    <td><?= htmlspecialchars($art['nom_service']) ?></td>
                    <td><span class="status-text" style="color: #64748b; font-size: 0.85rem;"><?= ucfirst($art['statut_article']) ?></span></td>
                    <td>
                        <select class="status-select" onchange="updateStatus(<?= $art['id_ligne'] ?>, this)">
                            <option value="" disabled selected>Changer...</option>
                            <option value="depose">Déposé</option>
                            <option value="nettoye">Nettoyé</option>
                            <option value="repere">Repassé</option>
                            <option value="conditionne">Prêt (Fini)</option>
                            <option value="livre">Livré</option>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// FONCTION DE MISE À JOUR SANS RECHARGEMENT (AJAX)
function updateStatus(idLigne, selectElement) {
    const nouveauStatut = selectElement.value;
    const formData = new FormData();
    formData.append('id_ligne', idLigne);
    formData.append('nouveau_statut', nouveauStatut);

    // Envoi de la requête au serveur
    fetch('update_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            // 1. Afficher le message de succès
            const toast = document.getElementById('toast');
            toast.style.display = 'block';
            setTimeout(() => { toast.style.display = 'none'; }, 3000);

            // 2. Mettre à jour le texte du statut dans la ligne sans recharger
            const row = document.getElementById('row-' + idLigne);
            row.querySelector('.status-text').innerText = nouveauStatut.charAt(0).toUpperCase() + nouveauStatut.slice(1);

            // 3. Si "livré", on peut masquer la ligne (car le ticket sort du flux actif)
            if(nouveauStatut === 'livre') {
                row.style.transition = '0.5s';
                row.style.opacity = '0';
                setTimeout(() => { row.remove(); }, 500);
            }
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => console.error('Erreur:', error));
}

// RECHERCHE INTELLIGENTE
document.getElementById('smartSearch').addEventListener('input', function() {
    let filter = this.value.toLowerCase();
    document.querySelectorAll('.item-row').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
    });
});
</script>

</body>
</html>
<?php require_once '../../templates/footer.php'; ?>