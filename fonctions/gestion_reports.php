<?php
// fonctions/gestion_reports.php

/**
 * Génère un rapport de bilan simplifié pour un pressing
 */
function generateBalanceReport($pdo, $startDate, $endDate) {
    $report = [
        'period' => [
            'start' => $startDate,
            'end' => $endDate
        ],
        'summary' => [],
        'details' => []
    ];
    
    try {
        // 1. Chiffre d'affaires
        $sqlCA = "SELECT 
                     DATE(date_depot) as date,
                     COUNT(*) as nb_tickets,
                     SUM(montant_total) as total_ca,
                     SUM(montant_verse) as total_verse
                  FROM tickets 
                  WHERE DATE(date_depot) BETWEEN ? AND ?
                  AND statut != 'annule'
                  GROUP BY DATE(date_depot)
                  ORDER BY DATE(date_depot)";
        
        $stmtCA = $pdo->prepare($sqlCA);
        $stmtCA->execute([$startDate, $endDate]);
        $report['details']['chiffre_affaires'] = $stmtCA->fetchAll(PDO::FETCH_ASSOC);
        
        // 2. Dépenses
        $sqlDepenses = "SELECT 
                           DATE(date_depense) as date,
                           categorie,
                           description,
                           montant,
                           mode_paiement
                        FROM depenses 
                        WHERE DATE(date_depense) BETWEEN ? AND ?
                        ORDER BY DATE(date_depense)";
        
        $stmtDepenses = $pdo->prepare($sqlDepenses);
        $stmtDepenses->execute([$startDate, $endDate]);
        $report['details']['depenses'] = $stmtDepenses->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. Résumé
        $report['summary'] = [
            'chiffre_affaires_total' => array_sum(array_column($report['details']['chiffre_affaires'], 'total_ca')),
            'depenses_total' => array_sum(array_column($report['details']['depenses'], 'montant')),
            'benefice' => array_sum(array_column($report['details']['chiffre_affaires'], 'total_ca')) - 
                         array_sum(array_column($report['details']['depenses'], 'montant'))
        ];
        
        return $report;
        
    } catch (PDOException $e) {
        error_log("Erreur génération rapport: " . $e->getMessage());
        return false;
    }
}

/**
 * Génère un rapport de stock
 */
function generateStockReport($pdo, $date = null) {
    if (!$date) {
        $date = date('Y-m-d');
    }
    
    try {
        $sql = "SELECT 
                   p.id_produit,
                   p.nom_produit,
                   p.categorie,
                   p.quantite_stock,
                   p.seuil_alerte,
                   p.prix_unitaire,
                   f.nom_fournisseur,
                   (p.quantite_stock * p.prix_unitaire) as valeur_stock,
                   CASE 
                       WHEN p.quantite_stock <= p.seuil_alerte THEN 'CRITIQUE'
                       WHEN p.quantite_stock <= (p.seuil_alerte * 2) THEN 'FAIBLE'
                       ELSE 'NORMAL'
                   END as statut_stock
                FROM produits p
                LEFT JOIN fournisseurs f ON p.id_fournisseur = f.id_fournisseur
                WHERE p.est_actif = 1
                ORDER BY statut_stock, p.categorie, p.nom_produit";
        
        $stmt = $pdo->query($sql);
        $stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcul des totaux
        $totals = [
            'nb_produits' => count($stock),
            'nb_critique' => count(array_filter($stock, function($p) { 
                return $p['statut_stock'] == 'CRITIQUE'; 
            })),
            'nb_faible' => count(array_filter($stock, function($p) { 
                return $p['statut_stock'] == 'FAIBLE'; 
            })),
            'valeur_totale' => array_sum(array_column($stock, 'valeur_stock'))
        ];
        
        return [
            'date' => $date,
            'stock' => $stock,
            'totals' => $totals
        ];
        
    } catch (PDOException $e) {
        error_log("Erreur rapport stock: " . $e->getMessage());
        return false;
    }
}

/**
 * Génère un rapport de performance mensuelle
 */
