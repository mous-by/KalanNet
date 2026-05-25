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

### Enseignants

Chemin : `Enseignants`

Le module enseignants couvre tout le cycle de gestion du personnel enseignant : création, fiche individuelle, carte professionnelle, affectations pédagogiques, émargements, cahier de présence, salaires, bulletin de salaire et état de paiement.

#### Liste des enseignants

La liste permet de consulter les enseignants de l'école active selon les droits de l'utilisateur connecté.

Fonctionnement :

- La liste est filtrable par recherche.
- Les enseignants archivés ne sont plus traités comme actifs.
- Les actions visibles dépendent des permissions.
- Un enseignant connecté ne voit que son propre dossier.

Actions disponibles selon les droits :

- Créer un enseignant.
- Voir le profil.
- Modifier la fiche.
- Archiver ou réactiver un enseignant.
- Imprimer la liste des enseignants.

Permissions principales :

- `enseignants_creation`
- `enseignants_modification`
- `enseignants_archiver_ou_reactiver`
- `enseignants_apercu`

Routes utilisées :

```text
GET  /enseignants
POST /enseignants/search
GET  /enseignants/create
POST /enseignants
GET  /enseignants/{id}
GET  /enseignants/{id}/edit
PUT  /enseignants/{id}
PATCH /enseignants/{id}/archive
PATCH /enseignants/{id}/reactivate
```

#### Enregistrement d'un enseignant

Le formulaire enseignant centralise les informations administratives, professionnelles et salariales.

Champs principaux :

- Nom et prénom.
- Genre.
- Email.
- Téléphone.
- Date et lieu de naissance.
- Diplôme.
- Spécialité.
- Matricule.
- Photo.
- Type de contrat.

Types de contrat :

- `FONCTIONNAIRE` : enseignant public, salaire géré par l'État.
- `CDI` : contrat privé avec salaire mensuel.
- `CDD` : contrat privé avec salaire mensuel et durée de contrat.
- `VCT` : vacataire payé selon les heures validées.

Selon le type de contrat, le formulaire affiche les champs adaptés :

- CDI/CDD : salaire mensuel et mode de mois payés dans l'année scolaire.
- CDD : durée du contrat.
- VCT : nombre d'heures et prix par heure.
- Fonctionnaire : informations administratives publiques, service employeur, statut matrimonial, enfants et ancienneté.

Mode de mois payés :

- `12/12` : le salaire mensuel suit les mois de l'année scolaire, du début à la fin définis dans `Configuration > Années scolaires`.
- `9/12` : le salaire commence au premier mois où l'enseignant a une présence ou un émargement validé dans l'année scolaire, puis suit les mois suivants sans dépasser la fin de l'année scolaire.

Migration associée :

```text
database/migrations/2026_05_24_000001_add_salary_months_mode_to_enseignants_table.php
```

Cette migration ajoute `salaire_mois_mode` sur la table `enseignants`.

#### Profil enseignant

Le profil enseignant regroupe toutes les informations utiles sur une seule page.

Contenu du profil :

- Identité.
- Coordonnées.
- Informations personnelles et professionnelles.
- Classes et matières affectées.
- Bulletin du mois.
- Émargements récents.
- Progression du programme.
- Présences récentes.

Le bouton `Imprimer Fiche` génère une fiche A4 imprimable propre, sans menus ni boutons de l'application.

#### Carte professionnelle enseignant

Depuis le profil enseignant, le bouton `Carte professionnelle` ouvre une carte verticale inspirée du modèle vertical des cartes scolaires.

Contenu de la carte :

- Drapeau du Mali.
- République du Mali.
- Devise nationale.
- Ministère de l'Éducation Nationale.
- Académie de l'école.
- CAP de l'école.
- Nom de l'école.
- Titre `Carte professionnelle`.
- Photo de l'enseignant.
- Nom de l'enseignant.
- Matricule.
- Contrat.
- Spécialité.
- Téléphone.
- Statut.
- Zone de signature de l'administration.

La carte ne contient pas l'année scolaire. Le bouton `Imprimer` du modal imprime uniquement la carte professionnelle, pas toute la fiche enseignant.

#### Émargements

Chemin : `Enseignants > Émargements`

