<?php

namespace App\Http\Controllers;

use App\Models\Emargement;
use App\Models\Enseignant;
use App\Models\Ecole;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Decaissement;
use App\Models\LigneSalaire;
use App\Models\Presence;
use App\Models\Salaire;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherSalaryController extends Controller
{
    public function index(Request $request)
    {
        return $this->salaryView($request, 'pay');
    }

    public function etat(Request $request)
    {
        return $this->salaryView($request, 'state');
    }

    public function etatPdf(Request $request)
    {
        $this->authorizeSalaryAccess();

        $data = $this->salaryViewData($request, 'state');
        $ecole = $data['schoolId'] ? Ecole::withoutGlobalScopes()->find($data['schoolId']) : null;

        $pdf = Pdf::loadView('pdf.enseignants.etat_salaires', [
            'rows' => $data['stateRows'],
            'summary' => $data['stateSummary'],
            'filters' => $data['filters'],
            'months' => $data['months'],
            'sources' => $data['sources'],
            'ecole' => $ecole,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('Etat_Paiement_Enseignants_' . $data['filters']['annee'] . '_' . $data['filters']['mois'] . '.pdf');
    }

    public function bulletin(Request $request)
    {
        $data = $request->validate([
            'id_enseignant' => 'required|integer|exists:enseignants,id_enseignant',
            'mois' => 'required|date_format:m',
            'annee' => 'required|integer|min:2000|max:2100',
            'source' => 'required|string|in:emargement,presence',
        ]);

        $this->authorizeSalaryAccess((int) $data['id_enseignant']);

        $user = Auth::user();
        $schoolId = session('idEcole') ?: $user->idEcole;
        $enseignant = $this->teachersQuery($user, $schoolId)
            ->with('ecole')
            ->findOrFail($data['id_enseignant']);
        $filters = [
            'mois' => str_pad((string) $data['mois'], 2, '0', STR_PAD_LEFT),
            'annee' => (string) $data['annee'],
            'source' => $data['source'],
            'id_enseignant' => $enseignant->id_enseignant,
        ];
        $row = $this->salaryRow($enseignant, $filters, $this->periodBounds((int) $filters['annee'], (int) $filters['mois']));

        $pdf = Pdf::loadView('pdf.enseignants.bulletin_salaire', [
            'row' => $row,
            'enseignant' => $enseignant,
            'ecole' => $enseignant->ecole,
            'filters' => $filters,
            'months' => $this->months(),
        ])->setPaper('a5', 'portrait');

        return $pdf->download('Bulletin_Salaire_' . $enseignant->id_enseignant . '_' . $filters['annee'] . '_' . $filters['mois'] . '.pdf');
    }

    private function salaryView(Request $request, string $mode)
    {
        $this->authorizeSalaryAccess();

        return view('enseignants.salaires', $this->salaryViewData($request, $mode));
    }

    private function salaryViewData(Request $request, string $mode): array
    {
        $user = Auth::user();
        $schoolId = session('idEcole') ?: $user->idEcole;
        $school = $schoolId ? Ecole::withoutGlobalScopes()->find($schoolId) : null;
        $sources = $this->availableSources($user, $school, $mode);
        $filters = $this->salaryFilters($request, $school, $sources);
        $salaryRows = $this->salaryRows($user, $schoolId, $filters);
        $stateRows = $this->stateRows($salaryRows);
        $summary = $this->salarySummary($salaryRows);
        $stateSummary = $this->salarySummary($stateRows);

        return [
            'salaryRows' => $salaryRows,
            'summary' => $summary,
            'stateRows' => $stateRows,
            'stateSummary' => $stateSummary,
            'filters' => $filters,
            'enseignants' => $this->teachersQuery($user, $schoolId)->orderBy('nom_prenom_enseignant')->get(),
            'bulkPeriods' => $this->bulkPeriods((int) $filters['annee'], (int) $filters['mois']),
            'bulkRows' => $this->bulkRows($user, $schoolId, $filters),
            'months' => $this->months(),
            'sources' => $sources,
            'mode' => $mode,
            'schoolId' => $schoolId,
        ];
    }

    public function storePayment(Request $request)
    {
        $this->authorizeSalaryPayment();

        if ($request->has('rows')) {
            return $this->storeBulkPayment($request);
        }

        $data = $request->validate([
            'id_enseignant' => 'required|integer|exists:enseignants,id_enseignant',
            'mois' => 'required|date_format:m',
            'annee' => 'required|integer|min:2000|max:2100',
            'source' => 'required|string|in:emargement,presence',
            'montant_verse' => 'required|numeric|min:1',
            'date_paiement' => 'required|date',
        ]);

        $user = Auth::user();
        $schoolId = session('idEcole') ?: $user->idEcole;
        $school = $schoolId ? Ecole::withoutGlobalScopes()->find($schoolId) : null;
        if (!array_key_exists($data['source'], $this->availableSources($user, $school, 'pay'))) {
            throw ValidationException::withMessages([
                'source' => 'Vous n’avez pas la permission de payer cette source.',
            ]);
        }
        $enseignant = $this->teachersQuery($user, $schoolId)->findOrFail($data['id_enseignant']);

        if (!$this->isSchoolPayableTeacher($enseignant)) {
            throw ValidationException::withMessages([
                'id_enseignant' => 'Le salaire de cet enseignant fonctionnaire est géré par l’État.',
            ]);
        }

        $filters = [
            'mois' => str_pad((string) $data['mois'], 2, '0', STR_PAD_LEFT),
            'annee' => (string) $data['annee'],
            'source' => $data['source'],
        ];
        $row = $this->salaryRow($enseignant, $filters, $this->periodBounds((int) $data['annee'], (int) $filters['mois']));

        if ($row['amount_due'] <= 0) {
            throw ValidationException::withMessages([
                'montant_verse' => 'Aucun salaire à payer pour cette période.',
            ]);
        }

        if ((float) $data['montant_verse'] > $row['remaining']) {
            throw ValidationException::withMessages([
                'montant_verse' => 'Le montant versé dépasse le reste à payer.',
            ]);
        }

        DB::transaction(function () use ($enseignant, $filters, $row, $data, $schoolId) {
            $amount = (float) $data['montant_verse'];
            $this->recordSalaryPayment($enseignant, $filters, $row, $amount, $data['date_paiement']);
            $this->recordSalaryDisbursement($schoolId, $amount, $data['date_paiement'], $filters, "Paiement salaire - {$enseignant->nom_prenom_enseignant}");
        });

        return redirect()
            ->route('enseignants.salaires', [
                'mois' => $filters['mois'],
                'annee' => $filters['annee'],
                'source' => $filters['source'],
                'id_enseignant' => $enseignant->id_enseignant,
            ])
            ->with('success', 'Versement de salaire enregistré avec succès.');
    }

    private function storeBulkPayment(Request $request)
    {
        $data = $request->validate([
            'date_paiement' => 'required|date',
            'redirect_mois' => 'nullable|date_format:m',
            'redirect_annee' => 'nullable|integer|min:2000|max:2100',
            'rows' => 'required|array|min:1',
            'rows.*.id_enseignant' => 'required|integer|exists:enseignants,id_enseignant',
            'rows.*.mois' => 'required|date_format:m',
            'rows.*.annee' => 'required|integer|min:2000|max:2100',
            'rows.*.source' => 'required|string|in:emargement,presence',
            'rows.*.montant_verse' => 'nullable|numeric|min:0',
        ]);

        $user = Auth::user();
        $schoolId = session('idEcole') ?: $user->idEcole;
        $school = $schoolId ? Ecole::withoutGlobalScopes()->find($schoolId) : null;
        $sources = $this->availableSources($user, $school, 'pay');
        $teacherIds = collect($data['rows'])->pluck('id_enseignant')->unique()->values()->all();
        $teachers = $this->teachersQuery($user, $schoolId)
            ->whereIn('id_enseignant', $teacherIds)
            ->get()
            ->keyBy('id_enseignant');

        if ($teachers->count() !== count($teacherIds)) {
            throw ValidationException::withMessages([
                'rows' => 'Un ou plusieurs enseignants sélectionnés ne peuvent pas être payés par cette école.',
            ]);
        }

        $payments = [];
        $mainSource = null;
        foreach ($data['rows'] as $index => $inputRow) {
            $amount = (float) ($inputRow['montant_verse'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $source = $inputRow['source'];
            if (!array_key_exists($source, $sources)) {
                throw ValidationException::withMessages([
                    "rows.$index.source" => 'Vous n’avez pas la permission de payer cette source.',
                ]);
            }

            $enseignant = $teachers->get((int) $inputRow['id_enseignant']);
            $month = (int) $inputRow['mois'];
            $year = (int) $inputRow['annee'];
            $filters = [
                'mois' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
                'annee' => (string) $year,
                'source' => $source,
            ];
            $period = $this->periodBounds($year, $month);
            $row = $this->salaryRow($enseignant, $filters, $period);

            if ($row['amount_due'] <= 0 || $row['remaining'] <= 0) {
                throw ValidationException::withMessages([
                    "rows.$index.montant_verse" => 'Aucun reste à payer pour une des lignes sélectionnées.',
                ]);
            }

            if ($amount > $row['remaining']) {
                throw ValidationException::withMessages([
                    "rows.$index.montant_verse" => 'Un montant saisi dépasse le reste à payer.',
                ]);
            }

            $mainSource ??= $source;
            $payments[] = [$enseignant, $filters, $row, $amount];
        }

        if (empty($payments)) {
            throw ValidationException::withMessages([
                'rows' => 'Veuillez saisir au moins un montant à payer.',
            ]);
        }

        $total = collect($payments)->sum(fn ($payment) => $payment[3]);

        DB::transaction(function () use ($payments, $data, $schoolId, $total, $mainSource) {
            foreach ($payments as [$enseignant, $filters, $row, $amount]) {
                $this->recordSalaryPayment($enseignant, $filters, $row, (float) $amount, $data['date_paiement']);
            }
            $firstFilters = $payments[0][1];
            $firstFilters['source'] = $mainSource ?? $firstFilters['source'];
            $this->recordSalaryDisbursement($schoolId, $total, $data['date_paiement'], $firstFilters, 'Paiement groupé des salaires enseignants');
        });

        return redirect()
            ->route('enseignants.salaires', [
                'mois' => $data['redirect_mois'] ?? now()->format('m'),
                'annee' => $data['redirect_annee'] ?? now()->format('Y'),
                'source' => $mainSource ?? array_key_first($sources),
            ])
            ->with('success', count($payments) . ' salaire(s) payé(s) pour un total de ' . number_format($total, 0, ',', ' ') . ' FCFA.');
    }

    private function recordSalaryPayment(Enseignant $enseignant, array $filters, array $row, float $amount, string $paymentDate): void
    {
        $salaire = Salaire::query()
            ->where('reference', $row['reference'])
            ->lockForUpdate()
            ->first();

        if (!$salaire) {
            $salaire = Salaire::create([
                'type_paiement' => $row['payment_type'],
                'reference' => $row['reference'],
                'montant_a_payer' => $row['amount_due'],
                'id_enseignant' => $enseignant->id_enseignant,
                'id_inscription' => null,
                'created_at' => now(),
                'mois' => $filters['mois'],
                'annee' => $filters['annee'],
            ]);
        } else {
            $salaire->update([
                'montant_a_payer' => $row['amount_due'],
            ]);
        }

        LigneSalaire::create([
            'id_salaire' => $salaire->id_salaire,
            'montant_verse' => (int) round($amount),
            'date_paiement' => $paymentDate,
        ]);
    }

    private function recordSalaryDisbursement(?int $schoolId, float $amount, string $paymentDate, array $filters, string $label): void
    {
        if ($amount <= 0) {
            return;
        }

        $caisse = Caisse::query()
            ->where('id_ecole', $schoolId)
            ->where('status', 1)
            ->lockForUpdate()
            ->first();

        if (!$caisse) {
            throw ValidationException::withMessages([
                'date_paiement' => 'Aucune caisse active trouvée pour enregistrer le décaissement du salaire.',
            ]);
        }

        if ((float) $caisse->montant_net < $amount) {
            throw ValidationException::withMessages([
                'montant_verse' => 'Solde insuffisant dans la caisse pour payer ce salaire.',
            ]);
        }

        $annee = $this->academicYearForPaymentDate($schoolId, $paymentDate);
        $periodLabel = ($this->months()[$filters['mois']] ?? $filters['mois']) . ' ' . $filters['annee'];
        $sourceLabel = $this->sources()[$filters['source']] ?? $filters['source'];

        Decaissement::create([
            'montant_decaissement' => (int) round($amount),
            'date_decaissement' => $paymentDate,
            'motif_decaissement' => $label . " ({$sourceLabel} - {$periodLabel})",
            'id_annee_scolaire' => $annee?->id_anneeScolaire,
            'id_caisse' => $caisse->id_caisse,
            'idUtilisateur' => Auth::id(),
            'valide' => 1,
        ]);

        $caisse->decrement('montant_net', $amount);
    }

    private function academicYearForPaymentDate(?int $schoolId, string $paymentDate): ?AnneeScolaire
    {
        return AnneeScolaire::withoutGlobalScopes()
            ->where(function ($query) use ($schoolId) {
                $query->when($schoolId, fn ($inner, $id) => $inner->where('id_ecole', $id)->orWhereNull('id_ecole'))
                    ->when(!$schoolId, fn ($inner) => $inner->whereNull('id_ecole'));
            })
            ->where('date_debut', '<=', $paymentDate)
            ->where('date_fin', '>=', $paymentDate)
            ->orderByRaw('id_ecole IS NULL')
            ->orderByDesc('id_anneeScolaire')
            ->first();
    }

    private function salaryFilters(Request $request, ?Ecole $school, array $sources): array
    {
        $defaultSource = $this->defaultSource($school, $sources);
        $requestedSource = $request->input('source', $defaultSource);

        if (!array_key_exists($requestedSource, $sources)) {
            $requestedSource = $defaultSource;
        }

        return [
            'mois' => str_pad((string) $request->input('mois', now()->format('m')), 2, '0', STR_PAD_LEFT),
            'annee' => (string) $request->input('annee', now()->format('Y')),
            'source' => $requestedSource,
            'id_enseignant' => $request->input('id_enseignant'),
        ];
    }

    private function salaryRows($user, ?int $schoolId, array $filters)
    {
        $period = $this->periodBounds((int) $filters['annee'], (int) $filters['mois']);
        $enseignants = $this->teachersQuery($user, $schoolId)
            ->when($filters['id_enseignant'], fn ($query, $id) => $query->where('id_enseignant', $id))
            ->with(['salaires.lignes'])
            ->orderBy('nom_prenom_enseignant')
            ->get();

        return $enseignants->map(fn (Enseignant $enseignant) => $this->salaryRow($enseignant, $filters, $period));
    }

    private function salarySummary($salaryRows): array
    {
        return [
            'due' => $salaryRows->sum('amount_due'),
            'paid' => $salaryRows->sum('paid'),
            'remaining' => $salaryRows->sum('remaining'),
            'teachers' => $salaryRows->count(),
        ];
    }

    private function stateRows($salaryRows)
    {
        return $salaryRows
            ->values();
    }

    private function bulkRows($user, ?int $schoolId, array $filters)
    {
        $academicYears = $this->salaryAcademicYears($schoolId, $filters);
        $enseignants = $this->teachersQuery($user, $schoolId)
            ->when($filters['id_enseignant'], fn ($query, $id) => $query->where('id_enseignant', $id))
            ->orderBy('nom_prenom_enseignant')
            ->get();

        return $enseignants
            ->flatMap(function (Enseignant $enseignant) use ($filters, $academicYears) {
                return $academicYears->flatMap(function (AnneeScolaire $annee) use ($enseignant, $filters) {
                    if (!$this->teacherHasActivityInAcademicYear($enseignant, $filters['source'], $annee)) {
                        return collect();
                    }

                    return collect($this->salaryPeriodsForTeacher($enseignant, $annee, $filters['source']))
                        ->map(function (array $period) use ($enseignant, $filters) {
                            $periodFilters = [
                                'mois' => $period['month'],
                                'annee' => $period['year'],
                                'source' => $filters['source'],
                                'id_enseignant' => $enseignant->id_enseignant,
                            ];
                            $row = $this->salaryRow(
                                $enseignant,
                                $periodFilters,
                                $this->periodBounds((int) $period['year'], (int) $period['month'])
                            );
                            $row['period_label'] = $period['label'];
                            $row['period_value'] = $period['year'] . '-' . $period['month'];
                            $row['academic_year'] = $period['academic_year'];

                            return $row;
                        })
                        ->filter(fn (array $row) => $row['amount_due'] > 0 && $row['remaining'] > 0);
                });
            })
            ->values();
    }

    private function salaryAcademicYears(?int $schoolId, array $filters)
    {
        $selectedDate = Carbon::create((int) $filters['annee'], (int) $filters['mois'], 1)->toDateString();
        $academicYear = AnneeScolaire::withoutGlobalScopes()
            ->when($schoolId, fn ($query, $id) => $query->where(function ($inner) use ($id) {
                $inner->where('id_ecole', $id)->orWhereNull('id_ecole');
            }))
            ->where('date_debut', '<=', $selectedDate)
            ->where('date_fin', '>=', $selectedDate)
            ->orderByRaw('id_ecole IS NULL')
            ->orderByDesc('id_anneeScolaire')
            ->first();

        if ($academicYear) {
            return collect([$academicYear]);
        }

        return AnneeScolaire::withoutGlobalScopes()
            ->when($schoolId, fn ($query, $id) => $query->where(function ($inner) use ($id) {
                $inner->where('id_ecole', $id)->orWhereNull('id_ecole');
            }))
            ->where(function ($query) {
                $query->whereNull('date_fin')
                    ->orWhere('date_fin', '<=', now()->toDateString());
            })
            ->orderByDesc('date_debut')
            ->orderByDesc('id_anneeScolaire')
            ->limit(1)
            ->get();
    }

    private function teacherHasActivityInAcademicYear(Enseignant $enseignant, string $source, AnneeScolaire $annee): bool
    {
        $query = $source === 'presence'
            ? Presence::query()->where('id_enseignant', $enseignant->id_enseignant)
            : Emargement::query()->where('id_enseignant', $enseignant->id_enseignant);

        return $query
            ->where('valide', 1)
            ->where('id_anneeScolaire', $annee->id_anneeScolaire)
            ->exists();
    }

    private function salaryPeriodsForTeacher(Enseignant $enseignant, AnneeScolaire $annee, string $source): array
    {
        $contract = strtoupper((string) $enseignant->type_contrat_enseignant);
        $monthsCount = in_array($contract, ['CDI', 'CDD'], true)
            ? (int) ($enseignant->salaire_mois_mode ?: 12)
            : 12;
        $monthsCount = in_array($monthsCount, [9, 12], true) ? $monthsCount : 12;
        $start = $monthsCount === 9
            ? $this->firstActivityMonthInAcademicYear($enseignant, $source, $annee)
            : $this->academicYearStart($annee);

        if (!$start) {
            return [];
        }

        $academicStart = $this->academicYearStart($annee);
        if ($start->lt($academicStart)) {
            $start = $academicStart;
        }

        $end = $this->academicYearEnd($annee);

        return collect(range(0, $monthsCount - 1))
            ->map(function (int $offset) use ($start, $annee) {
                $date = $start->copy()->addMonthsNoOverflow($offset);

                return [
                    'year' => $date->format('Y'),
                    'month' => $date->format('m'),
                    'label' => ($this->months()[$date->format('m')] ?? $date->format('m')) . ' ' . $date->format('Y'),
                    'academic_year' => $annee->annee,
                ];
            })
            ->filter(function (array $period) use ($end) {
                $date = Carbon::create((int) $period['year'], (int) $period['month'], 1)->startOfMonth();

                return $date->lte(now()->startOfMonth()) && $date->lte($end);
            })
            ->values()
            ->all();
    }

    private function academicYearStart(AnneeScolaire $annee): Carbon
    {
        if (!empty($annee->date_debut)) {
            return Carbon::parse($annee->date_debut)->startOfMonth();
        }

        if (preg_match('/(\d{4})/', (string) $annee->annee, $matches)) {
            return Carbon::create((int) $matches[1], 9, 1)->startOfMonth();
        }

        return now()->startOfYear();
    }

    private function academicYearEnd(AnneeScolaire $annee): Carbon
    {
        if (!empty($annee->date_fin)) {
            return Carbon::parse($annee->date_fin)->startOfMonth();
        }

        return $this->academicYearStart($annee)->copy()->addMonthsNoOverflow(11)->startOfMonth();
    }

    private function firstActivityMonthInAcademicYear(Enseignant $enseignant, string $source, AnneeScolaire $annee): ?Carbon
    {
        $dateColumn = $source === 'presence' ? 'date_presence' : 'date_emargement';
        $date = $source === 'presence'
            ? Presence::query()
                ->where('id_enseignant', $enseignant->id_enseignant)
                ->where('valide', 1)
                ->where('id_anneeScolaire', $annee->id_anneeScolaire)
                ->min($dateColumn)
            : Emargement::query()
                ->where('id_enseignant', $enseignant->id_enseignant)
                ->where('valide', 1)
                ->where('id_anneeScolaire', $annee->id_anneeScolaire)
                ->min($dateColumn);

        return $date ? Carbon::parse($date)->startOfMonth() : null;
    }

    private function salaryRow(Enseignant $enseignant, array $filters, array $period): array
    {
        $hours = $this->validatedHours($enseignant, $filters['source'], $period);
        $contract = $enseignant->type_contrat_enseignant ?: 'N/A';
        $hourlyRate = (float) ($enseignant->prix_heure ?? 0);
        $monthlySalary = (float) ($enseignant->salaire_enseignant ?? 0);
        $isVacataire = $contract === 'VCT';
        $amountDue = $isVacataire ? $hours * $hourlyRate : $this->monthlySalaryDue($enseignant, $filters, $period, $monthlySalary);
        $paymentType = $isVacataire ? $filters['source'] : 'mensuel';
        $reference = $this->salaryReference($enseignant, $filters, $paymentType);
        $salary = Salaire::with('lignes')->where('reference', $reference)->first();
        $paid = (float) ($salary?->lignes->sum('montant_verse') ?? 0);
        $remaining = max($amountDue - $paid, 0);

        return [
            'enseignant' => $enseignant,
            'contract' => $contract,
            'source' => $filters['source'],
            'payment_type' => $paymentType,
            'hours' => $hours,
            'hourly_rate' => $hourlyRate,
            'monthly_salary' => $monthlySalary,
            'amount_due' => $amountDue,
            'paid' => $paid,
            'remaining' => $remaining,
            'reference' => $reference,
            'salary' => $salary,
            'status' => $remaining <= 0 && $amountDue > 0 ? 'Payé' : ($paid > 0 ? 'Partiel' : 'À payer'),
        ];
    }

    private function monthlySalaryDue(Enseignant $enseignant, array $filters, array $period, float $monthlySalary): float
    {
        $annee = $this->academicYearForPeriod($enseignant, $period);
        if (!$annee) {
            return 0.0;
        }

        if (!$this->teacherHasActivityInAcademicYear($enseignant, $filters['source'], $annee)) {
            return 0.0;
        }

        $allowedPeriods = collect($this->salaryPeriodsForTeacher($enseignant, $annee, $filters['source']))
            ->contains(fn (array $salaryPeriod) => $salaryPeriod['year'] === (string) $filters['annee']
                && $salaryPeriod['month'] === str_pad((string) $filters['mois'], 2, '0', STR_PAD_LEFT));

        return $allowedPeriods ? $monthlySalary : 0.0;
    }

    private function academicYearForPeriod(Enseignant $enseignant, array $period): ?AnneeScolaire
    {
        $date = Carbon::parse($period['start'])->toDateString();

        return AnneeScolaire::withoutGlobalScopes()
            ->where(function ($query) use ($enseignant) {
                $query->where('id_ecole', $enseignant->id_ecole)->orWhereNull('id_ecole');
            })
            ->where('date_debut', '<=', $date)
            ->where('date_fin', '>=', $date)
            ->orderByRaw('id_ecole IS NULL')
            ->orderByDesc('id_anneeScolaire')
            ->first();
    }

    private function validatedHours(Enseignant $enseignant, string $source, array $period): float
    {
        if ($source === 'presence') {
            return (float) Presence::query()
                ->where('id_enseignant', $enseignant->id_enseignant)
                ->where('valide', 1)
                ->whereBetween('date_presence', [$period['start'], $period['end']])
                ->sum('nombre_heure');
        }

        return (float) Emargement::query()
            ->where('id_enseignant', $enseignant->id_enseignant)
            ->where('valide', 1)
            ->whereBetween('date_emargement', [$period['start'], $period['end']])
            ->sum('nombre_heure');
    }

    private function teachersQuery($user, ?int $schoolId)
    {
        $query = Enseignant::query()
            ->where('is_deleted', 0)
            ->whereIn('type_contrat_enseignant', $this->schoolPayableContracts());

        if ($user->droit === 'SupAdmin') {
            return $query;
        }

        return $query->where('id_ecole', $schoolId);
    }

    private function isSchoolPayableTeacher(Enseignant $enseignant): bool
    {
        return in_array(strtoupper((string) $enseignant->type_contrat_enseignant), $this->schoolPayableContracts(), true);
    }

    private function schoolPayableContracts(): array
    {
        return ['CDI', 'CDD', 'VCT'];
    }

    private function periodBounds(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();

        return [
            'start' => $start,
            'end' => $start->copy()->endOfMonth()->endOfDay(),
        ];
    }

    private function bulkPeriods(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();

        return collect(range(0, 11))
            ->map(function (int $offset) use ($start) {
                $date = $start->copy()->subMonthsNoOverflow($offset);

                return [
                    'value' => $date->format('Y-m'),
                    'label' => ($this->months()[$date->format('m')] ?? $date->format('m')) . ' ' . $date->format('Y'),
                ];
            })
            ->all();
    }

    private function salaryReference(Enseignant $enseignant, array $filters, string $paymentType): string
    {
        return 'SAL-' . strtoupper($paymentType) . '-' . $filters['annee'] . '-' . $filters['mois'] . '-' . $enseignant->id_enseignant;
    }

    private function months(): array
    {
        return [
            '01' => 'Janvier',
            '02' => 'Février',
            '03' => 'Mars',
            '04' => 'Avril',
            '05' => 'Mai',
            '06' => 'Juin',
            '07' => 'Juillet',
            '08' => 'Août',
            '09' => 'Septembre',
            '10' => 'Octobre',
            '11' => 'Novembre',
            '12' => 'Décembre',
        ];
    }

    private function sources(): array
    {
        return [
            'emargement' => 'Émargements',
            'presence' => 'Cahier de présence',
        ];
    }

    private function availableSources($user, ?Ecole $school, string $mode): array
    {
        $permissionBySource = $mode === 'pay'
            ? [
                'emargement' => ['emargement_paiement enseignant', 'emargement_paiement_enseignant', 'paiements_faire'],
                'presence' => ['presence_paiement enseignant', 'presence_paiement_enseignant', 'paiements_faire'],
            ]
            : [
                'emargement' => ['emargement_etat de payement', 'emargement_etat_de_payement', 'paiements_faire'],
                'presence' => ['presence_etat de payement', 'presence_etat_de_payement', 'paiements_faire'],
            ];

        if ($user->droit === 'SupAdmin') {
            return $this->sources();
        }

        $available = collect($this->sources())
            ->filter(fn ($label, $source) => $user->userHasAnyPermission($permissionBySource[$source]))
            ->all();

        if (!empty($available)) {
            return $available;
        }

        $default = $this->sourceForSchoolType($school);

        return [$default => $this->sources()[$default]];
    }

    private function defaultSource(?Ecole $school, array $sources): string
    {
        $preferred = $this->sourceForSchoolType($school);

        return array_key_exists($preferred, $sources) ? $preferred : array_key_first($sources);
    }

    private function sourceForSchoolType(?Ecole $school): string
    {
        $type = str($school?->typeEcole ?? '')->lower()->ascii()->toString();

        return str_contains($type, 'fondamentale i') && !str_contains($type, 'fondamentale ii')
            ? 'presence'
            : 'emargement';
    }

    private function authorizeSalaryAccess(?int $teacherId = null): void
    {
        $user = Auth::user();
        if ($user->droit === 'SupAdmin') {
            return;
        }

        if ($teacherId && (int) ($user->id_enseignant ?? 0) === $teacherId) {
            return;
        }

        if (!$user->userHasAnyPermission([
            'emargement_paiement enseignant',
            'presence_paiement enseignant',
            'emargement_etat de payement',
            'presence_etat de payement',
            'emargement_etat_de_payement',
            'presence_etat_de_payement',
            'paiements_faire',
        ])) {
            abort(403);
        }
    }

    private function authorizeSalaryPayment(): void
    {
        $user = Auth::user();
        if ($user->droit !== 'SupAdmin' && !$user->userHasAnyPermission(['emargement_paiement enseignant', 'presence_paiement enseignant', 'paiements_faire'])) {
            abort(403);
        }
    }
}
