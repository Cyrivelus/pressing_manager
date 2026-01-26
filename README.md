# pressing_manager
**Application de gestion commerciale pour pressing.**

Cette application web permet de gérer l'activité complète d'un pressing avec une sécurité renforcée.

### ⚠️ Fonctionnalités de sécurité incluses :
* **Honeypot (Pot de Miel) 🍯** : Protection invisible contre les robots spammeurs sur le formulaire de connexion.
* **Authentification Hybride** : Gestion des sessions et du Rate Limiting via **Redis** (haute performance) avec fallback automatique sur **MySQL**.
* **Protection CSRF** : Sécurisation des formulaires contre les attaques inter-sites.
* **Gestion des Rôles** : Redirection dynamique selon le profil (Admin, Caissier, Réceptionniste, etc.).

### Pré-requis :
* PHP 8.x
* MySQL / MariaDB
* Serveur Redis (optionnel, mais recommandé pour la performance)
