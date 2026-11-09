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
    public static function tableaux(string $type, array $series, array $terminales, array $techniques): array
    {
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

        if ($type === 'rentree') {
            return array_values(array_filter([
                ['code' => 'T1.1', 'titre' => 'Situation des classes et du matériel par niveau', 'lignes' => self::liste(['Salles de classe utilisées', 'Bureaux professeurs', 'Chaises professeurs', 'Tableaux muraux', 'Tableaux à chevalet', 'Ventilateurs', 'Tables-bancs', 'Tabourets', 'Fontaines à eau', 'Dispositifs de lavage de mains']), 'colonnes' => self::simple($niveaux), 'totaux' => 'colonne'],
                ['code' => 'T1.2', 'titre' => 'Situation des équipements collectifs', 'lignes' => self::liste(['Paillasses (tables pour labo)', 'Armoires', 'Machines à écrire', 'Machines ronéo', 'Photocopieuses', 'Machines à calculer', "Ordinateurs pour l'administration", 'Ordinateurs pour salle informatique', 'Climatiseurs', 'Fontaines à eau', 'Dispositifs de lavage de mains', 'Blocs latrines']), 'colonnes' => self::simple(['nombre' => 'Nombre'])],
                ['code' => 'T2', 'titre' => 'Volume horaire hebdomadaire par discipline et par série', 'lignes' => ['classes' => 'Nombre de classes (cours)'] + self::liste(self::DISCIPLINES), 'colonnes' => self::simple($series), 'totaux' => 'colonne'],
                ['code' => 'T5', 'titre' => 'Effectif des élèves par sexe, par année d\'étude et par série', 'lignes' => $series, 'colonnes' => self::simple(['f' => 'Filles', 'g' => 'Garçons']), 'totaux' => 'les deux'],
                ['code' => 'T6', 'titre' => 'Effectif des élèves passants et redoublants par sexe et par série', 'lignes' => $series, 'colonnes' => self::paires(['p' => 'Passants', 'r' => 'Redoublants']), 'totaux' => 'les deux'],
                ['code' => 'T7', 'titre' => 'Effectif des élèves par âge, par sexe et par série', 'lignes' => ['moins13' => 'Moins de 13 ans'] + collect(range(13, 25))->mapWithKeys(fn ($a) => ['a'.$a => $a === 25 ? '25 ans et plus' : $a.' ans'])->all(), 'colonnes' => self::paires($series), 'totaux' => 'les deux', 'note' => 'Âge atteint au 31 décembre de l\'année de la rentrée.'],
                ['code' => 'T8', 'titre' => "Effectif des élèves par région d'origine, par sexe et par série", 'lignes' => self::liste(self::REGIONS), 'colonnes' => self::paires($series), 'totaux' => 'les deux'],
                ['code' => 'T9', 'titre' => 'Effectif des élèves par scolarité (années passées au lycée) et par série', 'lignes' => collect(range(1, 6))->mapWithKeys(fn ($a) => ['a'.$a => $a.($a === 1 ? ' an' : ' ans')])->all(), 'colonnes' => self::paires($series), 'totaux' => 'les deux'],
                $terminales ? ['code' => 'T10', 'titre' => 'Résultats du baccalauréat de la session précédente', 'lignes' => $terminales, 'colonnes' => self::paires(['inscrits' => 'Inscrits', 'presents' => 'Présents', 'admis' => 'Admis']), 'totaux' => 'ligne'] : null,
                $terminales ? ['code' => 'T11', 'titre' => 'Répartition des élèves de terminale par scolarité et par sexe', 'lignes' => $terminales, 'colonnes' => self::paires(['a3' => '3 ans', 'a4' => '4 ans', 'a5' => '5 ans', 'a6' => '6 ans (ajournement)']), 'totaux' => 'les deux'] : null,
                $professeurs('T12', 'Répartition du corps professoral par sexe et statut selon la discipline'),
                $nationalites('T13'),
                $categories('T14'),
                ['code' => 'T15', 'titre' => 'Répartition du corps professoral par école de formation', 'lignes' => self::liste(['ENSUP', 'ENETP', 'INJS', 'INA', 'Conservatoire Balla Fasséké', 'Autres (extérieur)']), 'colonnes' => self::simple(['f' => 'F', 'h' => 'H']), 'totaux' => 'les deux'],
                ['code' => 'T16', 'titre' => 'Répartition du corps professoral par diplôme', 'lignes' => self::liste(['Doctorat', 'DEA', 'Master', 'Maîtrise', 'Licence', 'DEUG/DUT', 'Autres']), 'colonnes' => self::simple(['f' => 'F', 'h' => 'H']), 'totaux' => 'les deux'],
                $maliens('T17'),
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

        return array_values(array_filter([
            $professeurs('T1', 'Répartition du corps professoral par sexe et statut selon la discipline'),
            $nationalites('T2'),
            $categories('T3'),
            $maliens('T5'),
            $resultats('T6', '10CG', ['11L', '11S', '11SES']),
            $resultats('T7', '11L', ['TLL', 'TAL']),
            $resultats('T8', '11S', ['TSE', 'TSEXP']),
            $resultats('T9', '11SES', ['TSECO', 'TSS']),
            $techniques ? ['code' => 'T9b', 'titre' => "Résultats de fin d'année : technique et professionnel", 'lignes' => $techniques, 'colonnes' => self::paires(['effectif' => 'Effectif', 'passage' => 'Passage'] + $sorties), 'totaux' => 'ligne'] : null,
            $terminales ? ['code' => 'T11', 'titre' => "Résultats de fin d'année des terminales", 'lignes' => $terminales, 'colonnes' => self::paires(['effectif' => 'Effectif', 'admis' => 'Admis au Bac', 'red' => 'Redoublement', 'exclu' => 'Exclusion']), 'totaux' => 'ligne'] : null,
            $terminales ? ['code' => 'T12', 'titre' => "Résultats de l'examen du baccalauréat (réguliers et candidats libres)", 'lignes' => $terminales,
                'colonnes' => collect(['inscrits' => 'Inscrits', 'presents' => 'Présents', 'admis' => 'Admis'])->map(fn ($l, $c) => ['libelle' => $l, 'cols' => [$c.'_reg' => 'Rég.', $c.'_cl' => 'Libres']])->values()->all(), 'totaux' => 'ligne'] : null,
            $terminales ? ['code' => 'T13', 'titre' => 'Admis au baccalauréat par scolarité', 'lignes' => $terminales, 'colonnes' => self::paires(['a3' => '3 ans', 'a4' => '4 ans', 'a5' => '5 ans']), 'totaux' => 'les deux'] : null,
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
    public const REPRISE_ANNEE_PRECEDENTE = ['rentree' => ['T1.1', 'T1.2', 'T12', 'T13', 'T14', 'T15', 'T16', 'T17', 'T24'], 'fin' => ['T21']];
}
