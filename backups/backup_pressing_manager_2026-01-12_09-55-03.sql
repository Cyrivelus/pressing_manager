-- Sauvegarde Pressing Manager
-- Date: 12-01-2026 09:55:02
SET FOREIGN_KEY_CHECKS=0;



CREATE TABLE `agences` (
  `id_agence` int(11) NOT NULL AUTO_INCREMENT,
  `nom_agence` varchar(100) NOT NULL,
  `adresse` text DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `responsable_id` int(11) DEFAULT NULL,
  `date_ouverture` date DEFAULT NULL,
  `est_actif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_agence`),
  KEY `responsable_id` (`responsable_id`),
  CONSTRAINT `agences_ibfk_1` FOREIGN KEY (`responsable_id`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO agences VALUES('1','Pressing Principal','Adresse par défaut','00000000','contact@pressing.com',NULL,NULL,'1','2026-01-09 16:20:39');


CREATE TABLE `categories_service` (
  `id_categorie` int(11) NOT NULL AUTO_INCREMENT,
  `nom_categorie` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `delai_standard` int(11) DEFAULT NULL COMMENT 'Délai en heures',
  `prix_base` decimal(10,2) NOT NULL,
  `couleur_etiquette` varchar(7) DEFAULT '#3498db',
  PRIMARY KEY (`id_categorie`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO categories_service VALUES('1','Nettoyage à sec','Vêtements délicats nécessitant un nettoyage à sec','24','15.00','#3498db');
INSERT INTO categories_service VALUES('2','Lavage','Lavage et repassage standard','12','8.00','#2ecc71');
INSERT INTO categories_service VALUES('3','Repassage','Repassage uniquement','6','5.00','#e74c3c');
INSERT INTO categories_service VALUES('4','Rénovation','Détachage et réparation','48','25.00','#9b59b6');
INSERT INTO categories_service VALUES('5','Autres services','Services divers','24','10.00','#f39c12');


CREATE TABLE `clients` (
  `id_client` int(11) NOT NULL AUTO_INCREMENT,
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
  `id_agence` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_client`),
  KEY `id_agence` (`id_agence`),
  CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`id_agence`) REFERENCES `agences` (`id_agence`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO clients VALUES('1','TAMBOUG','Cyrile','696197525','cyrivelmars2013@gmail.com','SUPERETTE','2026-01-09 15:35:02','0','0.00',NULL,'1',NULL);


CREATE TABLE `depenses` (
  `id_depense` int(11) NOT NULL AUTO_INCREMENT,
  `id_agence` int(11) NOT NULL,
  `categorie` enum('loyer','salaires','electricite','eau','produits','maintenance','autre') NOT NULL,
  `description` text NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_depense` date NOT NULL,
  `id_fournisseur` int(11) DEFAULT NULL,
  `reference_facture` varchar(100) DEFAULT NULL,
  `mode_paiement` enum('especes','carte','cheque','virement') DEFAULT 'cheque',
  `id_utilisateur` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id_depense`),
  KEY `id_agence` (`id_agence`),
  KEY `id_fournisseur` (`id_fournisseur`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `idx_depenses_date` (`date_depense`),
  CONSTRAINT `depenses_ibfk_1` FOREIGN KEY (`id_agence`) REFERENCES `agences` (`id_agence`),
  CONSTRAINT `depenses_ibfk_2` FOREIGN KEY (`id_fournisseur`) REFERENCES `fournisseurs` (`id_fournisseur`) ON DELETE SET NULL,
  CONSTRAINT `depenses_ibfk_3` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `fournisseurs` (
  `id_fournisseur` int(11) NOT NULL AUTO_INCREMENT,
  `nom_fournisseur` varchar(100) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `categorie` enum('produit_nettoyage','equipement','emballage','autre') DEFAULT 'produit_nettoyage',
  `solde_du` decimal(10,2) DEFAULT 0.00,
  `est_actif` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_fournisseur`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO fournisseurs VALUES('1','FOURNISSEUR GENERAL',NULL,NULL,NULL,NULL,'autre','0.00','1');


CREATE TABLE `lignes_ticket` (
  `id_ligne` int(11) NOT NULL AUTO_INCREMENT,
  `id_ticket` int(11) NOT NULL,
  `id_service` int(11) NOT NULL,
  `quantite` decimal(10,2) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `sous_total` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `statut_article` enum('depose','nettoye','repere','conditionne','livre') DEFAULT 'depose',
  `notes` text DEFAULT NULL,
  `numero_etiquette` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_ligne`),
  UNIQUE KEY `numero_etiquette` (`numero_etiquette`),
  KEY `id_service` (`id_service`),
  KEY `idx_lignes_ticket` (`id_ticket`),
  CONSTRAINT `lignes_ticket_ibfk_1` FOREIGN KEY (`id_ticket`) REFERENCES `tickets` (`id_ticket`) ON DELETE CASCADE,
  CONSTRAINT `lignes_ticket_ibfk_2` FOREIGN KEY (`id_service`) REFERENCES `services` (`id_service`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO lignes_ticket VALUES('1','9','3','5.00','8.00','40.00',NULL,'livre',NULL,'TK-260109-0001-1');
INSERT INTO lignes_ticket VALUES('2','9','1','5.00','25.00','125.00',NULL,'livre',NULL,'TK-260109-0001-2');
INSERT INTO lignes_ticket VALUES('3','9','2','5.50','30.00','165.00',NULL,'livre',NULL,'TK-260109-0001-3');


CREATE TABLE `logs_activite` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `table_concernée` varchar(50) DEFAULT NULL,
  `id_enregistrement` int(11) DEFAULT NULL,
  `anciennes_valeurs` text DEFAULT NULL,
  `nouvelles_valeurs` text DEFAULT NULL,
  `ip_adresse` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `date_action` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_log`),
  KEY `id_utilisateur` (`id_utilisateur`),
  CONSTRAINT `logs_activite_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO logs_activite VALUES('1','1','Changement statut ticket vers: pret','tickets','9',NULL,'{\"ticket_id\":9,\"numero_ticket\":\"TK-260109-0001\",\"ancien_statut\":\"unknown\",\"nouveau_statut\":\"pret\",\"date_maj\":\"2026-01-09 16:40:53\"}',NULL,NULL,'2026-01-09 16:40:53');
INSERT INTO logs_activite VALUES('2','1','Livraison effectuée pour le ticket #TK-260109-0001','tickets','9',NULL,NULL,NULL,NULL,'2026-01-09 16:59:38');
INSERT INTO logs_activite VALUES('3','1','Livraison effectuée pour le ticket #TK-260109-0001','tickets','9',NULL,NULL,NULL,NULL,'2026-01-09 16:59:51');
INSERT INTO logs_activite VALUES('4','4','Création d\'un nouvel utilisateur : steve','utilisateurs','5',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-12 08:19:25');


CREATE TABLE `mouvements_stock` (
  `id_mouvement` int(11) NOT NULL AUTO_INCREMENT,
  `id_produit` int(11) NOT NULL,
  `type_mouvement` enum('entree','sortie','ajustement') NOT NULL,
  `quantite` decimal(10,2) NOT NULL,
  `date_mouvement` datetime NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `reference` varchar(100) DEFAULT NULL COMMENT 'Numéro de facture ou bon',
  `notes` text DEFAULT NULL,
  `quantite_apres` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_mouvement`),
  KEY `id_produit` (`id_produit`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `idx_mouvements_date` (`date_mouvement`),
  CONSTRAINT `mouvements_stock_ibfk_1` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`),
  CONSTRAINT `mouvements_stock_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO mouvements_stock VALUES('1','1','entree','2.00','2026-01-09 17:27:07','3','uyvyuvyu','','4.00');
INSERT INTO mouvements_stock VALUES('2','1','entree','2.00','2026-01-09 17:30:47','3','uyvyuvyu','','6.00');
INSERT INTO mouvements_stock VALUES('3','1','sortie','2.00','2026-01-09 17:36:34','1',NULL,'','4.00');


CREATE TABLE `notifications` (
  `id_notification` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) DEFAULT NULL,
  `id_agence` int(11) DEFAULT NULL,
  `type_notification` enum('alerte_stock','ticket_retard','ticket_pret','rapport','systeme') NOT NULL,
  `titre` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `lien` varchar(255) DEFAULT NULL,
  `est_lue` tinyint(1) DEFAULT 0,
  `date_notification` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_notification`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `id_agence` (`id_agence`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE,
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`id_agence`) REFERENCES `agences` (`id_agence`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO notifications VALUES('1','1','1','ticket_pret','Vêtements prêts - Ticket #TK-260109-0001','Le ticket #TK-260109-0001 est prêt pour retrait.','pages/tickets/view.php?id=9','0','2026-01-09 16:40:53');


CREATE TABLE `paiements` (
  `id_paiement` int(11) NOT NULL AUTO_INCREMENT,
  `id_ticket` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `mode_paiement` enum('especes','carte','cheque','mobile','autre') NOT NULL,
  `date_paiement` datetime NOT NULL,
  `id_utilisateur` int(11) NOT NULL COMMENT 'Caissier',
  `reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id_paiement`),
  KEY `id_ticket` (`id_ticket`),
  KEY `id_utilisateur` (`id_utilisateur`),
  CONSTRAINT `paiements_ibfk_1` FOREIGN KEY (`id_ticket`) REFERENCES `tickets` (`id_ticket`) ON DELETE CASCADE,
  CONSTRAINT `paiements_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO paiements VALUES('1','9','300.00','especes','2026-01-09 16:20:43','1','PAY-TK-260109-0001',NULL);
INSERT INTO paiements VALUES('2','9','30.00','especes','2026-01-09 16:50:54','1','ENC-TK-260109-0001',NULL);


CREATE TABLE `parametres_systeme` (
  `id_parametre` int(11) NOT NULL AUTO_INCREMENT,
  `cle_parametre` varchar(100) NOT NULL,
  `valeur_parametre` text DEFAULT NULL,
  `type_parametre` enum('string','integer','float','boolean','json') DEFAULT 'string',
  `categorie` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id_parametre`),
  UNIQUE KEY `cle_parametre` (`cle_parametre`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO parametres_systeme VALUES('1','app_name','Pressing Manager Pro','string','general','Nom de l\'application');
INSERT INTO parametres_systeme VALUES('2','currency','€','string','general','Devise');
INSERT INTO parametres_systeme VALUES('3','tax_rate','20','float','general','Taux de TVA');
INSERT INTO parametres_systeme VALUES('4','alert_stock_days','7','integer','stock','Jours d\'alerte stock');
INSERT INTO parametres_systeme VALUES('5','auto_backup','1','boolean','system','Sauvegarde automatique');
INSERT INTO parametres_systeme VALUES('6','backup_time','02:00','string','system','Heure de sauvegarde');
INSERT INTO parametres_systeme VALUES('7','max_login_attempts','3','integer','security','Tentatives de connexion max');
INSERT INTO parametres_systeme VALUES('8','session_timeout','1800','integer','security','Timeout session (secondes)');


CREATE TABLE `produits` (
  `id_produit` int(11) NOT NULL AUTO_INCREMENT,
  `nom_produit` varchar(100) NOT NULL,
  `id_fournisseur` int(11) DEFAULT NULL,
  `categorie` enum('lessive','detachant','cintre','sac','etiquette','autre') NOT NULL,
  `unite_mesure` varchar(20) DEFAULT 'unite',
  `quantite_stock` decimal(10,2) DEFAULT 0.00,
  `seuil_alerte` decimal(10,2) DEFAULT 10.00,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `emplacement` varchar(50) DEFAULT NULL,
  `date_peremption` date DEFAULT NULL,
  `est_actif` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_produit`),
  KEY `id_fournisseur` (`id_fournisseur`),
  KEY `idx_produits_stock` (`quantite_stock`),
  CONSTRAINT `produits_ibfk_1` FOREIGN KEY (`id_fournisseur`) REFERENCES `fournisseurs` (`id_fournisseur`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO produits VALUES('1','jrtitr',NULL,'lessive','','4.00','10.00','5000.00','',NULL,'1');
INSERT INTO produits VALUES('2','jrtitr','1','lessive','','2.00','10.00','5000.00','',NULL,'1');
INSERT INTO produits VALUES('3','jrtitr','1','lessive','','2.00','10.00','5000.00','',NULL,'1');
INSERT INTO produits VALUES('4','jrtitr','1','lessive','','2.00','10.00','5000.00','',NULL,'1');


CREATE TABLE `recettes` (
  `id_recette` int(11) NOT NULL AUTO_INCREMENT,
  `id_agence` int(11) NOT NULL,
  `date_recette` date NOT NULL,
  `montant_total` decimal(10,2) NOT NULL,
  `montant_especes` decimal(10,2) DEFAULT 0.00,
  `montant_carte` decimal(10,2) DEFAULT 0.00,
  `montant_cheque` decimal(10,2) DEFAULT 0.00,
  `montant_mobile` decimal(10,2) DEFAULT 0.00,
  `nombre_tickets` int(11) DEFAULT 0,
  `id_utilisateur` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id_recette`),
  KEY `id_agence` (`id_agence`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `idx_recettes_date` (`date_recette`),
  CONSTRAINT `recettes_ibfk_1` FOREIGN KEY (`id_agence`) REFERENCES `agences` (`id_agence`),
  CONSTRAINT `recettes_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `roles` (
  `id_role` int(11) NOT NULL AUTO_INCREMENT,
  `nom_role` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `niveau_permission` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_role`),
  UNIQUE KEY `nom_role` (`nom_role`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO roles VALUES('1','patron','Accès complet à toutes les fonctionnalités','100','2026-01-08 22:50:34');
INSERT INTO roles VALUES('2','receptionniste','Création de tickets, encaissement','50','2026-01-08 22:50:34');
INSERT INTO roles VALUES('3','caissier','Gestion des paiements, caisse','40','2026-01-08 22:50:34');
INSERT INTO roles VALUES('4','gestionnaire_stock','Gestion des stocks et fournisseurs','30','2026-01-08 22:50:34');
INSERT INTO roles VALUES('5','employe_pressing','Mise à jour statut des articles','20','2026-01-08 22:50:34');
INSERT INTO roles VALUES('6','Copie de receptionniste','Création de tickets, encaissement','50','2026-01-12 08:57:09');


CREATE TABLE `services` (
  `id_service` int(11) NOT NULL AUTO_INCREMENT,
  `nom_service` varchar(100) NOT NULL,
  `id_categorie` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `duree_estimee` int(11) DEFAULT NULL COMMENT 'Durée en minutes',
  `unite_mesure` varchar(20) DEFAULT 'pièce',
  `est_disponible` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_service`),
  KEY `id_categorie` (`id_categorie`),
  CONSTRAINT `services_ibfk_1` FOREIGN KEY (`id_categorie`) REFERENCES `categories_service` (`id_categorie`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO services VALUES('1','Costume complet','1','Nettoyage à sec d\'un costume','25.00','120','pièce','1');
INSERT INTO services VALUES('2','Robe de soirée','1','Nettoyage à sec d\'une robe','30.00','180','pièce','1');
INSERT INTO services VALUES('3','Chemise homme','2','Lavage et repassage','8.00','30','pièce','1');
INSERT INTO services VALUES('4','Pantalon','2','Lavage et repassage','10.00','45','pièce','1');
INSERT INTO services VALUES('5','Repassage chemise','3','Repassage uniquement','5.00','15','pièce','1');
INSERT INTO services VALUES('6','Détachage tache','4','Traitement de tache spécifique','15.00','60','tache','1');
INSERT INTO services VALUES('7','Ourlet pantalon','4','Réfection d\'ourlet','12.00','45','ourlet','1');


CREATE TABLE `tickets` (
  `id_ticket` int(11) NOT NULL AUTO_INCREMENT,
  `numero_ticket` varchar(20) NOT NULL,
  `id_client` int(11) DEFAULT NULL,
  `id_agence` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL COMMENT 'Réceptionniste',
  `date_depot` datetime NOT NULL,
  `date_retrait_prevue` datetime DEFAULT NULL,
  `date_retrait_reelle` datetime DEFAULT NULL,
  `statut` enum('en_attente','en_traitement','pret','recupere','annule') DEFAULT 'en_attente',
  `montant_total` decimal(10,2) DEFAULT 0.00,
  `montant_verse` decimal(10,2) DEFAULT 0.00,
  `montant_remise` decimal(10,2) DEFAULT 0.00,
  `mode_paiement` enum('especes','carte','cheque','mobile','autre') DEFAULT 'especes',
  `notes_client` text DEFAULT NULL,
  `notes_interne` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_ticket`),
  UNIQUE KEY `numero_ticket` (`numero_ticket`),
  KEY `id_agence` (`id_agence`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `idx_tickets_statut` (`statut`),
  KEY `idx_tickets_client` (`id_client`),
  KEY `idx_tickets_date` (`date_depot`),
  CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `clients` (`id_client`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`id_agence`) REFERENCES `agences` (`id_agence`),
  CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO tickets VALUES('9','TK-260109-0001','1','1','1','2026-01-09 16:20:40','2026-01-11 16:12:00','2026-01-09 16:59:50','recupere','330.00','330.00','0.00','especes','',NULL,'2026-01-09 16:20:40','2026-01-09 16:59:50');


CREATE TABLE `utilisateurs` (
  `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT,
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
  `code_agence` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `login_utilisateur` (`login_utilisateur`),
  KEY `id_role` (`id_role`),
  CONSTRAINT `utilisateurs_ibfk_1` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO utilisateurs VALUES('1','Jean','reception1','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','reception1@pressing.com','0123456789','2','2026-01-09 09:30:32','2026-01-12 07:39:58','1','0',NULL,NULL,NULL,NULL);
INSERT INTO utilisateurs VALUES('2','Caissier Test','caissier','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','caissier@pressing.com','0123456788','3','2026-01-09 09:50:00','2026-01-09 14:46:57','1','0',NULL,NULL,NULL,'AG001');
INSERT INTO utilisateurs VALUES('3','jean','jeannot','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','cyrillesteve@gmail.com','696197525','3','2026-01-09 12:26:47','2026-01-09 16:12:18','1','0',NULL,NULL,NULL,NULL);
INSERT INTO utilisateurs VALUES('4','Administrateur Principal','admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin@pressing.com','00000000','1','2026-01-09 13:15:02','2026-01-12 07:40:10','1','0',NULL,NULL,NULL,NULL);
INSERT INTO utilisateurs VALUES('5','Tamboug Cyrille Steve','steve','$2y$10$CtwDBbgtjOWXYPOijSwIMOq2tVpeP3Vh4MJaZou9qlKu2P1WPEn0i','cyrivelmars2013@gmail.com','696167525','2','2026-01-12 08:19:25',NULL,'1','0',NULL,NULL,NULL,'1');

SET FOREIGN_KEY_CHECKS=1;