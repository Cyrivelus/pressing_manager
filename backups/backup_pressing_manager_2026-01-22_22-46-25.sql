-- Sauvegarde Pressing Manager
-- Date: 22-01-2026 22:46:25
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

INSERT INTO agences VALUES('1','Principal','Adresse par défaut','00000000','contact@pressing.com',NULL,NULL,'1','2026-01-09 20:04:00');


CREATE TABLE `categories_service` (
  `id_categorie` int(11) NOT NULL AUTO_INCREMENT,
  `nom_categorie` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `delai_standard` int(11) DEFAULT NULL COMMENT 'Délai en heures',
  `prix_base` decimal(10,2) NOT NULL,
  `couleur_etiquette` varchar(7) DEFAULT '#3498db',
  PRIMARY KEY (`id_categorie`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;

INSERT INTO categories_service VALUES('1','Nettoyage à sec','Vêtements délicats nécessitant un nettoyage à sec','24','1500.00','#3498db');
INSERT INTO categories_service VALUES('2','Lavage','Lavage et repassage standard','12','800.00','#2ecc71');
INSERT INTO categories_service VALUES('3','Repassage','Repassage uniquement','6','500.00','#e74c3c');
INSERT INTO categories_service VALUES('4','Rénovation','Détachage et réparation','48','2500.00','#9b59b6');
INSERT INTO categories_service VALUES('5','Autres services','Services divers','24','1000.00','#f39c12');
INSERT INTO categories_service VALUES('6','Pressing & Blanchisserie','Entretien textile, lavage et repassage','48','500.00','#3498db');
INSERT INTO categories_service VALUES('7','Boissons & Bar','Vente de boissons fraîches et alcools','0','500.00','#e74c3c');
INSERT INTO categories_service VALUES('8','Restauration & Food','Plats cuisinés et snacks','1','1500.00','#27ae60');
INSERT INTO categories_service VALUES('9','Prêt-à-porter','Vente de vêtements et accessoires','0','2000.00','#f39c12');


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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

INSERT INTO clients VALUES('1','TAMBOUG','Cyrille','696197525','c.tanboug@sce-cameroun.com',NULL,'2026-01-09 19:59:26','8','0.00',NULL,'1',NULL);


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;

INSERT INTO lignes_ticket VALUES('1','1','1','4.00','25.00','100.00',NULL,'livre',NULL,'TK-260109-0001-1');
INSERT INTO lignes_ticket VALUES('2','1','3','11.00','8.00','88.00',NULL,'livre',NULL,'TK-260109-0001-2');
INSERT INTO lignes_ticket VALUES('3','2','3','2.00','800.00','1600.00',NULL,'livre',NULL,'TK-260110-0001-1');
INSERT INTO lignes_ticket VALUES('4','2','1','1.00','2500.00','2500.00',NULL,'livre',NULL,'TK-260110-0001-2');
INSERT INTO lignes_ticket VALUES('5','3','504','1.00','650.00','650.00',NULL,'livre',NULL,'TK-260113-0001-1');
INSERT INTO lignes_ticket VALUES('6','3','1','1.50','2500.00','3750.00',NULL,'livre',NULL,'TK-260113-0001-2');


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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;

INSERT INTO logs_activite VALUES('1','1','Changement statut ticket vers: pret','tickets','1',NULL,'{\"ticket_id\":1,\"numero_ticket\":\"TK-260109-0001\",\"ancien_statut\":\"unknown\",\"nouveau_statut\":\"pret\",\"date_maj\":\"2026-01-09 20:06:22\"}',NULL,NULL,'2026-01-09 20:06:22');
INSERT INTO logs_activite VALUES('2','1','Livraison effectuée pour le ticket #TK-260109-0001','tickets','1',NULL,NULL,NULL,NULL,'2026-01-09 20:13:12');
INSERT INTO logs_activite VALUES('3','1','Livraison effectuée pour le ticket #TK-260109-0001','tickets','1',NULL,NULL,NULL,NULL,'2026-01-09 20:13:22');
INSERT INTO logs_activite VALUES('4','1','Changement statut ticket vers: pret','tickets','2',NULL,'{\"ticket_id\":2,\"numero_ticket\":\"TK-260110-0001\",\"ancien_statut\":\"unknown\",\"nouveau_statut\":\"pret\",\"date_maj\":\"2026-01-10 17:28:46\"}',NULL,NULL,'2026-01-10 17:28:46');
INSERT INTO logs_activite VALUES('5','1','Livraison effectuée pour le ticket #TK-260110-0001','tickets','2',NULL,NULL,NULL,NULL,'2026-01-10 17:29:24');
INSERT INTO logs_activite VALUES('6',NULL,'Création de l\'utilisateur : Cyrivelus','utilisateurs','8',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2026-01-13 22:04:45');
INSERT INTO logs_activite VALUES('7','1','Changement statut ticket vers: pret','tickets','3',NULL,'{\"ticket_id\":3,\"numero_ticket\":\"TK-260113-0001\",\"ancien_statut\":\"unknown\",\"nouveau_statut\":\"pret\",\"date_maj\":\"2026-01-13 22:29:56\"}',NULL,NULL,'2026-01-13 22:29:56');
INSERT INTO logs_activite VALUES('8','1','Livraison effectuée pour le ticket #TK-260113-0001','tickets','3',NULL,NULL,NULL,NULL,'2026-01-13 22:30:26');


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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

INSERT INTO mouvements_stock VALUES('1','1','entree','20.00','2026-01-10 17:33:35','1','','','20.00');
INSERT INTO mouvements_stock VALUES('2','1','sortie','2.00','2026-01-10 17:34:19','1',NULL,'','18.00');


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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

INSERT INTO notifications VALUES('1','1','1','ticket_pret','Vêtements prêts - Ticket #TK-260109-0001','Le ticket #TK-260109-0001 est prêt pour retrait.','pages/tickets/view.php?id=1','0','2026-01-09 20:06:22');
INSERT INTO notifications VALUES('2','1','1','ticket_pret','Vêtements prêts - Ticket #TK-260110-0001','Le ticket #TK-260110-0001 est prêt pour retrait.','pages/tickets/view.php?id=2','0','2026-01-10 17:28:46');
INSERT INTO notifications VALUES('3','1','1','ticket_pret','Vêtements prêts - Ticket #TK-260113-0001','Le ticket #TK-260113-0001 est prêt pour retrait.','pages/tickets/view.php?id=3','0','2026-01-13 22:29:56');


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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;

INSERT INTO paiements VALUES('1','1','100.00','especes','2026-01-09 20:04:00','1','PAY-TK-260109-0001',NULL);
INSERT INTO paiements VALUES('2','1','88.00','especes','2026-01-09 20:11:16','1','ENC-TK-260109-0001',NULL);
INSERT INTO paiements VALUES('3','2','3000.00','especes','2026-01-10 17:24:22','1','PAY-TK-260110-0001',NULL);
INSERT INTO paiements VALUES('4','2','1100.00','especes','2026-01-10 17:26:05','1','ENC-TK-260110-0001',NULL);
INSERT INTO paiements VALUES('5','3','4000.00','especes','2026-01-13 21:54:34','1','PAY-TK-260113-0001',NULL);
INSERT INTO paiements VALUES('6','3','400.00','especes','2026-01-13 21:58:22','1','ENC-TK-260113-0001',NULL);


CREATE TABLE `parametres_systeme` (
  `id_parametre` int(11) NOT NULL AUTO_INCREMENT,
  `cle_parametre` varchar(100) NOT NULL,
  `valeur_parametre` text DEFAULT NULL,
  `type_parametre` enum('string','integer','float','boolean','json') DEFAULT 'string',
  `categorie` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id_parametre`),
  UNIQUE KEY `cle_parametre` (`cle_parametre`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;

INSERT INTO parametres_systeme VALUES('1','app_name','Pressing /commerce Manager Pro','string','general','Nom de l\'application');
INSERT INTO parametres_systeme VALUES('2','currency','FCFA','string','general','Devise');
INSERT INTO parametres_systeme VALUES('3','tax_rate','19,25','float','general','Taux de TVA');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

INSERT INTO produits VALUES('1','Gros plastique noir','1','sac','','18.00','10.00','200.00','',NULL,'1');


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



CREATE TABLE `roles` (
  `id_role` int(11) NOT NULL AUTO_INCREMENT,
  `nom_role` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `niveau_permission` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_role`),
  UNIQUE KEY `nom_role` (`nom_role`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

INSERT INTO roles VALUES('1','patron','Accès complet à toutes les fonctionnalités','100','2026-01-09 19:56:05');
INSERT INTO roles VALUES('2','receptionniste','Création de tickets, encaissement','50','2026-01-09 19:56:05');
INSERT INTO roles VALUES('3','caissier','Gestion des paiements, caisse','40','2026-01-09 19:56:05');
INSERT INTO roles VALUES('4','gestionnaire_stock','Gestion des stocks et fournisseurs','30','2026-01-09 19:56:05');
INSERT INTO roles VALUES('5','employe_pressing','Mise à jour statut des articles','20','2026-01-09 19:56:05');


CREATE TABLE `services` (
  `id_service` int(11) NOT NULL AUTO_INCREMENT,
  `nom_service` varchar(100) NOT NULL,
  `id_categorie` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `duree_estimee` int(11) DEFAULT NULL COMMENT 'Durée en minutes',
  `unite_mesure` varchar(50) DEFAULT 'pièce',
  `est_disponible` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_service`),
  KEY `id_categorie` (`id_categorie`),
  CONSTRAINT `services_ibfk_1` FOREIGN KEY (`id_categorie`) REFERENCES `categories_service` (`id_categorie`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=507 DEFAULT CHARSET=utf8mb4;

INSERT INTO services VALUES('1','Costume complet','1','Nettoyage à sec d\'un costume','2500.00','120','pièce','1');
INSERT INTO services VALUES('2','Robe de soirée','1','Nettoyage à sec d\'une robe','3000.00','180','pièce','1');
INSERT INTO services VALUES('3','Chemise homme','2','Lavage et repassage','800.00','30','pièce','1');
INSERT INTO services VALUES('4','Pantalon','2','Lavage et repassage','1000.00','45','pièce','1');
INSERT INTO services VALUES('5','Repassage chemise','3','Repassage uniquement','500.00','15','pièce','1');
INSERT INTO services VALUES('6','Détachage tache','4','Traitement de tache spécifique','1500.00','60','tache','1');
INSERT INTO services VALUES('7','Ourlet pantalon','4','Réfection d\'ourlet','1200.00','45','ourlet','1');
INSERT INTO services VALUES('502','Lavage Veste Costume','1',NULL,'1500.00',NULL,'pièce','1');
INSERT INTO services VALUES('503','Lavage Linge de maison','1',NULL,'1000.00',NULL,'kg','1');
INSERT INTO services VALUES('504','Bière Kadji 65cl','2',NULL,'650.00',NULL,'bouteille','1');
INSERT INTO services VALUES('505','Casier Djino (Verre)','2',NULL,'4500.00',NULL,'casier','1');
INSERT INTO services VALUES('506','Ndole Viande / Poisson','3',NULL,'2500.00',NULL,'plat','1');


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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

INSERT INTO tickets VALUES('1','TK-260109-0001','1','1','1','2026-01-09 20:04:00','2026-01-11 19:59:00','2026-01-09 20:13:22','recupere','188.00','188.00','0.00','especes','',NULL,'2026-01-09 20:04:00','2026-01-09 20:13:22');
INSERT INTO tickets VALUES('2','TK-260110-0001','1','1','1','2026-01-10 17:24:22','2026-01-13 17:23:00','2026-01-10 17:29:24','recupere','4100.00','4100.00','0.00','especes','',NULL,'2026-01-10 17:24:22','2026-01-10 17:29:24');
INSERT INTO tickets VALUES('3','TK-260113-0001','1','1','1','2026-01-13 21:54:34','2026-01-16 21:50:00','2026-01-13 22:30:26','recupere','4400.00','4400.00','0.00','especes','',NULL,'2026-01-13 21:54:34','2026-01-13 22:30:26');


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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;

INSERT INTO utilisateurs VALUES('1','mbreka','mbreka','$2y$10$9bmGVUMY3JlzmTh.2oemf.owYIp5ZXqI8EIdlIiszYlyuIo7APSWC','cyrillesteve@gmail.com','696197525','3','2026-01-09 19:56:19','2026-01-20 20:46:42','1','0',NULL,NULL,'2026-01-09 19:46:39','AG1');
INSERT INTO utilisateurs VALUES('5','admin','admin','$2y$10$9bmGVUMY3JlzmTh.2oemf.owYIp5ZXqI8EIdlIiszYlyuIo7APSWC',NULL,NULL,'1','2026-01-09 22:42:33','2026-01-22 22:37:10','1','0',NULL,NULL,'2026-01-09 22:42:02',NULL);
INSERT INTO utilisateurs VALUES('6','receptionniste','receptionniste','$2y$10$9bmGVUMY3JlzmTh.2oemf.owYIp5ZXqI8EIdlIiszYlyuIo7APSWC',NULL,NULL,'2','2026-01-10 17:36:56','2026-01-20 20:53:17','1','0',NULL,NULL,'2026-01-10 17:36:30',NULL);
INSERT INTO utilisateurs VALUES('7','','','$2y$10$cwgNLbhhwyqOYCiqbq/pk.rREA5sepf6BZ3K5X0Ws5/bGArkvK2o6',NULL,NULL,NULL,'2026-01-13 21:56:18','2026-01-13 21:54:50','1','4',NULL,NULL,'2026-01-13 21:54:50',NULL);
INSERT INTO utilisateurs VALUES('8','Tamboug Cyrille Steve','Cyrivelus','$2y$10$m9ecNZCBfW1RutO8vjg.seT9P3f3ocn86rhuAspOSug1WDiLi4sR.','c.tanboug@sce-cameroun.com','696197525','5','2026-01-13 22:04:45','2026-01-22 22:26:57','1','0',NULL,NULL,NULL,'1');

SET FOREIGN_KEY_CHECKS=1;