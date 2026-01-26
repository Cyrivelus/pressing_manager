<?php
// templates/footer.php
?>

</div> <footer class="app-footer">

    <div class="footer-content">
        <div class="footer-section">
            <div class="footer-brand">
                <span class="app-name">Kayade PRESSING/COMMERCE Manager</span>
                <span class="app-tagline">Gestion intelligente pour professionnels</span>
            </div>
        </div>
        
        <div class="footer-section">
            <div class="footer-info">
                <p class="copyright">
                    &copy; <?php echo date("Y"); ?> Kayade PRESSING/COMMERCE Manager. Tous droits réservés.
                </p>
                <p class="author">
                    Développé par <strong>Tamboug Cyrille Steve</strong> 
                    <span class="separator">•</span> 
                    <span class="tech-stack">Full Stack Developer & Security Specialist</span>
                </p>
            </div>
        </div>
        
        <div class="footer-section">
            <div class="footer-contact">
                <div class="contact-item">
                    <span class="contact-label">Support :</span>
                    <a href="mailto:cyrillestevetamboug@gmail.com" class="contact-link">cyrillestevetamboug@gmail.com</a>
                </div>
                <div class="contact-item">
                    <span class="contact-label">Tél :</span>
                    <span class="contact-value">+237 696 19 75 25</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="footer-links">
            <a href="<?php echo $base_url ?? ''; ?>mentions-legales.php" class="footer-link">Mentions légales</a>
            <span class="link-separator">|</span>
            <a href="<?php echo $base_url ?? ''; ?>confidentialite.php" class="footer-link">Confidentialité</a>
            <span class="link-separator">|</span>
            <a href="<?php echo $base_url ?? ''; ?>conditions.php" class="footer-link">Conditions d'utilisation</a>
        </div>
    </div>
</footer>

<style>
.app-footer {
    /* Modification : Fond blanc et texte noir */
    background: #ffffff;
    color: #333333;
    padding: 20px 0 10px;
    margin-top: 40px;
    border-top: 3px solid #f8b500;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05); /* Ombre plus légère */
    position: relative;
    width: 100%;
    z-index: 100;
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.footer-section {
    flex: 1;
    min-width: 250px;
    padding: 10px;
}

.footer-brand {
    text-align: left;
}

.app-name {
    display: block;
    font-size: 1.4rem;
    font-weight: 700;
    color: #2c3e50; /* Bleu foncé/noir pour le titre */
    margin-bottom: 5px;
    letter-spacing: 0.5px;
}

.app-tagline {
    font-size: 0.85rem;
    color: #7f8c8d; /* Gris pour le slogan */
    font-weight: 400;
    letter-spacing: 0.3px;
}

.footer-info {
    text-align: center;
}

.copyright {
    font-size: 0.9rem;
    margin-bottom: 5px;
    color: #333333;
}

.author {
    font-size: 0.85rem;
    color: #555555;
    margin-bottom: 0;
}

.tech-stack {
    font-size: 0.8rem;
    background: #f1f2f6; /* Fond gris clair pour le badge tech */
    padding: 2px 8px;
    border-radius: 12px;
    color: #2c3e50;
    border: 1px solid #ddd;
}

.separator {
    color: #f8b500;
    margin: 0 8px;
}

.footer-contact {
    text-align: right;
}

.contact-item {
    margin-bottom: 6px;
    font-size: 0.9rem;
}

.contact-label {
    color: #7f8c8d;
    font-weight: 500;
    margin-right: 5px;
}

.contact-link, .contact-value {
    color: #333333;
    text-decoration: none;
    transition: color 0.3s ease;
}

.contact-link:hover {
    color: #f8b500;
    text-decoration: underline;
}

.footer-bottom {
    border-top: 1px solid #eeeeee; /* Bordure très claire */
    margin-top: 15px;
    padding-top: 10px;
}

.footer-links {
    text-align: center;
    font-size: 0.85rem;
}

.footer-link {
    color: #555555;
    text-decoration: none;
    margin: 0 10px;
    transition: color 0.3s ease;
}

.footer-link:hover {
    color: #f8b500;
    text-decoration: underline;
}

.link-separator {
    color: #cccccc;
    margin: 0 5px;
}

/* Responsive Design (Inchangé mais adapté aux couleurs) */
@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .footer-brand, .footer-contact {
        text-align: center;
    }
    
    .footer-section {
        min-width: 100%;
        padding: 5px;
    }
    
    .contact-item {
        justify-content: center;
    }
    
    .footer-links {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
    }
    
    .link-separator {
        display: none;
    }
}

@media (max-width: 480px) {
    .app-footer {
        padding: 15px 0 8px;
    }
    .app-name {
        font-size: 1.2rem;
    }
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.app-footer {
    animation: fadeInUp 0.5s ease-out;
}

body.short-content .app-footer {
    position: fixed;
    bottom: 0;
    width: 90%;
}

body.long-content .app-footer {
    position: relative;
}
</style>

<script src="<?php echo $base_url ?? ''; ?>/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function adjustFooterPosition() {
        const body = document.body;
        const footer = document.querySelector('.app-footer');
        if(!footer) return;
        const windowHeight = window.innerHeight;
        const bodyHeight = body.offsetHeight;
        
        if (bodyHeight < windowHeight) {
            body.classList.add('short-content');
        } else {
            body.classList.remove('short-content');
        }
    }
    adjustFooterPosition();
    window.addEventListener('resize', adjustFooterPosition);
    window.addEventListener('load', adjustFooterPosition);
});
</script>

</body>
</html>