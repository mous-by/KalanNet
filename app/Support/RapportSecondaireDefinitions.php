<?php

namespace App\Support;

/**
 * Tableaux des rapports de rentrée et de fin d'année du secondaire, d'après les canevas officiels (ESG) et ce que les
 * lycées y remplissent réellement. Un tableau = des lignes, des colonnes (éventuellement groupées) et un type ; le moteur
 * (RapportSecondaireDonnees) les pré-remplit, l'écran et le PDF les affichent sans code propre à chaque tableau.
 */
class RapportSecondaireDefinitions
{
    public const TYPES = ['rentree' => 'Rapport de rentrée', 'fin' => "Rapport de fin d'année"];

    public const DISCIPLINES = ['Mathématiques', 'Physique et Chimie', 'SVT', 'Lettres/Linguistique', 'Philosophie', 'Histoire Géographie', 'Anglais', 'Allemand', 'Arabe', 'Russe', 'Chinois', 'Espagnol', 'Compta/Eco/Com', 'Compta/commerce', 'Economie', 'Math financière', 'Droit', 'Sociologie', 'Langues Nationales', 'Informatique', 'Education Civique et Morale', 'Education Physique et Sportive', 'Dessin Technique', 'Musique', 'Arts dramatiques', 'Arts plastiques', 'Danse', 'Projet personnel'];

    public const REGIONS = ['Bamako', 'Kayes', 'Kita', 'Nioro', 'Koulikoro', 'Dioïla', 'Nara', 'Ségou', 'San', 'Sikasso', 'Koutiala', 'Bougouni', 'Mopti', 'Bandiagara', 'Douentza', 'Tombouctou', 'Gao', 'Kidal', 'Ménaka', 'Taoudénit', 'Extérieur'];

    public const STATUTS = ['fetat' => 'F/ETAT', 'fct' => 'F/CT', 'contetat' => 'CONT ETAT', 'contct' => 'CONT CT', 'perm' => 'PERM', 'vact' => 'VACT', 'volontaire' => 'VOLONTAIRE'];

    public const POSTES = ['Proviseur', 'Censeur', 'Surveillant', 'Secrétaire', 'Econome', 'Planton', 'Chauffeur', 'Manœuvre', 'Ronéotypiste', 'Electricien', 'Gardien', 'Autres'];

    /** Renseignements généraux (tableau 0), communs aux deux rapports. @return array<string, string> */
    public static function infos(string $type): array
    {
        $infos = [
            'nom' => "Nom de l'établissement", 'region' => 'Région', 'cercle' => 'Cercle', 'arrondissement' => 'Arrondissement', 'commune' => 'Commune',
            'bp' => 'BP', 'telephone' => 'N° de téléphone', 'email' => 'Adresse email', 'gps' => 'Coordonnées GPS (longitude, latitude)',
            'date_creation' => 'Date de création', 'arrete_creation' => 'Arrêté / décision de création', 'arrete_ouverture' => "Arrêté / décision d'ouverture", 'date_ouverture' => "Date d'ouverture",
            'statut' => 'Statut', 'capacite' => "Capacité d'accueil",
            'salles_ordinaires' => 'Salles ordinaires', 'salles_ordinaires_utilisees' => 'Salles ordinaires utilisées',
            'salles_specialisees' => 'Salles spécialisées', 'salles_specialisees_utilisees' => 'Salles spécialisées utilisées', 'laboratoires' => 'Laboratoires',
            'effectif_f' => 'Élèves : filles', 'effectif_g' => 'Élèves : garçons', 'groupes' => 'Groupes pédagogiques',
            'personnel_global' => 'Effectif global du personnel', 'enseignants_f' => 'Enseignants : femmes', 'enseignants_h' => 'Enseignants : hommes',
            'fetat_f' => "Fonctionnaires de l'État : femmes", 'fetat_h' => "Fonctionnaires de l'État : hommes", 'fct_f' => 'Fonctionnaires des collectivités : femmes', 'fct_h' => 'Fonctionnaires des collectivités : hommes',
            'contractuels_f' => 'Contractuels : femmes', 'contractuels_h' => 'Contractuels : hommes', 'permanents_f' => 'Permanents : femmes', 'permanents_h' => 'Permanents : hommes',
            'vacataires_f' => 'Vacataires : femmes', 'vacataires_h' => 'Vacataires : hommes',
            'superficie' => 'Superficie du domaine scolaire', 'titre_foncier' => 'Références du titre foncier', 'electricite' => 'Électricité (oui / non)',
        ];
        if ($type === 'fin') {
            $infos += ['ordre' => "Ordre d'enseignement", 'mission' => 'Mission', 'diplome' => 'Diplôme de fin de cycle',
                'inscrits_depuis' => "Élèves inscrits depuis l'ouverture", 'diplomes_depuis' => "Diplômés depuis l'ouverture"];
        }

        return $infos + ['autres' => 'Autres informations'];
    }

