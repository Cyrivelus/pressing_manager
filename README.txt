
# 🧺 Pressing Manager Pro - Système de Gestion Intégré

**Pressing Manager Pro** est une solution web robuste conçue pour automatiser le cycle de vie complet d'un pressing : de la réception du linge à la livraison, en passant par la gestion des stocks, de la caisse et du suivi client.

## 📌 Table des matières

1. [Introduction](https://www.google.com/search?q=%231-introduction)
2. [Fonctionnalités Principales](https://www.google.com/search?q=%232-fonctionnalit%C3%A9s-principales)
* [2.1. Gestion des Tickets et Réception](https://www.google.com/search?q=%2321-gestion-des-tickets-et-r%C3%A9ception)
* [2.2. Suivi de Production & Statuts](https://www.google.com/search?q=%2322-suivi-de-production--statuts)
* [2.3. Caisse et Recettes Multi-agences](https://www.google.com/search?q=%2323-caisse-et-recettes-multi-agences)
* [2.4. Gestion des Stocks et Fournisseurs](https://www.google.com/search?q=%2324-gestion-des-stocks-et-fournisseurs)
* [2.5. Fidélité et Gestion Clientèle](https://www.google.com/search?q=%2325-fid%C3%A9lit%C3%A9-et-gestion-client%C3%A8le)
* [2.6. Administration et Habilitations](https://www.google.com/search?q=%2326-administration-et-habilitations)


3. [Architecture et Sécurité](https://www.google.com/search?q=%233-architecture-et-s%C3%A9curit%C3%A9)
4. [Spécifications Techniques](https://www.google.com/search?q=%234-sp%C3%A9cifications-techniques)

---

## 1. Introduction

Ce projet vise à professionnaliser la gestion des pressings en remplaçant les registres manuels par une interface numérique intuitive. L'application permet de minimiser les pertes de vêtements, d'optimiser l'utilisation des produits de nettoyage et de fournir une visibilité financière en temps réel aux propriétaires (Patrons) tout en simplifiant le travail des réceptionnistes.

---

## 2. Fonctionnalités Principales

### 2.1. Gestion des Tickets et Réception

* **Fonctionnement :** Saisie dynamique des articles avec choix des services (Lavage, Repassage, Nettoyage à sec). Génération automatique d'un numéro de ticket unique.
* **Utilité :** Éviter les erreurs de tarification et assurer un suivi précis dès le dépôt.
* **Résultat :** Impression de tickets clairs pour le client et étiquettes internes pour le traçage.

### 2.2. Suivi de Production & Statuts

* **Fonctionnement :** Système de changement de statut en un clic : `En attente` ➔ `En traitement` ➔ `Prêt` ➔ `Récupéré`.
* **Utilité :** Permet de savoir exactement où se trouve chaque vêtement dans le circuit de nettoyage.
* **Résultat :** Réduction des retards et meilleure information du client.

### 2.3. Caisse et Recettes Multi-agences

* **Fonctionnement :** Module de paiement intégré supportant plusieurs modes (Espèces, Mobile Money, Carte). Centralisation des recettes par agence.
* **Utilité :** Suivi rigoureux du flux de trésorerie et des restes à payer.
* **Résultat :** Rapports journaliers de caisse automatiques et réduction des écarts de fonds.

### 2.4. Gestion des Stocks et Fournisseurs

* **Fonctionnement :** Suivi des produits (lessive, cintres, emballages) avec alertes automatiques lorsque le **seuil critique** est atteint.
* **Utilité :** Éviter les ruptures de stock qui bloquent la production.
* **Résultat :** Inventaire permanent et gestion optimisée des commandes fournisseurs.

### 2.5. Fidélité et Gestion Clientèle

* **Fonctionnement :** Base de données client avec historique des commandes, calcul des points de fidélité et remises spéciales automatiques.
* **Utilité :** Améliorer la rétention client et personnaliser l'accueil.

### 2.6. Administration et Habilitations

* **Fonctionnement :** Gestion des rôles (Patron, Gérant, Réceptionniste) avec des permissions strictes.
* **Utilité :** Sécuriser les données sensibles (ex: seul le Patron peut supprimer un ticket ou voir le bénéfice net).

---

## 3. Architecture et Sécurité

L'application repose sur une architecture **MVC (Modèle-Vue-Contrôleur)** simplifiée, garantissant une maintenance aisée sans dépendances lourdes.

* **Sécurité des Accès :**
* Protection contre les accès directs aux fichiers via la constante `APP_ROOT`.
* Hachage des mots de passe avec `password_hash()` (BCRYPT).
* Système de **Captcha personnalisé** pour prévenir les tentatives de connexion automatisées.
* Protection **CSRF** sur les formulaires sensibles.


* **Gestion des Sessions :** Sessions sécurisées avec paramètres `httponly` et gestion d'expiration.
* **Intégrité des Données :** Utilisation de transactions SQL pour les opérations complexes (ex: création d'un ticket et ses lignes simultanément).

---

## 4. Spécifications Techniques

* **Langage :** PHP 8.x (Compatible 7.4+)
* **Base de Données :** MySQL / MariaDB (Moteur InnoDB avec clés étrangères)
* **Interface :** HTML5, CSS3 (Bootstrap 3.4), JavaScript (jQuery pour le dynamisme des tableaux)
* **Serveur Recommandé :** Apache avec module `mod_rewrite` activé
* **Structure :** Architecture modulaire sans Composer pour une portabilité maximale sur hébergements mutualisés.

---

*© 2026 Pressing Manager Pro - Solution de gestion métier.*

---
