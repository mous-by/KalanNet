<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Ecole;
use App\Models\Eleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class ResultatNationalController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $this->authorizeAccess();

        $schoolId = session('idEcole') ?: $user->idEcole;
        $classes = $this->examClasses($schoolId, $user);
        $examensDisponibles = $this->allowedExamLevels($schoolId);
        $annees = AnneeScolaire::orderByDesc('id_anneeScolaire')->get();
        $filters = $request->only(['id_classe', 'id_annee', 'niveau_examen']);
        $selectedClasse = $classes->firstWhere('id_classe', (int) ($filters['id_classe'] ?? 0));
        $niveauExamen = ($filters['niveau_examen'] ?? null) ?: ($selectedClasse ? $this->examLevel($selectedClasse) : null);
        $eleves = collect();
        $resultats = collect();

        if ($selectedClasse && !empty($filters['id_annee']) && $niveauExamen) {
            $eleves = Eleve::where('id_ecole', $schoolId)
                ->where('id_classe', $selectedClasse->id_classe)
                ->where('id_annee', (int) $filters['id_annee'])
                ->where('etat_dossier', 0)
                ->orderBy('prenom_eleve')->orderBy('nom_eleve')
                ->get();

            $resultats = DB::table('resultats_def_terminal')
                ->whereIn('id_eleve', $eleves->pluck('id_eleve'))
                ->where('id_annee', (int) $filters['id_annee'])
                ->where('niveau_examen', $niveauExamen)
                ->get()
                ->keyBy('id_eleve');
        }

        return view('pedagogie.resultats-nationaux', compact(
            'classes',
            'annees',
            'filters',
            'selectedClasse',
            'niveauExamen',
            'examensDisponibles',
            'eleves',
            'resultats'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $this->authorizeAccess();

        $data = $request->validate([
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'niveau_examen' => 'required|string|in:DEF,BAC',
            'date_resultat' => 'nullable|date',
            'resultats' => 'required|array',
            'resultats.*.decision' => 'nullable|string|in:admis,échec,echec',
            'resultats.*.moyenne' => 'nullable|numeric|min:0|max:20',
            'resultats.*.observation' => 'nullable|string|max:255',
        ]);

        $schoolId = session('idEcole') ?: $user->idEcole;
        $classe = Classe::where('idEcole', $schoolId)->findOrFail((int) $data['id_classe']);
        $this->ensureExamAllowed($schoolId, $data['niveau_examen'], $classe);

        $students = Eleve::where('id_ecole', $schoolId)
            ->where('id_classe', $classe->id_classe)
            ->where('id_annee', (int) $data['id_annee'])
            ->pluck('id_eleve')
            ->map(fn ($id) => (int) $id)
            ->all();

        $saved = 0;
        DB::transaction(function () use ($data, $students, &$saved) {
            foreach ($data['resultats'] as $studentId => $row) {
                $studentId = (int) $studentId;
                if (!in_array($studentId, $students, true) || empty($row['decision'])) {
                    continue;
                }

                $decision = $this->normalizeDecision($row['decision']);
                if (!$decision) {
                    continue;
                }
                DB::table('resultats_def_terminal')->updateOrInsert(
                    [
                        'id_eleve' => $studentId,
                        'id_annee' => (int) $data['id_annee'],
                        'niveau_examen' => $data['niveau_examen'],
                    ],
                    [
                        'decision' => $decision,
                        'moyenne' => $row['moyenne'] ?? null,
                        'observation' => $row['observation'] ?? null,
                        'date_resultat' => $data['date_resultat'] ?? now()->toDateString(),
                        'id_classe' => (int) $data['id_classe'],
                    ]
                );
                $saved++;
            }
        });

        return redirect()->route('pedagogie.resultats-nationaux.index', [
            'id_classe' => $data['id_classe'],
            'id_annee' => $data['id_annee'],
            'niveau_examen' => $data['niveau_examen'],
        ])->with('success', "{$saved} résultat(s) national(aux) enregistré(s).");
    }

    public function import(Request $request)
    {
        $user = Auth::user();
        $this->authorizeAccess();

        $data = $request->validate([
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'niveau_examen' => 'required|string|in:DEF,BAC',
            'date_resultat' => 'nullable|date',
            'fichier_resultats' => 'required|file|mimes:xls,xlsx,csv,txt,pdf|max:10240',
        ]);

        $schoolId = session('idEcole') ?: $user->idEcole;
        $classe = Classe::where('idEcole', $schoolId)->findOrFail((int) $data['id_classe']);
        $this->ensureExamAllowed($schoolId, $data['niveau_examen'], $classe);

        $students = Eleve::where('id_ecole', $schoolId)
            ->where('id_classe', $classe->id_classe)
            ->where('id_annee', (int) $data['id_annee'])
            ->get()
            ->keyBy(fn ($student) => Str::upper(trim((string) $student->matricule)));

        if ($students->isEmpty()) {
            return back()->with('error', 'Aucun élève actif trouvé pour cette classe et cette année.');
        }

        $extension = Str::lower($request->file('fichier_resultats')->getClientOriginalExtension());
        $rows = $extension === 'pdf'
            ? $this->rowsFromPdf($request->file('fichier_resultats')->getRealPath(), $students)
            : $this->rowsFromSpreadsheet($request->file('fichier_resultats')->getRealPath(), $extension);

        $saved = 0;
        $ignored = 0;
        DB::transaction(function () use ($rows, $students, $data, &$saved, &$ignored) {
            foreach ($rows as $row) {
                $matricule = Str::upper(trim((string) ($row['matricule'] ?? '')));
                $student = $students->get($matricule);
                $decision = $this->normalizeDecision((string) ($row['decision'] ?? ''));

                if (!$student || !$decision) {
                    $ignored++;
                    continue;
                }

                DB::table('resultats_def_terminal')->updateOrInsert(
                    [
                        'id_eleve' => $student->id_eleve,
                        'id_annee' => (int) $data['id_annee'],
                        'niveau_examen' => $data['niveau_examen'],
                    ],
                    [
                        'decision' => $decision,
                        'moyenne' => $row['moyenne'] ?? null,
                        'observation' => $row['observation'] ?? null,
                        'date_resultat' => $data['date_resultat'] ?? now()->toDateString(),
                        'id_classe' => (int) $data['id_classe'],
                    ]
                );
                $saved++;
            }
        });

        $message = "{$saved} résultat(s) importé(s).";
        if ($ignored > 0) {
            $message .= " {$ignored} ligne(s) ignorée(s).";
        }

        return redirect()->route('pedagogie.resultats-nationaux.index', [
            'id_classe' => $data['id_classe'],
            'id_annee' => $data['id_annee'],
            'niveau_examen' => $data['niveau_examen'],
        ])->with($saved > 0 ? 'success' : 'error', $saved > 0 ? $message : 'Aucun résultat importé. Vérifiez les matricules et les décisions.');
    }

    private function examClasses(?int $schoolId, $user)
    {
        $allowed = $this->allowedExamLevels($schoolId);

        return Classe::query()
            ->when($schoolId && $user->droit !== 'SupAdmin', fn ($query) => $query->where('idEcole', $schoolId))
            ->orderBy('nom_classe')
            ->get()
            ->filter(fn (Classe $classe) => in_array($this->examLevel($classe), $allowed, true))
            ->values();
    }

    private function examLevel(Classe $classe): ?string
    {
        return match ($this->extractClasseLevel($classe->nom_classe)) {
            9 => 'DEF',
            12 => 'BAC',
            default => null,
        };
    }

    private function extractClasseLevel(?string $name): ?int
    {
        return $name && preg_match('/\d+/', Str::ascii($name), $matches) ? (int) $matches[0] : null;
    }

    private function normalizeDecision(string $decision): ?string
    {
        $normalized = Str::lower(Str::ascii($decision));
        if (str_contains($normalized, 'admis') || str_contains($normalized, 'admit')) {
            return 'admis';
        }

        if (str_contains($normalized, 'echec') || str_contains($normalized, 'echoue') || str_contains($normalized, 'refuse')) {
            return 'échec';
        }

        return null;
    }

    private function ensureExamAllowed(?int $schoolId, string $niveau, Classe $classe): void
    {
        if (!in_array($niveau, $this->allowedExamLevels($schoolId), true) || $this->examLevel($classe) !== $niveau) {
            throw ValidationException::withMessages([
                'niveau_examen' => 'Cet examen ne correspond pas au type de l’école ou à la classe choisie.',
            ]);
        }
    }

    private function allowedExamLevels(?int $schoolId): array
    {
        $ecole = $schoolId ? Ecole::withoutGlobalScopes()->find($schoolId) : null;
        $type = Str::lower(Str::ascii((string) ($ecole->typeEcole ?? '')));

        if (str_contains($type, 'complexe')) {
            return ['DEF', 'BAC'];
        }

        if (str_contains($type, 'fondamentale ii')) {
            return ['DEF'];
        }

        if (str_contains($type, 'secondaire') || str_contains($type, 'lycee') || str_contains($type, 'technique')) {
            return ['BAC'];
        }

        return ['DEF', 'BAC'];
    }

    private function rowsFromSpreadsheet(string $path, string $extension): array
    {
        if (!class_exists(IOFactory::class)) {
            throw ValidationException::withMessages(['fichier_resultats' => 'La bibliothèque Excel n’est pas disponible.']);
        }

        $reader = $extension === 'csv' || $extension === 'txt'
            ? IOFactory::createReader('Csv')
            : IOFactory::createReaderForFile($path);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($sheet->toArray(null, true, true, true) as $index => $line) {
            $firstCell = Str::lower(Str::ascii(trim((string) ($line['A'] ?? ''))));
            if ($index === 1 && in_array($firstCell, ['matricule', 'numero', 'n matricule'], true)) {
                continue;
            }

            $matricule = trim((string) ($line['A'] ?? ''));
            if ($matricule === '') {
                continue;
            }

            $rows[] = [
                'matricule' => $matricule,
                'decision' => trim((string) ($line['B'] ?? '')),
                'moyenne' => $this->parseMoyenne($line['C'] ?? null),
                'observation' => trim((string) ($line['D'] ?? '')) ?: null,
            ];
        }

        return $rows;
    }

    private function rowsFromPdf(string $path, $students): array
    {
        if (!class_exists(PdfParser::class)) {
            throw ValidationException::withMessages(['fichier_resultats' => 'La lecture PDF n’est pas disponible sur ce serveur.']);
        }

        $text = (new PdfParser())->parseFile($path)->getText();
        $lines = preg_split('/\R+/', $text) ?: [];
        $rows = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            foreach ($students as $matricule => $student) {
                if ($matricule === '' || !str_contains(Str::upper($line), $matricule)) {
                    continue;
                }

                $rows[] = [
                    'matricule' => $matricule,
                    'decision' => $line,
                    'moyenne' => $this->parseMoyenneFromText($line),
                    'observation' => Str::limit($line, 255, ''),
                ];
                break;
            }
        }

        return $rows;
    }

    private function parseMoyenne($value): ?float
    {
        $normalized = str_replace(',', '.', trim((string) $value));
        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        $moyenne = (float) $normalized;
        return $moyenne >= 0 && $moyenne <= 20 ? round($moyenne, 2) : null;
    }

    private function parseMoyenneFromText(string $text): ?float
    {
        if (!preg_match_all('/\b([0-9]{1,2}(?:[\.,][0-9]{1,2})?)\b/', $text, $matches)) {
            return null;
        }

        foreach ($matches[1] as $match) {
            $moyenne = $this->parseMoyenne($match);
            if ($moyenne !== null) {
                return $moyenne;
            }
        }

        return null;
    }

    private function authorizeAccess(): void
    {
        $user = Auth::user();
        if (!$user || ($user->droit !== 'SupAdmin' && !$user->userHasAnyPermission(['evaluation_apercu', 'reinscriptions_apercu', 'inscriptions_reinscrire']))) {
            abort(403);
        }

        if (!Schema::hasTable('resultats_def_terminal')) {
            abort(500, 'La table des résultats nationaux n’est pas disponible.');
        }
    }
}
