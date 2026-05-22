<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public const CATALOG = [
        'dashboard' => [
            'label' => 'Tableau de bord',
            'permissions' => [
                'dashboard_apercu' => 'Voir le tableau de bord',
            ],
        ],
        'pedagogie_eleves_parents' => [
            'label' => 'Pédagogie - Élèves & Parents',
            'permissions' => [
                'eleves_apercu' => 'Voir les élèves',
                'eleves_modification' => 'Modifier les élèves',
                'eleves_supprimer' => 'Supprimer les élèves',
                'eleves_dossier' => 'Voir les dossiers élèves',
                'dossiers_eleves_apercu' => 'Voir les dossiers élèves',
                'inscriptions_apercu' => 'Voir les inscriptions',
                'inscriptions_inscrire' => 'Inscrire un élève',
                'reinscriptions_apercu' => 'Voir les réinscriptions',
                'inscriptions_reinscrire' => 'Réinscrire un élève',
                'parents_apercu' => 'Voir les parents',
                'parents_creation' => 'Créer un parent',
                'parents_modification' => 'Modifier les parents',
                'parents_supprimer' => 'Supprimer les parents',
            ],
        ],
        'pedagogie_enseignants' => [
            'label' => 'Pédagogie - Enseignants',
            'permissions' => [
                'enseignants_apercu' => 'Voir les enseignants',
                'enseignants_creation' => 'Créer un enseignant',
                'enseignants_modification' => 'Modifier les enseignants',
                'enseignants_archiver_ou_reactiver' => 'Archiver ou réactiver',
                'enseignants_emploi' => 'Voir emploi enseignant',
                'emargement_apercu' => 'Voir les émargements',
                'emargement_faire' => 'Faire un émargement',
                'emargement_modification' => 'Modifier un émargement',
                'emargement_supprimer' => 'Supprimer un émargement',
                'emargement_validation_admin' => 'Valider un émargement',
                'presence_apercu' => 'Voir le cahier de présence',
                'presence_creation' => 'Créer une présence',
                'presence_modification' => 'Modifier une présence',
                'presence_supprimer' => 'Supprimer une présence',
            ],
        ],
        'classes_cours' => [
            'label' => 'Classes & Cours',
            'permissions' => [
                'classes_apercu' => 'Voir les classes',
                'classes_creation' => 'Créer une classe',
                'classes_modification' => 'Modifier les classes',
                'classes_supprimer' => 'Supprimer les classes',
                'classes_programme_officiel' => 'Associer le programme officiel',
                'matieres_apercu' => 'Voir les matières',
                'matieres_creation' => 'Créer une matière',
                'matieres_modification' => 'Modifier les matières',
                'matieres_supprimer' => 'Supprimer les matières',
                'programmes_apercu' => 'Voir les programmes officiels',
                'programmes_creation' => 'Créer un programme officiel',
                'programmes_modification' => 'Modifier un programme officiel',
                'programmes_supprimer' => 'Supprimer un programme officiel',
                'programmes_pdf' => 'Télécharger le programme en PDF',
                'planning_apercu' => 'Voir emploi du temps',
                'planning_creation' => 'Créer emploi du temps',
                'planning_supprimer' => 'Supprimer emploi du temps',
                'planning_imprimer' => 'Imprimer emploi du temps',
            ],
        ],
        'evaluations' => [
            'label' => 'Évaluations',
            'permissions' => [
                'evaluation_apercu' => 'Voir les évaluations',
                'evaluation_creation' => 'Créer une évaluation',
                'evaluation_modification' => 'Modifier une évaluation',
                'evaluation_supprimer' => 'Supprimer une évaluation',
                'controle_apercu' => 'Voir les contrôles',
                'controle_creation' => 'Créer un contrôle',
                'controle_modification' => 'Modifier un contrôle',
                'bulletins_acces_bulletin' => 'Accéder aux bulletins',
            ],
        ],
        'finances' => [
            'label' => 'Finances',
            'permissions' => [
                'finances_planifications_apercu' => 'Voir planification paiements',
                'finances_planifications_creation' => 'Créer planification paiements',
                'finances_planifications_modification' => 'Modifier planification paiements',
                'finances_planifications_supprimer' => 'Supprimer planification paiements',
                'paiements_apercu' => 'Voir les paiements',
                'paiements_faire' => 'Faire un paiement',
                'paiements_annuler' => 'Annuler un paiement',
                'historique_paiement_apercu' => 'Voir historique paiements',
                'historique_paiement_export' => 'Exporter historique paiements',
                'caisses_apercu' => 'Voir la caisse',
                'caisses_creation' => 'Créer une caisse',
                'caisses_modification' => 'Modifier la caisse',
                'banques_apercu' => 'Voir les banques',
                'banques_creation' => 'Créer une banque',
                'banques_modification' => 'Modifier une banque',
                'banques_supprimer' => 'Supprimer une banque',
                'encaissement_apercu' => 'Voir encaissements',
                'encaissement_creation' => 'Créer un encaissement',
                'encaissement_modification' => 'Modifier un encaissement',
                'encaissement_supprimer' => 'Supprimer un encaissement',
                'decaissements_apercu' => 'Voir décaissements',
                'decaissements_creation' => 'Créer un décaissement',
                'decaissements_modification' => 'Modifier un décaissement',
                'decaissements_supprimer' => 'Supprimer un décaissement',
                'decaissements_validation' => 'Valider un décaissement',
                'versements_apercu' => 'Voir versements',
                'versements_creation' => 'Créer un versement',
                'versements_modification' => 'Modifier un versement',
                'versements_supprimer' => 'Supprimer un versement',
                'retraits_apercu' => 'Voir retraits',
                'retraits_creation' => 'Créer un retrait',
                'retraits_modification' => 'Modifier un retrait',
                'retraits_supprimer' => 'Supprimer un retrait',
            ],
        ],
        'configuration' => [
            'label' => 'Configuration',
            'permissions' => [
                'administrateur_tabsConfig' => 'Onglet administrateurs',
                'enseignants_tabsConfig' => 'Onglet enseignants',
                'parents_tabsConfig' => 'Onglet parents',
                'utilisateurs_apercu' => 'Voir utilisateurs',
                'permissions_apercu' => 'Voir permissions',
                'profiles_apercu' => 'Voir profils',
                'ecoles_apercu' => 'Voir écoles',
                'academies_apercu' => 'Voir académies',
                'dcap_apercu' => 'Voir DCAP',
                'dcap_voiraction' => 'Actions DCAP',
                'dcap_modifier' => 'Modifier DCAP',
                'dcap_activer' => 'Activer DCAP',
                'dcap_permission' => 'Permissions DCAP',
                'dae_apercu' => 'Voir DAE',
                'dae_voiraction' => 'Actions DAE',
                'dae_modifier' => 'Modifier DAE',
                'dae_activer' => 'Activer DAE',
                'dae_permission' => 'Permissions DAE',
                'annees_scolaires_apercu' => 'Voir années scolaires',
                'classes_officielles_apercu' => 'Voir classes officielles',
                'types_notes_apercu' => 'Voir types de notes',
                'status_controles_apercu' => 'Voir statuts de contrôle',
                'trimestres_apercu' => 'Voir trimestres',
                'documents_apercu' => 'Voir documents',
                'documents_manage' => 'Gérer documents',
            ],
        ],
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permission', 'permission_id', 'user_id');
    }

    public static function normalizeName(?string $name): string
    {
        $name = trim((string) $name);
        $name = str_replace([' ', '-'], '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');

        return function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
    }

    public static function canonicalName(?string $name): string
    {
        $name = self::normalizeName($name);

        $directAliases = [
            'appercu_programm' => 'programmes_apercu',
            'apercu_programm' => 'programmes_apercu',
            'appercu_programme' => 'programmes_apercu',
            'apercu_programme' => 'programmes_apercu',
            'programme_apercu' => 'programmes_apercu',
            'programme_appercu' => 'programmes_apercu',
            'programm_apercu' => 'programmes_apercu',
            'program_apercu' => 'programmes_apercu',
            'programme_pdf' => 'programmes_pdf',
            'voir_pdf_programme' => 'programmes_pdf',
            'voir_programme_pdf' => 'programmes_pdf',
            'pdf_programme' => 'programmes_pdf',
            'programme_creation' => 'programmes_creation',
            'programme_création' => 'programmes_creation',
            'programme_creer' => 'programmes_creation',
            'programme_créer' => 'programmes_creation',
            'programme_modification' => 'programmes_modification',
            'programme_modifier' => 'programmes_modification',
            'programme_supprimer' => 'programmes_supprimer',
            'programme_suppression' => 'programmes_supprimer',
            'programmes_suppression' => 'programmes_supprimer',
            'enseignants_creation' => 'enseignants_creation',
            'enseignants_création' => 'enseignants_creation',
            'enseignants_archiver_ou_reactiver' => 'enseignants_archiver_ou_reactiver',
            'enseignants_archiver_ou_réactiver' => 'enseignants_archiver_ou_reactiver',
            'inscription_inscrire' => 'inscriptions_inscrire',
            'inscription_reinscrire' => 'inscriptions_reinscrire',
            'parents_création' => 'parents_creation',
            'parents_suppression' => 'parents_supprimer',
            'eleves_suppression' => 'eleves_supprimer',
            'matieres_modif' => 'matieres_modification',
            'matieres_supp' => 'matieres_supprimer',
            'planning_création' => 'planning_creation',
            'planning_supp' => 'planning_supprimer',
            'bulletins_acces_bulletin' => 'bulletins_acces_bulletin',
            'bulletins_acces_au_bulletin' => 'bulletins_acces_bulletin',
            'emargement_suppresion' => 'emargement_supprimer',
            'emargement_suppression' => 'emargement_supprimer',
            'presence_création' => 'presence_creation',
            'presence_suppression' => 'presence_supprimer',
            'planification_de_paiements_apercu' => 'finances_planifications_apercu',
            'planification_de_paiements_création' => 'finances_planifications_creation',
            'planification_de_paiements_creation' => 'finances_planifications_creation',
            'planification_de_paiements_modification' => 'finances_planifications_modification',
            'planification_de_paiements_suppression' => 'finances_planifications_supprimer',
            'planifications_paiements_apercu' => 'finances_planifications_apercu',
            'banques_création' => 'banques_creation',
            'banques_suppression' => 'banques_supprimer',
            'caisses_création' => 'caisses_creation',
            'encaissement_création' => 'encaissement_creation',
            'encaissement_suppression' => 'encaissement_supprimer',
            'decaissements_création' => 'decaissements_creation',
            'decaissements_suppression' => 'decaissements_supprimer',
            'versements_création' => 'versements_creation',
            'versements_suppression' => 'versements_supprimer',
            'retraits_création' => 'retraits_creation',
            'retraits_suppression' => 'retraits_supprimer',
        ];

        if (isset($directAliases[$name])) {
            return $directAliases[$name];
        }

        [$module, $action] = self::splitName($name);

        return $module . '_' . self::canonicalActionName($action);
    }

    public static function equivalentNames(?string $name): array
    {
        $canonical = self::canonicalName($name);
        $names = [$name, self::normalizeName($name), $canonical];

        $aliases = [
            'programmes_apercu' => [
                'programmes_apercu',
                'programme_apercu',
                'programme_appercu',
                'appercu_programm',
                'apercu_programm',
                'appercu_programme',
                'apercu_programme',
                'programm_apercu',
                'program_apercu',
            ],
            'programmes_pdf' => [
                'programmes_pdf',
                'programme_pdf',
                'voir_pdf_programme',
                'voir_programme_pdf',
                'pdf_programme',
            ],
            'programmes_creation' => [
                'programmes_creation',
                'programme_creation',
                'programme_création',
                'programme_creer',
                'programme_créer',
            ],
            'programmes_modification' => [
                'programmes_modification',
                'programme_modification',
                'programme_modifier',
            ],
            'programmes_supprimer' => [
                'programmes_supprimer',
                'programme_supprimer',
                'programme_suppression',
                'programmes_suppression',
            ],
            'enseignants_creation' => [
                'enseignants_creation',
                'enseignants_création',
            ],
            'enseignants_archiver_ou_reactiver' => [
                'enseignants_archiver_ou_reactiver',
                'enseignants_archiver_ou_réactiver',
                'enseignants_archiver ou réactiver',
            ],
        ];

        return array_values(array_unique(array_filter(array_merge($names, $aliases[$canonical] ?? []))));
    }

    public static function splitName(string $permissionName): array
    {
        $permissionName = self::normalizeName($permissionName);

        $compositeModules = [
            'assistant_ia',
            'classes_officielles',
            'status_controles',
            'annees_scolaires',
            'types_notes',
            'dossiers_eleves',
        ];

        foreach ($compositeModules as $prefix) {
            if ($permissionName === $prefix) {
                return [self::canonicalModuleName($prefix), 'acces'];
            }

            $prefixWithSeparator = $prefix . '_';
            if (str_starts_with($permissionName, $prefixWithSeparator)) {
                $action = substr($permissionName, strlen($prefixWithSeparator));
                return [self::canonicalModuleName($prefix), $action !== '' ? $action : 'acces'];
            }
        }

        $parts = explode('_', $permissionName, 2);

        if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
            return [self::canonicalModuleName($parts[0]), $parts[1]];
        }

        return ['autres', $permissionName];
    }

    public static function canonicalModuleName(string $module): string
    {
        $module = self::normalizeName($module);

        $aliases = [
            'inscription' => 'inscriptions',
            'planification' => 'planifications',
            'programme' => 'programmes',
            'programm' => 'programmes',
            'program' => 'programmes',
            'profile' => 'profiles',
        ];

        return $aliases[$module] ?? $module;
    }

    public static function canonicalActionName(string $action): string
    {
        $action = self::normalizeName($action);

        $aliases = [
            'appercu' => 'apercu',
            'aperçu' => 'apercu',
            'creation' => 'creation',
            'création' => 'creation',
            'création_de' => 'creation',
            'creer' => 'creation',
            'créer' => 'creation',
            'modif' => 'modification',
            'modifier' => 'modification',
            'modification' => 'modification',
            'supp' => 'supprimer',
            'supression' => 'supprimer',
            'suppression' => 'supprimer',
            'supprimer' => 'supprimer',
            'archiver_ou_réactiver' => 'archiver_ou_reactiver',
            'archiver_ou_reactiver' => 'archiver_ou_reactiver',
        ];

        return $aliases[$action] ?? $action;
    }

    public static function moduleDisplayLabel(string $module): string
    {
        $module = self::canonicalModuleName($module);

        if (isset(self::CATALOG[$module])) {
            return self::CATALOG[$module]['label'];
        }

        $labels = [
            'assistant_ia' => 'Assistant IA',
            'inscriptions' => 'Inscriptions',
            'planifications' => 'Planifications',
            'programmes' => 'Programmes',
            'finances_planifications' => 'Finances',
            'finances' => 'Finances',
            'profiles' => 'Profils',
            'annees_scolaires' => 'Années scolaires',
            'types_notes' => 'Types de notes',
            'status_controles' => 'Statuts de contrôle',
            'classes_officielles' => 'Classes officielles',
            'dossiers_eleves' => 'Dossiers élèves',
            'autres' => 'Autres',
        ];

        return $labels[$module] ?? ucwords(str_replace('_', ' ', $module));
    }

    public static function groupedByModule()
    {
        $permissions = self::query()
            ->orderBy('name')
            ->get();

        $grouped = [];

        foreach ($permissions->groupBy(fn ($permission) => self::canonicalName($permission->name)) as $canonicalName => $duplicates) {
            $permission = $duplicates->sortBy('id')->first();
            $catalogEntry = self::catalogEntry($canonicalName);
            [$module, $action] = $catalogEntry
                ? [$catalogEntry['module'], $catalogEntry['label']]
                : self::splitName($canonicalName);
            $action = $catalogEntry ? $action : self::canonicalActionName($action);

            $grouped[$module] ??= [];
            $grouped[$module][] = (object) [
                'id' => $permission->id,
                'name' => $canonicalName,
                'module' => $module,
                'module_display' => self::moduleDisplayLabel($module),
                'action' => $action,
            ];
        }

        $ordered = [];
        foreach (array_keys(self::CATALOG) as $module) {
            if (isset($grouped[$module])) {
                $ordered[$module] = $grouped[$module];
                unset($grouped[$module]);
            }
        }
        ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);
        $grouped = $ordered + $grouped;

        foreach ($grouped as $module => $items) {
            usort($items, fn ($a, $b) => strnatcasecmp($a->action, $b->action));
            $grouped[$module] = $items;
        }

        return $grouped;
    }

    public static function catalogNames(): array
    {
        return collect(self::CATALOG)
            ->flatMap(fn ($module) => array_keys($module['permissions']))
            ->values()
            ->all();
    }

    public static function catalogEntry(string $canonicalName): ?array
    {
        foreach (self::CATALOG as $module => $definition) {
            if (array_key_exists($canonicalName, $definition['permissions'])) {
                return [
                    'module' => $module,
                    'module_label' => $definition['label'],
                    'label' => $definition['permissions'][$canonicalName],
                ];
            }
        }

        return null;
    }
}
