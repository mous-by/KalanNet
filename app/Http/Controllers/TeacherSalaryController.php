<?php

namespace App\Http\Controllers;

use App\Models\Emargement;
use App\Models\Enseignant;
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

    public function bulletin(Request $request)
    {
        $this->authorizeSalaryAccess();

        $data = $request->validate([
            'id_enseignant' => 'required|integer|exists:enseignants,id_enseignant',
            'mois' => 'required|date_format:m',
            'annee' => 'required|integer|min:2000|max:2100',
            'source' => 'required|string|in:emargement,presence',
        ]);

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
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Bulletin_Salaire_' . $enseignant->id_enseignant . '_' . $filters['annee'] . '_' . $filters['mois'] . '.pdf');
    }

    private function salaryView(Request $request, string $mode)
    {
        $this->authorizeSalaryAccess();

        $user = Auth::user();
        $schoolId = session('idEcole') ?: $user->idEcole;
        $filters = $this->salaryFilters($request);
        $salaryRows = $this->salaryRows($user, $schoolId, $filters);
        $summary = $this->salarySummary($salaryRows);

        return view('enseignants.salaires', [
            'salaryRows' => $salaryRows,
            'summary' => $summary,
            'filters' => $filters,
            'enseignants' => $this->teachersQuery($user, $schoolId)->orderBy('nom_prenom_enseignant')->get(),
            'months' => $this->months(),
            'sources' => $this->sources(),
            'mode' => $mode,
        ]);
    }

    public function storePayment(Request $request)
    {
        $this->authorizeSalaryPayment();

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

        DB::transaction(function () use ($enseignant, $filters, $row, $data) {
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
                'montant_verse' => (int) round((float) $data['montant_verse']),
                'date_paiement' => $data['date_paiement'],
            ]);
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

    private function salaryFilters(Request $request): array
    {
        return [
            'mois' => str_pad((string) $request->input('mois', now()->format('m')), 2, '0', STR_PAD_LEFT),
            'annee' => (string) $request->input('annee', now()->format('Y')),
            'source' => $request->input('source', 'emargement'),
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

    private function salaryRow(Enseignant $enseignant, array $filters, array $period): array
    {
        $hours = $this->validatedHours($enseignant, $filters['source'], $period);
        $contract = $enseignant->type_contrat_enseignant ?: 'N/A';
        $hourlyRate = (float) ($enseignant->prix_heure ?? 0);
        $monthlySalary = (float) ($enseignant->salaire_enseignant ?? 0);
        $isVacataire = $contract === 'VCT';
        $amountDue = $isVacataire ? $hours * $hourlyRate : $monthlySalary;
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

    private function authorizeSalaryAccess(): void
    {
        $user = Auth::user();
        if ($user->droit !== 'SupAdmin' && !$user->userHasAnyPermission([
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