    /**
     * Partie administrative, rédigée par l'établissement (canevas « Partie administrative » du ministère).
     *
     * @return array<string, array{0: string, 1: string}> rubrique => [titre, ce qu'elle doit contenir]
     */
    public static function redaction(string $type): array
    {
        $presentation = ["Présentation de l'établissement", 'Situation des infrastructures, effectif des élèves, personnel administratif, situation des enseignants.'];
        $analyse = ['Analyse des données statistiques', 'Analyse des données relatives aux infrastructures, aux élèves, aux enseignants et aux résultats scolaires.'];
        $conclusion = ['Conclusion', ''];

        return $type === 'rentree'
            ? ['presentation' => $presentation, 'deroulement' => ['Déroulement de la rentrée', 'Aspects pédagogiques, aspects environnementaux.'], 'analyse' => $analyse, 'conclusion' => $conclusion]
            : [
                'presentation' => $presentation,
                'deroulement' => ["Déroulement de l'année scolaire", 'Aspects pédagogiques ; formations continues et gestion des enseignants ; point de la mise en œuvre de l\'APC ; suivi et évaluations ; activités socio-culturelles et sportives.'],
                'difficultes' => ['Difficultés et propositions de solutions', ''],
                'partenaires' => ['Relations avec les partenaires', 'Partenaires techniques et financiers (PTF) ; partenaires sociaux.'],
                'analyse' => $analyse,
                'finances' => ['Situation financière', 'Fournitures et matériels scolaires ; crédit de fonctionnement.'],
                'conclusion' => $conclusion,
            ];
    }

    /** Renseignements qui ne changent pas d'une année à l'autre : repris du rapport précédent. */
    public const INFOS_STABLES = ['region', 'cercle', 'arrondissement', 'commune', 'bp', 'gps', 'date_creation', 'arrete_creation', 'arrete_ouverture', 'date_ouverture', 'capacite',
        'salles_ordinaires', 'salles_specialisees', 'laboratoires', 'superficie', 'titre_foncier', 'electricite', 'ordre', 'mission', 'diplome'];

    /** Tableaux dans l'ordre de leur numéro (T1.1, T1.2, T2… T9, T9b, T10…), comme dans le canevas. */
    private static function trier(array $tableaux): array
    {
        $rang = fn (array $t) => (float) preg_replace('/^T(\d+(?:\.\d+)?)b$/', '$1.5', preg_replace('/^T/', '', $t['code'])) ?: (float) substr($t['code'], 1);
        usort($tableaux, fn ($a, $b) => $rang($a) <=> $rang($b));

        return array_values($tableaux);
    }

    /**
     * Un tableau du canevas coupé en deux comme sur le papier : classes de passage (10e, 11e, filières techniques) puis
     * « suite » pour les terminales. Même code (mêmes données), numéro affiché « T7 » puis « T7 suite ».
     */
    private static function parCycle(string $code, string $titre, array $lignes, array $series, array $terminales, array $options): array
    {
        $passage = array_diff_key($series, $terminales);

        return array_values(array_filter([
            $passage ? ['code' => $code, 'titre' => $titre.' : classes de 10e et de 11e', 'lignes' => $lignes, 'colonnes' => self::paires($passage)] + $options : null,
            $terminales ? ['code' => $code, 'numero' => $code.' suite', 'titre' => $titre.' : classes de terminale', 'lignes' => $lignes, 'colonnes' => self::paires($terminales)] + $options : null,
        ]));
    }