L'émargement concerne par défaut les écoles de Fondamentale II, secondaire général et secondaire technique. Il peut aussi fonctionner pour une autre école si les permissions correspondantes sont données.

Fonctionnement :

- L'utilisateur filtre par enseignant, classe, matière, date, année scolaire et statut.
- Un enseignant connecté ne peut émarger que sur ses propres affectations.
- Un émargement validé ne peut plus être modifié.
- Un émargement validé ne peut pas être supprimé.
- Les heures validées servent au calcul des salaires VCT et à l'éligibilité des salaires mensuels liés à l'activité.

Données d'un émargement :

- Enseignant.
- Classe.
- Matière.
- Leçon.
- Trimestre.
- Date d'émargement.
- Nombre d'heures.
- Statut validé ou en attente.

Routes utilisées :

```text
GET    /enseignants/emargements
POST   /enseignants/emargements/filter
POST   /enseignants/emargements
PUT    /enseignants/emargements/{id}
PATCH  /enseignants/emargements/{id}/validate
DELETE /enseignants/emargements/{id}
```

#### Cahier de présence

Chemin : `Enseignants > Présences`

Le cahier de présence concerne par défaut le Fondamental I. Il peut aussi fonctionner pour une autre école si les permissions correspondantes sont données.

Fonctionnement :

- L'utilisateur filtre par enseignant, classe, dates, année scolaire et statut.
- Une présence peut contenir une ou plusieurs leçons.
- Une présence validée ne peut plus être modifiée.
- Une présence validée ne peut pas être supprimée.
- Les heures validées servent au calcul des salaires VCT et à l'éligibilité des salaires mensuels liés à l'activité.

Données d'une présence :

- Enseignant.
- Classe.
- Trimestre.
- Date de présence.
- Leçons associées.
- Nombre d'heures.
- Statut validé ou en attente.

Routes utilisées :

```text
GET    /enseignants/presences
POST   /enseignants/presences/filter
POST   /enseignants/presences
PUT    /enseignants/presences/{id}
PATCH  /enseignants/presences/{id}/validate
DELETE /enseignants/presences/{id}
```

#### Règle de choix entre présence et émargement

Le module salaire utilise une source d'activité :

- `presence` : cahier de présence.
- `emargement` : émargements.

Source par défaut :

- Fondamental I : cahier de présence.
- Fondamental II, secondaire, technique : émargement.

Exception :

Si une école reçoit explicitement une permission sur l'autre source, l'application doit l'autoriser. Exemple : une école hors Fondamental I peut utiliser le cahier de présence si elle a les permissions de présence.

Permissions liées :

- `presence_paiement enseignant`
- `presence_paiement_enseignant`
- `emargement_paiement enseignant`
- `emargement_paiement_enseignant`
- `presence_etat de payement`
- `presence_etat_de_payement`
- `emargement_etat de payement`
- `emargement_etat_de_payement`
- `paiements_faire`

#### Salaires enseignants

Chemin : `Enseignants > Salaires`

Le module salaire gère les enseignants payables par l'école : CDI, CDD et VCT. Les fonctionnaires sont exclus du paiement école, car leur salaire est géré par l'État.

Modes de calcul :

- CDI/CDD : salaire mensuel défini dans la fiche enseignant.
- VCT : heures validées x prix de l'heure.

Règles d'année scolaire :

- Les mois proposés doivent appartenir à l'année scolaire sélectionnée.
- L'année scolaire est définie par `date_debut` et `date_fin`.
- Pour `12/12`, le calcul commence au début de l'année scolaire.
- Pour `9/12`, le calcul commence au premier mois où l'enseignant a commencé à faire une présence ou un émargement validé.
- Le système ne propose pas de salaire hors de la période de l'année scolaire.

Paiement individuel :

- L'utilisateur choisit un mois, une année, une source et éventuellement un enseignant.
- Pour chaque enseignant, il saisit le montant à verser et la date.
- Le montant saisi ne peut pas dépasser le reste à payer.
- Un versement partiel est autorisé.
- Le statut devient `Partiel` si une partie seulement est payée.
- Le statut devient `Payé` lorsque le reste à payer est nul.

Paiement groupé des arriérés :

