@AGENTS.md

# CLAUDE.md

Ce fichier guide Claude Code sur ce projet. Garde-le concis : le détail complet du
cahier des charges est dans `docs/cahier-des-charges.md`, le schéma SQL complet et
commenté (avec les requêtes de référence) dans `docs/schema.sql`. Consulte ces
fichiers quand c'est pertinent plutôt que de dupliquer leur contenu ici.

## Projet

Application web pour un enseignant de CM2 gérant des séances de course de fond :
les élèves saisissent eux-mêmes leurs temps de passage après la course,
l'application génère des graphiques imprimables (durée de tour par tour) et des
statistiques.

## Stack

- PHP (vanilla, pas de framework lourd) + PDO
- MariaDB — hébergement O2switch (mutualisé, pas de Node.js côté serveur)
- TCPDF ou mPDF pour la génération des PDF
- JpGraph ou GD natif pour les graphiques (compatibles hébergement mutualisé)

## Rôles et droits (à respecter strictement)

- **Mot de passe unique "site"** : protège tout accès (saisie, export). Modifiable
  uniquement par l'administrateur, via un menu dédié.
- **Administrateur** (identifiants séparés du mot de passe site) : seul habilité à
  créer/modifier enseignants, années scolaires, classes et listes d'élèves.
- **Enseignants et élèves** (après le mot de passe site) : peuvent uniquement
  saisir/modifier des temps de passage et exporter des graphiques. Confiance
  réciproque : aucune restriction supplémentaire entre eux.
- Aucun verrouillage de séance : une saisie reste modifiable à tout moment.

## Règles métier critiques

- Les temps de passage saisis sont des **temps cumulés** depuis le départ, jamais
  des durées de tour.
- La durée d'un tour = différence entre deux temps cumulés consécutifs,
  **calculée à la volée**, jamais stockée.
- Le n° de tour n'est **pas stocké** : c'est le rang du temps cumulé une fois trié
  par ordre croissant.
- Un tour "manquant" = absence de ligne en base, pas une valeur NULL.
- Un temps "incertain" (écriture peu lisible sur la feuille papier) est marqué via
  le flag `incertain`, mais reste saisi avec une valeur.
- Un tour est incertain pour les stats si l'un des deux temps cumulés qui le
  délimitent est marqué incertain (voir `docs/schema.sql` pour la requête).
- Stats (moyenne, écart-type, meilleur/pire tour) : **excluent** les tours
  incertains. Le graphique les affiche quand même, avec un marqueur distinct.
- Comparaison multi-séances sur un même graphique : max 4 séances, distinction par
  pointillés/formes — jamais par couleur seule (impression noir et blanc).
- Suppression automatique d'une année scolaire entière (cascade) 2 ans après sa
  fin (31 août de l'année n+1).
- Import CSV élèves : proposer un aperçu multi-configurations (UTF-8 / Latin1 /
  Windows-1252 × virgule / point-virgule) avant validation par l'admin, avec choix
  de la colonne d'identification si plusieurs colonnes.
- Cookie : mémorise dernier enseignant/classe, dernière date de séance et dernière
  longueur de tour saisis, pour préremplir la saisie suivante — valeurs toujours
  visibles et modifiables, jamais cachées.

## Ergonomie

Utilisateurs = enfants de CM2 (10-11 ans). Écrans très simples, gros boutons,
clavier numérique pour les temps (`inputmode="numeric"`), format mm:ss. Interface
responsive (mobile / tablette / desktop).

## Documentation détaillée

- `docs/schema.sql` — schéma SQL complet, commenté, avec requêtes de référence
  (dérivation du n° de tour, statistiques, purge des données)
- `docs/cahier-des-charges.md` — spécification fonctionnelle complète
