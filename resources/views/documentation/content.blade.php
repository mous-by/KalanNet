<div class="documentation-content" style="max-width: 900px; margin: 0 auto; line-height: 1.85;">

    @php
        $docImg = fn (string $file) => !empty($isPdfExport)
            ? public_path('assets/images/documentation/' . $file)
            : asset('assets/images/documentation/' . $file);
    @endphp

    <div class="doc-tip">
        <strong>Vous n'aimez pas naviguer dans les menus ?</strong> Cliquez sur la bulle de discussion
        <strong>KalanBot</strong> en bas à droite de votre écran, appuyez sur le micro et dites simplement ce que
        vous voulez faire, par exemple <em>« inscris Sékou Traoré en 7ème année »</em> ou <em>« mets 15 sur 20 à
        Aïcha en mathématiques »</em>. KalanBot comprend le français parlé, reformule ce qu'il va faire et vous
        demande de confirmer avant toute action. Vous pouvez aussi lui poser des questions, comme
        <em>« comment j'inscris un élève ? »</em>, et il vous répondra directement. C'est souvent le moyen le
        plus rapide de se servir de KalanNet sans avoir à chercher dans les menus.
    </div>

    <p class="doc-intro">
        Ce guide vous accompagne pas à pas dans l'utilisation de KalanNet, même si vous n'avez pas l'habitude de
        travailler sur ordinateur. Vous n'avez pas besoin de tout lire d'un coup : allez directement à la partie
        qui vous intéresse grâce au sommaire ci-dessous, et revenez ici chaque fois que vous avez un doute. Les
        boutons et menus mentionnés portent exactement les mêmes noms que ceux affichés à l'écran.
    </p>

    <div class="doc-toc">
        <p class="doc-toc-title">Sommaire</p>
        <ol>
            <li><a href="#doc-1">Se connecter et se repérer</a></li>
            <li><a href="#doc-2">Avant de commencer : la Configuration</a></li>
            <li><a href="#doc-3">Élèves &amp; Parents</a></li>
            <li><a href="#doc-4">Enseignants</a></li>
            <li><a href="#doc-5">Classes &amp; Cours</a></li>
            <li><a href="#doc-6">Contrôles &amp; Évaluations</a></li>
            <li><a href="#doc-7">Annonces</a></li>
            <li><a href="#doc-8">Finances</a></li>
            <li><a href="#doc-9">Espace Parents</a></li>
            <li><a href="#doc-10">Abonnement de l'école</a></li>
            <li><a href="#doc-12">Espaces DAE et DCAP</a></li>
            <li><a href="#doc-13">Évaluations communes</a></li>
            <li><a href="#doc-11">Catalogue rapide : « où trouver quoi »</a></li>
        </ol>
    </div>

    <h4 id="doc-1" class="fw-bold text-uppercase mt-5 mb-3 border-bottom pb-2">1. Se connecter et se repérer</h4>

    <h5 class="fw-bold mt-4">Se connecter</h5>
    <p>Sur la page d'accueil, saisissez votre <strong>adresse email ou votre numéro de téléphone</strong> ainsi que
    votre <strong>mot de passe</strong>, puis cliquez sur le bouton de connexion. Si vous travaillez pour
    plusieurs écoles avec le même compte, KalanNet vous demandera de <strong>choisir l'école</strong> sur laquelle
    vous voulez travailler : ce choix détermine ce que vous verrez ensuite (les élèves, enseignants et paiements
    affichés seront toujours ceux de l'école sélectionnée).</p>
    <div class="doc-note">Si un message indique que l'abonnement de votre école a expiré, contactez la personne
    responsable de l'abonnement (voir la partie « Abonnement de l'école ») : l'accès sera limité tant que
    l'abonnement n'est pas renouvelé.</div>

    <h5 class="fw-bold mt-4">Premier accès : choisir son mot de passe</h5>
    <p>Quand un compte est créé (ou son mot de passe réinitialisé) par un administrateur, il reçoit un
    <strong>mot de passe temporaire</strong>. À la première connexion, KalanNet affiche la page
    <strong>« Choisissez votre mot de passe »</strong> : saisissez le mot de passe temporaire, puis votre nouveau mot
    de passe deux fois. Rien d'autre n'est accessible tant que ce n'est pas fait.</p>
    <ul>
        <li>Le mot de passe doit contenir <strong>au moins 8 caractères, avec des lettres et des chiffres</strong>,
        et être différent du mot de passe temporaire.</li>
        <li>Une <strong>barre colorée</strong> indique sa force pendant la saisie (rouge « Très faible » à vert
        « Fort ») : ajoutez des majuscules, des symboles ou plus de caractères pour la faire monter.</li>
        <li>L'icône en forme d'<strong>œil</strong> à droite du champ affiche ou masque ce que vous tapez.</li>
        <li>Après 5 mots de passe erronés, la connexion est bloquée 3 minutes : c'est une protection contre les
        tentatives d'intrusion.</li>
    </ul>

    <figure class="doc-shot">
        <img src="{{ $docImg('doc-connexion.jpg') }}" alt="Capture d'écran : page de connexion KalanNet, avec les champs identifiant et mot de passe">
        <figcaption>La page de connexion : identifiant, mot de passe, puis Connexion.</figcaption>
    </figure>

    <h5 class="fw-bold mt-4">Le menu de gauche</h5>
    <p>Sur le côté gauche de l'écran se trouve une colonne de menu (sur téléphone, elle apparaît en appuyant sur
    l'icône de menu en haut). Chaque ligne représente un module. Certaines lignes ont une petite flèche : cliquez
    dessus pour dérouler les sous-menus. Vous ne verrez que les modules pour lesquels vous avez une autorisation
    — c'est normal si votre menu est plus court que celui d'un(e) collègue, cela dépend de votre rôle.</p>

    <h5 class="fw-bold mt-4">Le tableau de bord</h5>
    <p>C'est la première page après connexion. Elle donne une vue d'ensemble : nombre d'élèves, indicateurs
    financiers, activité récente. C'est un bon point de départ pour vérifier que tout va bien, mais vous n'avez
    pas besoin de tout comprendre ici pour commencer à travailler — les autres parties de ce guide vous
    expliquent chaque action précisément.</p>

    <figure class="doc-shot">
        <img src="{{ $docImg('doc-dashboard.jpg') }}" alt="Capture d'écran : tableau de bord avec le menu à gauche et la bulle KalanBot en bas à droite">
        <figcaption>Le tableau de bord : le menu à gauche donne accès à tous vos modules, et la bulle KalanBot
        (en bas à droite) répond à vos questions à voix ou à l'écrit.</figcaption>
    </figure>

    <h4 id="doc-2" class="fw-bold text-uppercase mt-5 mb-3 border-bottom pb-2">2. Avant de commencer : la Configuration</h4>
    <p>Cette étape concerne surtout les administrateurs de l'école, en général une seule fois par année scolaire.
    Elle se trouve dans le menu <strong>Configuration</strong> (icône en forme d'engrenage).</p>
    <ul>
        <li><strong>Années scolaires</strong> : indiquez la date de début et de fin de l'année en cours. C'est
        indispensable : sans cette étape, plusieurs autres écrans de l'application resteront vides.</li>
        <li><strong>Écoles</strong> : vérifiez le nom, le logo et les coordonnées de votre établissement.</li>
        <li><strong>Académies &amp; CAP</strong> : utilisées pour les documents officiels (bulletins, rapports).
        En général déjà pré-remplies, à vérifier seulement.</li>
        <li><strong>Utilisateurs</strong> : c'est ici que l'on crée un compte pour chaque membre du personnel qui
        doit utiliser KalanNet, et qu'on choisit ce qu'il a le droit de voir ou de modifier (onglet « Assigner
        permissions »). À l'enregistrement, une fenêtre <strong>« Mot de passe temporaire »</strong> l'affiche
        <strong>une seule fois</strong> : copiez-le ou notez-le, puis transmettez-le en main propre. Si quelqu'un
        oublie son mot de passe, le bouton en forme de <strong>clé</strong> de la liste le réinitialise et affiche
        un nouveau mot de passe temporaire.</li>
        <li><strong>Classes officielles &amp; Types de notes</strong> : la structure pédagogique de base (les
        niveaux comme « 7ème année », les barèmes de notation).</li>
        <li><strong>Statuts de contrôle</strong> : les motifs utilisés lors de l'appel de présence (Présent,
        Absent, Retard...) et la pénalité que chacun entraîne sur la note de conduite.</li>
    </ul>
    <div class="doc-note">Vous n'avez pas besoin de tout configurer avant de commencer : si un champ obligatoire
    manque quelque part, KalanNet vous l'indiquera clairement au bon moment.</div>

    <figure class="doc-shot">
        <img src="{{ $docImg('doc-configuration.jpg') }}" alt="Capture d'écran : page Configuration, onglet Utilisateurs">
        <figcaption>Le module Configuration, ici sur l'onglet Utilisateurs.</figcaption>
    </figure>

    <h4 id="doc-3" class="fw-bold text-uppercase mt-5 mb-3 border-bottom pb-2">3. Élèves &amp; Parents</h4>
    <p>Menu : <strong>Élèves &amp; Parents</strong>.</p>

    <h5 class="fw-bold mt-4">Inscrire un nouvel élève</h5>
    <ol>
        <li>Cliquez sur <strong>Inscriptions</strong> dans le sous-menu.</li>
        <li>Remplissez le prénom, le nom, le genre, la classe et l'année scolaire de l'élève (les champs marqués
        d'une étoile sont obligatoires, le reste peut être complété plus tard).</li>
        <li>Le <strong>matricule</strong> (numéro unique de l'élève) peut être laissé vide : KalanNet le génère
        automatiquement.</li>
        <li><strong>Cas social</strong> : choisissez « Autre (frais particulier) » pour un élève qui bénéficie d'un
        tarif réduit, indiquez la nature du cas (orphelin, enfant du personnel…) et le frais qu'il paie pour
        l'année. Ce frais remplace le montant de la formule dans les paiements.</li>
        <li>Cliquez sur <strong>Valider l'inscription</strong>.</li>
    </ol>

    <figure class="doc-shot">
        <img src="{{ $docImg('doc-inscription.jpg') }}" alt="Capture d'écran : formulaire d'inscription individuelle avec le bouton Valider l'inscription en surbrillance">
        <figcaption>Le formulaire d'inscription : une fois les champs remplis, cliquez sur « Valider l'inscription ».</figcaption>
    </figure>

    <p>Pour inscrire plusieurs élèves d'un coup, utilisez <strong>Inscription par groupe</strong> : téléchargez le
    modèle Excel proposé, remplissez une ligne par élève, puis renvoyez le fichier. Pour faire passer une classe
    entière à l'année suivante en une seule fois (élèves admis, redoublants...), utilisez
    <strong>Réinscription</strong> : KalanNet propose automatiquement une décision par élève selon sa moyenne, que
    vous pouvez ajuster avant de valider.</p>

    <h5 class="fw-bold mt-4">Retrouver et suivre un élève</h5>
    <ul>
        <li><strong>Liste des Élèves</strong> : recherche par classe, impression de la liste, export Excel.</li>
        <li><strong>Dossiers élèves</strong> : la fiche complète d'un élève — parcours, paiements, reçus — en un
        seul endroit. Utile pour répondre rapidement à une question d'un parent.</li>
        <li><strong>Cartes scolaires</strong> : génère et imprime les cartes d'identité des élèves, avec plusieurs
        modèles au choix et un QR code contenant les informations de l'élève.</li>
    </ul>

    <div class="doc-shot-row">
        <figure class="doc-shot">
            <img src="{{ $docImg('doc-eleves-liste.jpg') }}" alt="Capture d'écran : liste des élèves">
            <figcaption>Liste des Élèves.</figcaption>
        </figure>
        <figure class="doc-shot">
            <img src="{{ $docImg('doc-cartes-scolaires.jpg') }}" alt="Capture d'écran : génération des cartes scolaires">
            <figcaption>Cartes scolaires.</figcaption>
        </figure>
    </div>

    <h5 class="fw-bold mt-4">Les parents</h5>
    <p>Dans <strong>Parents d'élèves</strong>, créez un compte parent et rattachez-le à un ou plusieurs élèves.
    Une fois connecté, ce parent ne voit que les informations concernant ses propres enfants (montant à payer,
    retards, reçus de paiement) — jamais celles des autres familles.</p>

    <figure class="doc-shot">
        <img src="{{ $docImg('doc-parents.jpg') }}" alt="Capture d'écran : page Parents d'élèves">
        <figcaption>Parents d'élèves : créer et rattacher un compte parent.</figcaption>
    </figure>

    <h4 id="doc-4" class="fw-bold text-uppercase mt-5 mb-3 border-bottom pb-2">4. Enseignants</h4>
    <p>Menu : <strong>Enseignants</strong>.</p>
    <ul>
        <li><strong>Liste des Enseignants / Ajouter Enseignant</strong> : gérez les fiches du personnel enseignant
        (type de contrat, spécialité...). Seuls le <strong>nom et le téléphone</strong> sont obligatoires, le reste
        peut être complété plus tard. Pour qu'un enseignant se connecte, créez-lui un compte dans Configuration ->
        Utilisateurs : il recevra un mot de passe temporaire.</li>
    </ul>

    <figure class="doc-shot">
        <img src="{{ $docImg('doc-enseignants.jpg') }}" alt="Capture d'écran : liste des enseignants avec le lien Ajouter Enseignant en surbrillance">
        <figcaption>Liste des Enseignants : le lien « Ajouter Enseignant » se trouve dans le sous-menu.</figcaption>
    </figure>

    <div class="doc-tip">
        <strong>Émargements</strong> et <strong>Cahier de présence</strong> servent au même objectif — déclarer
        les heures de cours faites par un enseignant — mais l'un des deux est utilisé selon le niveau de la
        classe : le <strong>Cahier de présence</strong> pour le Fondamental I (les plus jeunes classes), les
        <strong>Émargements</strong> pour le Fondamental II et le Secondaire. Ne vous inquiétez pas si vous ne
        voyez que l'un des deux dans votre menu : c'est normal, cela dépend de votre école.
    </div>
    <p>Dans les deux cas, la déclaration doit d'abord être <strong>validée</strong> par un responsable avant
    d'être prise en compte dans le calcul des salaires — c'est volontaire, pour éviter les erreurs.</p>
    <p><strong>Salaires</strong> : une fois les heures validées, cet écran calcule automatiquement ce qui est dû à
    chaque enseignant (selon son contrat et le mode de paiement 9 ou 12 mois), et permet d'enregistrer un
    versement. <strong>État de paiement</strong> donne une vue d'ensemble de qui a été payé ou non ce mois-ci.</p>

    <h4 id="doc-5" class="fw-bold text-uppercase mt-5 mb-3 border-bottom pb-2">5. Classes &amp; Cours</h4>
    <p>Menu : <strong>Classes &amp; Cours</strong>.</p>
    <ul>
        <li><strong>Classes</strong> : création des classes de votre école, avec les matières enseignées et
        l'enseignant responsable de chacune.</li>
        <li><strong>Matières</strong> : la liste des matières disponibles dans votre établissement.</li>
        <li><strong>Programmes officiels</strong> : le programme pédagogique (les leçons à couvrir) associé à
        chaque classe officielle.</li>
        <li><strong>Emploi du temps</strong> : la grille horaire hebdomadaire d'une classe, modifiable en cliquant
        directement sur les créneaux.</li>
    </ul>

    <div class="doc-shot-row">
        <figure class="doc-shot">
            <img src="{{ $docImg('doc-classes.jpg') }}" alt="Capture d'écran : liste des classes">
            <figcaption>Classes.</figcaption>
        </figure>
        <figure class="doc-shot">
            <img src="{{ $docImg('doc-emploi-du-temps.jpg') }}" alt="Capture d'écran : emploi du temps d'une classe">
            <figcaption>Emploi du temps.</figcaption>
        </figure>
    </div>

    <h4 id="doc-6" class="fw-bold text-uppercase mt-5 mb-3 border-bottom pb-2">6. Contrôles &amp; Évaluations</h4>
    <p>Menu : <strong>Contrôles &amp; Évaluations</strong>.</p>
    <div class="doc-tip">
        <strong>Appel de Présence</strong> ne sert pas seulement aux devoirs et compositions : c'est aussi l'écran
        utilisé pour l'<strong>appel quotidien des élèves</strong> (présent, absent, en retard...). Chaque statut
        déduit automatiquement des points sur la <strong>note de conduite</strong> de l'élève (qui commence à
        18/20), et peut envoyer une notification aux parents.
    </div>

    <figure class="doc-shot">
        <img src="{{ $docImg('doc-appel-presence.jpg') }}" alt="Capture d'écran : historique des appels avec le bouton Nouvel appel en surbrillance">
        <figcaption>Appel de Présence : cliquez sur « Nouvel appel » pour commencer.</figcaption>
    </figure>

    <p><strong>Notes &amp; Évaluations</strong> : programmez une évaluation (composition, devoir...) pour une
    classe et une matière, puis revenez sur cette même évaluation pour saisir la note de chaque élève. Selon le
    type d'école, les notes saisies peuvent nécessiter une <strong>validation</strong> par un responsable
    pédagogique avant d'apparaître sur les bulletins.</p>

    <figure class="doc-shot">
        <img src="{{ $docImg('doc-evaluations.jpg') }}" alt="Capture d'écran : liste des évaluations avec le bouton Préparer une évaluation en surbrillance">
        <figcaption>Notes &amp; Évaluations : « Préparer une évaluation » pour en programmer une nouvelle.</figcaption>
    </figure>

    @php
        $examensDocConfigures = \App\Support\ExamenNational::niveauxConfigures(session('idEcole'));
        $examensDocListe = $examensDocConfigures ? implode(', ', $examensDocConfigures) : 'DEF, BAC...';
    @endphp
    <p><strong>Résultats nationaux</strong> : enregistrez les résultats des examens nationaux ({{ $examensDocListe }})
    pour les classes concernées. <strong>Générer Bulletins</strong> : choisissez une classe puis une période (mois ou trimestre) ;
    KalanNet calcule automatiquement les moyennes et le classement, et vous pouvez imprimer un bulletin individuel
    ou toute une classe d'un coup. Un bouton <strong>Publier</strong> rend les bulletins visibles pour les parents
    dans leur propre espace.</p>

    <figure class="doc-shot">
        <img src="{{ $docImg('doc-bulletins.jpg') }}" alt="Capture d'écran : page Générer les bulletins par classe">
        <figcaption>Générer Bulletins : un bouton « Générer » par classe.</figcaption>
    </figure>

    <p><strong>Modèles de bulletin</strong> : sur la page des bulletins d'une classe, choisissez parmi six modèles.
    Le bouton <strong>Aperçu</strong> montre le bulletin d'un élève de la classe avant de choisir, et le dernier
    modèle choisi est retenu pour la classe. Tous les modèles s'impriment en noir et blanc et portent l'en-tête
    officiel (ministère, pays, académie, CAP).</p>
    <ul>
        <li><strong>Standard</strong> (A4 paysage) : le bulletin habituel de KalanNet, en deux exemplaires A5 côte à
            côte (un pour les parents, un pour l'école), utilisé tant qu'aucun autre modèle n'est choisi.</li>
        <li><strong>Classique</strong> (A4) : notes de classe et de composition, coefficients, moyenne, rang,
            statistiques de la classe et décision du conseil.</li>
        <li><strong>Par domaines bilingue</strong> (A4) : modèle médersa, avec un en-tête en français et en arabe
            et les matières groupées par domaine. La moyenne générale est la moyenne des domaines, et il y a une
            appréciation par maître (arabe et français).</li>
        <li><strong>Essentiel</strong> et <strong>Par domaines compact</strong> (A5) : des bulletins d'une demi-page.
            Pour les modèles A5, le bouton <strong>2 bulletins par feuille A4</strong> de la fenêtre de génération
            les imprime deux par feuille, à couper en deux.</li>
        <li><strong>Moderne</strong> (A4 paysage) : deux exemplaires A5 du même bulletin côte à côte, séparés par un
            trait de coupe (un pour les parents, un pour l'école), avec la photo de l'élève, une barre de niveau par
            matière et l'évolution depuis la période précédente.</li>
    </ul>
    <p><strong>Note de classe</strong> : au même endroit, choisissez si la note de classe est la
    <strong>moyenne de tous les devoirs</strong> de la période ou <strong>une seule note</strong> (la dernière saisie).</p>
    <p><strong>Classes à deux maîtres (écoles franco-arabes)</strong> : dans la fiche de la classe, cochez
    <strong>Classe à deux maîtres</strong>, puis choisissez le maître de la partie arabe et celui de la partie
    française. Chaque matière est rangée dans un <strong>domaine</strong>, et chaque domaine appartient à une partie :
    KalanNet donne automatiquement les matières de la partie arabe au maître arabe et celles de la partie française
    au maître de français. Vous pouvez ensuite confier une matière à un autre enseignant. La liste des domaines se
    règle dans <strong>Matières -> Domaines (UE)</strong>.</p>

    <h4 id="doc-7" class="fw-bold text-uppercase mt-5 mb-3 border-bottom pb-2">7. Annonces</h4>
    <p>Menu : <strong>Annonces</strong>. Rédigez un message à destination de tous, des parents, des enseignants ou
    des gestionnaires, puis choisissez de l'enregistrer en <strong>brouillon</strong> (personne ne le voit
    encore) ou de le <strong>publier</strong> (visible immédiatement par le public choisi). Une annonce publiée
    peut ensuite être archivée ou supprimée à tout moment.</p>
    <p>Les annonces de votre <strong>académie</strong> et de votre <strong>CAP</strong> arrivent aussi chez vous :
    dans la fenêtre « Nouvelles annonces » à la connexion, et en haut de la page Annonces, avec un badge qui
    indique leur origine (« Académie : … » ou « CAP : … »).</p>

    <figure class="doc-shot">
        <img src="{{ $docImg('doc-annonces.jpg') }}" alt="Capture d'écran : formulaire Nouvelle publication avec le bouton Enregistrer en surbrillance">
        <figcaption>Annonces : rédigez, choisissez le public, puis Enregistrer.</figcaption>
    </figure>

    <h4 id="doc-8" class="fw-bold text-uppercase mt-5 mb-3 border-bottom pb-2">8. Finances</h4>
    <p>Menu : <strong>Finances</strong>. C'est le module qui suit l'argent de l'école — prenez votre temps ici, et
    n'hésitez pas à demander de l'aide la première fois.</p>
    <h5 class="fw-bold mt-4">Enregistrer un paiement de scolarité</h5>
    <ol>
        <li>Allez dans <strong>Paiements Élèves</strong>, puis choisissez la <strong>classe</strong> et
        l'<strong>année scolaire</strong> concernées.</li>
        <li>KalanNet affiche automatiquement la liste des élèves de la classe avec ce qui est dû et ce qui reste
        à payer.</li>
        <li>Indiquez le montant reçu pour chaque élève (il ne peut pas dépasser le reste à payer).</li>
        <li>Cliquez sur <strong>valide les paiements</strong> : un reçu est généré automatiquement, imprimable en
        format normal ou en format ticket de caisse (imprimante thermique).</li>
    </ol>
    <div class="doc-note">Chaque paiement doit être associé à une <strong>caisse active</strong>. Si aucune caisse
    n'existe encore pour votre école, un administrateur doit d'abord en créer une dans <strong>Journal de
    Caisse</strong>.</div>

    <figure class="doc-shot">
        <img src="{{ $docImg('doc-paiements.jpg') }}" alt="Capture d'écran : page Paiements Élèves avec la classe et le bouton valide les paiements en surbrillance">
        <figcaption>Paiements Élèves : choisissez la classe (1), puis validez les paiements (2).</figcaption>
    </figure>
    <ul>
        <li><strong>Formule de paiement</strong> : fixez les frais scolaires de chaque classe pour l'année
        (mensuelle, trimestrielle, annuelle, ou <strong>par tranches</strong> avec une date limite par tranche),
        pour une ou plusieurs classes à la fois. Disponible aussi dans l'application mobile (Paiements ->
        Formules de paiement).</li>
        <li><strong>Subventions État</strong> : enregistrez un versement global reçu de l'État ; KalanNet le
        répartit automatiquement sur les élèves concernés.</li>
        <li><strong>Historique paiements</strong> : consultez et exportez tous les paiements passés.</li>
        <li><strong>Journal de Caisse</strong> : le solde actuel et tous les mouvements (entrées et sorties)
        de la caisse de l'école.</li>
        <li><strong>Dépenses</strong> : enregistrez une sortie d'argent (achat, frais...) ; selon votre rôle,
        elle est déduite immédiatement ou doit d'abord être validée par un responsable.</li>
        <li><strong>Banques, Versements, Retraits</strong> : suivi des comptes bancaires de l'école et des
        transferts entre la caisse et la banque.</li>
    </ul>

    <figure class="doc-shot">
        <img src="{{ $docImg('doc-caisse.jpg') }}" alt="Capture d'écran : Journal de Caisse">
        <figcaption>Journal de Caisse : solde et mouvements de la caisse.</figcaption>
    </figure>

    <h4 id="doc-9" class="fw-bold text-uppercase mt-5 mb-3 border-bottom pb-2">9. Espace Parents</h4>
    <p>Un parent qui se connecte à KalanNet voit une version simplifiée de l'application, limitée à ses propres
    enfants : dossier scolaire, montant à payer et échéances, bulletins publiés, reçus de paiement à télécharger,
    et les annonces qui le concernent. Si un parent rencontre une difficulté, vous pouvez vérifier sa situation
    directement depuis <strong>Dossiers élèves</strong> ou <strong>Parents d'élèves</strong>.</p>

    <h4 id="doc-10" class="fw-bold text-uppercase mt-5 mb-3 border-bottom pb-2">10. Abonnement de l'école</h4>
    <p>Menu : <strong>Abonnements</strong>. Cet écran affiche la formule d'abonnement en cours et permet de
    soumettre la preuve d'un paiement manuel (photo du reçu Orange Money, Wave...), qui sera ensuite validée par
    l'équipe KalanNet. Une alerte apparaît automatiquement une semaine avant l'expiration pour vous laisser le
    temps de renouveler.</p>

    <figure class="doc-shot">
        <img src="{{ $docImg('doc-abonnements.jpg') }}" alt="Capture d'écran : page Abonnements avec le bouton Envoyer la demande en surbrillance">
        <figcaption>Abonnements : joignez la preuve de paiement puis « Envoyer la demande ».</figcaption>
    </figure>

    <h4 id="doc-12" class="fw-bold text-uppercase mt-5 mb-3 border-bottom pb-2">11. Espaces DAE et DCAP</h4>
    <p>Les directeurs d'académie (<strong>DAE</strong>) et de CAP (<strong>DCAP</strong>) ont leur propre espace,
    qui s'ouvre dès la connexion. Leur menu se limite à : Tableau de bord, <strong>Effectifs</strong>,
    <strong>Évaluations communes</strong>, <strong>Annonces</strong> et Documentation. Ils consultent leur territoire mais n'interviennent
    jamais dans la gestion d'une école : ils ne peuvent ni inscrire, ni modifier, ni supprimer d'élève,
    d'enseignant ou de paiement.</p>
    <ul>
        <li><strong>Espace DAE</strong> : nombre de CAP, d'écoles (publiques et privées), d'enseignants, d'élèves
        et de classes de l'académie, et un tableau <strong>« Répartition par CAP »</strong> (directeur, écoles,
        enseignants, élèves de chaque CAP).</li>
        <li><strong>Espace DCAP</strong> : les mêmes chiffres pour le CAP, et la liste de ses écoles avec leur
        type, leur statut, leurs contacts et leurs effectifs.</li>
        <li><strong>Effectifs</strong> : pour chaque école, le nombre d'élèves par classe (filles, garçons, total) et
        le nombre d'enseignants (femmes, hommes). Le DAE peut filtrer par CAP. Ces chiffres sont en lecture seule.</li>
        <li><strong>Annonces</strong> (menu Annonces) : le DAE et le DCAP publient des annonces à leur territoire
        (bouton « Publier une annonce »), avec une pièce jointe si besoin, en choisissant les destinataires :
        administrateurs, gestionnaires, enseignants, parents, ou tout le monde. Le DAE peut aussi écrire aux seuls
        directeurs de CAP. Chacun ne reçoit que les annonces qui le concernent.</li>
    </ul>

    <h4 id="doc-13" class="fw-bold text-uppercase mt-5 mb-3 border-bottom pb-2">12. Évaluations communes</h4>
    <p>Une évaluation commune (composition harmonisée, devoir commun…) est préparée par l'académie ou le CAP et
    passée le même jour dans toutes les écoles concernées. Menu : <strong>Évaluations communes</strong>.</p>

    <h5 class="fw-bold mt-4">Le circuit des sujets</h5>
    <table class="doc-catalogue">
        <thead>
            <tr><th>Qui crée</th><th>Niveau</th><th>Circuit</th></tr>
        </thead>
        <tbody>
            <tr><td>Académie (DAE)</td><td>Fondamental I et II</td><td>DAE → DCAP de ses CAP → leurs écoles</td></tr>
            <tr><td>Académie (DAE)</td><td>Secondaire général ou technique</td><td>DAE → directement aux lycées et établissements techniques concernés</td></tr>
            <tr><td>CAP (DCAP)</td><td>Fondamental I et II</td><td>DCAP → ses écoles</td></tr>
        </tbody>
    </table>

    <h5 class="fw-bold mt-4">Pour le DAE ou le DCAP : créer une évaluation</h5>
    <ol>
        <li>Cliquez sur <strong>Nouvelle évaluation</strong>.</li>
        <li>Indiquez le titre, le <strong>niveau</strong> (obligatoire : il détermine le circuit), la matière, la
        date et l'heure de début, la durée et les consignes.</li>
        <li>Choisissez quand le sujet devient accessible aux écoles (par exemple <strong>1 h avant</strong> le
        début). Le DAE peut aussi limiter une évaluation du fondamental à certains CAP.</li>
        <li>Joignez le <strong>sujet</strong> (PDF, Word ou image) et, si vous l'avez, le corrigé, puis cliquez sur
        <strong>Publier</strong>. Les personnes concernées sont prévenues par une annonce.</li>
    </ol>
    <p>La page <strong>Suivi</strong> d'une évaluation montre, école par école, la date de téléchargement du
    sujet (et, pour le DAE, quels CAP ont publié l'évaluation). On peut y déposer le corrigé à tout moment ou
    annuler l'évaluation.</p>

    <h5 class="fw-bold mt-4">Pour le DCAP : publier une évaluation de l'académie</h5>
    <p>Une évaluation du fondamental envoyée par l'académie apparaît avec le badge <strong>« Reçue de
    l'académie · à publier »</strong>. Ouvrez-la, ajoutez si besoin vos propres consignes (centres de
    composition, remise des copies…) et cliquez sur <strong>Publier à mes écoles</strong>. Vos écoles ne la
    voient qu'après cette publication.</p>

    <h5 class="fw-bold mt-4">Pour l'école : récupérer le sujet</h5>
    <ul>
        <li>Seuls l'<strong>Admin</strong> et le <strong>Gestionnaire</strong> de l'école ont accès aux évaluations
        communes : le sujet est confidentiel.</li>
        <li>Avant l'heure fixée, le sujet est verrouillé (« Disponible le … à … »). Ensuite, le bouton
        <strong>Sujet</strong> le télécharge : imprimez-le pour l'épreuve et ne le diffusez pas avant.</li>
        <li>Le <strong>corrigé</strong> devient téléchargeable après la fin de l'épreuve.</li>
        <li>Chaque téléchargement est enregistré : l'académie ou le CAP voit que votre école a bien reçu le sujet.</li>
    </ul>
    <div class="doc-note">Les sujets ne sont jamais accessibles par un simple lien : KalanNet vérifie à chaque fois
    l'école, le rôle de la personne et l'heure avant de les donner.</div>

    <h4 id="doc-11" class="fw-bold text-uppercase mt-5 mb-3 border-bottom pb-2">13. Catalogue rapide : « où trouver quoi »</h4>
    <p>Un tableau pour retrouver rapidement où se trouve une action, sans avoir à relire tout le guide.</p>

    <table class="doc-catalogue">
        <thead>
            <tr><th>Je veux...</th><th>Menu à ouvrir</th></tr>
        </thead>
        <tbody>
            <tr><td>Inscrire un nouvel élève</td><td>Élèves &amp; Parents -> Inscriptions</td></tr>
            <tr><td>Inscrire plusieurs élèves d'un coup</td><td>Élèves &amp; Parents -> Inscription par groupe</td></tr>
            <tr><td>Faire passer une classe à l'année suivante</td><td>Élèves &amp; Parents -> Réinscription</td></tr>
            <tr><td>Voir la fiche complète d'un élève</td><td>Élèves &amp; Parents -> Dossiers élèves</td></tr>
            <tr><td>Imprimer des cartes scolaires</td><td>Élèves &amp; Parents -> Cartes scolaires</td></tr>
            <tr><td>Créer un compte parent</td><td>Élèves &amp; Parents -> Parents d'élèves</td></tr>
            <tr><td>Ajouter un enseignant</td><td>Enseignants -> Ajouter Enseignant</td></tr>
            <tr><td>Déclarer des heures de cours</td><td>Enseignants -> Émargements ou Cahier de présence</td></tr>
            <tr><td>Payer un enseignant</td><td>Enseignants -> Salaires</td></tr>
            <tr><td>Créer une classe</td><td>Classes &amp; Cours -> Classes</td></tr>
            <tr><td>Modifier l'emploi du temps</td><td>Classes &amp; Cours -> Emploi du temps</td></tr>
            <tr><td>Faire l'appel (présence quotidienne ou épreuve)</td><td>Contrôles &amp; Évaluations -> Appel de Présence</td></tr>
            <tr><td>Saisir des notes</td><td>Contrôles &amp; Évaluations -> Notes &amp; Évaluations</td></tr>
            <tr><td>Générer et publier des bulletins</td><td>Contrôles &amp; Évaluations -> Générer Bulletins</td></tr>
            <tr><td>Envoyer une annonce aux parents</td><td>Annonces</td></tr>
            <tr><td>Enregistrer un paiement de scolarité</td><td>Finances -> Paiements Élèves</td></tr>
            <tr><td>Voir le solde de la caisse</td><td>Finances -> Journal de Caisse</td></tr>
            <tr><td>Enregistrer une dépense</td><td>Finances -> Dépenses</td></tr>
            <tr><td>Créer un utilisateur / gérer les permissions</td><td>Configuration -> Utilisateurs</td></tr>
            <tr><td>Définir l'année scolaire en cours</td><td>Configuration -> Années scolaires</td></tr>
            <tr><td>Fixer les frais scolaires d'une classe</td><td>Finances -> Formule de paiement</td></tr>
            <tr><td>Réinitialiser le mot de passe d'un utilisateur</td><td>Configuration -> Utilisateurs -> bouton clé</td></tr>
            <tr><td>Changer mon mot de passe</td><td>Menu de mon nom (en haut à droite) -> Profil</td></tr>
            <tr><td>Télécharger le sujet d'une évaluation commune</td><td>Évaluations communes</td></tr>
            <tr><td>Créer une évaluation commune (DAE, DCAP)</td><td>Évaluations communes -> Nouvelle évaluation</td></tr>
            <tr><td>Publier à ses écoles une évaluation de l'académie (DCAP)</td><td>Évaluations communes -> Suivi -> Publier à mes écoles</td></tr>
            <tr><td>Publier une annonce à l'académie ou au CAP</td><td>Tableau de bord DAE / DCAP -> Publier une annonce</td></tr>
            <tr><td>Renouveler l'abonnement de l'école</td><td>Abonnements</td></tr>
            <tr><td>Poser une question ou dicter une instruction</td><td>Bulle KalanBot (en bas à droite)</td></tr>
        </tbody>
    </table>

</div>
