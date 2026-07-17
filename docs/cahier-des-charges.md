# Cahier des charges — Application course de fond CM2

## Contexte

Enseignant de CM2 organisant des séances de course de fond par binômes : un élève
court pendant que l'autre note ses temps de passage sur une feuille papier. Après
la séance, chaque élève saisit lui-même ses temps dans l'application.

## Utilisateurs et droits

- Accès protégé par un **mot de passe unique "site"**, modifiable uniquement par
  l'administrateur via un menu dédié.
- **Administrateur** : identifiants séparés du mot de passe site, seul habilité à
  créer/modifier enseignants, années scolaires, classes et listes d'élèves.
- **Enseignants et élèves** : une fois le mot de passe site validé, peuvent
  librement saisir/modifier des temps de passage et exporter des graphiques
  (confiance réciproque, aucune distinction de droits entre eux).
- Aucun verrouillage : une saisie reste modifiable à tout moment après coup.

## Structure des données

- Un enseignant est associé à une classe pour une année scolaire donnée (recréé
  chaque année ; deux classes la même année = deux enregistrements distincts).
- Élève identifié par un seul champ : le prénom. Pas d'historique d'une année sur
  l'autre. Homonymes gérés manuellement par l'administrateur (initiale, numéro ou
  surnom ajouté au prénom).
- Import de la liste d'élèves facilité par un fichier **CSV** : l'administrateur
  doit pouvoir prévisualiser le fichier selon plusieurs combinaisons encodage
  (UTF-8, Latin1, Windows-1252) × séparateur (virgule, point-virgule) et choisir
  celle qui affiche correctement les données, ainsi que la colonne à utiliser
  comme identifiant si le fichier en contient plusieurs.

## Parcours de saisie

- Sélection : année scolaire (année en cours proposée par défaut) → enseignant /
  classe (présélectionné si un seul existe) → élève.
- Formulaire : date de séance + longueur du tour (en mètres, optionnelle) + temps
  de passage successifs.
- Les temps saisis sont des **temps cumulés** (lecture du chrono à chaque
  passage), pas des durées de tour.
- Contrôle : temps strictement croissants.
- Un tour peut être laissé de côté s'il n'a pas été noté (absence de saisie, pas
  de valeur forcée).
- Un temps dont l'écriture papier est difficile à relire peut être saisi avec une
  case "incertain" cochée : la valeur reste enregistrée mais signalée.
- **Cookie** mémorisant les dernières valeurs saisies (enseignant/classe, date de
  séance, longueur du tour) pour préremplir la saisie de l'élève suivant
  (probablement de la même classe) — valeurs toujours visibles et modifiables,
  jamais masquées.
- Format de saisie des temps : mm:ss.

## Restitution

- **Graphique** par élève : n° de tour en abscisse (déduit du rang chronologique
  des temps cumulés, pas stocké), durée de tour en ordonnée (calculée, pas
  stockée).
- Comparaison possible de plusieurs séances d'un même élève sur un même
  graphique (max 4), distinguées par pointillés/formes (pas de dépendance à la
  couleur, pour une impression noir et blanc).
- Les tours "incertains" restent affichés sur le graphique avec un marqueur
  distinct (ex. point évidé), mais sont **exclus** des statistiques.
- En-tête de chaque page : prénom, classe, date. Vitesse moyenne affichée en
  légende si la longueur du tour est renseignée.
- Sélection multi-élèves par l'enseignant (tous ou une partie), export **PDF**
  (un graphique par page, tous les élèves sélectionnés dans un seul fichier).
- Export **CSV** des données brutes, en plus du PDF.
- **Statistiques** par séance : meilleur tour, tour le plus lent, vitesse
  moyenne, écart-type (calculé sur les tours d'une même séance pour un élève,
  afin de repérer les tours aberrants comme un tour marché).

## Suppression des données

- Une année scolaire entière (n/n+1) est supprimée automatiquement (en cascade :
  classe, élèves, séances, temps de passage) 2 ans après sa fin (31 août de
  l'année n+1).

## Ergonomie

- Utilisateurs = élèves de CM2 (10-11 ans). Interface très simple, gros boutons,
  clavier numérique pour les temps.
- Application responsive (mobile, tablette, desktop).

## Stack technique

- PHP (vanilla, sans framework lourd) + PDO, MariaDB — hébergement O2switch
  (mutualisé).
- Génération PDF : TCPDF ou mPDF. Graphiques : JpGraph ou GD natif (compatibles
  hébergement mutualisé, pas de Node.js nécessaire).
- Tâche cron (cPanel O2switch) pour la purge automatique des données.

---

*Historique : projet initialement envisagé en base NoSQL, basculé en SQL/MariaDB
pour coller aux contraintes de l'hébergement O2switch.*
