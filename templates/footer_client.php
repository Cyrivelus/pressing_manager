</main><div class="d-md-none" style="height: 70px;"></div> <nav class="navbar fixed-bottom navbar-light bg-white border-top d-md-none py-2">
        <div class="container d-flex justify-content-around text-center">
            <a href="index.php" class="text-decoration-none <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-primary' : 'text-muted' ?>">
                <i class="fas fa-home d-block mb-1"></i>
                <small style="font-size: 0.65rem;">Accueil</small>
            </a>
            <a href="historique.php" class="text-decoration-none <?= basename($_SERVER['PHP_SELF']) == 'historique.php' ? 'text-primary' : 'text-muted' ?>">
                <i class="fas fa-list-ul d-block mb-1"></i>
                <small style="font-size: 0.65rem;">Historique</small>
            </a>
            <a href="reservation.php" class="text-decoration-none <?= basename($_SERVER['PHP_SELF']) == 'reservation.php' ? 'text-primary' : 'text-muted' ?>">
                <i class="fas fa-calendar-check d-block mb-1"></i>
                <small style="font-size: 0.65rem;">Réserver</small>
            </a>
            <a href="preferences.php" class="text-decoration-none <?= basename($_SERVER['PHP_SELF']) == 'preferences.php' ? 'text-primary' : 'text-muted' ?>">
                <i class="fas fa-user d-block mb-1"></i>
                <small style="font-size: 0.65rem;">Profil</small>
            </a>
        </div>
    </nav>

    <footer class="py-4 mt-5 bg-white border-top">
        <div class="container text-center">
            <p class="text-muted small mb-1">&copy; <?= date('Y') ?> <strong>Kayade Manager</strong>. Tous droits réservés.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="#" class="text-muted small text-decoration-none">Aide</a>
                <a href="#" class="text-muted small text-decoration-none">Confidentialité</a>
                <a href="mailto:contact@kayade.com" class="text-muted small text-decoration-none">Support</a>
            </div>
        </div>
    </footer>

    <script src="../../js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Animation simple au clic sur les cartes d'action
        document.querySelectorAll('.action-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.opacity = '0.7';
            });
        });

        // Gestion du rafraîchissement si nécessaire
        console.log("Portail Client Kayade chargé.");
    </script>

</body>
</html>