function generateMonthlyReport($pdo, $year, $month) {
    $startDate = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
    $endDate = date('Y-m-t', strtotime($startDate));
    
    try {
        // Tickets par jour
        $sqlTickets = "SELECT 
                          DATE(date_depot) as jour,
                          COUNT(*) as nb_tickets,
                          SUM(montant_total) as montant_total,
                          AVG(montant_total) as moyenne_ticket
                       FROM tickets 
                       WHERE DATE(date_depot) BETWEEN ? AND ?
                       AND statut != 'annule'
                       GROUP BY DATE(date_depot)
                       ORDER BY DATE(date_depot)";
        
        $stmtTickets = $pdo->prepare($sqlTickets);
        $stmtTickets->execute([$startDate, $endDate]);
        $tickets = $stmtTickets->fetchAll(PDO::FETCH_ASSOC);
        
        // Services les plus vendus
        $sqlServices = "SELECT 
                           s.nom_service,
                           COUNT(lt.id_ligne) as nb_ventes,
                           SUM(lt.sous_total) as chiffre_affaires
                        FROM lignes_ticket lt
                        JOIN services s ON lt.id_service = s.id_service
                        JOIN tickets t ON lt.id_ticket = t.id_ticket
                        WHERE DATE(t.date_depot) BETWEEN ? AND ?
                        GROUP BY s.nom_service
                        ORDER BY nb_ventes DESC
                        LIMIT 10";
        
        $stmtServices = $pdo->prepare($sqlServices);
        $stmtServices->execute([$startDate, $endDate]);
        $services = $stmtServices->fetchAll(PDO::FETCH_ASSOC);
        
        // Synthèse
        $summary = [
            'periode' => $startDate . ' au ' . $endDate,
            'total_tickets' => count($tickets),
            'total_ca' => array_sum(array_column($tickets, 'montant_total')),
            'moyenne_ticket' => count($tickets) > 0 ? 
                array_sum(array_column($tickets, 'montant_total')) / count($tickets) : 0,
            'meilleur_jour' => !empty($tickets) ? 
                array_reduce($tickets, function($a, $b) { 
                    return $a['montant_total'] > $b['montant_total'] ? $a : $b; 
                }) : null
        ];
        
        return [
            'summary' => $summary,
            'tickets' => $tickets,
            'services' => $services
               ];

    } catch (PDOException $e) {
        error_log("Erreur rapport mensuel: " . $e->getMessage());
        return false;
    }
}

/**
 * Génère un rapport de performance des employés
 */
