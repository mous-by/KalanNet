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

        $rows = null;
        if ($request->filled('id_classe') && $request->filled('id_annee')) {
            Classe::where('idEcole', $idEcole)->findOrFail($request->integer('id_classe'));
            $rows = $this->legacyPaymentRows(
                $request->integer('id_classe'),
                $request->integer('id_annee'),
                (string) $request->input('type_planification', '')
            )->map(fn ($row) => [
                'eleve' => [
                    'id_eleve' => $row->eleve->id_eleve,
                    'prenom_eleve' => $row->eleve->prenom_eleve,
                    'nom_eleve' => $row->eleve->nom_eleve,
                ],
                'id_planification' => $row->planification->id_planification,
                'motif' => $row->planification->motif,
                'montant_total' => $row->montant_total,
                'montant_deja_paye' => $row->montant_deja_paye,
                'reste_a_payer' => $row->reste_a_payer,
                'parents' => $row->parents->map(fn ($parent) => [
                    'id_parent' => $parent->id_parent,
                    'nom_prenom_parent' => $parent->nom_prenom_parent,
                    'telephone_parent' => $parent->telephone_parent,
                ]),
            ]);
        }

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
            'rows' => $rows,
        ]);
    }

    public function storePaiementsGroupes(Request $request)
    {
        $this->ensurePermission('paiements_faire');

        $data = $request->validate([
            'id_classe' => 'required|exists:classe,id_classe',
            'id_annee' => 'required|exists:anneescolaire,id_anneeScolaire',
            'id_trimestre' => 'required|exists:trimestre,id_trimestre',
            'date_paiement' => 'required|date',
            'type_planification' => 'nullable|string|max:40',
            'rows' => 'required|array|min:1',
            'rows.*.id_eleve' => 'required|integer',
            'rows.*.id_planification' => 'required|integer',
            'rows.*.motif' => 'nullable|string|max:255',
            'rows.*.montant_recu' => 'required|numeric|min:1',
            'rows.*.parent_id' => 'nullable',
            'rows.*.autre_personne_nom' => 'nullable|string|max:100',
            'rows.*.autre_personne_telephone' => 'nullable|string|max:20',
        ]);

        $idEcole = (int) session('idEcole');
        Classe::where('idEcole', $idEcole)->findOrFail($data['id_classe']);
        $createdIds = [];
        $errors = [];

        foreach ($data['rows'] as $row) {
            $eleveId = (int) $row['id_eleve'];

            try {
                $paiement = DB::transaction(function () use ($data, $row, $eleveId, $idEcole) {
                    $eleve = \App\Models\Eleve::where('id_ecole', $idEcole)
                        ->where('id_classe', $data['id_classe'])
                        ->where('id_annee', $data['id_annee'])
                        ->where('etat_dossier', 0)
                        ->findOrFail($eleveId);

                    $planification = \App\Models\Planification::where('id_classe', $data['id_classe'])
                        ->where('id_annee', $data['id_annee'])
                        ->findOrFail((int) $row['id_planification']);

                    $montantRecu = (float) $row['montant_recu'];
                    $reste = $this->legacyRemainingForPlan($eleveId, $planification, (int) $data['id_annee'], $data['date_paiement']);
                    if ($montantRecu > $reste) {
                        throw ValidationException::withMessages(['montant_recu' => 'Le montant reçu dépasse le reste à payer.']);
                    }

                    $payer = $this->resolveLegacyPayer(
                        $eleve,
                        $row['parent_id'] ?? null,
                        $row['autre_personne_nom'] ?? null,
                        $row['autre_personne_telephone'] ?? null
                    );

                    $caisse = Caisse::where('id_ecole', $idEcole)
                        ->where('status', 1)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $motifInput = trim((string) ($row['motif'] ?? ''));
                    $paiement = Paiement::create([
                        'montant' => $montantRecu,
                        'montant_paye' => $montantRecu,
                        'date_paiement' => $data['date_paiement'],
                        'mode_reglement' => 'especes',
                        'statut' => 'valide',
                        'motif' => $motifInput !== '' ? $motifInput : $planification->motif,
                        'id_classe' => $data['id_classe'],
                        'id_annee' => $data['id_annee'],
                        'id_trimestre' => $data['id_trimestre'],
                        'reference' => $this->referenceService->nextReference(),
                        'idEcole' => $idEcole,
                        'id_eleve' => $eleve->id_eleve,
                        'parent' => $payer['parent'],
                        'nom_payeur' => $payer['nom_payeur'],
                        'telephone' => $payer['telephone'],
                        'id_utilisateur' => Auth::id(),
                        'id_caisse' => $caisse->id_caisse,
                        'numero_recu' => $this->referenceService->nextReceiptNumber($idEcole),
                        'id_planification' => $planification->id_planification,
                    ]);

                    $encaissement = Encaissement::create([
                        'paiement_id' => $paiement->id_paiement,
                        'type_operation' => 'Paiement eleve',
                        'date_encaissement' => $data['date_paiement'],
                        'motif_encaissement' => $paiement->motif,
                        'montant_encaissement' => $montantRecu,
                        'statut' => 'valide',
                        'id_annee_scolaire' => $data['id_annee'],
                        'id_caisse' => $caisse->id_caisse,
                        'idUtilisateur' => Auth::id(),
                    ]);

                    $paiement->encaissement_id = $encaissement->id_encaissement;
                    $paiement->save();

                    \App\Models\LignePaiementEleve::create([
                        'id_classe' => $data['id_classe'],
                        'id_annee' => $data['id_annee'],
                        'id_paiement' => $paiement->id_paiement,
                        'id_eleve' => $eleve->id_eleve,
                        'id_trimestre' => $data['id_trimestre'],
                        'idEcole' => $idEcole,
                    ]);

                    $caisse->montant_net = (float) $caisse->montant_net + $montantRecu;
                    $caisse->save();

                    return $paiement;
                });

                $createdIds[] = $paiement->id_paiement;
            } catch (ValidationException $exception) {
                $errors[] = "Élève #{$eleveId}: " . collect($exception->errors())->flatten()->first();
            } catch (\Throwable) {
                $errors[] = "Élève #{$eleveId}: paiement non enregistré.";
            }
        }

        return response()->json([
            'created' => count($createdIds),
            'created_payment_ids' => $createdIds,
            'errors' => $errors,
        ], $createdIds ? 201 : 422);
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
        $this->ensurePermission('encaissement_creation');
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
