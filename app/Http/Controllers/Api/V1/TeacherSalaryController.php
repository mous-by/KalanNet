<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\TeacherSalaryController as WebTeacherSalaryController;
use App\Models\Ecole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherSalaryController extends WebTeacherSalaryController
{
    // etatPdf() and bulletin() stream PDFs directly and are routed straight
    // to the web controller (see routes/api.php) — no override needed.

    public function index(Request $request)
    {
        $this->authorizeSalaryAccess();

        return response()->json($this->salaryViewData($request, 'pay'));
    }

    public function etat(Request $request)
    {
        $this->authorizeSalaryAccess();

        return response()->json($this->salaryViewData($request, 'state'));
    }

    public function storePayment(Request $request)
    {
        $this->authorizeSalaryPayment();

        if ($request->has('rows')) {
            return $this->storeBulkPaymentJson($request);
        }

        $data = $request->validate([
            'id_enseignant' => 'required|integer|exists:enseignants,id_enseignant',
            'mois' => 'required|date_format:m',
            'annee' => 'required|integer|min:2000|max:2100',
            'source' => 'required|string|in:emargement,presence',
            'montant_verse' => 'required|numeric|min:1',
            'date_paiement' => 'required|date',
        ]);

        $user = $request->user();
        $schoolId = session('idEcole') ?: $user->idEcole;
        $school = $schoolId ? Ecole::withoutGlobalScopes()->find($schoolId) : null;

        if (!array_key_exists($data['source'], $this->availableSources($user, $school, 'pay'))) {
            throw ValidationException::withMessages(['source' => 'Vous n’avez pas la permission de payer cette source.']);
        }

        $enseignant = $this->teachersQuery($user, $schoolId)->findOrFail($data['id_enseignant']);

        if (!$this->isSchoolPayableTeacher($enseignant)) {
            throw ValidationException::withMessages(['id_enseignant' => 'Le salaire de cet enseignant fonctionnaire est géré par l’État.']);
        }

        $filters = [
            'mois' => str_pad((string) $data['mois'], 2, '0', STR_PAD_LEFT),
            'annee' => (string) $data['annee'],
            'source' => $data['source'],
        ];
        $row = $this->salaryRow($enseignant, $filters, $this->periodBounds((int) $data['annee'], (int) $filters['mois']));

        if ($row['amount_due'] <= 0) {
            throw ValidationException::withMessages(['montant_verse' => 'Aucun salaire à payer pour cette période.']);
        }

        if ((float) $data['montant_verse'] > $row['remaining']) {
            throw ValidationException::withMessages(['montant_verse' => 'Le montant versé dépasse le reste à payer.']);
        }

        DB::transaction(function () use ($enseignant, $filters, $row, $data, $schoolId) {
            $amount = (float) $data['montant_verse'];
            $this->recordSalaryPayment($enseignant, $filters, $row, $amount, $data['date_paiement']);
            $this->recordSalaryDisbursement($schoolId, $amount, $data['date_paiement'], $filters, "Paiement salaire - {$enseignant->nom_prenom_enseignant}");
        });

        return response()->json(['success' => true]);
    }

    protected function storeBulkPaymentJson(Request $request)
    {
        $data = $request->validate([
            'date_paiement' => 'required|date',
            'rows' => 'required|array|min:1',
            'rows.*.id_enseignant' => 'required|integer|exists:enseignants,id_enseignant',
            'rows.*.mois' => 'required|date_format:m',
            'rows.*.annee' => 'required|integer|min:2000|max:2100',
            'rows.*.source' => 'required|string|in:emargement,presence',
            'rows.*.montant_verse' => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();
        $schoolId = session('idEcole') ?: $user->idEcole;
        $school = $schoolId ? Ecole::withoutGlobalScopes()->find($schoolId) : null;
        $sources = $this->availableSources($user, $school, 'pay');
        $teacherIds = collect($data['rows'])->pluck('id_enseignant')->unique()->values()->all();
        $teachers = $this->teachersQuery($user, $schoolId)->whereIn('id_enseignant', $teacherIds)->get()->keyBy('id_enseignant');

        if ($teachers->count() !== count($teacherIds)) {
            throw ValidationException::withMessages(['rows' => 'Un ou plusieurs enseignants sélectionnés ne peuvent pas être payés par cette école.']);
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
                throw ValidationException::withMessages(["rows.$index.source" => 'Vous n’avez pas la permission de payer cette source.']);
            }

            $enseignant = $teachers->get((int) $inputRow['id_enseignant']);
            $month = (int) $inputRow['mois'];
            $year = (int) $inputRow['annee'];
            $filters = ['mois' => str_pad((string) $month, 2, '0', STR_PAD_LEFT), 'annee' => (string) $year, 'source' => $source];
            $row = $this->salaryRow($enseignant, $filters, $this->periodBounds($year, $month));

            if ($row['amount_due'] <= 0 || $row['remaining'] <= 0) {
                throw ValidationException::withMessages(["rows.$index.montant_verse" => 'Aucun reste à payer pour une des lignes sélectionnées.']);
            }

            if ($amount > $row['remaining']) {
                throw ValidationException::withMessages(["rows.$index.montant_verse" => 'Un montant saisi dépasse le reste à payer.']);
            }

            $mainSource ??= $source;
            $payments[] = [$enseignant, $filters, $row, $amount];
        }

        if (empty($payments)) {
            throw ValidationException::withMessages(['rows' => 'Veuillez saisir au moins un montant à payer.']);
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

        return response()->json(['success' => true, 'count' => count($payments), 'total' => $total]);
    }
}
