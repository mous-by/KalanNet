# KalanNet

KalanNet est une application Laravel de gestion scolaire. Elle regroupe les modules principaux d'une école : élèves, inscriptions, parents, pédagogie, finances, emploi du temps, bulletins, utilisateurs et configuration.

Ce document sert de base technique et fonctionnelle pour préparer plus tard un manuel d'utilisation complet.

## Prérequis

- PHP 8.3 ou plus
- MySQL ou MariaDB
- Composer
- Node.js et npm si les assets Vite doivent être reconstruits
- Extensions PHP requises par Laravel et PhpSpreadsheet

## Installation locale

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Si l'application est utilisée avec XAMPP, vérifier que `APP_URL` dans `.env` correspond à l'URL réellement utilisée.

## Commandes utiles

```bash
php artisan optimize:clear
php artisan route:list
php artisan view:clear
php artisan config:clear
```

## Modules principaux

### Tableau de bord

Le tableau de bord donne une vue synthétique de l'établissement connecté : effectifs, statistiques, finances et indicateurs selon les droits de l'utilisateur.

### Élèves

Chemin : `Élèves > Liste des Élèves`

Fonctionnement de la liste :

- Par défaut, aucun élève n'est affiché.
- L'utilisateur doit choisir une classe et une année scolaire.
- Les filtres sont envoyés en `POST` afin de ne pas exposer les paramètres dans l'URL.
- La liste s'affiche sans pagination.
- Une recherche peut filtrer par nom, prénom ou matricule.

Données affichées dans le tableau :

- N°
- Prénom et nom
- Matricule
- Classe
- Année scolaire
- Genre
- Date de naissance
- Lieu de naissance
- Adresse
- Cas social
- Date d'inscription
- Photo
- Actions

Actions disponibles :

- Voir le profil de l'élève
- Modifier les informations de l'élève
- Enregistrer un transfert avec destination, motif, travail et conduite
- Retirer l'élève des listes actives avec confirmation
- Sélectionner un ou plusieurs élèves avec les cases à cocher
- Cocher tous les élèves visibles
- Imprimer la liste en PDF
- Exporter la liste en Excel

La suppression utilise une confirmation SweetAlert. L'élève retiré n'est plus visible dans les listes actives, mais son historique reste conservé.

Le transfert est séparé de l'abandon. L'abandon, l'exclusion et l'année blanche sont gérés dans le module de réinscription intelligente.

La carte scolaire et le dossier élève ne sont pas affichés dans les actions de la liste, car ils disposent chacun d'un accès dédié dans le menu `Élèves & Parents`.

### Impression PDF de la liste des élèves

Le bouton d'impression est masqué tant qu'aucune liste filtrée n'est affichée.

Deux modes sont possibles :

- Si aucun élève n'est coché, le PDF contient toute la liste filtrée.
- Si des élèves sont cochés, le PDF contient uniquement la sélection.

Le PDF reprend l'en-tête institutionnel utilisé par les bulletins :

- Ministère de l'Éducation Nationale
- Académie d'Enseignement
- CAP
- République du Mali
- Devise nationale
- Nom de l'école au-dessus du titre

Colonnes du PDF :

- N°
- Matricule
- Prénom et nom
- Genre
- Date de naissance
- Lieu de naissance

Route utilisée :

```text
POST /eleves/liste/pdf
```

### Export Excel de la liste des élèves

Le bouton d'export Excel suit la même logique que le PDF :

- Sans sélection : export de toute la liste filtrée.
- Avec sélection : export uniquement des élèves cochés.

Le fichier généré est au format `.xlsx`.

Colonnes exportées :

- N°
- Matricule
- Prénom
- Nom
- Genre
- Date de naissance
- Lieu de naissance

Route utilisée :

```text
POST /eleves/liste/excel
```

### Cartes scolaires

Chemin : `Élèves & Parents > Cartes scolaires`

Le module permet de préparer et d'imprimer les cartes d'identité scolaires par classe et par année scolaire.

Fonctionnement :

- Par défaut, aucune carte n'est affichée.
- L'utilisateur choisit une classe et une année scolaire.
- Les filtres sont envoyés en `POST` afin de ne pas afficher `id_classe` et `id_annee` dans l'URL.
- Le système affiche les élèves actifs correspondant au filtre.
- Une recherche peut filtrer par nom, prénom ou matricule.
- L'utilisateur peut cocher tous les élèves ou seulement quelques-uns.
- Si aucun élève n'est coché, le PDF contient toutes les cartes de la liste filtrée.
- Si des élèves sont cochés, le PDF contient uniquement les cartes sélectionnées.
- Les modèles disponibles sont : institutionnel, moderne, badge vertical, Alliance amélioré, horizon et compact.