    /** T9 par niveau, comme le canevas : 10e CG (1 à 3 ans), 11e (2 à 5 ans), terminales (3 à 6 ans), filières techniques. */
    private static function scolarites(array $series, array $terminales): array
    {
        $ans = fn (int $de, int $a) => collect(range($de, $a))->mapWithKeys(fn ($n) => ['a'.$n => $n.($n === 1 ? ' an' : ' ans')])->all();
        $titre = 'Effectif des élèves par scolarité (années depuis le DEF) et par série';
        $blocs = [
            ['classes de 10e CG', array_intersect_key($series, ['10CG' => 1]), $ans(1, 3)],
            ['classes de 11e', array_intersect_key($series, array_flip(['11L', '11S', '11SES'])), $ans(2, 5)],
            ['classes de terminale', $terminales, $ans(3, 6)],
            ['filières techniques', array_filter($series, fn ($l, $cle) => str_starts_with((string) $cle, 'c'), ARRAY_FILTER_USE_BOTH), $ans(1, 6)],
        ];
        $tableaux = [];
        foreach ($blocs as [$libelle, $colonnes, $lignes]) {
            if ($colonnes) {
                $tableaux[] = ['code' => 'T9', 'numero' => $tableaux ? 'T9 suite' : 'T9', 'titre' => $titre.' : '.$libelle, 'lignes' => $lignes, 'colonnes' => self::paires($colonnes), 'totaux' => 'les deux',
                    'note' => 'En 2026-2027 : CG26 = 1 an, CG25 = 2 ans… L\'année du DEF se saisit sur la fiche de l\'élève ou dans « Année du DEF des élèves ».'];
            }
        }

        return $tableaux;
    }

    private static function simple(array $colonnes): array
    {
        return [['libelle' => null, 'cols' => $colonnes]];
    }

    /** Une paire de colonnes F / G (ou F / H) par groupe. */
    private static function paires(array $groupes, string $second = 'G'): array
    {
        return collect($groupes)->map(fn ($libelle, $cle) => ['libelle' => $libelle, 'cols' => [$cle.'_f' => 'F', $cle.'_g' => $second]])->values()->all();
    }

    private static function liste(array $libelles): array
    {
        return collect($libelles)->mapWithKeys(fn ($l, $i) => ['l'.$i => $l])->all();
    }