- L'interface groupe les mois par enseignant afin d'éviter les répétitions difficiles à lire.
- L'utilisateur saisit uniquement les montants à payer maintenant.
- Les lignes à `0` sont ignorées.
- Plusieurs enseignants peuvent être payés dans une même opération.
- Plusieurs mois peuvent être régularisés dans une même opération.
- Il n'est pas obligatoire de payer tout le dû : les paiements partiels restent possibles.

Décaissement :

- Un paiement de salaire est un décaissement.
- À chaque versement validé, le système crée une ligne de décaissement.
- Le montant est retiré de la caisse active de l'école.
- Si aucune caisse active n'existe, le paiement est refusé.
- Si le solde de caisse est insuffisant, le paiement est refusé.

Référence de salaire :

```text
SAL-{TYPE}-{ANNEE}-{MOIS}-{ID_ENSEIGNANT}
```

Exemples :

- `SAL-MENSUEL-2026-04-6`
- `SAL-EMARGEMENT-2026-04-6`
- `SAL-PRESENCE-2026-04-6`

Routes utilisées :

```text
GET  /enseignants/salaires
POST /enseignants/salaires/paiement
```

#### État de paiement des enseignants

Chemin : `Enseignants > Salaires > État`

L'état de paiement n'est pas un bulletin individuel. C'est un document de décision, comparable à un mandat de paiement.

Objectif :

- Afficher tous les enseignants concernés par le mois sélectionné.
- Voir le salaire dû, déjà versé et le reste à payer.
- Permettre au décideur de savoir combien décaisser de la caisse.
- Générer un PDF de mandat de paiement.

Le PDF d'état de paiement est distinct du bulletin de salaire.

Routes utilisées :

```text
GET /enseignants/salaires/etat
GET /enseignants/salaires/etat/pdf
```

Vue PDF :

```text
resources/views/pdf/enseignants/etat_salaires.blade.php
```

#### Bulletin de salaire enseignant

Le bulletin de salaire est individuel. Il est disponible :

- Depuis l'état de paiement.
- Depuis le profil enseignant, dans l'onglet `Bulletin du mois`.

Format :

- PDF A5 portrait.
- Présentation de type vrai bulletin de salaire.
- En-tête école.
- Période.
- Identité de l'enseignant.
- Contrat.
- Base de calcul.
- Montant brut.
- Déjà versé.
- Net à payer.
- Signatures.

Accès enseignant :

- Un enseignant connecté peut accéder à son propre bulletin depuis son profil.
- Il ne peut pas accéder au bulletin d'un autre enseignant s'il n'a pas les permissions de gestion des salaires.

Route utilisée :

```text
GET /enseignants/salaires/bulletin
```

Vue PDF :

```text
resources/views/pdf/enseignants/bulletin_salaire.blade.php
```

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

Chemin : `Évaluations > Générer Bulletins`

Le module bulletins permet de générer les bulletins des élèves par classe, avec un fonctionnement inspiré du modèle Alliance.

Fonctionnement général :

- L'utilisateur ouvre `Générer Bulletins`.
- Le système affiche la liste des classes accessibles à l'utilisateur connecté.
- L'utilisateur choisit une classe avec le bouton `Générer`.
- Il choisit ensuite l'année scolaire.
- Il choisit le mode du bulletin : `Trimestriel` ou `Composition mensuelle`.
- Fondamentale I est positionnée en composition mensuelle par défaut.
- Les autres ordres d'enseignement peuvent aussi utiliser la composition mensuelle si l'école fonctionne ainsi.
- Le système charge les élèves ayant des notes pour l'année et la période choisies.
- Un spinner s'affiche pendant le chargement du filtre.

Modes de période :

- `Trimestriel` : le bulletin est calculé sur le trimestre sélectionné.
- `Composition mensuelle` : le bulletin est calculé sur le mois sélectionné.
- Si un mois est envoyé, le titre PDF devient `Composition du mois ...`, quel que soit l'ordre d'enseignement.
- Si un trimestre est envoyé, le titre PDF devient `Bulletin du ...`.

Calcul des notes :