Atelier de configuration :

- Choix du modèle de carte.
- Couleur principale appliquée aux bordures arrondies de la carte.
- Couleur secondaire appliquée aux bordures de la photo.
- Titre imprimé sur la carte.
- Libellé de signature.
- Signature électronique dessinée dans l'interface.
- Mémorisation locale de la signature électronique par école : une signature déjà faite est réutilisée automatiquement, sauf si l'utilisateur la modifie ou l'efface.
- Affichage optionnel du drapeau du Mali.
- Deux présentations du drapeau : en haut à gauche ou en filigrane diagonal vert-jaune-rouge.
- Affichage optionnel du logo de l'école.
- Affichage optionnel du QR code.

Informations affichées sur la carte :

- République du Mali et devise nationale
- Nom de l'école
- Téléphone de l'administrateur de l'école, avec fallback sur le téléphone de l'école
- Année scolaire
- Photo de l'élève si disponible
- Prénom et nom
- Matricule
- Classe
- Genre
- Date et lieu de naissance
- QR code contenant directement les informations essentielles de l'élève
- Signature électronique si elle est renseignée

QR code :

- Le QR code n'encode plus une URL locale de type `localhost`.
- Il contient directement les données de la carte : école, téléphone, année, classe, élève, matricule, sexe, date et lieu de naissance.
- Le scan du QR peut donc afficher les informations sans dépendre de l'adresse locale de développement.

Routes utilisées :

```text
GET|POST /eleves/cartes-scolaires
POST     /eleves/cartes-scolaires/pdf
```

### Dossiers élèves

Chemin : `Élèves & Parents > Dossiers élèves`

Le module dossiers élèves centralise les informations essentielles d'un élève dans une page unique, pensée pour être lisible par un utilisateur non informaticien.

Fonctionnement de la liste :

- Par défaut, aucun dossier n'est affiché.
- L'utilisateur doit sélectionner une classe et une année scolaire.
- Un indicateur de chargement s'affiche pendant l'application des filtres.
- La liste peut être filtrée par statut : actifs, transférés ou retirés.
- La recherche peut filtrer par nom, prénom ou matricule.
- Chaque ligne ouvre directement le dossier de l'élève.

Contenu du dossier :

- Identité, matricule, classe, année scolaire et école.
- Photo et informations de naissance.
- Responsables rattachés au dossier.
- Synthèse financière : montant prévu, montant payé, reste à payer et statut.
- Échéances ou anciennes planifications selon le système utilisé.
- Paiements récents avec accès au reçu normal et au reçu thermique.
- Résultats récents, moyennes et historique du dossier si les tables existent.
- Alertes utiles : parent manquant, matricule absent, plan de paiement manquant, échéance en retard, dossier transféré ou retiré.

Paiements :

