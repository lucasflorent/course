-- =====================================================================
-- Schéma de base de données — Application course de fond CM2
-- SGBD : MariaDB (hébergement O2switch)
-- =====================================================================

-- Compte(s) administrateur : identifiants séparés du mot de passe "site"
CREATE TABLE administrateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifiant VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mot de passe unique protégeant l'accès à l'application (saisie + export).
-- Une seule ligne (id=1), modifiable uniquement par un administrateur authentifié.
CREATE TABLE parametres_site (
    id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    modifie_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT une_seule_ligne CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Une ligne = un enseignant, pour une classe, pour une année scolaire donnée.
-- Si un enseignant a 2 classes la même année : 2 lignes distinctes.
-- Les enseignants sont ressaisis chaque année (pas d'historique inter-années).
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enseignant VARCHAR(100) NOT NULL,
    annee_debut YEAR NOT NULL,          -- 2025 = année scolaire "2025/2026"
    libelle_classe VARCHAR(100),        -- optionnel, ex. "CM2 A"
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_enseignant_annee (annee_debut, enseignant)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Élèves d'une classe. Un seul champ d'identification (prénom).
-- Les homonymes sont gérés manuellement par l'administrateur
-- (ex. "Léa B.", "Léa 2") au moment de l'import/saisie de la liste.
CREATE TABLE eleves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    classe_id INT NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    FOREIGN KEY (classe_id) REFERENCES classes(id) ON DELETE CASCADE,
    INDEX idx_classe (classe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Une séance de course pour un élève, à une date donnée.
-- Longueur du tour : champ du formulaire de saisie (pas un réglage enseignant),
-- optionnelle (NULL si non renseignée).
CREATE TABLE seances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    eleve_id INT NOT NULL,
    date_seance DATE NOT NULL,
    longueur_tour_m DECIMAL(6,2) NULL,
    saisi_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modifie_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (eleve_id) REFERENCES eleves(id) ON DELETE CASCADE,
    INDEX idx_eleve_date (eleve_id, date_seance),
    CHECK (longueur_tour_m IS NULL OR longueur_tour_m > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Temps de passage bruts (temps CUMULÉS depuis le départ, jamais des durées).
-- Pas de numéro de tour stocké : il est déduit du tri chronologique (voir
-- requêtes ci-dessous). Pas de ligne = tour manquant. incertain=1 = écriture
-- peu lisible sur la feuille papier, valeur saisie quand même mais à prendre
-- avec réserve.
CREATE TABLE temps_passage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seance_id INT NOT NULL,
    temps_cumule_s SMALLINT UNSIGNED NOT NULL,
    incertain BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (seance_id) REFERENCES seances(id) ON DELETE CASCADE,
    UNIQUE KEY unique_temps_par_seance (seance_id, temps_cumule_s)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- Requête de référence : dérivation du n° de tour, de la durée de tour et
-- du flag "tour incertain" (incertain si l'un des 2 temps qui le délimitent
-- est marqué incertain) — à utiliser pour construire le graphique.
-- Le premier tour est chronométré depuis le départ (temps_precedent = 0,
-- implicite, jamais stocké) : N temps de passage saisis donnent N tours.
-- =====================================================================
-- WITH tours AS (
--     SELECT
--         temps_cumule_s,
--         incertain,
--         COALESCE(LAG(temps_cumule_s) OVER (ORDER BY temps_cumule_s), 0) AS temps_precedent,
--         COALESCE(LAG(incertain)      OVER (ORDER BY temps_cumule_s), FALSE) AS precedent_incertain
--     FROM temps_passage
--     WHERE seance_id = ?
-- )
-- SELECT
--     ROW_NUMBER() OVER (ORDER BY temps_cumule_s) AS numero_tour,
--     temps_cumule_s - temps_precedent AS duree_tour_s,
--     (incertain OR precedent_incertain) AS tour_incertain
-- FROM tours
-- ORDER BY temps_cumule_s;

-- =====================================================================
-- Requête de référence : statistiques d'une séance (exclut les tours
-- incertains). À exécuter sur le résultat de la requête ci-dessus.
-- =====================================================================
-- SELECT
--     MIN(duree_tour_s)        AS meilleur_tour_s,
--     MAX(duree_tour_s)        AS pire_tour_s,
--     AVG(duree_tour_s)        AS duree_moyenne_s,
--     STDDEV_POP(duree_tour_s) AS ecart_type_s
-- FROM (<requête ci-dessus>) AS tours
-- WHERE tour_incertain = FALSE;
-- Si le résultat est NULL (aucun tour certain), afficher "Non calculable"
-- plutôt qu'une erreur ou un 0.

-- =====================================================================
-- Requête de référence : purge d'une année scolaire entière 2 ans après sa
-- fin (année n/n+1 se termine le 31/08 de l'année n+1). Le cascade
-- (ON DELETE CASCADE) supprime automatiquement élèves, séances et temps de
-- passage associés. À lancer via une tâche cron (cPanel O2switch).
-- =====================================================================
-- DELETE FROM classes
-- WHERE CURDATE() >= STR_TO_DATE(CONCAT(annee_debut + 3, '-09-01'), '%Y-%m-%d');
