<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use App\Models\Classe;
use App\Models\AnneeScolaire;
use App\Models\Ecole;
use App\Models\ParentModel;
use App\Models\Planification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class InscriptionController extends Controller
{
    public function index(Request $request)
    {
        $classes = Classe::where('idEcole', session('idEcole'))->orderBy('nom_classe')->get();
        $classeIds = $classes->pluck('id_classe');
        $annees = AnneeScolaire::orderByDesc('id_anneeScolaire')->get();
        $parents = ParentModel::where('idEcole', session('idEcole'))->orderBy('nom_prenom_parent')->get();
        $planifications = Planification::whereIn('id_classe', $classeIds)->orderBy('motif')->get();
        $planificationRequired = $this->schoolRequiresPlanification();
        $planificationLabel = $planificationRequired ? 'Planification' : 'Coopérative';
        $eleves = Eleve::where('id_ecole', session('idEcole'))
            ->where('etat_dossier', 0)
            ->with('classe')
            ->orderBy('prenom_eleve')->orderBy('nom_eleve')
            ->get();
        $activeTab = in_array($request->query('tab'), ['group', 'reinscription'], true) ? $request->query('tab') : 'individual';
        $reinscriptionFilters = $activeTab === 'reinscription' ? $this->defaultReinscriptionFilters($annees) : [];
        $reinscriptionPreview = null;

        return view('pedagogie.inscriptions.index', compact('classes', 'annees', 'parents', 'planifications', 'planificationRequired', 'planificationLabel', 'eleves', 'activeTab', 'reinscriptionFilters', 'reinscriptionPreview'));
    }

    public function create()
    {
        return redirect()->route('inscriptions.index', ['tab' => 'individual']);
    }

    public function createGroup()
    {
        return redirect()->route('inscriptions.index', ['tab' => 'group']);
    }

    public function createReinscription()
    {
        return redirect()->route('inscriptions.index', ['tab' => 'reinscription']);
    }

    public function previewReinscription(Request $request)
    {
        $context = $this->reinscriptionContext($request);

        return view('pedagogie.inscriptions.index', $context);
    }

    public function importGroup(Request $request)
    {
        $data = $request->validate([
            'fichier_excel' => 'required|file|mimes:xls,xlsx',
            'id_classe' => 'required|exists:classe,id_classe',
            'id_annee' => 'required|exists:anneescolaire,id_anneeScolaire',
            'id_planification' => [$this->schoolRequiresPlanification() ? 'required' : 'nullable', 'integer', 'exists:planification,id_planification'],
            'date_inscription' => 'nullable|date',
        ]);

        $idEcole = session('idEcole');
        $classe = Classe::where('idEcole', $idEcole)->find($data['id_classe']);
        if (!$classe) {
            throw ValidationException::withMessages([
                'id_classe' => 'La classe choisie n’appartient pas à votre école.',
            ]);
        }

        $planificationId = $data['id_planification'] ?? null;
        $planification = $planificationId
            ? Planification::where('id_classe', $data['id_classe'])
                ->where('id_annee', $data['id_annee'])
                ->find($planificationId)
            : null;
        if ($planificationId && !$planification) {
            throw ValidationException::withMessages([
                'id_planification' => 'La planification choisie ne correspond pas à cette classe et cette année scolaire.',
            ]);
        }

        if (!class_exists(IOFactory::class)) {
            return back()->with('error', 'La bibliothèque PhpSpreadsheet n’est pas disponible.');
        }

        $spreadsheet = IOFactory::load($request->file('fichier_excel')->getRealPath());
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestDataRow();
        $createdCount = 0;

        DB::transaction(function () use ($worksheet, $highestRow, $data, $idEcole, $planificationId, &$createdCount) {
            $dateInscription = $data['date_inscription'] ?? now()->toDateString();

            for ($row = 2; $row <= $highestRow; $row++) {
                $prenom = trim((string) $worksheet->getCell('A' . $row)->getValue());
                $nom = trim((string) $worksheet->getCell('B' . $row)->getValue());
                if ($prenom === '' && $nom === '') {
                    continue;
                }
                if ($prenom === '' || $nom === '') {
                    continue;
                }

                $dateNaissance = $this->normalizeExcelDate($worksheet->getCell('C' . $row)->getValue());
                $lieuNaiss = trim((string) $worksheet->getCell('D' . $row)->getValue()) ?: 'Non renseigné';
                $adresse = trim((string) $worksheet->getCell('E' . $row)->getValue()) ?: null;
                $genre = ucfirst(strtolower(trim((string) $worksheet->getCell('F' . $row)->getValue())));
                if (!in_array($genre, ['Masculin', 'Féminin'], true)) {
                    $genre = 'Masculin';
                }
                $casSocial = trim((string) $worksheet->getCell('G' . $row)->getValue());
                $casSocialNormalized = strtolower($casSocial);
                if ($casSocialNormalized === 'dispensé' || $casSocialNormalized === 'dispenser') {
                    $casSocial = 'Dipenser';
                } elseif ($casSocialNormalized === 'malade') {
                    $casSocial = 'Malade';
                } else {
                    $casSocial = 'normal';
                }
                $matricule = $this->normalizeMatricule((string) $worksheet->getCell('H' . $row)->getValue());

                $eleve = new Eleve();
                $eleve->prenom_eleve = $prenom;
                $eleve->nom_eleve = $nom;
                $eleve->date_naissance = $dateNaissance;
                $eleve->lieu_naiss = $lieuNaiss;
                $eleve->adresse_eleve = $adresse;
                $eleve->id_classe = $data['id_classe'];
                $eleve->id_annee = $data['id_annee'];
                $eleve->genre_eleve = $genre;
                $eleve->matricule = $matricule ?: $this->generateMatricule(['nom_eleve' => $nom, 'prenom_eleve' => $prenom]);
                $eleve->date_inscription = $dateInscription;
                $eleve->image = 'assets/images/avatars/avatar-1.png';
                $eleve->cas_social = $casSocial;
                $eleve->mode_paiement = null;
                $eleve->id_ecole = $idEcole;
                $eleve->etat_dossier = 0;
                $eleve->save();

                DB::table('ligne_inscription')->insert([
                    'id_eleve' => $eleve->id_eleve,
                    'id_classe' => $data['id_classe'],
                    'id_annee' => $data['id_annee'],
                    'id_planification' => $planificationId,
                    'date_inscription' => $dateInscription,
                ]);

                $createdCount++;
            }

            if ($createdCount === 0) {
                throw new \Exception('Aucune ligne valide trouvée dans le fichier Excel.');
            }
        });

        return redirect()->route('inscriptions.index', ['tab' => 'group'])->with('success', "Import effectué : {$createdCount} élève(s) inscrits.");
    }

    public function downloadGroupTemplate()
    {
        if (!class_exists(IOFactory::class)) {
            return back()->with('error', 'La bibliothèque PhpSpreadsheet n’est pas disponible.');
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'prenom_eleve');
        $sheet->setCellValue('B1', 'nom_eleve');
        $sheet->setCellValue('C1', 'date_naissance');
        $sheet->setCellValue('D1', 'lieu_naissance');
        $sheet->setCellValue('E1', 'adresse_eleve');
        $sheet->setCellValue('F1', 'genre_eleve');
        $sheet->setCellValue('G1', 'cas_social');
        $sheet->setCellValue('H1', 'matricule');
        $sheet->setCellValue('A2', 'Issa');
        $sheet->setCellValue('B2', 'Diallo');
        $sheet->setCellValue('C2', '2009-04-22');
        $sheet->setCellValue('D2', 'Ségou');
        $sheet->setCellValue('E2', 'Banankabougou');
        $sheet->setCellValue('F2', 'Masculin');
        $sheet->setCellValue('G2', 'normal');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'modele_inscription_groupe.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function normalizeExcelDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $normalized = trim((string) $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized)) {
            return $normalized;
        }

        $timestamp = strtotime($normalized);
        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'prenom_eleve' => 'required',
            'nom_eleve' => 'required',
            'id_classe' => 'required',
            'id_annee' => 'required',
            'genre_eleve' => 'required',
            'date_naissance' => 'nullable',
            'lieu_naiss' => 'nullable',
            'adresse_eleve' => 'nullable',
            'cas_social' => 'nullable',
            'mode_paiement' => 'nullable',
            'date_inscription' => 'nullable|date',
            'matricule' => 'nullable|string|max:50',
            'image' => 'nullable|image|max:5120',
            'parent_id' => 'nullable|exists:parents,id_parent',
            'lien_parent' => 'nullable|string|max:100',
            'informer' => 'nullable|string|in:Oui,Non',
            'id_planification' => [$this->schoolRequiresPlanification() ? 'required' : 'nullable', 'integer', 'exists:planification,id_planification'],
        ]);

        $planificationId = $data['id_planification'] ?? null;
        if ($planificationId) {
            Planification::where('id_classe', $data['id_classe'])
                ->where('id_annee', $data['id_annee'])
                ->findOrFail($planificationId);
        }

        DB::transaction(function () use ($request, $data, $planificationId) {
            $eleve = new Eleve();
            $eleve->prenom_eleve = $data['prenom_eleve'];
            $eleve->nom_eleve = $data['nom_eleve'];
            $eleve->date_naissance = $data['date_naissance'] ?? null;
            $eleve->lieu_naiss = $data['lieu_naiss'] ?: 'Non renseigné';
            $eleve->adresse_eleve = $data['adresse_eleve'] ?? null;
            $eleve->id_classe = $data['id_classe'];
            $eleve->id_annee = $data['id_annee'];
            $eleve->genre_eleve = $data['genre_eleve'];
            $eleve->matricule = $this->normalizeMatricule($data['matricule'] ?? null) ?: $this->generateMatricule($data);
            $eleve->date_inscription = $data['date_inscription'] ?? now()->toDateString();
            $eleve->image = $this->storeImage($request);
            $eleve->cas_social = $data['cas_social'] ?: 'normal';
            $eleve->mode_paiement = $data['mode_paiement'] ?? null;
            $eleve->id_ecole = session('idEcole');
            $eleve->save();

            if (!empty($data['parent_id'])) {
                $eleve->parents()->syncWithoutDetaching([
                    $data['parent_id'] => [
                        'lien_parent' => $data['lien_parent'] ?? 'Parent',
                        'informer' => $data['informer'] ?? 'Non',
                    ],
                ]);
            }

            DB::table('ligne_inscription')->insert([
                'id_eleve' => $eleve->id_eleve,
                'id_classe' => $data['id_classe'],
                'id_annee' => $data['id_annee'],
                'id_planification' => $planificationId,
                'date_inscription' => $eleve->date_inscription,
            ]);
        });

        return redirect()->route('eleves.index')->with('success', 'Élève inscrit avec succès.');
    }

    public function storeGroup(Request $request)
    {
        $data = $request->validate([
            'id_classe' => 'required|exists:classe,id_classe',
            'id_annee' => 'required|exists:anneescolaire,id_anneeScolaire',
            'id_planification' => [$this->schoolRequiresPlanification() ? 'required' : 'nullable', 'integer', 'exists:planification,id_planification'],
            'date_inscription' => 'nullable|date',
            'eleves' => 'required|array|min:1',
            'eleves.*.prenom_eleve' => 'required|string|max:255',
            'eleves.*.nom_eleve' => 'required|string|max:255',
            'eleves.*.genre_eleve' => 'required|string',
            'eleves.*.date_naissance' => 'nullable|date',
            'eleves.*.lieu_naiss' => 'nullable|string|max:255',
            'eleves.*.matricule' => 'nullable|string|max:50',
        ]);

        $idEcole = session('idEcole');
        Classe::where('idEcole', $idEcole)->findOrFail($data['id_classe']);
        $planificationId = $data['id_planification'] ?? null;
        if ($planificationId) {
            Planification::where('id_classe', $data['id_classe'])
                ->where('id_annee', $data['id_annee'])
                ->findOrFail($planificationId);
        }
        $dateInscription = $data['date_inscription'] ?? now()->toDateString();

        DB::transaction(function () use ($data, $dateInscription, $idEcole, $planificationId) {
            foreach ($data['eleves'] as $row) {
                $eleve = Eleve::create([
                    'prenom_eleve' => $row['prenom_eleve'],
                    'nom_eleve' => $row['nom_eleve'],
                    'date_naissance' => $row['date_naissance'] ?? null,
                    'lieu_naiss' => $row['lieu_naiss'] ?? 'Non renseigné',
                    'adresse_eleve' => null,
                    'id_classe' => $data['id_classe'],
                    'id_annee' => $data['id_annee'],
                    'genre_eleve' => $row['genre_eleve'],
                    'matricule' => $this->normalizeMatricule($row['matricule'] ?? null) ?: $this->generateMatricule($row),
                    'date_inscription' => $dateInscription,
                    'image' => 'assets/images/avatars/avatar-1.png',
                    'cas_social' => 'normal',
                    'mode_paiement' => null,
                    'id_ecole' => $idEcole,
                    'etat_dossier' => 0,
                ]);

                DB::table('ligne_inscription')->insert([
                    'id_eleve' => $eleve->id_eleve,
                    'id_classe' => $data['id_classe'],
                    'id_annee' => $data['id_annee'],
                    'id_planification' => $planificationId,
                    'date_inscription' => $dateInscription,
                ]);
            }
        });

        return redirect()->route('eleves.index', ['id_classe' => $data['id_classe'], 'id_annee' => $data['id_annee']])
            ->with('success', 'Inscription par groupe enregistrée avec succès.');
    }

    public function storeReinscription(Request $request)
    {
        if ($request->has('preview_reinscription')) {
            return $this->previewReinscription($request);
        }

        if ($request->has('eleves')) {
            return $this->storeReinscriptionsIntelligentes($request);
        }

        $data = $request->validate([
            'id_eleve' => 'required|exists:eleve,id_eleve',
            'id_classe' => 'required|exists:classe,id_classe',
            'id_annee' => 'required|exists:anneescolaire,id_anneeScolaire',
            'statut' => 'required|in:passant,redoublant,non défini,non_defini,ajourne,abandon,exclu',
            'date_reinscription' => 'nullable|date',
        ]);

        $idEcole = session('idEcole');
        $eleve = Eleve::where('id_ecole', $idEcole)
            ->where('etat_dossier', 0)
            ->findOrFail($data['id_eleve']);

        Classe::where('idEcole', $idEcole)->findOrFail($data['id_classe']);
        AnneeScolaire::findOrFail($data['id_annee']);

        $dateReinscription = $data['date_reinscription'] ?? now()->toDateString();

        if (DB::table('ligne_reinscription')
            ->where('id_eleve', $eleve->id_eleve)
            ->where('id_annee', $data['id_annee'])
            ->exists()) {
            return redirect()->route('inscriptions.index', ['tab' => 'reinscription'])
                ->with('error', 'Cet élève a déjà été réinscrit pour l’année scolaire choisie.');
        }

        DB::transaction(function () use ($eleve, $data, $dateReinscription) {
            $reinscriptionId = DB::table('reinscription')->insertGetId([
                'statut' => $data['statut'],
                'date_reinscription' => $dateReinscription,
                'enrolement' => 1,
                'moyenneGeneral' => null,
            ]);

            DB::table('ligne_reinscription')->insert([
                'id_eleve' => $eleve->id_eleve,
                'id_classe' => $data['id_classe'],
                'id_annee' => $data['id_annee'],
                'id_reinscription' => $reinscriptionId,
            ]);

            $eleve->id_classe = $data['id_classe'];
            $eleve->id_annee = $data['id_annee'];
            $eleve->date_inscription = $dateReinscription;
            $eleve->save();
        });

        return redirect()->route('inscriptions.index', ['tab' => 'reinscription'])
            ->with('success', 'Réinscription enregistrée avec succès.');
    }

    private function reinscriptionContext(Request $request): array
    {
        $classes = Classe::where('idEcole', session('idEcole'))->orderBy('nom_classe')->get();
        $classeIds = $classes->pluck('id_classe');
        $annees = AnneeScolaire::orderByDesc('id_anneeScolaire')->get();
        $parents = ParentModel::where('idEcole', session('idEcole'))->orderBy('nom_prenom_parent')->get();
        $planifications = Planification::whereIn('id_classe', $classeIds)->orderBy('motif')->get();
        $planificationRequired = $this->schoolRequiresPlanification();
        $planificationLabel = $planificationRequired ? 'Planification' : 'Coopérative';
        $eleves = Eleve::where('id_ecole', session('idEcole'))
            ->where('etat_dossier', 0)
            ->with('classe')
            ->orderBy('prenom_eleve')->orderBy('nom_eleve')
            ->get();

        $activeTab = 'reinscription';
        $reinscriptionPreview = $this->buildReinscriptionPreview($request, $classes);
        $reinscriptionFilters = array_merge(
            $this->defaultReinscriptionFilters($annees),
            array_filter($request->only(['source_classe_id', 'source_annee_id', 'target_annee_id', 'target_classe_id', 'date_reinscription']), fn ($value) => $value !== null && $value !== '')
        );

        return compact(
            'classes',
            'annees',
            'parents',
            'planifications',
            'planificationRequired',
            'planificationLabel',
            'eleves',
            'activeTab',
            'reinscriptionPreview',
            'reinscriptionFilters'
        );
    }

    private function buildReinscriptionPreview(Request $request, $classes): array
    {
        $data = $request->validate([
            'source_classe_id' => 'required|integer|exists:classe,id_classe',
            'source_annee_id' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'target_annee_id' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'target_classe_id' => 'nullable|integer|exists:classe,id_classe',
            'date_reinscription' => 'nullable|date',
        ]);

        $idEcole = session('idEcole');
        $sourceClasse = $classes->firstWhere('id_classe', (int) $data['source_classe_id']);
        if (!$sourceClasse) {
            throw ValidationException::withMessages(['source_classe_id' => 'La classe source n’appartient pas à votre école.']);
        }

        $sourceAnnee = AnneeScolaire::find((int) $data['source_annee_id']);
        $targetAnnee = AnneeScolaire::find((int) $data['target_annee_id']);
        $this->ensureTargetYearAfterSource($sourceAnnee, $targetAnnee);

        $suggestedTargetClasse = !empty($data['target_classe_id'])
            ? $classes->firstWhere('id_classe', (int) $data['target_classe_id'])
            : $this->suggestNextClasse($sourceClasse, $classes);

        if ($suggestedTargetClasse && (int) $suggestedTargetClasse->idEcole !== (int) $idEcole) {
            throw ValidationException::withMessages(['target_classe_id' => 'La classe cible n’appartient pas à votre école.']);
        }

        $defaultTargetClasse = $suggestedTargetClasse ?: $sourceClasse;
        $examLevel = $this->nationalExamLevel($sourceClasse);

        $students = Eleve::where('id_ecole', $idEcole)
            ->where('etat_dossier', 0)
            ->where('id_classe', $sourceClasse->id_classe)
            ->where('id_annee', (int) $data['source_annee_id'])
            ->with('classe')
            ->orderBy('prenom_eleve')->orderBy('nom_eleve')
            ->get();

        $existingIds = DB::table('ligne_reinscription')
            ->whereIn('id_eleve', $students->pluck('id_eleve'))
            ->where('id_annee', (int) $data['target_annee_id'])
            ->pluck('id_eleve')
            ->map(fn ($id) => (int) $id)
            ->all();

        $threshold = $this->passageThreshold($sourceClasse);
        $rows = $students->map(function ($student) use ($sourceClasse, $defaultTargetClasse, $suggestedTargetClasse, $data, $existingIds, $threshold, $examLevel) {
            $moyenne = $this->moyenneAnnuelle($student->id_eleve, $sourceClasse->id_classe, (int) $data['source_annee_id']);
            $resultatNational = $examLevel
                ? $this->resultatNational($student->id_eleve, $sourceClasse->id_classe, (int) $data['source_annee_id'], $examLevel)
                : null;
            $proposal = $examLevel
                ? $this->proposeNationalExamDecision($resultatNational, $examLevel, $suggestedTargetClasse)
                : $this->proposeDecision($moyenne, $threshold);
            $classeCibleId = match ($proposal) {
                'redoublant' => $sourceClasse->id_classe,
                'admis_sortant', 'diplome_sortant', 'en_attente_resultat' => null,
                default => $defaultTargetClasse->id_classe,
            };

            return [
                'eleve' => $student,
                'moyenne' => $moyenne,
                'moyenne_examen' => $resultatNational?->moyenne !== null ? round((float) $resultatNational->moyenne, 2) : null,
                'resultat_national' => $resultatNational?->decision,
                'niveau_examen' => $examLevel,
                'seuil' => $threshold,
                'decision_proposee' => $proposal,
                'classe_cible_id' => $classeCibleId,
                'deja_reinscrit' => in_array((int) $student->id_eleve, $existingIds, true),
            ];
        });

        return [
            'sourceClasse' => $sourceClasse,
            'targetClasse' => $examLevel === 'BAC' || ($examLevel === 'DEF' && !$suggestedTargetClasse) ? null : $defaultTargetClasse,
            'sourceAnnee' => $sourceAnnee,
            'targetAnnee' => $targetAnnee,
            'date' => $data['date_reinscription'] ?? now()->toDateString(),
            'rows' => $rows,
            'seuil' => $threshold,
            'stats' => [
                'total' => $rows->count(),
                'passants' => $rows->where('decision_proposee', 'passant')->count(),
                'redoublants' => $rows->where('decision_proposee', 'redoublant')->count(),
                'sans_moyenne' => $rows->where('decision_proposee', 'non_defini')->count(),
                'sortants' => $rows->whereIn('decision_proposee', ['admis_sortant', 'diplome_sortant'])->count(),
                'en_attente_resultat' => $rows->where('decision_proposee', 'en_attente_resultat')->count(),
                'deja_reinscrits' => $rows->where('deja_reinscrit', true)->count(),
            ],
            'niveauExamen' => $examLevel,
        ];
    }

    private function storeReinscriptionsIntelligentes(Request $request)
    {
        $data = $request->validate([
            'source_classe_id' => 'required|integer|exists:classe,id_classe',
            'source_annee_id' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'target_annee_id' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'date_reinscription' => 'nullable|date',
            'eleves' => 'required|array|min:1',
            'eleves.*.selected' => 'nullable',
            'eleves.*.id_eleve' => 'required|integer|exists:eleve,id_eleve',
            'eleves.*.decision' => 'nullable|string|in:passant,redoublant,admis_sortant,diplome_sortant,en_attente_resultat,ajourne,abandon,exclu',
            'eleves.*.id_classe' => 'nullable|integer|exists:classe,id_classe',
            'eleves.*.motif_decision' => 'nullable|string|max:1000',
        ]);

        $idEcole = session('idEcole');
        $sourceClasse = Classe::where('idEcole', $idEcole)->findOrFail((int) $data['source_classe_id']);
        $sourceAnnee = AnneeScolaire::findOrFail((int) $data['source_annee_id']);
        $targetAnnee = AnneeScolaire::findOrFail((int) $data['target_annee_id']);
        $this->ensureTargetYearAfterSource($sourceAnnee, $targetAnnee);
        $dateReinscription = $data['date_reinscription'] ?? now()->toDateString();
        $selectedRows = collect($data['eleves'])->filter(fn ($row) => !empty($row['selected']));

        if ($selectedRows->isEmpty()) {
            return redirect()->route('inscriptions.index', ['tab' => 'reinscription'])
                ->with('error', 'Veuillez cocher au moins un élève à réinscrire.');
        }

        $missingMotif = $selectedRows->first(function ($row) {
            return in_array($row['decision'] ?? null, ['ajourne', 'abandon', 'exclu'], true)
                && trim((string) ($row['motif_decision'] ?? '')) === '';
        });
        if ($missingMotif) {
            throw ValidationException::withMessages([
                'motif_decision' => 'Le motif est obligatoire pour les décisions Ajourné, Abandon et Exclu.',
            ]);
        }

        $classes = Classe::where('idEcole', $idEcole)->get()->keyBy('id_classe');
        $threshold = $this->passageThreshold($sourceClasse);
        $created = 0;
        $skipped = 0;
        $forced = 0;
        $sorties = 0;
        $ajournes = 0;

        DB::transaction(function () use ($selectedRows, $data, $idEcole, $sourceClasse, $classes, $threshold, $dateReinscription, &$created, &$skipped, &$forced, &$sorties, &$ajournes) {
            foreach ($selectedRows as $row) {
                $eleve = Eleve::where('id_ecole', $idEcole)
                    ->where('id_classe', $sourceClasse->id_classe)
                    ->where('id_annee', (int) $data['source_annee_id'])
                    ->find((int) $row['id_eleve']);

                if (!$eleve || DB::table('ligne_reinscription')
                    ->where('id_eleve', (int) $row['id_eleve'])
                    ->where('id_annee', (int) $data['target_annee_id'])
                    ->exists()) {
                    $skipped++;
                    continue;
                }

                $decision = $row['decision'] ?? null;
                if (!$decision) {
                    $skipped++;
                    continue;
                }
                if ($decision === 'en_attente_resultat') {
                    $skipped++;
                    continue;
                }

                $moyenne = $this->moyenneAnnuelle($eleve->id_eleve, $sourceClasse->id_classe, (int) $data['source_annee_id']);
                $examLevel = $this->nationalExamLevel($sourceClasse);
                $resultatNational = $examLevel
                    ? $this->resultatNational($eleve->id_eleve, $sourceClasse->id_classe, (int) $data['source_annee_id'], $examLevel)
                    : null;
                $statut = $decision;

                if (!$examLevel && $decision === 'passant' && $moyenne !== null && $moyenne < $threshold) {
                    $statut = 'passage_force';
                    $forced++;
                }

                $targetClasseId = !empty($row['id_classe']) ? (int) $row['id_classe'] : null;
                if (in_array($decision, ['redoublant', 'ajourne', 'abandon', 'exclu'], true)) {
                    $targetClasseId = $sourceClasse->id_classe;
                }

                if ($targetClasseId !== null && !$classes->has($targetClasseId)) {
                    $skipped++;
                    continue;
                }
                if (in_array($decision, ['passant', 'redoublant'], true) && $targetClasseId === null) {
                    $skipped++;
                    continue;
                }

                $insert = [
                    'statut' => $statut,
                    'date_reinscription' => $dateReinscription,
                    'enrolement' => in_array($decision, ['passant', 'redoublant'], true) ? 1 : 0,
                    'moyenneGeneral' => $resultatNational?->moyenne ?? $moyenne,
                ];
                if (Schema::hasColumn('reinscription', 'statut_propose')) {
                    $insert['statut_propose'] = $examLevel
                        ? $this->proposeNationalExamDecision($resultatNational, $examLevel, $classes->get($targetClasseId))
                        : $this->proposeDecision($moyenne, $threshold);
                }
                if (Schema::hasColumn('reinscription', 'motif_decision')) {
                    $insert['motif_decision'] = $row['motif_decision'] ?? null;
                }

                $reinscriptionId = DB::table('reinscription')->insertGetId($insert);

                DB::table('ligne_reinscription')->insert([
                    'id_eleve' => $eleve->id_eleve,
                    'id_classe' => $targetClasseId,
                    'id_annee' => (int) $data['target_annee_id'],
                    'id_reinscription' => $reinscriptionId,
                ]);

                if (in_array($decision, ['passant', 'redoublant'], true)) {
                    $eleve->id_classe = $targetClasseId;
                    $eleve->id_annee = (int) $data['target_annee_id'];
                    $eleve->date_inscription = $dateReinscription;
                    $eleve->etat_dossier = 0;
                    $eleve->save();
                } elseif ($decision === 'ajourne') {
                    $ajournes++;
                } elseif (in_array($decision, ['admis_sortant', 'diplome_sortant'], true)) {
                    $eleve->id_annee = (int) $data['target_annee_id'];
                    $eleve->etat_dossier = 2;
                    $eleve->save();
                    $sorties++;
                } else {
                    $eleve->id_classe = $targetClasseId;
                    $eleve->id_annee = (int) $data['target_annee_id'];
                    $eleve->etat_dossier = 2;
                    $eleve->save();
                    $sorties++;
                }

                $created++;
            }
        });

        $message = "{$created} décision(s) de réinscription enregistrée(s).";
        if ($forced > 0) {
            $message .= " {$forced} passage(s) forcé(s).";
        }
        if ($ajournes > 0) {
            $message .= " {$ajournes} ajourné(s).";
        }
        if ($sorties > 0) {
            $message .= " {$sorties} sortie(s) pour abandon/exclusion.";
        }
        if ($skipped > 0) {
            $message .= " {$skipped} élève(s) ignoré(s) car déjà traité(s) ou invalide(s).";
        }

        return redirect()->route('inscriptions.index', ['tab' => 'reinscription'])
            ->with('success', $message);
    }

    private function passageThreshold(Classe $classe): float
    {
        $ordre = Str::lower(Str::ascii((string) $classe->ordreEnseignement));

        return str_contains($ordre, 'fondamentale1') || str_contains($ordre, 'fondamentale i') ? 5.0 : 10.0;
    }

    private function proposeDecision(?float $moyenne, float $threshold): string
    {
        if ($moyenne === null) {
            return 'non_defini';
        }

        return $moyenne >= $threshold ? 'passant' : 'redoublant';
    }

    private function moyenneAnnuelle(int $idEleve, int $idClasse, int $idAnnee): ?float
    {
        if (!Schema::hasTable('moyenne_eleve')) {
            return null;
        }

        $query = DB::table('moyenne_eleve')
            ->where('id_eleve', $idEleve)
            ->where('id_classe', $idClasse)
            ->where('id_anneeScolaire', $idAnnee)
            ->where('moyenne', '>', 0);

        if (Schema::hasColumn('moyenne_eleve', 'valide')) {
            $query->where('valide', 1);
        }

        $average = $query->avg('moyenne');

        return $average !== null ? round((float) $average, 2) : null;
    }

    private function nationalExamLevel(Classe $classe): ?string
    {
        return match ($this->extractClasseLevel($classe->nom_classe)) {
            9 => 'DEF',
            12 => 'BAC',
            default => null,
        };
    }

    private function resultatNational(int $idEleve, int $idClasse, int $idAnnee, string $niveau): ?object
    {
        if (!Schema::hasTable('resultats_def_terminal')) {
            return null;
        }

        return DB::table('resultats_def_terminal')
            ->where('id_eleve', $idEleve)
            ->where('id_annee', $idAnnee)
            ->where('niveau_examen', $niveau)
            ->where(function ($query) use ($idClasse) {
                $query->whereNull('id_classe')->orWhere('id_classe', $idClasse);
            })
            ->orderByDesc('date_resultat')
            ->orderByDesc('id')
            ->first();
    }

    private function proposeNationalExamDecision(?object $resultat, string $niveau, ?Classe $nextClasse): string
    {
        if (!$resultat || !$resultat->decision) {
            return 'en_attente_resultat';
        }

        $decision = Str::lower(Str::ascii((string) $resultat->decision));
        if ($decision === 'admis') {
            if ($niveau === 'BAC') {
                return 'diplome_sortant';
            }

            return $nextClasse ? 'passant' : 'admis_sortant';
        }

        return 'redoublant';
    }

    private function suggestNextClasse(Classe $sourceClasse, $classes): ?Classe
    {
        $currentLevel = $this->extractClasseLevel($sourceClasse->nom_classe);
        if ($currentLevel === null) {
            return null;
        }

        return $classes->first(function ($classe) use ($currentLevel, $sourceClasse) {
            return (int) $classe->id_classe !== (int) $sourceClasse->id_classe
                && $this->extractClasseLevel($classe->nom_classe) === $currentLevel + 1;
        });
    }

    private function extractClasseLevel(?string $name): ?int
    {
        if (!$name) {
            return null;
        }

        return preg_match('/\d+/', Str::ascii($name), $matches) ? (int) $matches[0] : null;
    }

    private function defaultReinscriptionFilters($annees): array
    {
        $current = $this->currentSchoolYear($annees);
        $next = $current ? $this->nextSchoolYear($current, $annees) : null;

        return [
            'source_annee_id' => $current?->id_anneeScolaire,
            'target_annee_id' => $next?->id_anneeScolaire,
            'date_reinscription' => now()->toDateString(),
        ];
    }

    private function currentSchoolYear($annees)
    {
        $today = now()->toDateString();

        $current = $annees->first(function ($annee) use ($today) {
            return !empty($annee->date_debut)
                && !empty($annee->date_fin)
                && $annee->date_debut <= $today
                && $annee->date_fin >= $today;
        });

        return $current ?: $annees->first();
    }

    private function nextSchoolYear($current, $annees)
    {
        $currentStart = $this->schoolYearStart($current);
        if ($currentStart === null) {
            return null;
        }

        return $annees->first(fn ($annee) => $this->schoolYearStart($annee) === $currentStart + 1);
    }

    private function schoolYearStart($annee): ?int
    {
        if (!$annee) {
            return null;
        }

        if (preg_match('/(20\d{2}|19\d{2})/', (string) $annee->annee, $matches)) {
            return (int) $matches[1];
        }

        if (!empty($annee->date_debut) && preg_match('/^(20\d{2}|19\d{2})/', (string) $annee->date_debut, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function ensureTargetYearAfterSource($sourceAnnee, $targetAnnee): void
    {
        $sourceStart = $this->schoolYearStart($sourceAnnee);
        $targetStart = $this->schoolYearStart($targetAnnee);

        if ($sourceStart !== null && $targetStart !== null && $targetStart <= $sourceStart) {
            throw ValidationException::withMessages([
                'target_annee_id' => 'L’année cible doit obligatoirement être après l’année actuelle.',
            ]);
        }
    }

    private function schoolRequiresPlanification(): bool
    {
        $ecole = Ecole::withoutGlobalScopes()->find(session('idEcole'));
        $statut = Str::lower(Str::ascii((string) ($ecole->statut ?? '')));

        return $statut !== 'public';
    }

    private function generateMatricule(array $data): string
    {
        $year = now()->format('y');
        $nom = $this->asciiLetters($data['nom_eleve'] ?? '');
        $prenom = $this->asciiLetters($data['prenom_eleve'] ?? '');
        $seed = strtoupper(str_pad(substr($nom, 0, 2) . substr($prenom, 0, 2), 4, 'X'));

        return 'ELV-' . $year . '-' . $seed . random_int(100, 999);
    }

    private function normalizeMatricule(?string $matricule): ?string
    {
        $matricule = trim((string) $matricule);
        if ($matricule === '') {
            return null;
        }

        $matricule = Str::ascii($matricule);
        $matricule = preg_replace('/[^A-Za-z0-9_-]/', '', $matricule);

        return $matricule !== '' ? substr($matricule, 0, 50) : null;
    }

    private function asciiLetters(string $value): string
    {
        $value = Str::ascii($value);
        $value = preg_replace('/[^A-Za-z]/', '', $value);

        return strtoupper($value ?: 'XXXX');
    }

    private function storeImage(Request $request): string
    {
        if (!$request->hasFile('image')) {
            return 'assets/images/avatars/avatar-1.png';
        }

        $directory = public_path('image_eleves');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $file = $request->file('image');
        $name = uniqid('eleve_', true) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $name);

        return 'image_eleves/' . $name;
    }
}