- Le dossier utilise les plans de paiement modernes quand ils existent.
- Si aucun plan moderne n'est disponible, il utilise les anciennes planifications liées à l'inscription.
- Le reste à payer est calculé depuis les paiements réellement enregistrés.
- L'alerte `Aucun plan de paiement n'est encore attaché à cet élève` n'apparaît que si aucun plan moderne et aucune ancienne planification ne sont trouvés.

Navigation :

- Les boutons de section restent sur la même page et utilisent des ancres internes.
- Le bouton de modification n'est pas affiché dans le dossier.

Accès parent :

- Un parent connecté accède au dossier de ses enfants depuis son tableau de bord.
- Il ne peut ouvrir que les dossiers des élèves qui lui sont rattachés.
- Il peut télécharger les reçus de paiement de ses propres enfants, y compris le reçu thermique.

Routes utilisées :

```text
GET /eleves/dossiers
GET /eleves/{id}
```

### Inscriptions

Chemin : `Élèves & Parents > Inscriptions`

Types d'inscription :

- Inscription individuelle
- Inscription par groupe via fichier Excel
- Réinscription

Points importants :

- Le matricule peut être saisi ou généré automatiquement.
- Les noms accentués sont normalisés pour éviter les erreurs d'encodage dans les matricules automatiques.
- Les imports Excel acceptent les fichiers `.xls` et `.xlsx`.
- Les planifications doivent correspondre à la classe et à l'année choisies.

### Inscription par groupe

L'inscription par groupe utilise un modèle Excel téléchargeable depuis l'interface.

Colonnes attendues :

- `prenom_eleve`
- `nom_eleve`
- `date_naissance`
- `lieu_naissance`
- `adresse_eleve`
- `genre_eleve`
- `cas_social`
- `matricule`

La route d'import est :

```text
POST /pedagogie/inscriptions/groupe/import
```

Si la classe ou la planification ne correspond pas, le système renvoie une erreur de formulaire au lieu d'une page 404.

### Réinscription intelligente

Chemin : `Élèves & Parents > Réinscription`

La réinscription se fait par classe afin d'éviter de traiter les élèves un par un.

Prérequis :

- L'année scolaire actuelle doit exister.
- L'année scolaire cible doit être créée dans `Configuration > Années scolaires`.
- Les classes sources et les classes suivantes doivent être créées.
- Les moyennes annuelles doivent exister pour que le système propose automatiquement passant ou redoublant. Si les moyennes n'existent pas encore, le système laisse l'administration décider manuellement.

Fonctionnement :

- L'utilisateur choisit la classe actuelle.
- Le système cherche automatiquement la classe suivante.
- Exemple : `7eme année` propose automatiquement `8eme année` si elle existe.
- Si la classe suivante n'existe pas, un message demande de la créer avant de préparer la réinscription.
- L'utilisateur choisit l'année actuelle.
- Le système sélectionne automatiquement l'année cible suivante si elle existe.
- Une année cible ne peut pas être une année précédente ou la même année que l'année actuelle.
- Le bouton `Préparer la liste` reste masqué tant que la classe actuelle, l'année actuelle, l'année cible et la classe cible ne sont pas prêtes.

Règles de décision automatique :

- Fondamentale I : passant si la moyenne annuelle est supérieure ou égale à `5`.
- Fondamentale II, secondaire général et secondaire technique : passant si la moyenne annuelle est supérieure ou égale à `10`.
- Si la moyenne est inférieure au seuil, le système propose `Redoublant`.
- Si la moyenne n'existe pas, le système affiche `Moyenne non disponible`.

Décisions possibles :

- `Passant` : l'élève passe dans la classe cible et l'année cible.
- `Redoublant` : l'élève reste dans la même classe mais passe dans l'année cible.
- `Ajourné / année blanche` : l'élève garde sa situation actuelle et la décision est enregistrée.
- `Abandon` : l'élève sort des listes actives.
- `Exclu` : l'élève sort des listes actives.

Cas particulier du passage forcé :

Si un élève n'a pas la moyenne mais que l'administration choisit quand même `Passant`, le système enregistre la décision comme un passage forcé.

Ajourné, abandon et exclusion :

- Un motif est demandé pour ces décisions.
- L'historique reste conservé.
- Les élèves en abandon ou exclusion ne sont plus comptés comme élèves actifs.

Routes utilisées :

```text
GET  /pedagogie/inscriptions/reinscription
POST /pedagogie/inscriptions/reinscription
```

Migration associée :

```text
database/migrations/2026_05_21_000001_add_decision_details_to_reinscription_table.php
```

Cette migration ajoute les champs permettant de conserver la décision proposée et le motif administratif.

### Planifications financières

Chemin : `Finances > Planification`

La création de planification permet de sélectionner plusieurs classes en une seule opération.

Exemple :

- Sélectionner 1ère année, 2ème année et 3ème année.
- Saisir une seule planification mensuelle.
- Le système crée automatiquement les lignes nécessaires pour chaque classe sélectionnée.

La saisie est intelligente :

- Une planification mensuelle sert de base.
- Le bouton `+` propose automatiquement la suite logique : mensuelle, trimestrielle, annuelle.
- Les dates de fin sont calculées automatiquement.
- Le montant est calculé depuis la base mensuelle.

Règles de calcul :

- Mensuelle : montant mensuel x 1
- Trimestrielle : montant mensuel x 3
- Annuelle : montant mensuel x 9
- Une année scolaire annuelle se termine au 30 juin.

Exemple :

```text
Mensuelle : 5 000
Trimestrielle : 15 000
Annuelle : 45 000
```

### Paiements

Le module finances permet de gérer :

- Planifications
- Paiements élèves
- Historique
- Reçus PDF
- Reçus thermiques PDF
- Exports PDF et Excel selon les écrans concernés

Reçus :

- Le reçu normal est disponible via la route de téléchargement standard.
- Le reçu thermique est disponible pour les paiements élèves et dans le dossier élève.
- Un parent peut télécharger uniquement les reçus des enfants qui lui sont rattachés.

Routes utilisées :

```text
GET /finances/paiements/{id}/download
GET /finances/paiements/{id}/thermique
```

### Bulletins

Les bulletins utilisent un gabarit PDF institutionnel avec :

- En-tête école / académie / CAP
- République du Mali
- Classe
- Année scolaire
- Notes
- Moyenne
- Rang
- Signatures

Ce gabarit sert de référence visuelle pour les autres PDF scolaires.

### Emploi du temps

Le module emploi du temps permet :

- La sélection d'une classe et d'une année scolaire
- La saisie et la sauvegarde d'une grille
- L'export PDF de l'emploi du temps

### Parents

Le module parents permet :

- Créer un parent
- Modifier un parent
- Rattacher des élèves
- Définir le lien parental
- Définir si le parent doit être informé

Espace parent :

- Le tableau de bord parent affiche les enfants rattachés au compte.
- Un bouton `Ouvrir` permet d'accéder au dossier de chaque enfant.
- Les cartes financières du tableau de bord sont dynamiques :
  - montant prévu ;
  - déjà payé ;
  - reste à payer ;
  - nombre de dossiers en retard.
- Les montants sont calculés depuis les plans de paiement modernes ou, si nécessaire, depuis les anciennes planifications.
- Les derniers paiements affichés correspondent uniquement aux enfants du parent connecté.

### Configuration

Le module configuration regroupe :

- Écoles
- Académies
- CAP
- Années scolaires
- Utilisateurs
- Permissions
- Classes officielles
- Types de notes
- Statuts de contrôle

Logo de l'école :

- Le logo peut être ajouté ou remplacé lors de la création ou de la modification d'une école.
- Les formats acceptés sont `jpg`, `jpeg`, `png` et `webp`.
- La taille maximale acceptée est de 2 Mo.
- Un aperçu du fichier sélectionné s'affiche avant l'enregistrement.
- Le logo enregistré peut être utilisé sur les cartes scolaires quand l'option d'affichage du logo est activée.

## Gestion des droits

L'application utilise des permissions pour afficher ou bloquer certaines actions selon le profil connecté.

Exemples :

- Aperçu
- Création
- Modification
- Suppression
- Export PDF
- Paiements

## Fichiers importants

Routes :

```text
routes/web.php
```

Contrôleurs principaux :

```text
app/Http/Controllers/DashboardController.php
app/Http/Controllers/EleveController.php
app/Http/Controllers/InscriptionController.php
app/Http/Controllers/FinanceController.php
app/Http/Controllers/BulletinController.php
app/Http/Controllers/TimetableController.php
```

Vues principales :

```text
resources/views/eleves/index.blade.php
resources/views/eleves/dossiers.blade.php
resources/views/eleves/show.blade.php
resources/views/dashboards/parent.blade.php
resources/views/pedagogie/inscriptions/index.blade.php
resources/views/finances/planifications/create.blade.php
resources/views/pdf/eleves_liste.blade.php
resources/views/pdf/bulletin.blade.php
resources/views/pdf/cartes_scolaires.blade.php
resources/views/pdf/finances/recu_paiement_thermique.blade.php
```

## Notes pour le futur manuel d'utilisation

À documenter avec captures d'écran :

- Connexion et sélection de l'école
- Navigation dans le menu
- Création d'une année scolaire
- Création des classes
- Création des planifications multi-classes
- Inscription individuelle
- Import d'inscriptions par groupe
- Réinscription intelligente par classe
- Gestion des décisions passant, redoublant, passage forcé, ajourné, abandon et exclusion
- Consultation de la liste des élèves
- Consultation des dossiers élèves
- Accès parent au dossier de l'enfant
- Modification et retrait d'un élève actif
- Génération des cartes scolaires
- Impression PDF de toute une liste
- Impression PDF d'une sélection d'élèves
- Export Excel d'une liste
- Gestion des parents
- Paiement et reçu
- Reçu thermique
- Bulletin et export PDF
- Emploi du temps
- Gestion des utilisateurs et permissions

## Dépannage courant

### Route introuvable après modification

Exécuter :

```bash
php artisan optimize:clear
```

### Une page Blade ne se met pas à jour

Exécuter :

```bash
php artisan view:clear
```

### Erreur sur les fichiers Excel

Vérifier que PhpSpreadsheet est installé :

```bash
composer show phpoffice/phpspreadsheet
```

### Erreur d'encodage sur les matricules

Les matricules générés automatiquement sont normalisés en ASCII. Si un matricule est saisi manuellement ou importé, éviter les caractères spéciaux.

## Technologies

- Laravel
- Blade
- MySQL/MariaDB
- Bootstrap
- DomPDF
- PhpSpreadsheet
