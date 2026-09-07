<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\FinanceController as WebFinanceController;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Classe;
use App\Models\Decaissement;
use App\Models\Ecole;
use App\Models\Encaissement;
use App\Models\Paiement;
use App\Models\Trimestre;
use App\Rules\MaliPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class FinanceController extends WebFinanceController
{
    public function paiements(Request $request)
    {
        $idEcole = session('idEcole');
        $this->ensurePermission('paiements_apercu');

        $paiements = Paiement::with(['eleve', 'classe', 'echeance'])
            ->where('idEcole', $idEcole)
            ->when($request->filled('id_classe'), fn ($q) => $q->where('id_classe', $request->id_classe))
            ->when($request->filled('id_annee'), fn ($q) => $q->where('id_annee', $request->id_annee))
            ->orderBy('date_paiement', 'desc')
            ->paginate(20);

        $ecole = Ecole::find($idEcole);

        return response()->json([
            'paiements' => $paiements,
            'classes' => Classe::where('idEcole', $idEcole)->orderBy('nom_classe')->get(),
            'annees' => AnneeScolaire::orderByDesc('id_anneeScolaire')->get(),
            'trimestres' => Trimestre::withoutGlobalScopes()
                ->where(fn ($q) => $q->where('id_ecole', $idEcole)->orWhereNull('id_ecole'))
                ->orderBy('id_trimestre')
                ->get(),
            'caisse' => Caisse::where('id_ecole', $idEcole)->where('status', 1)->first(),
            'is_public_school' => $this->isPublicSchool($ecole),
            'next_reference' => $this->nextPaiementReference(),
        ]);
    }

    public function storePaiement(Request $request)
    {
        $this->ensurePermission('paiements_faire');
        if ($request->filled('telephone')) {
            $request->merge(['telephone' => MaliPhone::normalize($request->input('telephone'))]);
        }

        $data = $request->validate([
            'echeance_id' => 'required|exists:echeances_paiement,id',
            'date_paiement' => 'required|date',
            'motif' => 'nullable|string|max:100',
            'montant_paye' => 'required|numeric|min:1',
            'mode_reglement' => 'required|string|max:40',
            'parent_id' => 'nullable|exists:parents,id_parent',
            'nom_payeur' => 'nullable|string|max:100',
            'telephone' => ['nullable', 'string', 'max:20', new MaliPhone()],
        ]);

        $paiement = $this->paiementService->encaisser($data);

        return response()->json($paiement->fresh(['eleve', 'classe']), 201);
    }

    public function updatePaiement(Request $request, $id)
    {
        $this->ensurePermission('paiements_faire');
        $idEcole = session('idEcole');
        $paiement = Paiement::where('idEcole', $idEcole)->findOrFail($id);

        $data = $request->validate([
            'reference' => 'required|string|max:100',
            'date_paiement' => 'required|date',
            'motif' => 'required|string|max:50',
            'id_classe' => 'required|exists:classe,id_classe',
            'id_annee' => 'required|exists:anneescolaire,id_anneeScolaire',
        ]);

        Classe::where('idEcole', $idEcole)->findOrFail($data['id_classe']);

        $paiement->update([
            'reference' => trim($data['reference']),
            'date_paiement' => $data['date_paiement'],
            'motif' => trim($data['motif']),
            'id_classe' => $data['id_classe'],
            'id_annee' => $data['id_annee'],
        ]);

        return response()->json($paiement->fresh(['eleve', 'classe']));
    }

    public function cancelPaiement(Request $request, $id)
    {
        $this->ensurePermission('paiements_faire');
        $data = $request->validate(['motif_annulation' => 'required|string|max:255']);

        $paiement = Paiement::where('idEcole', session('idEcole'))->findOrFail($id);
        $this->paiementService->cancelPayment($paiement, $data['motif_annulation']);

        return response()->json(['success' => true]);
    }

    public function caisse()
    {
        $this->ensurePermission('caisses_apercu');
        $idEcole = session('idEcole');
        $caisse = Caisse::where('id_ecole', $idEcole)->first();
        $annees = AnneeScolaire::orderByDesc('id_anneeScolaire')->get();

        if (!$caisse) {
            return response()->json(['caisse' => null, 'mouvements' => [], 'annees' => $annees]);
        }

        $encaissements = Encaissement::where('id_caisse', $caisse->id_caisse)->get()->map(function ($item) {
            $item->type = 'RECETTE';
            $item->date = $item->date_encaissement;
            $item->montant = $item->montant_encaissement;
            $item->motif = $item->motif_encaissement;
            return $item;
        });

        $decaissements = Decaissement::with('utilisateur')->where('id_caisse', $caisse->id_caisse)->get()->map(function ($item) {
            $item->type = 'DEPENSE';
            $item->date = $item->date_decaissement;
            $item->montant = $item->montant_decaissement;
            $item->motif = $item->motif_decaissement;
            return $item;
        });

        return response()->json([
            'caisse' => $caisse,
            'mouvements' => $encaissements->concat($decaissements)->sortByDesc('date')->values(),
            'annees' => $annees,
        ]);
    }

    public function storeEncaissement(Request $request)
    {
        $data = $request->validate([
            'id_caisse' => 'required|exists:caisse,id_caisse',
            'id_annee_scolaire' => 'required|exists:anneescolaire,id_anneeScolaire',
            'type_operation' => 'required|string|max:255',
            'date_encaissement' => 'required|date',
            'motif_encaissement' => 'required|string|max:255',
            'montant_encaissement' => 'required|numeric|min:1',
        ]);

        $caisse = $this->getOwnedCaisse($data['id_caisse']);

        $encaissement = DB::transaction(function () use ($data, $caisse) {
            $encaissement = Encaissement::create([
                'type_operation' => $data['type_operation'],
                'date_encaissement' => $data['date_encaissement'],
                'motif_encaissement' => $data['motif_encaissement'],
                'montant_encaissement' => $data['montant_encaissement'],
                'id_annee_scolaire' => $data['id_annee_scolaire'],
                'id_caisse' => $caisse->id_caisse,
                'idUtilisateur' => Auth::id(),
            ]);

            $caisse->increment('montant_net', $data['montant_encaissement']);

            return $encaissement;
        });

        return response()->json($encaissement, 201);
    }

    public function storeDecaissement(Request $request)
    {
        $this->ensurePermission('decaissements_creation');
        $data = $request->validate([
            'id_caisse' => 'required|exists:caisse,id_caisse',
            'id_annee_scolaire' => 'required|exists:anneescolaire,id_anneeScolaire',
            'date_decaissement' => 'required|date',
            'motif_decaissement' => 'required|string|max:255',
            'montant_decaissement' => 'required|numeric|min:1',
        ]);

        $caisse = $this->getOwnedCaisse($data['id_caisse']);
        $shouldValidateNow = $this->canValidateDecaissement(Auth::user());

        if ($shouldValidateNow && (float) $caisse->montant_net < (float) $data['montant_decaissement']) {
            return response()->json(['message' => 'Solde insuffisant pour effectuer cette sortie.'], 422);
        }

        $decaissement = DB::transaction(function () use ($data, $caisse, $shouldValidateNow) {
            $decaissement = Decaissement::create(array_filter([
                'date_decaissement' => $data['date_decaissement'],
                'motif_decaissement' => $data['motif_decaissement'],
                'montant_decaissement' => $data['montant_decaissement'],
                'id_annee_scolaire' => $data['id_annee_scolaire'],
                'id_caisse' => $caisse->id_caisse,
                'idUtilisateur' => Auth::id(),
                'valide' => $shouldValidateNow ? 1 : 0,
                'validated_by' => $shouldValidateNow && Schema::hasColumn('decaissement', 'validated_by') ? Auth::id() : null,
                'validated_at' => $shouldValidateNow && Schema::hasColumn('decaissement', 'validated_at') ? now() : null,
            ], fn ($value) => $value !== null));

            if ($shouldValidateNow) {
                $caisse->decrement('montant_net', $data['montant_decaissement']);
            }

            return $decaissement;
        });

        if (!$shouldValidateNow) {
            $this->notifyDecaissementValidators($decaissement);
        }

        return response()->json($decaissement, 201);
    }

    public function validateDecaissement($id)
    {
        if (!$this->canValidateDecaissement(Auth::user())) {
            abort(403, 'Permission insuffisante.');
        }
        $idEcole = session('idEcole');

        DB::transaction(function () use ($id, $idEcole) {
            $decaissement = Decaissement::with('caisse')
                ->whereHas('caisse', fn ($query) => $query->where('id_ecole', $idEcole))
                ->lockForUpdate()
                ->findOrFail($id);

            if ((int) $decaissement->valide === 1) {
                return;
            }

            $caisse = Caisse::where('id_ecole', $idEcole)->whereKey($decaissement->id_caisse)->lockForUpdate()->firstOrFail();

            if ((float) $caisse->montant_net < (float) $decaissement->montant_decaissement) {
                throw ValidationException::withMessages([
                    'decaissement' => 'Solde insuffisant dans la caisse pour valider cette dépense.',
                ]);
            }

            $caisse->montant_net = (float) $caisse->montant_net - (float) $decaissement->montant_decaissement;
            $caisse->save();

            $updates = ['valide' => 1];
            if (Schema::hasColumn('decaissement', 'validated_by')) {
                $updates['validated_by'] = Auth::id();
            }
            if (Schema::hasColumn('decaissement', 'validated_at')) {
                $updates['validated_at'] = now();
            }
            $decaissement->update($updates);
        });

        return response()->json(['success' => true]);
    }

    // contexteEleve() is already JSON in the web controller and reused unmodified via inheritance.
}