    /**
     * @param  array<string, string>  $series  lignes de l'établissement (série du général ou classe du technique)
     * @param  array<string, string>  $terminales  séries de terminale du général présentes
     * @param  array<string, string>  $techniques  classes du technique
     * @return list<array<string, mixed>>
     */
    public static function tableaux(string $type, array $series, array $terminales, array $techniques, array $tailles = []): array
    {
        // Listes du canevas (salles, personnel, véhicules…) : des lignes numérotées, remplies en texte ; le PDF n'imprime que les lignes remplies.
        $liste = fn (string $code, string $titre, int $n, array $colonnes, ?string $note = null) => ['code' => $code, 'titre' => $titre, 'type' => 'texte', 'liste' => true,
            'lignes' => collect(range(1, max(1, $n)))->mapWithKeys(fn ($i) => ['n'.$i => (string) $i])->all(), 'colonnes' => self::simple($colonnes)] + ($note ? ['note' => $note] : []);
        $personnel = ['prenoms' => 'Prénoms', 'nom' => 'Nom', 'sexe' => 'Sexe', 'naissance' => 'Année de naissance', 'matricule' => 'N° Mle',
            'integration' => "Date d'intégration / recrutement", 'titularisation' => 'Titularisation', 'arrivee' => "Arrivée dans l'établissement", 'specialite' => 'Spécialité'];
        $grade = ['cat' => 'Cat.', 'classe' => 'Clas.', 'echelon' => 'Éch.', 'situation' => 'Situation matrimoniale', 'enfants' => "Nbre d'enfants", 'observations' => 'Observations'];
        $ages = ['moins20' => 'Moins de 20 ans', 'a20' => '20 à 29 ans', 'a30' => '30 à 39 ans', 'a40' => '40 à 49 ans', 'a50' => '50 ans et plus'];
        $agesSexe = collect($ages)->flatMap(fn ($l, $c) => [$c.'_f' => $l.' — F', $c.'_h' => $l.' — H'])->all();
        $etatSalle = 'État de la salle : a = bon état, b = état peu satisfaisant, c = mauvais état.';
        $salle = ['dimension' => 'Dimensions (L × l)', 'etat' => 'État (a, b, c)', 'capacite' => "Capacité d'accueil", 'occupees' => 'Places occupées', 'disponibles' => 'Places disponibles', 'heures' => "Heures d'utilisation / semaine"];
        $montants = ['du' => 'Montant dû', 'paye_anterieur' => 'Payé (antérieur)', 'paye_octobre' => 'Payé (octobre)', 'reliquat' => 'Reliquat'];
        $niveaux = ['n1' => '10e / 1re année', 'n2' => '11e / 2e année', 'n3' => 'Terminale / 3e année'];
        $statuts = collect(self::STATUTS);
        $professeurs = fn (string $code, string $titre) => ['code' => $code, 'titre' => $titre, 'lignes' => self::liste(self::DISCIPLINES),
            'colonnes' => [['libelle' => 'Hommes', 'cols' => $statuts->mapWithKeys(fn ($l, $c) => [$c.'_h' => $l])->all()], ['libelle' => 'Femmes', 'cols' => $statuts->mapWithKeys(fn ($l, $c) => [$c.'_f' => $l])->all()]],
            'totaux' => 'les deux', 'note' => 'F/ETAT : fonctionnaire de l\'État ; F/CT : fonctionnaire des collectivités ; PERM : permanent ; VACT : vacataire.'];
        $nationalites = fn (string $code) => ['code' => $code, 'titre' => 'Répartition du corps professoral par nationalité', 'lignes' => self::liste(['Maliens', 'Autres pays africains', 'Américains', 'Français', 'Chinois', 'Autres']), 'colonnes' => self::simple(['f' => 'F', 'h' => 'H']), 'totaux' => 'les deux'];
        $categories = fn (string $code) => ['code' => $code, 'titre' => 'Répartition du corps professoral par catégorie, par sexe et par statut', 'lignes' => self::liste(['Fonct État', 'Fonct CT', 'Contractuel ÉTAT', 'Contractuel CT', 'Permanents', 'Vacataires', 'Volontaires']),
            'colonnes' => self::paires(['a' => 'Catégorie A', 'b' => 'Catégorie B'], 'H'), 'totaux' => 'les deux'];
        $maliens = fn (string $code) => ['code' => $code, 'titre' => 'Répartition des professeurs maliens et étrangers par discipline', 'lignes' => self::liste(self::DISCIPLINES), 'colonnes' => self::paires(['maliens' => 'Maliens', 'etrangers' => 'Étrangers'], 'H'), 'totaux' => 'les deux'];
        $administration = fn (string $code) => ['code' => $code, 'titre' => 'Administration', 'type' => 'texte', 'lignes' => self::liste(self::POSTES), 'colonnes' => self::simple(['prenom' => 'Prénom', 'nom' => 'Nom', 'telephone' => 'Téléphone', 'email' => 'Email'])];

        $generales = array_filter($series, fn ($l, $cle) => !str_starts_with((string) $cle, 'c'), ARRAY_FILTER_USE_BOTH);
        $toutesTerminales = array_intersect_key(RapportSuccinctDonnees::SERIES, array_flip(['TAL', 'TLL', 'TSECO', 'TSS', 'TSE', 'TSEXP']));

        if ($type === 'rentree') {
            return self::trier(array_filter([
                ['code' => 'T1.1', 'titre' => 'Situation des classes et du matériel par niveau', 'lignes' => self::liste(['Salles de classe utilisées', 'Bureaux professeurs', 'Chaises professeurs', 'Tableaux muraux', 'Tableaux à chevalet', 'Ventilateurs', 'Tables-bancs', 'Tabourets', 'Fontaines à eau', 'Dispositifs de lavage de mains']), 'colonnes' => self::simple($niveaux), 'totaux' => 'colonne'],
                ['code' => 'T1.2', 'titre' => 'Situation des équipements collectifs', 'lignes' => self::liste(['Paillasses (tables pour labo)', 'Armoires', 'Machines à écrire', 'Machines ronéo', 'Photocopieuses', 'Machines à calculer', "Ordinateurs pour l'administration", 'Ordinateurs pour salle informatique', 'Climatiseurs', 'Fontaines à eau', 'Dispositifs de lavage de mains', 'Blocs latrines']), 'colonnes' => self::simple(['nombre' => 'Nombre'])],
                ['code' => 'T2', 'titre' => 'Volume horaire hebdomadaire par discipline et par série', 'lignes' => ['classes' => 'Nombre de classes (cours)'] + self::liste(self::DISCIPLINES), 'colonnes' => self::simple($series), 'totaux' => 'colonne'],
                ['code' => 'T5', 'titre' => 'Effectif des élèves par sexe, par année d\'étude et par série', 'lignes' => $series, 'colonnes' => self::simple(['f' => 'Filles', 'g' => 'Garçons']), 'totaux' => 'les deux'],
                ['code' => 'T6', 'titre' => 'Effectif des élèves passants et redoublants par sexe et par série', 'lignes' => $series, 'colonnes' => self::paires(['p' => 'Passants', 'r' => 'Redoublants']), 'totaux' => 'les deux'],
                ...self::parCycle('T7', 'Effectif des élèves par âge, par sexe et par série', ['moins13' => 'Moins de 13 ans'] + collect(range(13, 25))->mapWithKeys(fn ($a) => ['a'.$a => $a === 25 ? '25 ans et plus' : $a.' ans'])->all(),
                    $series, $terminales, ['totaux' => 'les deux', 'note' => 'Âge atteint au 31 décembre de l\'année de la rentrée.']),
                ...self::parCycle('T8', "Effectif des élèves par région d'origine, par sexe et par série", self::liste(self::REGIONS), $series, $terminales, ['totaux' => 'les deux']),
                ...self::scolarites($series, $terminales),
                $generales ? ['code' => 'T10', 'titre' => 'Résultats du baccalauréat de la session écoulée', 'lignes' => $toutesTerminales, 'totaux' => 'ligne',
                    'note' => 'Session de l\'année scolaire écoulée. Présents : à compléter (KalanNet ne connaît que les admis et les échecs).',
                    'colonnes' => self::paires(['inscrits' => 'Inscrits', 'presents' => 'Présents', 'admis' => 'Admis'])] : null,
                $generales ? ['code' => 'T11', 'titre' => 'Répartition par scolarité et par sexe des admis au baccalauréat de la session écoulée', 'lignes' => $toutesTerminales, 'totaux' => 'les deux',
                    'note' => 'Scolarité comptée depuis l\'année du DEF (CG) de chaque admis.', 'colonnes' => self::paires(['a3' => '3 ans', 'a4' => '4 ans', 'a5' => '5 ans', 'a6' => '6 ans (ajournement)'])] : null,
                $professeurs('T12', 'Répartition du corps professoral par sexe et statut selon la discipline'),
                $nationalites('T13'),
                $categories('T14'),
                ['code' => 'T15', 'titre' => 'Répartition du corps professoral par école de formation', 'lignes' => self::liste(['ENSUP', 'ENETP', 'INJS', 'INA', 'Conservatoire Balla Fasséké', 'Autres (extérieur)']), 'colonnes' => self::simple(['f' => 'F', 'h' => 'H']), 'totaux' => 'les deux'],
                ['code' => 'T16', 'titre' => 'Répartition du corps professoral par diplôme', 'lignes' => self::liste(['Doctorat', 'DEA', 'Master', 'Maîtrise', 'Licence', 'DEUG/DUT', 'Autres']), 'colonnes' => self::simple(['f' => 'F', 'h' => 'H']), 'totaux' => 'les deux'],
                $maliens('T17'),
                $liste('T3', "Recensement des locaux d'enseignement : salles non spécialisées", 30, ['numero' => 'N° de la salle'] + $salle, $etatSalle),
                ['code' => 'T4', 'titre' => "Recensement des locaux d'enseignement : salles spécialisées", 'type' => 'texte', 'note' => $etatSalle.' CDI : centre de documentation et d\'information.',
                    'lignes' => self::liste(['Labo de physique chimie 1', 'Labo de physique chimie 2', 'Labo de physique chimie 3', 'Labo de SVT 1', 'Labo de SVT 2', 'Labo de SVT 3',
                        'Salle informatique 1', 'Salle informatique 2', 'Salle informatique 3', 'Infirmerie', 'Bibliothèque', 'CDI']), 'colonnes' => self::simple($salle)],
                $liste('T18', "Personnel de l'assistance technique", 10, ['nom' => 'Prénoms et nom', 'naissance' => 'Date de naissance', 'specialite' => 'Spécialité',
                    'arrivee_mali' => "Date d'arrivée au Mali", 'mutation' => "Date de mutation dans l'établissement", 'situation' => 'Situation matrimoniale']),
                ['code' => 'T19', 'titre' => "Répartition du personnel enseignant tenant des classes par tranche d'âge, par catégorie, par sexe et par statut", 'lignes' => $agesSexe, 'totaux' => 'ligne',
                    'colonnes' => [['libelle' => 'Catégorie A', 'cols' => $statuts->mapWithKeys(fn ($l, $c) => ['a_'.$c => $l])->all()],
                        ['libelle' => 'Catégorie B', 'cols' => $statuts->mapWithKeys(fn ($l, $c) => ['b_'.$c => $l])->all()],
                        ['libelle' => 'Catégorie C', 'cols' => $statuts->only(['fetat', 'fct', 'perm', 'vact'])->mapWithKeys(fn ($l, $c) => ['c_'.$c => $l])->all()]]],
                ['code' => 'T20', 'titre' => "Personnel ne tenant pas de classe (enseignant ou non) par tranche d'âge et par catégorie", 'lignes' => $agesSexe, 'totaux' => 'les deux',
                    'colonnes' => self::simple(['a' => 'Catégorie A', 'b' => 'Catégorie B', 'c' => 'Catégorie C', 'd' => 'Catégorie D', 'conventionnaire' => 'Conventionnaire'])],
                $liste('T21', 'Situation du personnel enseignant', ($tailles['enseignants'] ?? 0) + 5, $personnel + ['apc' => 'Formé en APC (oui / non)', 'fonction' => 'Fonction',
                    'heures' => 'Heures / semaine', 'heures_sup' => 'Heures sup. / semaine'] + $grade, 'Pré-rempli avec les enseignants de KalanNet ; complétez les autres colonnes.'),
                $liste('T22', 'Situation du personnel non enseignant', 20, $personnel + ['fonction' => 'Fonction'] + $grade, 'Situation matrimoniale : M = marié(e), C = célibataire, D = divorcé(e), V = veuf / veuve.'),
                $liste('T23', 'Situation du personnel partant à la retraite en janvier', 10, $personnel + ['fonction' => 'Fonction'] + $grade),
                ['code' => 'T25', 'titre' => 'Situation du crédit de fonctionnement', 'type' => 'texte', 'lignes' => self::liste(['Crédit délégué', 'Crédit engagé', 'Crédit disponible']),
                    'colonnes' => self::simple(['montant' => 'À la date du 01/01 au 30/09', 'analyse' => "Analyse de l'exécution du crédit"])],
                ['code' => 'T26', 'titre' => 'Situation des salaires du personnel', 'type' => 'texte', 'note' => 'Période : du mois d\'octobre de l\'année écoulée au mois de septembre de la nouvelle année scolaire (montants en francs).',
                    'lignes' => self::liste(['Octobre', 'Novembre', 'Décembre', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre']), 'colonnes' => self::simple($montants)],
                ['code' => 'T27', 'titre' => 'Situation des accessoires de salaires et des heures supplémentaires (octobre à septembre)', 'type' => 'texte',
                    'lignes' => self::liste(['Allocation', 'Heures supplémentaires', 'Avancement', "Frais d'examens", 'Total']), 'colonnes' => self::simple($montants)],
                $liste('T28', 'Situation du parc auto et moto', 8, ['marque' => 'Marque et type', 'immatriculation' => "N° d'immatriculation", 'acquisition' => "Date d'acquisition", 'etat' => 'État', 'observations' => 'Observations']),
                $liste('T29', 'Liste des activités du Comité de gestion scolaire (CGS)', 10, ['libelle' => "Libellé de l'activité", 'nature' => 'Nature', 'montant' => 'Montant',
                    'source' => 'Source de financement', 'delai' => "Délai d'exécution", 'realisation' => 'Réalisation', 'observations' => 'Observations']),
                ['code' => 'T30', 'titre' => 'Répartition des élèves handicapés par série et par sexe selon le type et le degré de handicap', 'totaux' => 'les deux', 'colonnes' => self::paires($series),
                    'lignes' => collect(['vue' => 'Vue', 'ouie' => 'Ouïe', 'motricite' => 'Motricité globale (marcher, monter)', 'motricite_fine' => 'Motricité fine (écrire, saisir)', 'intellect' => 'Intellect',
                        'communication' => 'Communication (comprendre, se faire comprendre)', 'comportement' => 'Comportement, socialisation', 'multiples' => 'Multiples handicaps'])
                        ->flatMap(fn ($l, $c) => [$c.'_1' => $l.' — quelques difficultés', $c.'_2' => $l.' — beaucoup de difficultés', $c.'_3' => $l.' — n\'y parvient pas'])->all()],
                $liste('T31', 'Interventions des partenaires', 10, ['partenaire' => 'Partenaire', 'zones' => "Zones d'intervention", 'domaines' => "Domaines d'intervention",
                    'realisations' => 'Réalisations (nombre)', 'periode' => "Période d'intervention", 'observations' => 'Observations']),
                $administration('T24'),
            ]));
        }

        $sorties = ['red' => 'Redoublement', 'insuffisance' => 'Exclusion : insuffisance de travail', 'epuisement' => 'Exclusion : épuisement de scolarité', 'abandon' => 'Abandon'];
        $resultats = function (string $code, string $cle, array $passages) use ($series, $sorties) {
            if (!isset($series[$cle])) {
                return null;
            }

            return ['code' => $code, 'titre' => 'Résultats de fin d\'année : '.$series[$cle], 'lignes' => [$cle => $series[$cle]],
                'colonnes' => self::paires(['effectif' => 'Effectif'] + collect($passages)->mapWithKeys(fn ($c) => ['p'.$c => 'Passage en '.(RapportSuccinctDonnees::SERIES[$c] ?? $c)])->all() + $sorties)];
        };

        return self::trier(array_filter([
            $professeurs('T1', 'Répartition du corps professoral par sexe et statut selon la discipline'),
            $nationalites('T2'),
            $categories('T3'),
            $maliens('T5'),
            $resultats('T6', '10CG', ['11L', '11S', '11SES']),
            $resultats('T7', '11L', ['TLL', 'TAL']),
            $resultats('T8', '11S', ['TSE', 'TSEXP']),
            $resultats('T9', '11SES', ['TSECO', 'TSS']),
            $techniques ? ['code' => 'T9b', 'titre' => "Résultats de fin d'année : technique et professionnel", 'lignes' => $techniques, 'colonnes' => self::paires(['effectif' => 'Effectif', 'passage' => 'Passage'] + $sorties), 'totaux' => 'ligne'] : null,
            $generales ? ['code' => 'T11', 'titre' => "Résultats de fin d'année des terminales", 'lignes' => $toutesTerminales, 'colonnes' => self::paires(['effectif' => 'Effectif', 'admis' => 'Admis au Bac', 'red' => 'Redoublement', 'exclu' => 'Exclusion']), 'totaux' => 'ligne'] : null,
            $generales ? ['code' => 'T12', 'titre' => "Résultats de l'examen du baccalauréat (réguliers et candidats libres)", 'lignes' => $toutesTerminales,
                'colonnes' => collect(['inscrits' => 'Inscrits', 'presents' => 'Présents', 'admis' => 'Admis'])->map(fn ($l, $c) => ['libelle' => $l, 'cols' => [$c.'_reg' => 'Rég.', $c.'_cl' => 'Libres']])->values()->all(), 'totaux' => 'ligne'] : null,
            $generales ? ['code' => 'T13', 'titre' => 'Admis au baccalauréat par scolarité', 'lignes' => $toutesTerminales, 'colonnes' => self::paires(['a3' => '3 ans', 'a4' => '4 ans', 'a5' => '5 ans']), 'totaux' => 'les deux'] : null,
            ($onze = array_intersect_key($series, array_flip(['11L', '11S', '11SES']))) ? ['code' => 'T10', 'titre' => "Récapitulatif par série et par sexe des résultats de fin d'année des classes de 11e",
                'note' => 'Passage : 11e L → TLL puis TAL ; 11e S → TSE puis TSEXP ; 11e SES → TSECO puis TSS.', 'lignes' => $onze,
                'colonnes' => self::paires(['effectif' => 'Effectif', 'p1' => 'Passage (1re série)', 'p2' => 'Passage (2e série)'] + $sorties), 'totaux' => 'ligne'] : null,
            ['code' => 'T14', 'titre' => "Récapitulatif des résultats de fin d'année par année d'études", 'totaux' => 'ligne',
                'lignes' => ['n10' => '10e CG', 'n11' => '11e', 'n12' => 'Terminales'], 'colonnes' => self::simple(['effectif' => 'Effectif', 'passage' => 'Proposés au passage', 'redoublement' => 'Proposés au redoublement', 'exclusion' => "Proposés à l'exclusion"])],
            $generales ? ['code' => 'T16', 'titre' => 'Résultats du baccalauréat par rapport à la moyenne annuelle', 'lignes' => $toutesTerminales, 'totaux' => 'ligne',
                'note' => 'Présentés au Bac de la session et admis, selon leur moyenne annuelle (M.A). Le pourcentage se calcule sur les présentés de chaque tranche.',
                'colonnes' => collect(['m10' => 'M.A < 10', 'm12' => '10 ≤ M.A < 12', 'm14' => '12 ≤ M.A < 14', 'm16' => '14 ≤ M.A < 16', 'm20' => 'M.A ≥ 16'])
                    ->map(fn ($l, $c) => ['libelle' => $l, 'cols' => [$c.'_total' => 'Présentés', $c.'_admis' => 'Admis']])->values()->all()] : null,
            ['code' => 'T17', 'titre' => 'Effectifs restants : passants et redoublants par série', 'lignes' => $series, 'totaux' => 'les deux', 'colonnes' => self::paires(['passants' => 'Passants', 'redoublants' => 'Redoublants'])],
            $generales ? $liste('T23', 'Liste des élèves admis au baccalauréat', ($tailles['admis'] ?? 0) + 3, ['prenoms' => 'Prénoms', 'nom' => 'Nom', 'matricule' => 'N° Mle',
                'moy_ann' => 'Moy. annuelle', 'moy_admission' => "Moy. d'admission", 'mention' => 'Mention', 'serie' => 'Série'], 'Pré-rempli avec les admis enregistrés dans KalanNet.') : null,
            ['code' => 'T15', 'titre' => 'Répartition des élèves par tranche de moyenne annuelle', 'lignes' => $series, 'colonnes' => self::paires(['m7' => 'M.A < 7', 'm10' => '7 ≤ M.A < 10', 'm12' => '10 ≤ M.A < 12', 'm14' => '12 ≤ M.A < 14', 'm16' => '14 ≤ M.A < 16', 'm20' => 'M.A ≥ 16']), 'totaux' => 'les deux'],
            ['code' => 'T18', 'titre' => 'Capacité d\'accueil pour la rentrée scolaire prochaine', 'lignes' => $series, 'colonnes' => self::simple(['capacite' => 'Capacité par salle', 'salles' => 'Nombre de salles', 'disponibles' => 'Places disponibles', 'a_pourvoir' => 'Places à pourvoir']), 'totaux' => 'ligne'],
            ['code' => 'T19', 'titre' => 'Perspectives pour la nouvelle rentrée (effectifs probables)', 'lignes' => $series, 'colonnes' => self::simple(['passants' => 'Passants', 'redoublants' => 'Redoublants', 'effectifs' => 'Effectifs', 'classes' => 'Nombre de classes', 'salles' => 'Nombre de salles']), 'totaux' => 'ligne'],
            ['code' => 'T20', 'titre' => 'Besoins nouveaux en personnel enseignant', 'lignes' => self::liste(self::DISCIPLINES), 'colonnes' => self::simple(['en_place' => 'Professeurs en place', 'departs' => 'Départs probables', 'besoins' => 'Besoins nouveaux']), 'totaux' => 'ligne'],
            ['code' => 'T21', 'titre' => 'Besoins nouveaux en personnel ne tenant pas de classe', 'lignes' => self::liste(['Catégorie A', 'Catégorie B', 'Catégorie C', 'Catégorie D', 'Catégorie E']), 'colonnes' => self::simple(['en_place' => 'Effectif en place', 'departs' => 'Départs probables', 'besoins' => 'Besoins nouveaux']), 'totaux' => 'ligne'],
            $administration('T22'),
        ]));
    }

    /** Tableaux du rapport de fin d'année repris du rapport de rentrée de la même année. */
    public const REPRISE_DE_LA_RENTREE = ['T1' => 'T12', 'T2' => 'T13', 'T3' => 'T14', 'T5' => 'T17', 'T22' => 'T24'];

    /** Tableaux qui changent peu : repris du rapport du même type de l'année précédente. */
    /** Listes nominatives pré-remplies : « Reprendre les chiffres de KalanNet » garde les colonnes saisies. */
    public const LISTES_PRE_REMPLIES = ['T21', 'T23'];

    /** Tableaux pré-remplis ajoutés en novembre 2026 : complétés dans les rapports déjà enregistrés. */
    public const PRE_REMPLIS_AJOUTES = ['rentree' => ['T21'], 'fin' => ['T10', 'T14', 'T17', 'T23']];

    public const REPRISE_ANNEE_PRECEDENTE = ['rentree' => ['T1.1', 'T1.2', 'T12', 'T13', 'T14', 'T15', 'T16', 'T17', 'T24', 'T3', 'T4', 'T18', 'T22', 'T28', 'T31'], 'fin' => ['T21']];
}