- Le bulletin accepte les types de notes modernes de KalanNet : `devoir`, `composition`, `NT10`.
- Il reste compatible avec les anciens codes Alliance : `dv`, `cp`, `NT10`.
- Pour Fondamentale I et les compositions mensuelles, le calcul peut utiliser les notes mensuelles.
- Pour les bulletins trimestriels, le calcul utilise les devoirs et compositions du trimestre.
- La conduite est liée au trimestre et n'est pas forcée dans un bulletin mensuel.

Liste des bulletins :

- Le tableau affiche les élèves ayant une moyenne calculable pour la période.
- L'utilisateur peut cocher un ou plusieurs élèves.
- La case d'en-tête permet de tout sélectionner.
- Le bouton `Imprimer la sélection` génère uniquement les bulletins cochés.
- Le bouton `Toute la classe` génère tous les bulletins disponibles pour la période.
- Un compteur affiche le nombre de bulletins sélectionnés.
- Une flèche `Retour` couleur thème permet de revenir à la liste des classes.

Génération PDF :

- L'impression individuelle ouvre le PDF dans le navigateur.
- L'impression groupée ouvre un onglet d'attente avec spinner pendant la génération.
- Le PDF remplace automatiquement l'onglet d'attente quand il est prêt.
- En impression groupée, chaque élève occupe une page.
- Le format utilisé est A5 portrait.

Gabarit PDF :

Le bulletin reprend la disposition Alliance :

- Ministère de l'Éducation Nationale.
- Académie d'Enseignement.
- CAP.
- République du Mali et devise nationale.
- Logo de l'école.
- Nom de l'école ou nom du complexe selon la configuration.
- Téléphone de l'école.
- Titre de période.
- Bloc identité élève avec double bordure.
- Tableau des matières, notes, coefficients, moyennes et appréciations.
- Moyenne de l'élève.
- Rang.
- Moyenne du premier.
- Zone `Avis du Directeur Général`.
- Zone `Le Tuteur`.

Les libellés Académie et CAP sont nettoyés à l'affichage pour éviter les répétitions du type :

- `Académie d'Enseignement de ACADEMIE DE KAYES`
- `CAP de CAP KAYES RIVE GAUCHE`

Routes utilisées :

```text
GET  /pedagogie/bulletins
GET  /pedagogie/classes/{idClasse}/bulletins
GET  /pedagogie/classes/{idClasse}/bulletins/data
POST /pedagogie/classes/{idClasse}/bulletins/pdf
GET  /pedagogie/bulletins/{id}/download
```

Fichiers principaux :

```text
app/Http/Controllers/BulletinController.php
resources/views/bulletins/classes.blade.php
resources/views/bulletins/index.blade.php
resources/views/pdf/bulletin.blade.php
resources/views/pdf/bulletins_classe.blade.php
resources/views/pdf/partials/bulletin_alliance.blade.php
```

Ce gabarit sert aussi de référence visuelle pour les autres PDF scolaires.

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
app/Http/Controllers/EnseignantController.php
app/Http/Controllers/EmargementController.php
app/Http/Controllers/PresenceController.php
app/Http/Controllers/TeacherSalaryController.php
```

Vues principales :

```text
resources/views/eleves/index.blade.php
resources/views/eleves/dossiers.blade.php
resources/views/eleves/show.blade.php
resources/views/enseignants/index.blade.php
resources/views/enseignants/form.blade.php
resources/views/enseignants/show.blade.php
resources/views/enseignants/emargements.blade.php
resources/views/enseignants/presences.blade.php
resources/views/enseignants/salaires.blade.php
resources/views/dashboards/parent.blade.php
resources/views/pedagogie/inscriptions/index.blade.php
resources/views/finances/planifications/create.blade.php
resources/views/pdf/eleves_liste.blade.php
resources/views/pdf/bulletin.blade.php
resources/views/pdf/cartes_scolaires.blade.php
resources/views/pdf/enseignants/bulletin_salaire.blade.php
resources/views/pdf/enseignants/etat_salaires.blade.php
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
- Création, modification, archivage et réactivation d'un enseignant
- Profil enseignant, fiche imprimable et carte professionnelle
- Émargements enseignants
- Cahier de présence enseignants
- Paiement individuel d'un salaire enseignant
- Paiement groupé des arriérés enseignants
- État de paiement des enseignants et PDF de mandat
- Bulletin de salaire enseignant au format A5
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