function generateEmployeeReport($pdo, $startDate, $endDate) {
    try {
        // Performance par employé
        $sqlEmployes = "SELECT 
                           e.id_employe,
                           e.nom,
                           e.prenom,
                           COUNT(DISTINCT t.id_ticket) as nb_tickets,
                           COUNT(lt.id_ligne) as nb_services,
                           SUM(lt.sous_total) as chiffre_affaires,
                           AVG(DATEDIFF(t.date_recuperation, t.date_depot)) as delai_moyen
                        FROM employes e
                        LEFT JOIN tickets t ON e.id_employe = t.id_employe
                        LEFT JOIN lignes_ticket lt ON t.id_ticket = lt.id_ticket
                        WHERE (t.date_depot IS NULL OR DATE(t.date_depot) BETWEEN ? AND ?)
                        AND (t.statut IS NULL OR t.statut != 'annule')
                        GROUP BY e.id_employe, e.nom, e.prenom
                        ORDER BY chiffre_affaires DESC";
        
        $stmtEmployes = $pdo->prepare($sqlEmployes);
        $stmtEmployes->execute([$startDate, $endDate]);
        $employes = $stmtEmployes->fetchAll(PDO::FETCH_ASSOC);
        
        // Services par employé
        $sqlServicesParEmploye = "SELECT 
                                     e.id_employe,
                                     s.nom_service,
                                     COUNT(lt.id_ligne) as nb_realisations,
                                     SUM(lt.sous_total) as chiffre_affaires_service
                                  FROM employes e
                                  JOIN tickets t ON e.id_employe = t.id_employe
                                  JOIN lignes_ticket lt ON t.id_ticket = lt.id_ticket
                                  JOIN services s ON lt.id_service = s.id_service
                                  WHERE DATE(t.date_depot) BETWEEN ? AND ?
                                  AND t.statut != 'annule'
                                  GROUP BY e.id_employe, s.nom_service
                                  ORDER BY e.id_employe, nb_realisations DESC";
        
        $stmtServicesParEmploye = $pdo->prepare($sqlServicesParEmploye);
        $stmtServicesParEmploye->execute([$startDate, $endDate]);
        $servicesParEmploye = $stmtServicesParEmploye->fetchAll(PDO::FETCH_ASSOC);
        
        // Organiser les services par employé
        $servicesParEmployeOrganises = [];
        foreach ($servicesParEmploye as $service) {
            $idEmploye = $service['id_employe'];
            if (!isset($servicesParEmployeOrganises[$idEmploye])) {
                $servicesParEmployeOrganises[$idEmploye] = [];
            }
            $servicesParEmployeOrganises[$idEmploye][] = $service;
        }
        
        return [
            'periode' => $startDate . ' au ' . $endDate,
            'employes' => $employes,
            'services_par_employe' => $servicesParEmployeOrganises,
            'totals' => [
                'total_employes' => count($employes),
                'total_ca' => array_sum(array_column($employes, 'chiffre_affaires')),
                'total_services' => array_sum(array_column($employes, 'nb_services')),
                'moyenne_ca_par_employe' => count($employes) > 0 ? 
                    array_sum(array_column($employes, 'chiffre_affaires')) / count($employes) : 0
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Erreur rapport employés: " . $e->getMessage());
        return false;
    }
}

/**
 * Génère un rapport de fidélisation clients
 */
function generateCustomerReport($pdo, $startDate, $endDate) {
    try {
        // Clients les plus actifs
        $sqlClients = "SELECT 
                          c.id_client,
                          c.nom,
                          c.prenom,
                          c.telephone,
                          COUNT(DISTINCT t.id_ticket) as nb_tickets,
                          SUM(t.montant_total) as montant_total,
                          MIN(t.date_depot) as premier_achat,
                          MAX(t.date_depot) as dernier_achat,
                          AVG(t.montant_total) as moyenne_panier,
                          DATEDIFF(?, MAX(t.date_depot)) as jours_inactivite
                       FROM clients c
                       LEFT JOIN tickets t ON c.id_client = t.id_client
                       WHERE (t.date_depot IS NULL OR DATE(t.date_depot) BETWEEN ? AND ?)
                       AND (t.statut IS NULL OR t.statut != 'annule')
                       GROUP BY c.id_client, c.nom, c.prenom, c.telephone
                       ORDER BY montant_total DESC, nb_tickets DESC
                       LIMIT 50";
        
        $stmtClients = $pdo->prepare($sqlClients);
        $stmtClients->execute([$endDate, $startDate, $endDate]);
        $clients = $stmtClients->fetchAll(PDO::FETCH_ASSOC);
        
        // Nouveaux clients sur la période
        $sqlNouveauxClients = "SELECT 
                                  COUNT(DISTINCT c.id_client) as nb_nouveaux_clients,
                                  DATE(c.date_inscription) as date_inscription
                               FROM clients c
                               WHERE DATE(c.date_inscription) BETWEEN ? AND ?
                               GROUP BY DATE(c.date_inscription)
                               ORDER BY DATE(c.date_inscription)";
        
        $stmtNouveauxClients = $pdo->prepare($sqlNouveauxClients);
        $stmtNouveauxClients->execute([$startDate, $endDate]);
        $nouveauxClients = $stmtNouveauxClients->fetchAll(PDO::FETCH_ASSOC);
        
        // Fréquentation par segment
        $segments = [
            'occasionnels' => ['min' => 1, 'max' => 3],
            'reguliers' => ['min' => 4, 'max' => 10],
            'fideles' => ['min' => 11, 'max' => null]
        ];
        
        $segmentationClients = [];
        foreach ($segments as $segment => $limits) {
            $sqlSegment = "SELECT COUNT(*) as nb_clients
                          FROM (
                              SELECT c.id_client, COUNT(t.id_ticket) as nb_tickets
                              FROM clients c
                              LEFT JOIN tickets t ON c.id_client = t.id_client
                              WHERE (t.statut IS NULL OR t.statut != 'annule')
                              GROUP BY c.id_client
                          ) as stats
                          WHERE nb_tickets >= ?" . 
                          ($limits['max'] ? " AND nb_tickets <= ?" : "");
            
            $params = [$limits['min']];
            if ($limits['max']) {
                $params[] = $limits['max'];
            }
            
            $stmtSegment = $pdo->prepare($sqlSegment);
            $stmtSegment->execute($params);
            $segmentationClients[$segment] = $stmtSegment->fetchColumn();
        }
        
        return [
            'periode' => $startDate . ' au ' . $endDate,
            'clients_actifs' => $clients,
            'nouveaux_clients' => $nouveauxClients,
            'segmentation' => $segmentationClients,
            'statistiques' => [
                'total_clients_actifs' => count($clients),
                'total_nouveaux_clients' => array_sum(array_column($nouveauxClients, 'nb_nouveaux_clients')),
                'moyenne_tickets_par_client' => count($clients) > 0 ? 
                    array_sum(array_column($clients, 'nb_tickets')) / count($clients) : 0,
                'ticket_moyen' => count($clients) > 0 ? 
                    array_sum(array_column($clients, 'montant_total')) / 
                    array_sum(array_column($clients, 'nb_tickets')) : 0
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Erreur rapport clients: " . $e->getMessage());
        return false;
    }
}

/**
 * Génère un rapport d'analyse des services
 */
function generateServicesAnalysis($pdo, $startDate, $endDate) {
    try {
        // Analyse détaillée par service
        $sqlAnalyse = "SELECT 
                          s.id_service,
                          s.nom_service,
                          s.categorie,
                          s.prix_standard,
                          COUNT(lt.id_ligne) as nombre_realisations,
                          SUM(lt.sous_total) as chiffre_affaires,
                          AVG(lt.sous_total) as prix_moyen,
                          MIN(lt.sous_total) as prix_min,
                          MAX(lt.sous_total) as prix_max,
                          COUNT(DISTINCT t.id_client) as clients_uniques
                       FROM services s
                       LEFT JOIN lignes_ticket lt ON s.id_service = lt.id_service
                       LEFT JOIN tickets t ON lt.id_ticket = t.id_ticket
                       WHERE (t.date_depot IS NULL OR DATE(t.date_depot) BETWEEN ? AND ?)
                       AND (t.statut IS NULL OR t.statut != 'annule')
                       AND s.est_actif = 1
                       GROUP BY s.id_service, s.nom_service, s.categorie, s.prix_standard
                       ORDER BY chiffre_affaires DESC";
        
        $stmtAnalyse = $pdo->prepare($sqlAnalyse);
        $stmtAnalyse->execute([$startDate, $endDate]);
        $analyseServices = $stmtAnalyse->fetchAll(PDO::FETCH_ASSOC);
        
        // Tendances temporelles
        $sqlTendances = "SELECT 
                            DATE(t.date_depot) as date,
                            s.nom_service,
                            COUNT(lt.id_ligne) as nb_ventes,
                            SUM(lt.sous_total) as chiffre_affaires
                         FROM lignes_ticket lt
                         JOIN tickets t ON lt.id_ticket = t.id_ticket
                         JOIN services s ON lt.id_service = s.id_service
                         WHERE DATE(t.date_depot) BETWEEN ? AND ?
                         AND t.statut != 'annule'
                         GROUP BY DATE(t.date_depot), s.nom_service
                         ORDER BY DATE(t.date_depot), nb_ventes DESC";
        
        $stmtTendances = $pdo->prepare($sqlTendances);
        $stmtTendances->execute([$startDate, $endDate]);
        $tendances = $stmtTendances->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcul des parts de marché par catégorie
        $categories = [];
        foreach ($analyseServices as $service) {
            $categorie = $service['categorie'];
            if (!isset($categories[$categorie])) {
                $categories[$categorie] = [
                    'nombre_services' => 0,
                    'total_ventes' => 0,
                    'chiffre_affaires' => 0
                ];
            }
            $categories[$categorie]['nombre_services']++;
            $categories[$categorie]['total_ventes'] += $service['nombre_realisations'];
            $categories[$categorie]['chiffre_affaires'] += $service['chiffre_affaires'];
        }
        
        // Classement des services
        $totalCA = array_sum(array_column($analyseServices, 'chiffre_affaires'));
        foreach ($analyseServices as &$service) {
            $service['part_marche'] = $totalCA > 0 ? 
                ($service['chiffre_affaires'] / $totalCA) * 100 : 0;
        }
        
        return [
            'periode' => $startDate . ' au ' . $endDate,
            'analyse_services' => $analyseServices,
            'tendances' => $tendances,
            'categories' => $categories,
            'totaux' => [
                'total_services' => count($analyseServices),
                'total_ventes' => array_sum(array_column($analyseServices, 'nombre_realisations')),
                'chiffre_affaires_total' => $totalCA,
                'service_plus_vendu' => !empty($analyseServices) ? 
                    $analyseServices[0] : null
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Erreur analyse services: " . $e->getMessage());
        return false;
    }
}

/**
 * Exporte un rapport en format CSV
 */
function exportReportToCSV($reportData, $filename) {
    if (empty($reportData)) {
        return false;
    }
    
    $csvContent = "";
    
    // En-tête
    $csvContent .= "Rapport généré le: " . date('Y-m-d H:i:s') . "\n";
    
    // Données principales
    foreach ($reportData as $section => $data) {
        $csvContent .= "\n=== " . strtoupper($section) . " ===\n";
        
        if (is_array($data)) {
            if (!empty($data)) {
                // En-têtes de colonnes
                $firstRow = reset($data);
                $headers = array_keys($firstRow);
                $csvContent .= implode(',', $headers) . "\n";
                
                // Données
                foreach ($data as $row) {
                    $csvContent .= implode(',', array_map(function($value) {
                        return '"' . str_replace('"', '""', $value) . '"';
                    }, $row)) . "\n";
                }
            }
        } else {
            $csvContent .= $data . "\n";
        }
    }
    
    // Sauvegarde dans un fichier
    $filepath = __DIR__ . '/../exports/' . $filename . '_' . date('Ymd_His') . '.csv';
    
    if (!is_dir(dirname($filepath))) {
        mkdir(dirname($filepath), 0755, true);
    }
    
    if (file_put_contents($filepath, $csvContent)) {
        return $filepath;
    }
    
    return false;
}

/**
 * Génère un rapport d'activité quotidienne
 */
function generateDailyActivityReport($pdo, $date = null) {
    if (!$date) {
        $date = date('Y-m-d');
    }
    
    try {
        // Tickets du jour
        $sqlTickets = "SELECT 
                          t.id_ticket,
                          t.numero_ticket,
                          t.date_depot,
                          t.date_recuperation_prevue,
                          t.date_recuperation,
                          t.statut,
                          t.montant_total,
                          t.montant_verse,
                          c.nom as client_nom,
                          c.prenom as client_prenom,
                          e.nom as employe_nom,
                          e.prenom as employe_prenom
                       FROM tickets t
                       LEFT JOIN clients c ON t.id_client = c.id_client
                       LEFT JOIN employes e ON t.id_employe = e.id_employe
                       WHERE DATE(t.date_depot) = ?
                       ORDER BY t.date_depot DESC";
        
        $stmtTickets = $pdo->prepare($sqlTickets);
        $stmtTickets->execute([$date]);
        $tickets = $stmtTickets->fetchAll(PDO::FETCH_ASSOC);
        
        // Services du jour
        $sqlServices = "SELECT 
                           s.nom_service,
                           COUNT(lt.id_ligne) as quantite,
                           SUM(lt.sous_total) as total
                        FROM lignes_ticket lt
                        JOIN services s ON lt.id_service = s.id_service
                        JOIN tickets t ON lt.id_ticket = t.id_ticket
                        WHERE DATE(t.date_depot) = ?
                        GROUP BY s.nom_service
                        ORDER BY quantite DESC";
        
        $stmtServices = $pdo->prepare($sqlServices);
        $stmtServices->execute([$date]);
        $services = $stmtServices->fetchAll(PDO::FETCH_ASSOC);
        
        // Statistiques du jour
        $stats = [
            'total_tickets' => count($tickets),
            'tickets_termines' => count(array_filter($tickets, function($t) { 
                return $t['statut'] == 'recupere'; 
            })),
            'tickets_en_cours' => count(array_filter($tickets, function($t) { 
                return in_array($t['statut'], ['depose', 'en_cours']); 
            })),
            'chiffre_affaires' => array_sum(array_column($tickets, 'montant_total')),
            'encaissements' => array_sum(array_column($tickets, 'montant_verse')),
            'services_vendus' => array_sum(array_column($services, 'quantite'))
        ];
        
        // Tickets à récupérer aujourd'hui
        $sqlARecuperer = "SELECT 
                             t.numero_ticket,
                             t.date_depot,
                             t.date_recuperation_prevue,
                             t.montant_total,
                             c.nom as client_nom,
                             c.prenom as client_prenom,
                             c.telephone
                          FROM tickets t
                          JOIN clients c ON t.id_client = c.id_client
                          WHERE DATE(t.date_recuperation_prevue) = ?
                          AND t.statut NOT IN ('recupere', 'annule')
                          ORDER BY t.date_recuperation_prevue";
        
        $stmtARecuperer = $pdo->prepare($sqlARecuperer);
        $stmtARecuperer->execute([$date]);
        $aRecuperer = $stmtARecuperer->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'date' => $date,
            'statistiques' => $stats,
            'tickets' => $tickets,
            'services' => $services,
            'a_recuperer' => $aRecuperer,
            'derniere_mise_a_jour' => date('Y-m-d H:i:s')
        ];
        
    } catch (PDOException $e) {
        error_log("Erreur rapport quotidien: " . $e->getMessage());
        return false;
    }
}

/**
 * Vérifie la cohérence des données pour une période
 */
function verifyDataConsistency($pdo, $startDate, $endDate) {
    try {
        $anomalies = [];
        
        // 1. Vérifier les tickets sans lignes
        $sqlTicketsSansLignes = "SELECT t.id_ticket, t.numero_ticket, t.date_depot
                                FROM tickets t
                                LEFT JOIN lignes_ticket lt ON t.id_ticket = lt.id_ticket
                                WHERE DATE(t.date_depot) BETWEEN ? AND ?
                                AND lt.id_ligne IS NULL
                                AND t.statut != 'annule'";
        
        $stmtTicketsSansLignes = $pdo->prepare($sqlTicketsSansLignes);
        $stmtTicketsSansLignes->execute([$startDate, $endDate]);
        $ticketsSansLignes = $stmtTicketsSansLignes->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($ticketsSansLignes)) {
            $anomalies['tickets_sans_lignes'] = $ticketsSansLignes;
        }
        
        // 2. Vérifier les incohérences de montants
        $sqlIncoherenceMontants = "SELECT 
                                      t.id_ticket,
                                      t.numero_ticket,
                                      t.montant_total as montant_ticket,
                                      SUM(lt.sous_total) as total_lignes,
                                      t.montant_total - SUM(lt.sous_total) as difference
                                   FROM tickets t
                                   JOIN lignes_ticket lt ON t.id_ticket = lt.id_ticket
                                   WHERE DATE(t.date_depot) BETWEEN ? AND ?
                                   AND t.statut != 'annule'
                                   GROUP BY t.id_ticket, t.numero_ticket, t.montant_total
                                   HAVING ABS(t.montant_total - SUM(lt.sous_total)) > 0.01";
        
        $stmtIncoherenceMontants = $pdo->prepare($sqlIncoherenceMontants);
        $stmtIncoherenceMontants->execute([$startDate, $endDate]);
        $incoherencesMontants = $stmtIncoherenceMontants->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($incoherencesMontants)) {
            $anomalies['incoherences_montants'] = $incoherencesMontants;
        }
        
        // 3. Vérifier les dates incohérentes
        $sqlDatesIncoherentes = "SELECT 
                                    id_ticket,
                                    numero_ticket,
                                    date_depot,
                                    date_recuperation_prevue,
                                    date_recuperation
                                 FROM tickets
                                 WHERE DATE(date_depot) BETWEEN ? AND ?
                                 AND statut != 'annule'
                                 AND (date_recuperation < date_depot 
                                      OR date_recuperation_prevue < date_depot)";
        
        $stmtDatesIncoherentes = $pdo->prepare($sqlDatesIncoherentes);
        $stmtDatesIncoherentes->execute([$startDate, $endDate]);
        $datesIncoherentes = $stmtDatesIncoherentes->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($datesIncoherentes)) {
            $anomalies['dates_incoherentes'] = $datesIncoherentes;
        }
        
        return [
            'periode' => $startDate . ' au ' . $endDate,
            'date_verification' => date('Y-m-d H:i:s'),
            'anomalies' => $anomalies,
            'resume' => [
                'total_anomalies' => count($ticketsSansLignes) + 
                                    count($incoherencesMontants) + 
                                    count($datesIncoherentes),
                'tickets_sans_lignes' => count($ticketsSansLignes),
                'incoherences_montants' => count($incoherencesMontants),
                'dates_incoherentes' => count($datesIncoherentes)
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Erreur vérification données: " . $e->getMessage());
        return false;
    }
}
?>