<?php
/**
 * includes/modeles/form_creation.php
 * Formulaire de création d'un modèle de facture
 */
?>

<style>
    .form-container {
        max-width: 900px;
        margin: 0 auto;
    }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }
    .form-group {
        margin-bottom: 1.25rem;
    }
    .form-group.full-width {
        grid-column: span 2;
    }
    .label-pro {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }
    .input-pro {
        width: 100%;
        padding: 0.6rem 0.8rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 0.95rem;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .input-pro:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    .select-pro {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 1rem;
        padding-right: 2.5rem;
    }
    .helper-text {
        font-size: 0.8rem;
        color: #6b7280;
        margin-top: 0.4rem;
    }
    .footer-actions {
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
    }
    .toggle-container {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
    }
    .toggle-input {
        width: 40px;
        height: 20px;
        background: #d1d5db;
        border-radius: 20px;
        position: relative;
        appearance: none;
        cursor: pointer;
        transition: background 0.3s;
    }
    .toggle-input:checked {
        background: #2563eb;
    }
    .toggle-input::before {
        content: "";
        position: absolute;
        width: 16px;
        height: 16px;
        background: white;
        border-radius: 50%;
        top: 2px;
        left: 2px;
        transition: transform 0.3s;
    }
    .toggle-input:checked::before {
        transform: translateX(20px);
    }
</style>

<div class="form-container">
    <form action="modeles.php" method="POST" id="formModele">
        <input type="hidden" name="action" value="creer_modele">

        <div class="form-grid">
            <div class="form-group full-width">
                <label class="label-pro" for="nom">Nom du modèle de facture *</label>
                <input type="text" id="nom" name="nom" class="input-pro" placeholder="Ex: Facture Standard Pressing" required>
                <p class="helper-text">Ce nom servira à identifier le modèle lors de la création de facture.</p>
            </div>

            <div class="form-group">
                <label class="label-pro" for="id_type_facture">Type de facture</label>
                <select id="id_type_facture" name="id_type_facture" class="input-pro select-pro">
                    <option value="">Sélectionner un type...</option>
                    <?php if (!empty($types_facture)): ?>
                        <?php foreach ($types_facture as $type): ?>
                            <option value="<?= $type['id_type'] ?>"><?= htmlspecialchars($type['nom']) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Aucun type disponible</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="label-pro" for="id_entreprise">Entreprise / Filiale</label>
                <select id="id_entreprise" name="id_entreprise" class="input-pro select-pro">
                    <?php if (!empty($entreprises)): ?>
                        <?php foreach ($entreprises as $ent): ?>
                            <option value="<?= $ent['id_entreprise'] ?>"><?= htmlspecialchars($ent['nom']) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Aucune entreprise disponible</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="toggle-container">
                    <input type="checkbox" name="est_par_defaut" value="1" class="toggle-input">
                    <span class="label-pro" style="margin-bottom:0">Définir comme modèle par défaut</span>
                </label>
            </div>

            <div class="form-group">
                <label class="toggle-container">
                    <input type="checkbox" name="est_actif" value="1" class="toggle-input" checked>
                    <span class="label-pro" style="margin-bottom:0">Activer ce modèle immédiatement</span>
                </label>
            </div>

            <div class="form-group full-width">
                <label class="label-pro" for="description">Notes internes (optionnel)</label>
                <textarea id="description" name="description" class="input-pro" rows="3" placeholder="Description courte de l'usage de ce modèle..."></textarea>
            </div>
        </div>

        <div class="footer-actions">
            <a href="modeles.php" class="btn-pro btn-outline">
                Annuler
            </a>
            <button type="submit" class="btn-pro btn-blue">
                <svg style="width:1.2rem; height:1.2rem; margin-right:8px" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Créer le modèle
            </button>
        </div>
    </form>
</div>

<script>
    // Validation simple côté client
    document.getElementById('formModele').addEventListener('submit', function(e) {
        const nom = document.getElementById('nom').value.trim();
        if (nom.length < 3) {
            e.preventDefault();
            alert('Veuillez donner un nom explicite au modèle (3 caractères minimum).');
        }
    });
</script>