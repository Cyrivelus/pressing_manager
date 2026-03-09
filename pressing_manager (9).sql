-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 06 fév. 2026 à 16:10
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `pressing_manager`
--

-- --------------------------------------------------------

--
-- Structure de la table `abonnements`
--

CREATE TABLE `abonnements` (
  `id_abonnement` int(11) NOT NULL,
  `id_client` int(11) DEFAULT NULL,
  `type_abonnement` varchar(50) DEFAULT NULL,
  `forfait_mensuel` decimal(10,2) DEFAULT NULL,
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `statut` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `activites_config`
--

CREATE TABLE `activites_config` (
  `id_activite` int(11) NOT NULL,
  `type_activite` varchar(20) NOT NULL,
  `nom_activite` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `date_activation` datetime DEFAULT NULL,
  `date_desactivation` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `activites_config`
--

INSERT INTO `activites_config` (`id_activite`, `type_activite`, `nom_activite`, `description`, `actif`, `date_activation`, `date_desactivation`, `created_at`) VALUES
(1, 'pressing', 'Pressing & Blanchisserie', 'Service de nettoyage et pressing', 1, NULL, NULL, '2026-02-04 14:24:54'),
(2, 'commerce', 'Boutique & Commerce', 'Vente de produits textiles et accessoires', 1, NULL, NULL, '2026-02-04 14:24:54'),
(3, 'hotel', 'Hôtel & Services', 'Services hôteliers et réservations', 1, NULL, NULL, '2026-02-04 14:24:54');

-- --------------------------------------------------------

--
-- Structure de la table `agences`
--

CREATE TABLE `agences` (
  `id_agence` int(11) NOT NULL,
  `nom_agence` varchar(100) NOT NULL,
  `adresse` text DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `responsable_id` int(11) DEFAULT NULL,
  `date_ouverture` date DEFAULT NULL,
  `est_actif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `agences`
--

INSERT INTO `agences` (`id_agence`, `nom_agence`, `adresse`, `telephone`, `email`, `responsable_id`, `date_ouverture`, `est_actif`, `created_at`) VALUES
(1, 'Agence Principale', 'Adresse par défaut', '00000000', 'contact@pressing.com', NULL, NULL, 1, '2026-01-09 15:20:39');

-- --------------------------------------------------------

--
-- Structure de la table `archives_alertes`
--

CREATE TABLE `archives_alertes` (
  `id_archive` int(11) NOT NULL,
  `type_alerte` enum('stock','retard','oublie') NOT NULL,
  `description` text DEFAULT NULL,
  `date_alerte` datetime DEFAULT current_timestamp(),
  `date_resolution` datetime DEFAULT NULL,
  `id_utilisateur_traitement` int(11) DEFAULT NULL,
  `notes_resolution` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `date_action` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `boutique_produits`
--

CREATE TABLE `boutique_produits` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,0) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `categorie` varchar(100) DEFAULT 'Divers',
  `photo` varchar(255) DEFAULT NULL,
  `date_ajout` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `boutique_produits`
--

INSERT INTO `boutique_produits` (`id`, `nom`, `description`, `prix`, `stock`, `categorie`, `photo`, `date_ajout`) VALUES
(1, 'Lessive Liquide 2L', 'Parfumé au savon de Marseille', 5500, 15, 'Produits Entretien', NULL, '2026-01-26 11:26:52'),
(2, 'Assouplissant 1L', 'Douceur longue durée', 3500, 8, 'Produits Entretien', NULL, '2026-01-26 11:26:52'),
(3, 'Housse de protection', 'Pour costumes et robes', 1500, 50, 'Accessoires', NULL, '2026-01-26 11:26:52');

-- --------------------------------------------------------

--
-- Structure de la table `categories_service`
--

CREATE TABLE `categories_service` (
  `id_categorie` int(11) NOT NULL,
  `nom_categorie` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `delai_standard` int(11) DEFAULT NULL COMMENT 'Délai en heures',
  `prix_base` decimal(10,2) NOT NULL,
  `couleur_etiquette` varchar(7) DEFAULT '#3498db'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categories_service`
--

INSERT INTO `categories_service` (`id_categorie`, `nom_categorie`, `description`, `delai_standard`, `prix_base`, `couleur_etiquette`) VALUES
(1, 'Nettoyage à sec', 'Vêtements délicats nécessitant un nettoyage à sec', 24, 15.00, '#3498db'),
(2, 'Lavage', 'Lavage et repassage standard', 12, 8.00, '#2ecc71'),
(3, 'Repassage', 'Repassage uniquement', 6, 5.00, '#e74c3c'),
(4, 'Rénovation', 'Détachage et réparation', 48, 25.00, '#9b59b6'),
(5, 'Autres services', 'Services divers', 24, 10.00, '#f39c12'),
(6, 'Pressing & Blanchisserie', 'Entretien textile, lavage et repassage', 48, 500.00, '#3498db'),
(7, 'Boissons & Bar', 'Vente de boissons fraîches et alcools', 0, 500.00, '#e74c3c'),
(8, 'Restauration & Food', 'Plats cuisinés et snacks', 1, 1500.00, '#27ae60'),
(9, 'Prêt-à-porter', 'Vente de vêtements et accessoires', 0, 2000.00, '#f39c12');

-- --------------------------------------------------------

--
-- Structure de la table `certifications`
--

CREATE TABLE `certifications` (
  `id_certif` int(11) NOT NULL,
  `nom_label` varchar(150) NOT NULL,
  `organisme` varchar(100) NOT NULL,
  `date_obtention` date NOT NULL,
  `date_expiration` date NOT NULL,
  `fichier_pdf` varchar(255) DEFAULT 'default_cert.pdf',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `certifications`
--

INSERT INTO `certifications` (`id_certif`, `nom_label`, `organisme`, `date_obtention`, `date_expiration`, `fichier_pdf`, `created_at`) VALUES
(1, 'Eco-Label Européen', 'AFNOR', '2025-01-01', '2027-01-01', 'default_cert.pdf', '2026-01-26 12:13:10'),
(2, 'Norme ISO 14001', 'Bureau Veritas', '2024-06-15', '2026-06-15', 'default_cert.pdf', '2026-01-26 12:13:10');

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id_client` int(11) NOT NULL,
  `id_parrain` int(11) DEFAULT NULL,
  `nom_client` varchar(100) NOT NULL,
  `prenom_client` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `date_inscription` timestamp NOT NULL DEFAULT current_timestamp(),
  `points_fidelite` int(11) DEFAULT 0,
  `remise_speciale` decimal(5,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `est_actif` tinyint(1) DEFAULT 1,
  `id_agence` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id_client`, `id_parrain`, `nom_client`, `prenom_client`, `telephone`, `email`, `adresse`, `date_inscription`, `points_fidelite`, `remise_speciale`, `notes`, `est_actif`, `id_agence`) VALUES
(1, NULL, 'CLIENT', 'Cyrille', '696197525', 'cyrivelmars2013@gmail.com', 'SUPERETTE', '2026-01-09 14:35:02', 4, 0.00, NULL, 1, NULL),
(2, NULL, 'CLIENT', 'Passager', '0000000000', 'client.passager@exemple.com', 'Non renseignée', '2026-01-16 15:35:17', 24, 0.00, NULL, 1, 1),
(3, NULL, 'LAMAVA', 'john', '+237672897161', NULL, NULL, '2026-02-06 10:38:44', 0, 0.00, 'PARTENAIRE_HOTEL | Contrat: Gold | Créé le: 2026-02-06 11:38:44', 1, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `client_activites`
--

CREATE TABLE `client_activites` (
  `id` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `type_activite` varchar(20) NOT NULL,
  `date_premiere_visite` datetime DEFAULT NULL,
  `date_derniere_visite` datetime DEFAULT NULL,
  `total_visites` int(11) DEFAULT 0,
  `total_depenses` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `clotures_caisse`
--

CREATE TABLE `clotures_caisse` (
  `id_cloture` int(11) NOT NULL,
  `id_agence` int(11) NOT NULL,
  `date_cloture` datetime DEFAULT current_timestamp(),
  `montant_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_especes` decimal(15,2) DEFAULT 0.00,
  `montant_mobile` decimal(15,2) DEFAULT 0.00,
  `montant_autres` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `id_utilisateur` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `clotures_caisse`
--

INSERT INTO `clotures_caisse` (`id_cloture`, `id_agence`, `date_cloture`, `montant_total`, `montant_especes`, `montant_mobile`, `montant_autres`, `notes`, `id_utilisateur`) VALUES
(7, 1, '2026-01-28 16:34:16', 4150.00, 4150.00, 0.00, 0.00, '', 1);

-- --------------------------------------------------------

--
-- Structure de la table `comptes`
--

CREATE TABLE `comptes` (
  `id_compte` int(11) NOT NULL,
  `nom_compte` varchar(50) NOT NULL,
  `solde` decimal(15,2) DEFAULT 0.00,
  `type_compte` enum('Caisse','Banque','Mobile Money') DEFAULT 'Caisse',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `comptes`
--

INSERT INTO `comptes` (`id_compte`, `nom_compte`, `solde`, `type_compte`, `date_creation`) VALUES
(1, 'Caisse Principale', 0.00, 'Caisse', '2026-01-26 11:48:42'),
(2, 'Compte Bancaire', 0.00, 'Banque', '2026-01-26 11:48:42'),
(3, 'Orange Money / Mobile Money', 0.00, 'Mobile Money', '2026-01-26 11:48:42');

-- --------------------------------------------------------

--
-- Structure de la table `consommables`
--

CREATE TABLE `consommables` (
  `id_consommable` int(11) NOT NULL,
  `nom_article` varchar(100) NOT NULL,
  `categorie` enum('Produits Chimiques','Emballage','Maintenance','Papeterie','Autre') DEFAULT 'Autre',
  `conditionnement` varchar(100) DEFAULT NULL COMMENT 'Ex: Bidon de 20L, Rouleau de 500m',
  `stock_actuel` decimal(10,2) DEFAULT 0.00,
  `stock_alerte` decimal(10,2) DEFAULT 0.00,
  `unite_mesure` varchar(20) DEFAULT 'Unités',
  `dernier_prix_achat` decimal(10,2) DEFAULT 0.00,
  `fournisseur_habituel` varchar(100) DEFAULT NULL,
  `date_mise_a_jour` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `consommables`
--

INSERT INTO `consommables` (`id_consommable`, `nom_article`, `categorie`, `conditionnement`, `stock_actuel`, `stock_alerte`, `unite_mesure`, `dernier_prix_achat`, `fournisseur_habituel`, `date_mise_a_jour`) VALUES
(1, 'Lessive Liquide Pro', 'Produits Chimiques', 'Bidon 20L', 2.00, 5.00, 'Bidons', 45000.00, NULL, '2026-01-23 13:18:32'),
(2, 'Solvant Nettoyage à Sec', 'Produits Chimiques', 'Fût 50L', 1.00, 2.00, 'Fûts', 125000.00, NULL, '2026-01-23 13:18:32'),
(3, 'Cintres Métalliques', 'Emballage', 'Carton de 500', 12.00, 10.00, 'Cartons', 15000.00, NULL, '2026-01-23 13:18:32'),
(4, 'Film Gaine Plastique', 'Emballage', 'Rouleau 500m', 8.00, 3.00, 'Rouleaux', 8500.00, NULL, '2026-01-23 13:18:32'),
(5, 'Papier Thermique Ticket', 'Papeterie', 'Boite de 20', 1.00, 2.00, 'Boites', 12000.00, NULL, '2026-01-23 13:18:32');

-- --------------------------------------------------------

--
-- Structure de la table `cycles_production`
--

CREATE TABLE `cycles_production` (
  `id_cycle` int(11) NOT NULL,
  `id_machine` int(11) NOT NULL,
  `id_ticket` int(11) DEFAULT NULL,
  `heure_debut` datetime NOT NULL,
  `duree_estimee` int(11) NOT NULL DEFAULT 45,
  `notes` text DEFAULT NULL,
  `heure_fin` datetime NOT NULL,
  `statut` enum('prevu','en_cours','termine','annule') DEFAULT 'prevu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `cycles_production`
--

INSERT INTO `cycles_production` (`id_cycle`, `id_machine`, `id_ticket`, `heure_debut`, `duree_estimee`, `notes`, `heure_fin`, `statut`) VALUES
(1, 2, 13, '2026-01-29 15:31:00', 45, '', '0000-00-00 00:00:00', 'en_cours'),
(2, 2, 14, '2026-02-06 14:55:00', 45, '', '0000-00-00 00:00:00', 'en_cours');

-- --------------------------------------------------------

--
-- Structure de la table `depenses`
--

CREATE TABLE `depenses` (
  `id_depense` int(11) NOT NULL,
  `id_agence` int(11) NOT NULL,
  `categorie` enum('loyer','salaires','electricite','eau','produits','maintenance','autre') NOT NULL,
  `description` text NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_depense` date NOT NULL,
  `id_fournisseur` int(11) DEFAULT NULL,
  `reference_facture` varchar(100) DEFAULT NULL,
  `mode_paiement` enum('especes','carte','cheque','virement') DEFAULT 'cheque',
  `id_utilisateur` int(11) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL,
  `nom_evenement` varchar(100) NOT NULL COMMENT 'Ex: Nouveau Client, Commande Prête',
  `objet` varchar(255) DEFAULT NULL COMMENT 'Sujet de l email',
  `corps_message` text DEFAULT NULL COMMENT 'Contenu avec variables {NOM_CLIENT}',
  `statut` enum('Actif','Inactif') DEFAULT 'Actif',
  `date_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `email_templates`
--

INSERT INTO `email_templates` (`id`, `nom_evenement`, `objet`, `corps_message`, `statut`, `date_modification`) VALUES
(1, 'Nouveau Client', 'Bienvenue chez Kayade', 'Bonjour {NOM_CLIENT}, merci de nous avoir fait confiance !', 'Actif', '2026-01-28 15:43:46'),
(2, 'Commande Prête', 'Bonne nouvelle ! Votre linge est prêt', 'Bonjour {NOM_CLIENT}, votre commande #{NUM_TICKET} est prête.', 'Actif', '2026-01-28 15:43:28'),
(3, 'Anniversaire Client', 'Un cadeau pour votre anniversaire 🎂', 'Joyeux anniversaire {NOM_CLIENT} !', 'Inactif', '2026-01-28 15:43:28'),
(4, 'Retrait effectué', 'Votre avis nous intéresse', 'Comment s\'est passé votre retrait ?', 'Actif', '2026-01-28 15:43:28');

-- --------------------------------------------------------

--
-- Structure de la table `equipements`
--

CREATE TABLE `equipements` (
  `id_equipement` int(11) NOT NULL,
  `nom_equipement` varchar(100) NOT NULL,
  `numero_serie` varchar(50) DEFAULT NULL,
  `type_entretien` varchar(100) DEFAULT 'Maintenance préventive',
  `derniere_maintenance` date DEFAULT NULL,
  `frequence_jours` int(11) DEFAULT 30 COMMENT 'Nombre de jours entre deux entretiens',
  `est_actif` tinyint(1) DEFAULT 1,
  `date_acquisition` date DEFAULT NULL,
  `etat` enum('neuf','bon','usagé','en_panne') DEFAULT 'bon'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `equipements`
--

INSERT INTO `equipements` (`id_equipement`, `nom_equipement`, `numero_serie`, `type_entretien`, `derniere_maintenance`, `frequence_jours`, `est_actif`, `date_acquisition`, `etat`) VALUES
(1, 'Lave-linge Industriel 15kg', 'LG-IND-001', 'Nettoyage filtres et graissage', '2025-12-29', 30, 1, NULL, 'bon'),
(2, 'Sèche-linge Rotatif', 'DRY-PRO-88', 'Aspiration peluches et courroies', '2025-12-09', 30, 1, NULL, 'bon'),
(3, 'Chaudière Vapeur Centrale', 'VAP-GEN-500', 'Détartrage et sécurité', '2026-01-13', 90, 1, NULL, 'bon'),
(4, 'Presse à Repasser', 'IRON-MAID-02', 'Vérification joints vapeur', '2026-01-18', 15, 1, NULL, 'bon');

-- --------------------------------------------------------

--
-- Structure de la table `etapes_production`
--

CREATE TABLE `etapes_production` (
  `id_etape` int(11) NOT NULL,
  `id_ticket` int(11) NOT NULL,
  `status_etape` enum('lavage','sechage','repassage','pret') NOT NULL,
  `progression` int(11) DEFAULT 0,
  `id_utilisateur` int(11) DEFAULT NULL,
  `date_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `etapes_production`
--

INSERT INTO `etapes_production` (`id_etape`, `id_ticket`, `status_etape`, `progression`, `id_utilisateur`, `date_update`, `notes`) VALUES
(2, 9, 'repassage', 50, NULL, '2026-01-28 09:43:08', 'Contrôle Qualité: '),
(3, 10, 'pret', 100, NULL, '2026-01-29 14:14:12', 'Contrôle Qualité: '),
(4, 9, 'pret', 100, NULL, '2026-02-06 13:56:42', 'Contrôle Qualité: '),
(5, 11, 'repassage', 50, NULL, '2026-02-06 13:57:04', 'Contrôle Qualité: '),
(6, 12, 'repassage', 50, NULL, '2026-02-06 13:58:09', 'Contrôle Qualité: ');

-- --------------------------------------------------------

--
-- Structure de la table `factures`
--

CREATE TABLE `factures` (
  `id_facture` int(11) NOT NULL,
  `numero_facture` varchar(20) NOT NULL,
  `date_facture` datetime DEFAULT current_timestamp(),
  `date_echeance` date DEFAULT NULL,
  `id_client` int(11) DEFAULT NULL,
  `montant_ht` decimal(10,2) DEFAULT NULL,
  `montant_tva` decimal(10,2) DEFAULT NULL,
  `montant_ttc` decimal(10,2) DEFAULT NULL,
  `statut_paiement` enum('En attente','Payé','Partiel') DEFAULT 'En attente',
  `mode_paiement` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `fidelite_cartes`
--

CREATE TABLE `fidelite_cartes` (
  `id_carte` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `numero_carte` varchar(50) NOT NULL,
  `type_programme` enum('points','prepaye','mixte') DEFAULT 'points',
  `points` int(11) DEFAULT 0,
  `solde_monetaire` decimal(10,2) DEFAULT 0.00,
  `date_emission` date NOT NULL,
  `date_expiration` date DEFAULT NULL,
  `statut` enum('active','suspendue','expiree') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `fidelite_cartes`
--

INSERT INTO `fidelite_cartes` (`id_carte`, `id_client`, `numero_carte`, `type_programme`, `points`, `solde_monetaire`, `date_emission`, `date_expiration`, `statut`) VALUES
(1, 1, 'PM-2026-001', 'mixte', 150, 25000.00, '2026-01-23', NULL, 'active'),
(2, 2, 'PM-2026-002', 'points', 45, 0.00, '2026-01-23', NULL, 'active');

-- --------------------------------------------------------

--
-- Structure de la table `fournisseurs`
--

CREATE TABLE `fournisseurs` (
  `id_fournisseur` int(11) NOT NULL,
  `nom_fournisseur` varchar(100) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `categorie` enum('produit_nettoyage','equipement','emballage','autre') DEFAULT 'produit_nettoyage',
  `solde_du` decimal(10,2) DEFAULT 0.00,
  `est_actif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `fournisseurs`
--

INSERT INTO `fournisseurs` (`id_fournisseur`, `nom_fournisseur`, `contact`, `telephone`, `email`, `adresse`, `categorie`, `solde_du`, `est_actif`) VALUES
(1, 'FOURNISSEUR GENERAL', NULL, NULL, NULL, NULL, 'autre', 0.00, 1);

-- --------------------------------------------------------

--
-- Structure de la table `historique_cycles`
--

CREATE TABLE `historique_cycles` (
  `id_historique` int(11) NOT NULL,
  `id_cycle` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `date_action` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `historique_tarifs`
--

CREATE TABLE `historique_tarifs` (
  `id_histo` int(11) NOT NULL,
  `id_service` int(11) NOT NULL,
  `ancien_prix` decimal(10,2) DEFAULT NULL,
  `nouveau_prix` decimal(10,2) DEFAULT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `date_changement` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `interventions`
--

CREATE TABLE `interventions` (
  `id_intervention` int(11) NOT NULL,
  `id_equipement` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `date_intervention` datetime DEFAULT current_timestamp(),
  `type_panne` enum('Électrique','Mécanique','Fuite (Eau/Vapeur)','Logiciel / Système','Autre') DEFAULT 'Autre',
  `description_panne` text NOT NULL,
  `piece_remplacee` varchar(255) DEFAULT NULL,
  `technicien_nom` varchar(100) DEFAULT NULL,
  `cout_total` decimal(10,2) DEFAULT 0.00,
  `statut` enum('en_cours','termine') DEFAULT 'en_cours'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `interventions`
--

INSERT INTO `interventions` (`id_intervention`, `id_equipement`, `id_utilisateur`, `date_intervention`, `type_panne`, `description_panne`, `piece_remplacee`, `technicien_nom`, `cout_total`, `statut`) VALUES
(1, 1, 1, '2026-01-23 14:35:10', 'Mécanique', 'Remplacement de la courroie de transmission principale', 'Courroie V-Belt X2', 'Electro-Service SARL', 45000.00, 'termine'),
(2, 2, 1, '2026-01-23 14:35:10', 'Fuite (Eau/Vapeur)', 'Fuite détectée sur le raccord d\'arrivée vapeur', NULL, 'Interne', 0.00, 'en_cours');

-- --------------------------------------------------------

--
-- Structure de la table `journal_production`
--

CREATE TABLE `journal_production` (
  `id_log` int(11) NOT NULL,
  `id_ligne` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `date_action` datetime DEFAULT current_timestamp(),
  `utilisateur` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lignes_facture`
--

CREATE TABLE `lignes_facture` (
  `id_ligne` int(11) NOT NULL,
  `id_facture` int(11) DEFAULT NULL,
  `id_service` int(11) DEFAULT NULL,
  `quantite` int(11) DEFAULT NULL,
  `prix_unitaire` decimal(10,2) DEFAULT NULL,
  `total_ligne` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lignes_ticket`
--

CREATE TABLE `lignes_ticket` (
  `id_ligne` int(11) NOT NULL,
  `id_ticket` int(11) NOT NULL,
  `rfid_tag` varchar(50) DEFAULT NULL,
  `id_service` int(11) NOT NULL,
  `quantite` decimal(10,2) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `sous_total` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `photo_avant` varchar(255) DEFAULT NULL,
  `photo_apres` varchar(255) DEFAULT NULL,
  `statut_article` enum('depose','nettoye','repere','conditionne','livre') DEFAULT 'depose',
  `notes` text DEFAULT NULL,
  `numero_etiquette` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `lignes_ticket`
--

INSERT INTO `lignes_ticket` (`id_ligne`, `id_ticket`, `rfid_tag`, `id_service`, `quantite`, `prix_unitaire`, `sous_total`, `description`, `photo_avant`, `photo_apres`, `statut_article`, `notes`, `numero_etiquette`) VALUES
(1, 9, NULL, 3, 5.00, 8.00, 40.00, NULL, NULL, NULL, 'livre', NULL, 'TK-260109-0001-1'),
(2, 9, NULL, 1, 5.00, 25.00, 125.00, NULL, NULL, NULL, 'livre', NULL, 'TK-260109-0001-2'),
(3, 9, NULL, 2, 5.50, 30.00, 165.00, NULL, NULL, NULL, 'livre', NULL, 'TK-260109-0001-3'),
(4, 10, NULL, 8, 2.00, 1500.00, 3000.00, NULL, NULL, NULL, 'livre', NULL, 'TK-260113-0001-1'),
(5, 10, NULL, 10, 2.00, 650.00, 1300.00, NULL, NULL, NULL, 'livre', NULL, 'TK-260113-0001-2'),
(6, 11, NULL, 10, 1.00, 650.00, 650.00, NULL, NULL, NULL, 'conditionne', NULL, 'TK-260128-0001-1'),
(7, 11, NULL, 9, 2.00, 1000.00, 2000.00, NULL, NULL, NULL, 'conditionne', NULL, 'TK-260128-0001-2'),
(8, 11, NULL, 8, 1.00, 1500.00, 1500.00, NULL, NULL, NULL, 'livre', NULL, 'TK-260128-0001-3'),
(9, 12, NULL, 11, 1.00, 4500.00, 4500.00, NULL, NULL, NULL, 'livre', NULL, 'TK-260129-0001-1'),
(10, 12, NULL, 3, 1.00, 8.00, 8.00, NULL, NULL, NULL, 'livre', NULL, 'TK-260129-0001-2'),
(11, 12, NULL, 11, 1.00, 4500.00, 4500.00, NULL, NULL, NULL, 'livre', NULL, 'TK-260129-0001-3'),
(12, 13, NULL, 10, 1.00, 650.00, 650.00, NULL, NULL, NULL, 'livre', NULL, 'TK-260129-0002-1'),
(13, 13, NULL, 1, 1.00, 25.00, 25.00, NULL, NULL, NULL, 'livre', NULL, 'TK-260129-0002-2'),
(14, 13, NULL, 9, 1.00, 1000.00, 1000.00, NULL, NULL, NULL, 'livre', NULL, 'TK-260129-0002-3'),
(15, 14, NULL, 11, 1.00, 4500.00, 4500.00, NULL, NULL, NULL, 'livre', NULL, 'TK-260129-0003-1'),
(16, 14, NULL, 9, 1.00, 1000.00, 1000.00, NULL, NULL, NULL, 'livre', NULL, 'TK-260129-0003-2'),
(17, 18, NULL, 1, 2.00, 25.00, 50.00, NULL, NULL, NULL, 'depose', NULL, NULL),
(18, 18, NULL, 11, 2.00, 4500.00, 9000.00, NULL, NULL, NULL, '', NULL, NULL),
(19, 19, NULL, 1, 1.00, 25.00, 25.00, NULL, NULL, NULL, '', NULL, NULL),
(20, 19, NULL, 11, 3.00, 4500.00, 13500.00, NULL, NULL, NULL, '', NULL, NULL),
(21, 20, NULL, 8, 1.00, 1500.00, 1500.00, NULL, NULL, NULL, 'depose', NULL, 'TK-260206-0001-1'),
(22, 20, NULL, 9, 1.00, 1000.00, 1000.00, NULL, NULL, NULL, 'depose', NULL, 'TK-260206-0001-2'),
(23, 21, NULL, 10, 1.00, 650.00, 650.00, NULL, NULL, NULL, 'depose', NULL, 'TK-260206-0002-1'),
(24, 21, NULL, 12, 1.00, 2500.00, 2500.00, NULL, NULL, NULL, 'depose', NULL, 'TK-260206-0002-2');

-- --------------------------------------------------------

--
-- Structure de la table `logs_activite`
--

CREATE TABLE `logs_activite` (
  `id_log` int(11) NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `table_concernée` varchar(50) DEFAULT NULL,
  `id_enregistrement` int(11) DEFAULT NULL,
  `anciennes_valeurs` text DEFAULT NULL,
  `nouvelles_valeurs` text DEFAULT NULL,
  `ip_adresse` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `date_action` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `logs_activite`
--

INSERT INTO `logs_activite` (`id_log`, `id_utilisateur`, `action`, `table_concernée`, `id_enregistrement`, `anciennes_valeurs`, `nouvelles_valeurs`, `ip_adresse`, `user_agent`, `date_action`) VALUES
(1, 1, 'Changement statut ticket vers: pret', 'tickets', 9, NULL, '{\"ticket_id\":9,\"numero_ticket\":\"TK-260109-0001\",\"ancien_statut\":\"unknown\",\"nouveau_statut\":\"pret\",\"date_maj\":\"2026-01-09 16:40:53\"}', NULL, NULL, '2026-01-09 15:40:53'),
(2, 1, 'Livraison effectuée pour le ticket #TK-260109-0001', 'tickets', 9, NULL, NULL, NULL, NULL, '2026-01-09 15:59:38'),
(3, 1, 'Livraison effectuée pour le ticket #TK-260109-0001', 'tickets', 9, NULL, NULL, NULL, NULL, '2026-01-09 15:59:51'),
(4, 4, 'Création d\'un nouvel utilisateur : steve', 'utilisateurs', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 07:19:25'),
(5, 1, 'Changement statut ticket vers: pret', 'tickets', 10, NULL, '{\"ticket_id\":10,\"numero_ticket\":\"TK-260113-0001\",\"ancien_statut\":\"unknown\",\"nouveau_statut\":\"pret\",\"date_maj\":\"2026-01-14 07:50:54\"}', NULL, NULL, '2026-01-14 06:50:54'),
(6, 1, 'Livraison effectuée pour le ticket #TK-260113-0001', 'tickets', 10, NULL, NULL, NULL, NULL, '2026-01-14 06:55:25'),
(7, NULL, 'Création de l\'utilisateur : Cyrivelus', 'utilisateurs', 8, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 07:10:17'),
(8, 1, 'Changement statut ticket vers: pret', 'tickets', 9, NULL, '{\"ticket_id\":9,\"numero_ticket\":\"TK-260109-0001\",\"ancien_statut\":\"unknown\",\"nouveau_statut\":\"pret\",\"date_maj\":\"2026-01-28 15:54:34\"}', NULL, NULL, '2026-01-28 14:54:34'),
(9, 1, 'Changement statut ticket vers: pret', 'tickets', 11, NULL, '{\"ticket_id\":11,\"numero_ticket\":\"TK-260128-0001\",\"ancien_statut\":\"unknown\",\"nouveau_statut\":\"pret\",\"date_maj\":\"2026-01-28 15:58:31\"}', NULL, NULL, '2026-01-28 14:58:31');

-- --------------------------------------------------------

--
-- Structure de la table `machines`
--

CREATE TABLE `machines` (
  `id_machine` int(11) NOT NULL,
  `nom_machine` varchar(100) NOT NULL,
  `type_machine` enum('lavage','sechage','repassage') NOT NULL,
  `capacite` decimal(5,2) DEFAULT NULL,
  `statut` enum('disponible','en_cours','maintenance','hors_service') DEFAULT 'disponible',
  `date_installation` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `machines`
--

INSERT INTO `machines` (`id_machine`, `nom_machine`, `type_machine`, `capacite`, `statut`, `date_installation`) VALUES
(1, 'Laveuse Industrielle L1', 'lavage', 15.00, 'disponible', NULL),
(2, 'Laveuse Industrielle L2', 'lavage', 10.00, 'disponible', NULL),
(3, 'Sèche-linge Rapide S1', 'sechage', 15.00, 'disponible', NULL),
(4, 'Calandre Repassage C1', 'repassage', 20.00, 'disponible', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `modeles_facture`
--

CREATE TABLE `modeles_facture` (
  `id_modele` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `id_type_facture` int(11) DEFAULT NULL,
  `id_entreprise` int(11) DEFAULT NULL,
  `contenu_html` longtext DEFAULT NULL,
  `est_par_defaut` tinyint(1) DEFAULT 0,
  `est_actif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mouvements_caisse`
--

CREATE TABLE `mouvements_caisse` (
  `id_mouvement` int(11) NOT NULL,
  `id_compte` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `type_mouvement` enum('Entrée','Sortie') NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `date_mouvement` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mouvements_stock`
--

CREATE TABLE `mouvements_stock` (
  `id_mouvement` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `type_mouvement` enum('entree','sortie','ajustement') NOT NULL,
  `quantite` decimal(10,2) NOT NULL,
  `date_mouvement` datetime NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `reference` varchar(100) DEFAULT NULL COMMENT 'Numéro de facture ou bon',
  `notes` text DEFAULT NULL,
  `quantite_apres` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `mouvements_stock`
--

INSERT INTO `mouvements_stock` (`id_mouvement`, `id_produit`, `type_mouvement`, `quantite`, `date_mouvement`, `id_utilisateur`, `reference`, `notes`, `quantite_apres`) VALUES
(1, 1, 'entree', 2.00, '2026-01-09 17:27:07', 3, 'uyvyuvyu', '', 4.00),
(2, 1, 'entree', 2.00, '2026-01-09 17:30:47', 3, 'uyvyuvyu', '', 6.00),
(3, 1, 'sortie', 2.00, '2026-01-09 17:36:34', 1, NULL, '', 4.00);

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id_notification` int(11) NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `id_agence` int(11) DEFAULT NULL,
  `type_notification` enum('alerte_stock','ticket_retard','ticket_pret','rapport','systeme') NOT NULL,
  `titre` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `lien` varchar(255) DEFAULT NULL,
  `est_lue` tinyint(1) DEFAULT 0,
  `date_notification` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id_notification`, `id_utilisateur`, `id_agence`, `type_notification`, `titre`, `message`, `lien`, `est_lue`, `date_notification`) VALUES
(1, 1, 1, 'ticket_pret', 'Vêtements prêts - Ticket #TK-260109-0001', 'Le ticket #TK-260109-0001 est prêt pour retrait.', 'pages/tickets/view.php?id=9', 0, '2026-01-09 15:40:53'),
(2, 1, 1, 'ticket_pret', 'Vêtements prêts - Ticket #TK-260113-0001', 'Le ticket #TK-260113-0001 est prêt pour retrait.', 'pages/tickets/view.php?id=10', 0, '2026-01-14 06:50:55'),
(3, 1, 1, 'ticket_pret', 'Vêtements prêts - Ticket #TK-260109-0001', 'Le ticket #TK-260109-0001 est prêt pour retrait.', 'pages/tickets/view.php?id=9', 0, '2026-01-28 14:54:34'),
(4, 1, 1, 'ticket_pret', 'Vêtements prêts - Ticket #TK-260128-0001', 'Le ticket #TK-260128-0001 est prêt pour retrait.', 'pages/tickets/view.php?id=11', 0, '2026-01-28 14:58:31');

-- --------------------------------------------------------

--
-- Structure de la table `notifications_clients`
--

CREATE TABLE `notifications_clients` (
  `id_notification` int(11) NOT NULL,
  `id_client` int(11) DEFAULT NULL,
  `type_notification` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `date_envoi` datetime DEFAULT current_timestamp(),
  `statut` enum('envoye','echec','en_attente') DEFAULT NULL,
  `canal` enum('SMS','Email','Push') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications_clients`
--

INSERT INTO `notifications_clients` (`id_notification`, `id_client`, `type_notification`, `message`, `date_envoi`, `statut`, `canal`) VALUES
(1, NULL, 'System_Log', 'Mise à jour du template ID #1 par l\'administrateur.', '2026-01-28 15:43:46', 'envoye', 'Email');

-- --------------------------------------------------------

--
-- Structure de la table `paiements`
--

CREATE TABLE `paiements` (
  `id_paiement` int(11) NOT NULL,
  `id_ticket` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `mode_paiement` enum('especes','carte','cheque','mobile','autre') NOT NULL,
  `date_paiement` datetime NOT NULL,
  `id_utilisateur` int(11) NOT NULL COMMENT 'Caissier',
  `reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `paiements`
--

INSERT INTO `paiements` (`id_paiement`, `id_ticket`, `montant`, `mode_paiement`, `date_paiement`, `id_utilisateur`, `reference`, `notes`) VALUES
(1, 9, 300.00, 'especes', '2026-01-09 16:20:43', 1, 'PAY-TK-260109-0001', NULL),
(2, 9, 30.00, 'especes', '2026-01-09 16:50:54', 1, 'ENC-TK-260109-0001', NULL),
(3, 10, 3000.00, 'especes', '2026-01-13 16:08:52', 1, 'PAY-TK-260113-0001', NULL),
(4, 10, 1300.00, 'especes', '2026-01-13 17:16:13', 1, 'ENC-TK-260113-0001', NULL),
(5, 11, 4000.00, 'especes', '2026-01-28 15:51:16', 1, 'PAY-TK-260128-0001', NULL),
(6, 11, 150.00, 'especes', '2026-01-28 16:06:44', 1, 'ENC-TK-260128-0001', NULL),
(7, 12, 9000.00, 'especes', '2026-01-29 14:58:10', 1, 'PAY-TK-260129-0001', NULL),
(8, 13, 1500.00, 'especes', '2026-01-29 15:00:09', 1, 'PAY-TK-260129-0002', NULL),
(9, 14, 5000.00, 'especes', '2026-01-29 15:11:55', 1, 'PAY-TK-260129-0003', NULL),
(10, 12, 8.00, 'especes', '2026-01-29 15:38:23', 1, 'ENC-TK-260129-0001', NULL),
(11, 14, 500.00, 'especes', '2026-02-06 12:08:54', 1, 'ENC-TK-260129-0003', NULL),
(12, 18, 9050.00, 'especes', '2026-02-06 12:09:07', 1, 'ENC-HOT-260206115334', NULL),
(13, 19, 13525.00, 'especes', '2026-02-06 12:15:12', 1, 'ENC-CH-23-CF18', NULL),
(14, 13, 175.00, 'especes', '2026-02-06 12:19:56', 1, 'ENC-TK-260129-0002', NULL),
(15, 20, 2000.00, 'especes', '2026-02-06 15:00:37', 1, 'PAY-TK-260206-0001', NULL),
(16, 21, 3000.00, 'especes', '2026-02-06 15:06:01', 1, 'PAY-TK-260206-0002', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `parametres_systeme`
--

CREATE TABLE `parametres_systeme` (
  `id_parametre` int(11) NOT NULL,
  `cle_parametre` varchar(100) NOT NULL,
  `valeur_parametre` text DEFAULT NULL,
  `type_parametre` enum('string','integer','float','boolean','json') DEFAULT 'string',
  `categorie` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `parametres_systeme`
--

INSERT INTO `parametres_systeme` (`id_parametre`, `cle_parametre`, `valeur_parametre`, `type_parametre`, `categorie`, `description`) VALUES
(1, 'app_name', 'Pressing Manager Pro', 'string', 'general', 'Nom de l\'application'),
(2, 'currency', '€', 'string', 'general', 'Devise'),
(3, 'tax_rate', '20', 'float', 'general', 'Taux de TVA'),
(4, 'alert_stock_days', '7', 'integer', 'stock', 'Jours d\'alerte stock'),
(5, 'auto_backup', '1', 'boolean', 'system', 'Sauvegarde automatique'),
(6, 'backup_time', '02:00', 'string', 'system', 'Heure de sauvegarde'),
(7, 'max_login_attempts', '3', 'integer', 'security', 'Tentatives de connexion max'),
(8, 'session_timeout', '1800', 'integer', 'security', 'Timeout session (secondes)');

-- --------------------------------------------------------

--
-- Structure de la table `permissions`
--

CREATE TABLE `permissions` (
  `id_permission` int(11) NOT NULL,
  `code_permission` varchar(100) NOT NULL,
  `nom_permission` varchar(255) NOT NULL,
  `categorie` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `permissions`
--

INSERT INTO `permissions` (`id_permission`, `code_permission`, `nom_permission`, `categorie`, `description`) VALUES
(1, 'dashboard', 'Tableau de Bord', 'Navigation Principale', 'Accès au tableau de bord principal'),
(2, 'intelligent_dashboard', 'Dashboard Intelligent', 'Navigation Principale', 'Accès au dashboard intelligent avec analyses avancées'),
(3, 'gestion_tickets', 'Gestion des Tickets', 'Opérations', 'Créer, modifier et gérer les tickets'),
(4, 'tracabilite', 'Traçabilité', 'Opérations', 'Suivi RFID et QR code des articles'),
(5, 'atelier', 'Atelier', 'Opérations', 'Gestion de la production et planning machines'),
(6, 'maintenance', 'Maintenance', 'Opérations', 'Planning et suivi des interventions'),
(7, 'qualite', 'Qualité', 'Opérations', 'Gestion des réclamations et satisfaction'),
(8, 'gestion_clients', 'Gestion des Clients', 'Clients & Ventes', 'Créer et gérer les fiches clients'),
(9, 'abonnements', 'Abonnements', 'Clients & Ventes', 'Gérer les abonnements clients'),
(10, 'fidelite', 'Fidélité', 'Clients & Ventes', 'Programme de fidélité et promotions'),
(11, 'caisse', 'Caisse', 'Clients & Ventes', 'Accès à la caisse et encaissements'),
(12, 'gestion_factures', 'Factures', 'Clients & Ventes', 'Création et gestion des factures'),
(13, 'gestion_stock', 'Gestion du Stock', 'Stock & Inventaire', 'Gestion des produits et inventaire'),
(14, 'consommables', 'Consommables', 'Stock & Inventaire', 'Gestion des consommables et alertes'),
(15, 'gestion_fournisseurs', 'Fournisseurs', 'Stock & Inventaire', 'Gestion des fournisseurs'),
(16, 'administration', 'Administration', 'Administration', 'Accès à l\'administration générale'),
(17, 'audit', 'Audit & Logs', 'Administration', 'Consultation des logs d\'activité'),
(18, 'reservation_online', 'Réservation en Ligne', 'Services & Digital', 'Gestion des réservations en ligne'),
(19, 'client_portal', 'Portail Client', 'Services & Digital', 'Accès au portail client'),
(20, 'paiements_online', 'Paiements Online', 'Services & Digital', 'Gestion des paiements en ligne'),
(21, 'livraison', 'Livraison', 'Services & Digital', 'Planning des tournées et suivi GPS'),
(22, 'urgences', 'Services Urgences', 'Services & Digital', 'Service express et urgences'),
(23, 'boutique', 'Boutique en Ligne', 'Services & Digital', 'Boutique en ligne et commandes'),
(24, 'marketing', 'Marketing', 'Marketing & Communication', 'Campagnes email et avis clients'),
(25, 'notifications', 'Notifications', 'Marketing & Communication', 'Envoi de SMS et emails automatiques'),
(26, 'rapports', 'Rapports', 'Administration', 'Génération et consultation des rapports'),
(27, 'gestion_comptes', 'Comptes', 'Administration', 'Gestion des comptes clients'),
(28, 'gestion_agences', 'Gestion des Agences', 'Administration', 'Gestion des agences'),
(29, 'partenariats', 'Partenariats', 'Administration', 'Gestion des partenariats'),
(30, 'environnement', 'Environnement', 'Administration', 'Suivi environnemental'),
(31, 'gestion_caisse', 'Gestion Caisse', 'Administration', 'Gestion complète de la caisse');

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

CREATE TABLE `produits` (
  `id_produit` int(11) NOT NULL,
  `nom_produit` varchar(100) NOT NULL,
  `id_fournisseur` int(11) DEFAULT NULL,
  `categorie` enum('lessive','detachant','cintre','sac','etiquette','autre') NOT NULL,
  `unite_mesure` varchar(20) DEFAULT 'unite',
  `quantite_stock` decimal(10,2) DEFAULT 0.00,
  `seuil_alerte` decimal(10,2) DEFAULT 10.00,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `emplacement` varchar(50) DEFAULT NULL,
  `date_peremption` date DEFAULT NULL,
  `est_actif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id_produit`, `nom_produit`, `id_fournisseur`, `categorie`, `unite_mesure`, `quantite_stock`, `seuil_alerte`, `prix_unitaire`, `emplacement`, `date_peremption`, `est_actif`) VALUES
(1, 'jrtitr', NULL, 'lessive', '', 4.00, 10.00, 5000.00, '', NULL, 1),
(2, 'jrtitr', 1, 'lessive', '', 2.00, 10.00, 5000.00, '', NULL, 1),
(3, 'jrtitr', 1, 'lessive', '', 2.00, 10.00, 5000.00, '', NULL, 1),
(4, 'jrtitr', 1, 'lessive', '', 2.00, 10.00, 5000.00, '', NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `produits_chimiques`
--

CREATE TABLE `produits_chimiques` (
  `id_produit` int(11) NOT NULL,
  `nom_produit` varchar(100) NOT NULL,
  `code_interne` varchar(20) DEFAULT NULL,
  `type_usage` varchar(50) DEFAULT NULL COMMENT 'Détachage, Solvant, Lessive',
  `stock_actuel` decimal(10,2) DEFAULT 0.00,
  `unite` varchar(10) DEFAULT 'L',
  `degre_dangerosite` int(11) DEFAULT 1 COMMENT '1 à 5',
  `icon_danger` varchar(50) DEFAULT 'fa-exclamation-circle',
  `couleur_alerte` varchar(20) DEFAULT 'warning',
  `fds_valide` tinyint(1) DEFAULT 1,
  `url_fds` varchar(255) DEFAULT NULL,
  `date_derniere_maj` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `produits_chimiques`
--

INSERT INTO `produits_chimiques` (`id_produit`, `nom_produit`, `code_interne`, `type_usage`, `stock_actuel`, `unite`, `degre_dangerosite`, `icon_danger`, `couleur_alerte`, `fds_valide`, `url_fds`, `date_derniere_maj`) VALUES
(1, 'Perchloroéthylène', 'SOLV-01', 'Solvant Machine', 200.00, 'L', 5, 'fa-biohazard', 'danger', 1, NULL, '2026-01-26 12:05:27'),
(2, 'K-Tex Détachant', 'DET-05', 'Détachage à froid', 15.50, 'L', 3, 'fa-flask', 'warning', 0, NULL, '2026-01-26 12:05:27'),
(3, 'Alcali Lessive', 'LES-10', 'Nettoyage Eau', 50.00, 'L', 2, 'fa-tint', 'info', 1, NULL, '2026-01-26 12:05:27');

-- --------------------------------------------------------

--
-- Structure de la table `programme_fidelite`
--

CREATE TABLE `programme_fidelite` (
  `id_fidelite` int(11) NOT NULL,
  `id_client` int(11) DEFAULT NULL,
  `points_cumules` int(11) DEFAULT 0,
  `niveau` varchar(50) DEFAULT NULL,
  `date_inscription` date DEFAULT NULL,
  `parrain_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `promotions`
--

CREATE TABLE `promotions` (
  `id_promo` int(11) NOT NULL,
  `nom_promo` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `code_promo` varchar(20) DEFAULT NULL,
  `type_remise` enum('pourcentage','fixe') DEFAULT 'pourcentage',
  `valeur_remise` decimal(10,2) NOT NULL,
  `cible_client` varchar(50) DEFAULT 'Tous les clients',
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `est_active` tinyint(1) DEFAULT 1,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `promotions`
--

INSERT INTO `promotions` (`id_promo`, `nom_promo`, `description`, `code_promo`, `type_remise`, `valeur_remise`, `cible_client`, `date_debut`, `date_fin`, `est_active`, `date_creation`) VALUES
(1, 'Offre de Bienvenue', 'Réduction sur la toute première commande en boutique.', 'WELCOME26', 'pourcentage', 15.00, 'Nouveaux uniquement', '2026-01-23', '2027-01-23', 1, '2026-01-23 13:43:37'),
(2, 'Pack Mariage', 'Nettoyage complet Robe de mariée + Costume.', NULL, 'fixe', 5000.00, 'Tous les clients', '2026-01-23', '2026-03-23', 1, '2026-01-23 13:43:37'),
(3, 'Réactivation Client', 'On vous revoit ? -20% sur votre prochain passage.', 'REVIENS', 'pourcentage', 20.00, 'Clients inactifs (>3 mois)', '2026-01-23', '2026-02-23', 1, '2026-01-23 13:43:37'),
(4, 'solde de janvier', 'Facture Fournisseur N° 5465gfreuy - Berlin', 'été', 'pourcentage', 10.00, 'Nouveaux clients', '2026-01-29', '2026-06-29', 1, '2026-01-29 14:40:47');

-- --------------------------------------------------------

--
-- Structure de la table `recettes`
--

CREATE TABLE `recettes` (
  `id_recette` int(11) NOT NULL,
  `id_agence` int(11) NOT NULL,
  `date_recette` date NOT NULL,
  `montant_total` decimal(10,2) NOT NULL,
  `montant_especes` decimal(10,2) DEFAULT 0.00,
  `montant_carte` decimal(10,2) DEFAULT 0.00,
  `montant_cheque` decimal(10,2) DEFAULT 0.00,
  `montant_mobile` decimal(10,2) DEFAULT 0.00,
  `nombre_tickets` int(11) DEFAULT 0,
  `id_utilisateur` int(11) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `registre_dechets`
--

CREATE TABLE `registre_dechets` (
  `id` int(11) NOT NULL,
  `date_collecte` date NOT NULL,
  `type_dechet` varchar(100) NOT NULL,
  `poids_volume` decimal(10,2) NOT NULL,
  `unite` enum('Kg','Litres','Unités') DEFAULT 'Kg',
  `prestataire` varchar(150) NOT NULL,
  `num_bordereau` varchar(50) DEFAULT NULL,
  `statut` enum('En attente','Traité','Recyclé') DEFAULT 'Traité',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `registre_dechets`
--

INSERT INTO `registre_dechets` (`id`, `date_collecte`, `type_dechet`, `poids_volume`, `unite`, `prestataire`, `num_bordereau`, `statut`, `created_at`) VALUES
(1, '2026-01-12', 'Boues de solvants', 45.00, 'Litres', 'Eco-Clean Services', 'BSDD-2026-001', 'Traité', '2026-01-26 12:09:50'),
(2, '2026-01-15', 'Films plastiques LDPE', 12.50, 'Kg', 'Recyclage Pro', 'CERT-9928', 'Traité', '2026-01-26 12:09:50'),
(3, '2026-01-20', 'Cartons et papiers', 30.00, 'Kg', 'Association Locale', 'REC-441', 'Traité', '2026-01-26 12:09:50');

-- --------------------------------------------------------

--
-- Structure de la table `reservations_online`
--

CREATE TABLE `reservations_online` (
  `id_reservation` int(11) NOT NULL,
  `id_client` int(11) DEFAULT NULL,
  `date_reservation` datetime DEFAULT NULL,
  `creneau_horaire` varchar(50) DEFAULT NULL,
  `statut` varchar(20) DEFAULT NULL,
  `token_confirmation` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
  `id_role` int(11) NOT NULL,
  `nom_role` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `niveau_permission` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `description_role` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id_role`, `nom_role`, `description`, `niveau_permission`, `created_at`, `description_role`) VALUES
(1, 'patron', 'Propriétaire/Super Administrateur', 100, '2026-01-08 21:50:34', NULL),
(2, 'receptionniste', 'Agent de réception', 70, '2026-01-08 21:50:34', NULL),
(3, 'caissier', 'Agent de caisse', 65, '2026-01-08 21:50:34', NULL),
(4, 'gestionnaire_stock', 'Gestionnaire de stock', 75, '2026-01-08 21:50:34', NULL),
(5, 'employe_pressing', 'Employé pressing', 50, '2026-01-08 21:50:34', NULL),
(6, 'Copie de receptionniste', 'Création de tickets, encaissement', 50, '2026-01-12 07:57:09', NULL),
(7, 'Responsable', 'Responsable d\'agence', 90, '2026-01-29 11:38:31', NULL),
(8, 'Technicien', 'Technicien pressing', 60, '2026-01-29 11:38:31', NULL),
(10, 'reception_hotel', NULL, 1, '2026-02-04 14:49:56', 'Réceptionniste Hôtel - Gestion des réservations et services hôteliers'),
(11, 'gestionnaire_hotel', NULL, 1, '2026-02-04 14:49:56', 'Gestionnaire Hôtel - Administration complète de l\'hôtel'),
(12, 'vendeur_boutique', NULL, 1, '2026-02-04 14:49:56', 'Vendeur Boutique - Gestion des ventes et stocks commerce'),
(13, 'gestionnaire_commerce', NULL, 1, '2026-02-04 14:49:56', 'Gestionnaire Commerce - Administration complète du commerce');

-- --------------------------------------------------------

--
-- Structure de la table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id_role` int(11) NOT NULL,
  `id_permission` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `role_permissions`
--

INSERT INTO `role_permissions` (`id_role`, `id_permission`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 16),
(1, 17),
(1, 18),
(1, 19),
(1, 20),
(1, 21),
(1, 22),
(1, 23),
(1, 24),
(1, 25),
(1, 26),
(1, 27),
(1, 28),
(1, 29),
(1, 30),
(1, 31),
(2, 3),
(2, 4),
(2, 8),
(2, 9),
(2, 10),
(2, 11),
(2, 18),
(2, 22),
(3, 3),
(3, 10),
(3, 11),
(3, 20),
(4, 1),
(4, 5),
(4, 13),
(4, 14),
(5, 3),
(5, 4),
(5, 5),
(7, 1),
(7, 2),
(7, 3),
(7, 4),
(7, 5),
(7, 6),
(7, 7),
(7, 8),
(7, 9),
(7, 10),
(7, 12),
(7, 13),
(7, 14),
(7, 21),
(7, 22),
(7, 26),
(7, 27),
(7, 31),
(8, 3),
(8, 4),
(8, 5),
(8, 6),
(8, 13);

-- --------------------------------------------------------

--
-- Structure de la table `services`
--

CREATE TABLE `services` (
  `id_service` int(11) NOT NULL,
  `nom_service` varchar(100) NOT NULL,
  `id_categorie` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `duree_estimee` int(11) DEFAULT NULL COMMENT 'Durée en minutes',
  `unite_mesure` varchar(50) DEFAULT 'pièce',
  `est_disponible` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `services`
--

INSERT INTO `services` (`id_service`, `nom_service`, `id_categorie`, `description`, `prix_unitaire`, `duree_estimee`, `unite_mesure`, `est_disponible`) VALUES
(1, 'Costume complet', 1, 'Nettoyage à sec d\'un costume', 25.00, 120, 'pièce', 1),
(2, 'Robe de soirée', 1, 'Nettoyage à sec d\'une robe', 30.00, 180, 'pièce', 1),
(3, 'Chemise homme', 2, 'Lavage et repassage', 8.00, 30, 'pièce', 1),
(4, 'Pantalon', 2, 'Lavage et repassage', 10.00, 45, 'pièce', 1),
(5, 'Repassage chemise', 3, 'Repassage uniquement', 5.00, 15, 'pièce', 1),
(6, 'Détachage tache', 4, 'Traitement de tache spécifique', 15.00, 60, 'tache', 1),
(7, 'Ourlet pantalon', 4, 'Réfection d\'ourlet', 12.00, 45, 'ourlet', 1),
(8, 'Lavage Veste Costume', 1, NULL, 1500.00, NULL, 'pièce', 1),
(9, 'Lavage Linge de maison', 1, NULL, 1000.00, NULL, 'kg', 1),
(10, 'Bière Kadji 65cl', 2, NULL, 650.00, NULL, 'bouteille', 1),
(11, 'Casier Djino (Verre)', 2, NULL, 4500.00, NULL, 'casier', 1),
(12, 'Ndole Viande / Poisson', 3, NULL, 2500.00, NULL, 'plat', 1);

-- --------------------------------------------------------

--
-- Structure de la table `tarifs_activites`
--

CREATE TABLE `tarifs_activites` (
  `id` int(11) NOT NULL,
  `type_activite` varchar(20) NOT NULL,
  `code_tarif` varchar(50) NOT NULL,
  `libelle_tarif` varchar(100) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `devise` varchar(3) DEFAULT 'XAF',
  `actif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tarif_categories`
--

CREATE TABLE `tarif_categories` (
  `id_categorie` int(11) NOT NULL,
  `code_categorie` varchar(50) NOT NULL,
  `nom_categorie` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `type_activite` varchar(20) DEFAULT 'all',
  `ordre_affichage` int(11) DEFAULT 0,
  `actif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tickets`
--

CREATE TABLE `tickets` (
  `id_ticket` int(11) NOT NULL,
  `numero_ticket` varchar(20) NOT NULL,
  `id_client` int(11) DEFAULT NULL,
  `id_agence` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL COMMENT 'Réceptionniste',
  `date_depot` datetime NOT NULL,
  `date_retrait_prevue` datetime DEFAULT NULL,
  `date_retrait_reelle` datetime DEFAULT NULL,
  `statut` enum('en_attente','en_traitement','pret','recupere','annule') DEFAULT 'en_attente',
  `notes` text DEFAULT NULL,
  `montant_total` decimal(10,2) DEFAULT 0.00,
  `montant_verse` decimal(10,2) DEFAULT 0.00,
  `montant_remise` decimal(10,2) DEFAULT 0.00,
  `mode_paiement` enum('especes','carte','cheque','mobile','autre') DEFAULT 'especes',
  `notes_client` text DEFAULT NULL,
  `notes_interne` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nombre_articles` int(11) DEFAULT 1,
  `statut_livraison` varchar(50) DEFAULT 'en_attente',
  `mode_retrait` enum('boutique','livraison') DEFAULT 'boutique',
  `total_ttc` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tickets`
--

INSERT INTO `tickets` (`id_ticket`, `numero_ticket`, `id_client`, `id_agence`, `id_utilisateur`, `date_depot`, `date_retrait_prevue`, `date_retrait_reelle`, `statut`, `notes`, `montant_total`, `montant_verse`, `montant_remise`, `mode_paiement`, `notes_client`, `notes_interne`, `created_at`, `updated_at`, `nombre_articles`, `statut_livraison`, `mode_retrait`, `total_ttc`) VALUES
(9, 'TK-260109-0001', 1, 1, 1, '2026-01-09 16:20:40', '2026-01-11 16:12:00', '2026-01-09 16:59:50', 'pret', NULL, 330.00, 330.00, 0.00, 'especes', '', NULL, '2026-01-09 15:20:40', '2026-02-06 14:34:06', 1, 'en_attente', 'boutique', 0.00),
(10, 'TK-260113-0001', 1, 1, 1, '2026-01-13 16:08:52', '2026-01-16 15:58:00', '2026-01-14 07:55:25', 'pret', NULL, 4300.00, 4300.00, 0.00, 'especes', '', NULL, '2026-01-13 15:08:52', '2026-01-29 14:14:12', 1, 'en_attente', 'boutique', 0.00),
(11, 'TK-260128-0001', 2, 1, 1, '2026-01-28 15:51:15', '2026-01-31 15:50:00', NULL, 'pret', NULL, 4150.00, 4150.00, 0.00, 'especes', '', NULL, '2026-01-28 14:51:15', '2026-02-06 14:26:51', 1, 'en_attente', 'boutique', 0.00),
(12, 'TK-260129-0001', 2, 1, 1, '2026-01-29 14:58:10', '2026-02-01 14:55:00', NULL, 'recupere', NULL, 9008.00, 9008.00, 0.00, 'especes', '', NULL, '2026-01-29 13:58:10', '2026-02-06 14:51:27', 1, 'en_attente', 'boutique', 0.00),
(13, 'TK-260129-0002', 2, 1, 1, '2026-01-29 15:00:09', '2026-02-01 14:59:00', NULL, 'recupere', NULL, 1675.00, 1675.00, 0.00, 'especes', '', NULL, '2026-01-29 14:00:09', '2026-02-06 14:48:40', 1, 'en_attente', 'boutique', 0.00),
(14, 'TK-260129-0003', 2, 1, 1, '2026-01-29 15:11:55', '2026-02-01 15:11:00', NULL, 'recupere', NULL, 5500.00, 5500.00, 0.00, 'especes', '', NULL, '2026-01-29 14:11:55', '2026-02-06 14:44:04', 1, 'en_attente', 'boutique', 0.00),
(18, 'HOT-260206115334', 3, 1, 1, '2026-02-06 11:53:34', NULL, NULL, 'en_traitement', 'Priorité: Normal', 9050.00, 9050.00, 0.00, 'especes', 'Chambre: 23', NULL, '2026-02-06 10:53:34', '2026-02-06 11:09:07', 4, 'en_attente', 'boutique', 0.00),
(19, 'CH-23-CF18', 3, 1, 1, '2026-02-06 11:58:42', NULL, NULL, 'en_traitement', 'Linge Chambre: 23 | Priorité: Express', 13525.00, 13525.00, 0.00, 'especes', NULL, NULL, '2026-02-06 10:58:42', '2026-02-06 11:15:12', 1, 'en_attente', 'boutique', 13525.00),
(20, 'TK-260206-0001', 2, 1, 1, '2026-02-06 15:00:36', '2026-02-09 14:59:00', NULL, 'en_attente', NULL, 2500.00, 2000.00, 0.00, 'especes', '', NULL, '2026-02-06 14:00:36', '2026-02-06 14:00:36', 1, 'en_attente', 'boutique', 0.00),
(21, 'TK-260206-0002', 2, 1, 1, '2026-02-06 15:06:01', '2026-02-09 14:59:00', NULL, 'en_attente', NULL, 3150.00, 3000.00, 0.00, 'especes', '', NULL, '2026-02-06 14:06:01', '2026-02-06 14:06:01', 1, 'en_attente', 'boutique', 0.00);

-- --------------------------------------------------------

--
-- Structure de la table `tournees`
--

CREATE TABLE `tournees` (
  `id_tournee` int(11) NOT NULL,
  `id_livreur` int(11) NOT NULL,
  `date_tournee` date NOT NULL,
  `heure_depart` time DEFAULT NULL,
  `vehicule_immatriculation` varchar(50) DEFAULT NULL,
  `zone_geographique` varchar(100) DEFAULT NULL,
  `statut` enum('preparation','en_cours','terminee') DEFAULT 'preparation',
  `zone_livraison` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tournees`
--

INSERT INTO `tournees` (`id_tournee`, `id_livreur`, `date_tournee`, `heure_depart`, `vehicule_immatriculation`, `zone_geographique`, `statut`, `zone_livraison`) VALUES
(1, 1, '2026-01-23', '09:00:00', 'LT-882-AB', 'Bonapriso / Akwa', 'preparation', NULL),
(2, 1, '2026-02-06', '11:41:00', NULL, 'Collecte - ', 'preparation', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `tournees_details`
--

CREATE TABLE `tournees_details` (
  `id_detail` int(11) NOT NULL,
  `id_tournee` int(11) NOT NULL,
  `id_ticket` int(11) NOT NULL,
  `ordre_passage` int(11) DEFAULT NULL,
  `statut_livraison` enum('en_attente','livre','absent','annule') DEFAULT 'en_attente',
  `heure_effective_livraison` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tracabilite_tickets`
--

CREATE TABLE `tracabilite_tickets` (
  `id_trace` int(11) NOT NULL,
  `id_ticket` int(11) DEFAULT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `date_heure` datetime DEFAULT current_timestamp(),
  `photo_avant` varchar(255) DEFAULT NULL,
  `photo_apres` varchar(255) DEFAULT NULL,
  `note_qualite` text DEFAULT NULL,
  `note_commentaire` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id_utilisateur` int(11) NOT NULL,
  `nom_complet` varchar(100) NOT NULL,
  `login_utilisateur` varchar(50) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `id_role` int(11) DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `derniere_connexion` datetime DEFAULT NULL,
  `est_actif` tinyint(1) DEFAULT 1,
  `tentatives_echec` int(11) DEFAULT 0,
  `date_blocage` datetime DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `remember_expiry` datetime DEFAULT NULL,
  `code_agence` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_utilisateur`, `nom_complet`, `login_utilisateur`, `mot_de_passe`, `email`, `telephone`, `id_role`, `date_creation`, `derniere_connexion`, `est_actif`, `tentatives_echec`, `date_blocage`, `remember_token`, `remember_expiry`, `code_agence`) VALUES
(1, 'Jean', 'reception1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reception1@pressing.com', '0123456789', 2, '2026-01-09 08:30:32', '2026-02-06 15:22:04', 1, 0, NULL, NULL, NULL, NULL),
(2, 'Caissier Test', 'caissier', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'caissier@pressing.com', '0123456788', 3, '2026-01-09 08:50:00', '2026-02-06 15:07:23', 1, 0, NULL, NULL, NULL, 'AG001'),
(3, 'jean', 'jeannot', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cyrillesteve@gmail.com', '696197525', 3, '2026-01-09 11:26:47', '2026-02-06 10:00:29', 1, 0, NULL, NULL, NULL, NULL),
(4, 'Administrateur Principal', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@pressing.com', '00000000', 1, '2026-01-09 12:15:02', '2026-02-06 08:47:51', 1, 0, NULL, NULL, NULL, NULL),
(5, 'Tamboug Cyrille Steve', 'steve', '$2y$10$CtwDBbgtjOWXYPOijSwIMOq2tVpeP3Vh4MJaZou9qlKu2P1WPEn0i', 'cyrivelmars2013@gmail.com', '696167525', 2, '2026-01-12 07:19:25', NULL, 1, 3, NULL, NULL, NULL, '1'),
(6, 'employe_pressing', 'employe_pressing', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 5, '2026-01-12 10:11:44', '2026-02-06 15:25:27', 1, 0, NULL, NULL, NULL, NULL),
(7, 'gestionnaire_stock', 'gestionnaire_stock', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 4, '2026-01-12 10:19:41', '2026-02-06 10:07:07', 1, 0, NULL, NULL, NULL, NULL),
(9, 'Sophie Martin', 'reception_hotel', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reception.hotel@kayade.com', '0123456701', 10, '2026-02-04 14:49:57', '2026-02-06 10:08:18', 1, 0, NULL, NULL, NULL, NULL),
(10, 'Marc Dubois', 'gestion_hotel', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gestion.hotel@kayade.com', '0123456702', 11, '2026-02-04 14:49:57', '2026-02-06 16:05:25', 1, 0, NULL, NULL, NULL, NULL),
(11, 'Marie Petit', 'service_chambre', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'service.chambre@kayade.com', '0123456703', 5, '2026-02-04 14:49:57', '2026-02-06 10:13:15', 1, 0, NULL, NULL, NULL, NULL),
(12, 'Julie Lambert', 'vendeur_boutique', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendeur.boutique@kayade.com', '0123456704', 12, '2026-02-04 14:49:57', '2026-02-06 14:33:52', 1, 0, NULL, NULL, NULL, NULL),
(13, 'Thomas Renault', 'gestion_commerce', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gestion.commerce@kayade.com', '0123456705', 13, '2026-02-04 14:49:57', '2026-02-06 16:06:37', 1, 0, NULL, NULL, NULL, NULL),
(14, 'Laura Moreau', 'caissier_boutique', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'caissier.boutique@kayade.com', '0123456706', 3, '2026-02-04 14:49:57', '2026-02-06 16:08:52', 1, 0, NULL, NULL, NULL, NULL),
(15, 'Directeur Kayade', 'directeur', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'directeur@kayade.com', '0123456700', 1, '2026-02-04 14:49:58', '2026-02-06 11:25:43', 1, 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur_activites`
--

CREATE TABLE `utilisateur_activites` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `type_activite` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateur_activites`
--

INSERT INTO `utilisateur_activites` (`id`, `id_utilisateur`, `type_activite`) VALUES
(7, 1, 'pressing'),
(8, 2, 'pressing'),
(12, 4, 'all'),
(9, 6, 'pressing'),
(10, 7, 'pressing'),
(1, 9, 'hotel'),
(2, 10, 'hotel'),
(3, 11, 'hotel'),
(4, 12, 'commerce'),
(5, 13, 'commerce'),
(6, 14, 'commerce'),
(11, 15, 'all');

-- --------------------------------------------------------

--
-- Structure de la table `ventes_boutique`
--

CREATE TABLE `ventes_boutique` (
  `id_vente` int(11) NOT NULL,
  `date_vente` datetime DEFAULT current_timestamp(),
  `id_client` int(11) DEFAULT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `montant_total` decimal(10,0) NOT NULL,
  `nb_articles` int(11) DEFAULT 0,
  `mode_paiement` varchar(50) DEFAULT 'Espèces'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ventes_boutique_details`
--

CREATE TABLE `ventes_boutique_details` (
  `id_detail` int(11) NOT NULL,
  `id_vente` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ventes_details`
--

CREATE TABLE `ventes_details` (
  `id` int(11) NOT NULL,
  `id_vente` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `sous_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `abonnements`
--
ALTER TABLE `abonnements`
  ADD PRIMARY KEY (`id_abonnement`),
  ADD KEY `id_client` (`id_client`);

--
-- Index pour la table `activites_config`
--
ALTER TABLE `activites_config`
  ADD PRIMARY KEY (`id_activite`),
  ADD UNIQUE KEY `type_activite` (`type_activite`);

--
-- Index pour la table `agences`
--
ALTER TABLE `agences`
  ADD PRIMARY KEY (`id_agence`),
  ADD KEY `responsable_id` (`responsable_id`);

--
-- Index pour la table `archives_alertes`
--
ALTER TABLE `archives_alertes`
  ADD PRIMARY KEY (`id_archive`);

--
-- Index pour la table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `boutique_produits`
--
ALTER TABLE `boutique_produits`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `categories_service`
--
ALTER TABLE `categories_service`
  ADD PRIMARY KEY (`id_categorie`);

--
-- Index pour la table `certifications`
--
ALTER TABLE `certifications`
  ADD PRIMARY KEY (`id_certif`);

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id_client`),
  ADD KEY `id_agence` (`id_agence`),
  ADD KEY `fk_parrain` (`id_parrain`);

--
-- Index pour la table `client_activites`
--
ALTER TABLE `client_activites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_client` (`id_client`),
  ADD KEY `idx_activite` (`type_activite`);

--
-- Index pour la table `clotures_caisse`
--
ALTER TABLE `clotures_caisse`
  ADD PRIMARY KEY (`id_cloture`),
  ADD KEY `id_agence` (`id_agence`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `comptes`
--
ALTER TABLE `comptes`
  ADD PRIMARY KEY (`id_compte`);

--
-- Index pour la table `consommables`
--
ALTER TABLE `consommables`
  ADD PRIMARY KEY (`id_consommable`);

--
-- Index pour la table `cycles_production`
--
ALTER TABLE `cycles_production`
  ADD PRIMARY KEY (`id_cycle`),
  ADD KEY `idx_cycles_machine` (`id_machine`,`heure_debut`);

--
-- Index pour la table `depenses`
--
ALTER TABLE `depenses`
  ADD PRIMARY KEY (`id_depense`),
  ADD KEY `id_agence` (`id_agence`),
  ADD KEY `id_fournisseur` (`id_fournisseur`),
  ADD KEY `id_utilisateur` (`id_utilisateur`),
  ADD KEY `idx_depenses_date` (`date_depense`);

--
-- Index pour la table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `equipements`
--
ALTER TABLE `equipements`
  ADD PRIMARY KEY (`id_equipement`);

--
-- Index pour la table `etapes_production`
--
ALTER TABLE `etapes_production`
  ADD PRIMARY KEY (`id_etape`),
  ADD KEY `id_ticket` (`id_ticket`);

--
-- Index pour la table `factures`
--
ALTER TABLE `factures`
  ADD PRIMARY KEY (`id_facture`);

--
-- Index pour la table `fidelite_cartes`
--
ALTER TABLE `fidelite_cartes`
  ADD PRIMARY KEY (`id_carte`),
  ADD UNIQUE KEY `numero_carte` (`numero_carte`),
  ADD KEY `fk_fidelite_client` (`id_client`);

--
-- Index pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  ADD PRIMARY KEY (`id_fournisseur`);

--
-- Index pour la table `historique_cycles`
--
ALTER TABLE `historique_cycles`
  ADD PRIMARY KEY (`id_historique`),
  ADD KEY `id_cycle` (`id_cycle`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `historique_tarifs`
--
ALTER TABLE `historique_tarifs`
  ADD PRIMARY KEY (`id_histo`),
  ADD KEY `id_service` (`id_service`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `interventions`
--
ALTER TABLE `interventions`
  ADD PRIMARY KEY (`id_intervention`),
  ADD KEY `fk_intervention_equipement` (`id_equipement`),
  ADD KEY `fk_intervention_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `journal_production`
--
ALTER TABLE `journal_production`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_ligne` (`id_ligne`),
  ADD KEY `date_action` (`date_action`);

--
-- Index pour la table `lignes_facture`
--
ALTER TABLE `lignes_facture`
  ADD PRIMARY KEY (`id_ligne`),
  ADD KEY `id_facture` (`id_facture`);

--
-- Index pour la table `lignes_ticket`
--
ALTER TABLE `lignes_ticket`
  ADD PRIMARY KEY (`id_ligne`),
  ADD UNIQUE KEY `numero_etiquette` (`numero_etiquette`),
  ADD KEY `id_service` (`id_service`),
  ADD KEY `idx_lignes_ticket` (`id_ticket`);

--
-- Index pour la table `logs_activite`
--
ALTER TABLE `logs_activite`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `machines`
--
ALTER TABLE `machines`
  ADD PRIMARY KEY (`id_machine`);

--
-- Index pour la table `modeles_facture`
--
ALTER TABLE `modeles_facture`
  ADD PRIMARY KEY (`id_modele`);

--
-- Index pour la table `mouvements_caisse`
--
ALTER TABLE `mouvements_caisse`
  ADD PRIMARY KEY (`id_mouvement`),
  ADD KEY `id_compte` (`id_compte`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD PRIMARY KEY (`id_mouvement`),
  ADD KEY `id_produit` (`id_produit`),
  ADD KEY `id_utilisateur` (`id_utilisateur`),
  ADD KEY `idx_mouvements_date` (`date_mouvement`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id_notification`),
  ADD KEY `id_utilisateur` (`id_utilisateur`),
  ADD KEY `id_agence` (`id_agence`);

--
-- Index pour la table `notifications_clients`
--
ALTER TABLE `notifications_clients`
  ADD PRIMARY KEY (`id_notification`),
  ADD KEY `id_client` (`id_client`);

--
-- Index pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD PRIMARY KEY (`id_paiement`),
  ADD KEY `id_ticket` (`id_ticket`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `parametres_systeme`
--
ALTER TABLE `parametres_systeme`
  ADD PRIMARY KEY (`id_parametre`),
  ADD UNIQUE KEY `cle_parametre` (`cle_parametre`);

--
-- Index pour la table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id_permission`),
  ADD UNIQUE KEY `code_permission` (`code_permission`),
  ADD UNIQUE KEY `UNIQUE_code_permission` (`code_permission`),
  ADD UNIQUE KEY `code_permission_2` (`code_permission`),
  ADD KEY `idx_code_permission` (`code_permission`);

--
-- Index pour la table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id_produit`),
  ADD KEY `id_fournisseur` (`id_fournisseur`),
  ADD KEY `idx_produits_stock` (`quantite_stock`);

--
-- Index pour la table `produits_chimiques`
--
ALTER TABLE `produits_chimiques`
  ADD PRIMARY KEY (`id_produit`),
  ADD UNIQUE KEY `code_interne` (`code_interne`);

--
-- Index pour la table `programme_fidelite`
--
ALTER TABLE `programme_fidelite`
  ADD PRIMARY KEY (`id_fidelite`),
  ADD KEY `id_client` (`id_client`);

--
-- Index pour la table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id_promo`),
  ADD UNIQUE KEY `code_promo` (`code_promo`);

--
-- Index pour la table `recettes`
--
ALTER TABLE `recettes`
  ADD PRIMARY KEY (`id_recette`),
  ADD KEY `id_agence` (`id_agence`),
  ADD KEY `id_utilisateur` (`id_utilisateur`),
  ADD KEY `idx_recettes_date` (`date_recette`);

--
-- Index pour la table `registre_dechets`
--
ALTER TABLE `registre_dechets`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `reservations_online`
--
ALTER TABLE `reservations_online`
  ADD PRIMARY KEY (`id_reservation`),
  ADD KEY `id_client` (`id_client`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_role`),
  ADD UNIQUE KEY `nom_role` (`nom_role`),
  ADD UNIQUE KEY `nom_role_2` (`nom_role`),
  ADD KEY `idx_nom_role` (`nom_role`);

--
-- Index pour la table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id_role`,`id_permission`),
  ADD KEY `id_permission` (`id_permission`),
  ADD KEY `idx_role_permission` (`id_role`,`id_permission`);

--
-- Index pour la table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id_service`),
  ADD KEY `id_categorie` (`id_categorie`);

--
-- Index pour la table `tarifs_activites`
--
ALTER TABLE `tarifs_activites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_activite_tarif` (`type_activite`,`code_tarif`);

--
-- Index pour la table `tarif_categories`
--
ALTER TABLE `tarif_categories`
  ADD PRIMARY KEY (`id_categorie`),
  ADD UNIQUE KEY `unique_categorie` (`code_categorie`,`type_activite`);

--
-- Index pour la table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id_ticket`),
  ADD UNIQUE KEY `numero_ticket` (`numero_ticket`),
  ADD KEY `id_agence` (`id_agence`),
  ADD KEY `id_utilisateur` (`id_utilisateur`),
  ADD KEY `idx_tickets_statut` (`statut`),
  ADD KEY `idx_tickets_client` (`id_client`),
  ADD KEY `idx_tickets_date` (`date_depot`);

--
-- Index pour la table `tournees`
--
ALTER TABLE `tournees`
  ADD PRIMARY KEY (`id_tournee`),
  ADD KEY `fk_tournee_livreur` (`id_livreur`);

--
-- Index pour la table `tournees_details`
--
ALTER TABLE `tournees_details`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `fk_detail_tournee` (`id_tournee`),
  ADD KEY `fk_detail_ticket` (`id_ticket`);

--
-- Index pour la table `tracabilite_tickets`
--
ALTER TABLE `tracabilite_tickets`
  ADD PRIMARY KEY (`id_trace`),
  ADD KEY `id_ticket` (`id_ticket`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `login_utilisateur` (`login_utilisateur`),
  ADD KEY `id_role` (`id_role`);

--
-- Index pour la table `utilisateur_activites`
--
ALTER TABLE `utilisateur_activites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_activity` (`id_utilisateur`,`type_activite`);

--
-- Index pour la table `ventes_boutique`
--
ALTER TABLE `ventes_boutique`
  ADD PRIMARY KEY (`id_vente`);

--
-- Index pour la table `ventes_boutique_details`
--
ALTER TABLE `ventes_boutique_details`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_vente` (`id_vente`);

--
-- Index pour la table `ventes_details`
--
ALTER TABLE `ventes_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_vente` (`id_vente`),
  ADD KEY `id_produit` (`id_produit`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `abonnements`
--
ALTER TABLE `abonnements`
  MODIFY `id_abonnement` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `activites_config`
--
ALTER TABLE `activites_config`
  MODIFY `id_activite` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `agences`
--
ALTER TABLE `agences`
  MODIFY `id_agence` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `archives_alertes`
--
ALTER TABLE `archives_alertes`
  MODIFY `id_archive` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `boutique_produits`
--
ALTER TABLE `boutique_produits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `categories_service`
--
ALTER TABLE `categories_service`
  MODIFY `id_categorie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `certifications`
--
ALTER TABLE `certifications`
  MODIFY `id_certif` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id_client` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `client_activites`
--
ALTER TABLE `client_activites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `clotures_caisse`
--
ALTER TABLE `clotures_caisse`
  MODIFY `id_cloture` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `comptes`
--
ALTER TABLE `comptes`
  MODIFY `id_compte` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `consommables`
--
ALTER TABLE `consommables`
  MODIFY `id_consommable` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `cycles_production`
--
ALTER TABLE `cycles_production`
  MODIFY `id_cycle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `depenses`
--
ALTER TABLE `depenses`
  MODIFY `id_depense` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `equipements`
--
ALTER TABLE `equipements`
  MODIFY `id_equipement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `etapes_production`
--
ALTER TABLE `etapes_production`
  MODIFY `id_etape` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `factures`
--
ALTER TABLE `factures`
  MODIFY `id_facture` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `fidelite_cartes`
--
ALTER TABLE `fidelite_cartes`
  MODIFY `id_carte` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  MODIFY `id_fournisseur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `historique_cycles`
--
ALTER TABLE `historique_cycles`
  MODIFY `id_historique` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `historique_tarifs`
--
ALTER TABLE `historique_tarifs`
  MODIFY `id_histo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `interventions`
--
ALTER TABLE `interventions`
  MODIFY `id_intervention` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `journal_production`
--
ALTER TABLE `journal_production`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lignes_facture`
--
ALTER TABLE `lignes_facture`
  MODIFY `id_ligne` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lignes_ticket`
--
ALTER TABLE `lignes_ticket`
  MODIFY `id_ligne` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT pour la table `logs_activite`
--
ALTER TABLE `logs_activite`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `machines`
--
ALTER TABLE `machines`
  MODIFY `id_machine` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `modeles_facture`
--
ALTER TABLE `modeles_facture`
  MODIFY `id_modele` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `mouvements_caisse`
--
ALTER TABLE `mouvements_caisse`
  MODIFY `id_mouvement` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  MODIFY `id_mouvement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id_notification` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `notifications_clients`
--
ALTER TABLE `notifications_clients`
  MODIFY `id_notification` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `paiements`
--
ALTER TABLE `paiements`
  MODIFY `id_paiement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `parametres_systeme`
--
ALTER TABLE `parametres_systeme`
  MODIFY `id_parametre` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id_permission` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT pour la table `produits`
--
ALTER TABLE `produits`
  MODIFY `id_produit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `produits_chimiques`
--
ALTER TABLE `produits_chimiques`
  MODIFY `id_produit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `programme_fidelite`
--
ALTER TABLE `programme_fidelite`
  MODIFY `id_fidelite` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id_promo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `recettes`
--
ALTER TABLE `recettes`
  MODIFY `id_recette` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `registre_dechets`
--
ALTER TABLE `registre_dechets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `reservations_online`
--
ALTER TABLE `reservations_online`
  MODIFY `id_reservation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `roles`
--
ALTER TABLE `roles`
  MODIFY `id_role` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `services`
--
ALTER TABLE `services`
  MODIFY `id_service` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `tarifs_activites`
--
ALTER TABLE `tarifs_activites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tarif_categories`
--
ALTER TABLE `tarif_categories`
  MODIFY `id_categorie` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id_ticket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT pour la table `tournees`
--
ALTER TABLE `tournees`
  MODIFY `id_tournee` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `tournees_details`
--
ALTER TABLE `tournees_details`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tracabilite_tickets`
--
ALTER TABLE `tracabilite_tickets`
  MODIFY `id_trace` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `utilisateur_activites`
--
ALTER TABLE `utilisateur_activites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `ventes_boutique`
--
ALTER TABLE `ventes_boutique`
  MODIFY `id_vente` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ventes_boutique_details`
--
ALTER TABLE `ventes_boutique_details`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ventes_details`
--
ALTER TABLE `ventes_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `abonnements`
--
ALTER TABLE `abonnements`
  ADD CONSTRAINT `abonnements_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `clients` (`id_client`) ON DELETE CASCADE;

--
-- Contraintes pour la table `agences`
--
ALTER TABLE `agences`
  ADD CONSTRAINT `agences_ibfk_1` FOREIGN KEY (`responsable_id`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE SET NULL;

--
-- Contraintes pour la table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`id_agence`) REFERENCES `agences` (`id_agence`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_parrain` FOREIGN KEY (`id_parrain`) REFERENCES `clients` (`id_client`);

--
-- Contraintes pour la table `client_activites`
--
ALTER TABLE `client_activites`
  ADD CONSTRAINT `client_activites_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `clients` (`id_client`);

--
-- Contraintes pour la table `cycles_production`
--
ALTER TABLE `cycles_production`
  ADD CONSTRAINT `cycles_production_ibfk_1` FOREIGN KEY (`id_machine`) REFERENCES `machines` (`id_machine`) ON DELETE CASCADE;

--
-- Contraintes pour la table `depenses`
--
ALTER TABLE `depenses`
  ADD CONSTRAINT `depenses_ibfk_1` FOREIGN KEY (`id_agence`) REFERENCES `agences` (`id_agence`),
  ADD CONSTRAINT `depenses_ibfk_2` FOREIGN KEY (`id_fournisseur`) REFERENCES `fournisseurs` (`id_fournisseur`) ON DELETE SET NULL,
  ADD CONSTRAINT `depenses_ibfk_3` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `etapes_production`
--
ALTER TABLE `etapes_production`
  ADD CONSTRAINT `etapes_production_ibfk_1` FOREIGN KEY (`id_ticket`) REFERENCES `tickets` (`id_ticket`) ON DELETE CASCADE;

--
-- Contraintes pour la table `fidelite_cartes`
--
ALTER TABLE `fidelite_cartes`
  ADD CONSTRAINT `fk_fidelite_client` FOREIGN KEY (`id_client`) REFERENCES `clients` (`id_client`) ON DELETE CASCADE;

--
-- Contraintes pour la table `historique_cycles`
--
ALTER TABLE `historique_cycles`
  ADD CONSTRAINT `historique_cycles_ibfk_1` FOREIGN KEY (`id_cycle`) REFERENCES `cycles_production` (`id_cycle`) ON DELETE SET NULL,
  ADD CONSTRAINT `historique_cycles_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `historique_tarifs`
--
ALTER TABLE `historique_tarifs`
  ADD CONSTRAINT `historique_tarifs_ibfk_1` FOREIGN KEY (`id_service`) REFERENCES `services` (`id_service`),
  ADD CONSTRAINT `historique_tarifs_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `interventions`
--
ALTER TABLE `interventions`
  ADD CONSTRAINT `fk_intervention_equipement` FOREIGN KEY (`id_equipement`) REFERENCES `equipements` (`id_equipement`),
  ADD CONSTRAINT `fk_intervention_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `lignes_facture`
--
ALTER TABLE `lignes_facture`
  ADD CONSTRAINT `lignes_facture_ibfk_1` FOREIGN KEY (`id_facture`) REFERENCES `factures` (`id_facture`) ON DELETE CASCADE;

--
-- Contraintes pour la table `lignes_ticket`
--
ALTER TABLE `lignes_ticket`
  ADD CONSTRAINT `lignes_ticket_ibfk_1` FOREIGN KEY (`id_ticket`) REFERENCES `tickets` (`id_ticket`) ON DELETE CASCADE,
  ADD CONSTRAINT `lignes_ticket_ibfk_2` FOREIGN KEY (`id_service`) REFERENCES `services` (`id_service`);

--
-- Contraintes pour la table `logs_activite`
--
ALTER TABLE `logs_activite`
  ADD CONSTRAINT `logs_activite_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE SET NULL;

--
-- Contraintes pour la table `mouvements_caisse`
--
ALTER TABLE `mouvements_caisse`
  ADD CONSTRAINT `mouvements_caisse_ibfk_1` FOREIGN KEY (`id_compte`) REFERENCES `comptes` (`id_compte`),
  ADD CONSTRAINT `mouvements_caisse_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD CONSTRAINT `mouvements_stock_ibfk_1` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`),
  ADD CONSTRAINT `mouvements_stock_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`id_agence`) REFERENCES `agences` (`id_agence`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications_clients`
--
ALTER TABLE `notifications_clients`
  ADD CONSTRAINT `notifications_clients_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `clients` (`id_client`) ON DELETE CASCADE;

--
-- Contraintes pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `paiements_ibfk_1` FOREIGN KEY (`id_ticket`) REFERENCES `tickets` (`id_ticket`) ON DELETE CASCADE,
  ADD CONSTRAINT `paiements_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `produits_ibfk_1` FOREIGN KEY (`id_fournisseur`) REFERENCES `fournisseurs` (`id_fournisseur`) ON DELETE SET NULL;

--
-- Contraintes pour la table `programme_fidelite`
--
ALTER TABLE `programme_fidelite`
  ADD CONSTRAINT `programme_fidelite_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `clients` (`id_client`) ON DELETE CASCADE;

--
-- Contraintes pour la table `recettes`
--
ALTER TABLE `recettes`
  ADD CONSTRAINT `recettes_ibfk_1` FOREIGN KEY (`id_agence`) REFERENCES `agences` (`id_agence`),
  ADD CONSTRAINT `recettes_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `reservations_online`
--
ALTER TABLE `reservations_online`
  ADD CONSTRAINT `reservations_online_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `clients` (`id_client`) ON DELETE CASCADE;

--
-- Contraintes pour la table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`id_permission`) REFERENCES `permissions` (`id_permission`) ON DELETE CASCADE;

--
-- Contraintes pour la table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`id_categorie`) REFERENCES `categories_service` (`id_categorie`) ON DELETE SET NULL;

--
-- Contraintes pour la table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `clients` (`id_client`) ON DELETE SET NULL,
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`id_agence`) REFERENCES `agences` (`id_agence`),
  ADD CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `tournees`
--
ALTER TABLE `tournees`
  ADD CONSTRAINT `fk_tournee_livreur` FOREIGN KEY (`id_livreur`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `tournees_details`
--
ALTER TABLE `tournees_details`
  ADD CONSTRAINT `fk_detail_ticket` FOREIGN KEY (`id_ticket`) REFERENCES `tickets` (`id_ticket`),
  ADD CONSTRAINT `fk_detail_tournee` FOREIGN KEY (`id_tournee`) REFERENCES `tournees` (`id_tournee`) ON DELETE CASCADE;

--
-- Contraintes pour la table `tracabilite_tickets`
--
ALTER TABLE `tracabilite_tickets`
  ADD CONSTRAINT `tracabilite_tickets_ibfk_1` FOREIGN KEY (`id_ticket`) REFERENCES `tickets` (`id_ticket`) ON DELETE CASCADE,
  ADD CONSTRAINT `tracabilite_tickets_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE SET NULL;

--
-- Contraintes pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `utilisateurs_ibfk_1` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`) ON DELETE SET NULL;

--
-- Contraintes pour la table `utilisateur_activites`
--
ALTER TABLE `utilisateur_activites`
  ADD CONSTRAINT `utilisateur_activites_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `ventes_details`
--
ALTER TABLE `ventes_details`
  ADD CONSTRAINT `fk_produit_boutique_real` FOREIGN KEY (`id_produit`) REFERENCES `boutique_produits` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vente_boutique_real` FOREIGN KEY (`id_vente`) REFERENCES `ventes_boutique` (`id_vente`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
