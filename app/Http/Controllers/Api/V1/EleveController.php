<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\EleveController as WebEleveController;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Paiement;
use Illuminate\Http\Request;

class EleveController extends WebEleveController
{
    public function index(Request $request)
    {
        $idEcole = session('idEcole');

        $query = Eleve::where('id_ecole', $idEcole)
            ->where('etat_dossier', 0)
            ->with('classe');

        if ($request->filled('id_classe')) {
            $query->where('id_classe', $request->integer('id_classe'));
        }

        if ($request->filled('id_annee')) {
            $query->where('id_annee', $request->integer('id_annee'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom_eleve', 'LIKE', "%{$search}%")
                    ->orWhere('prenom_eleve', 'LIKE', "%{$search}%")
                    ->orWhere('matricule', 'LIKE', "%{$search}%");
            });
        }

        $eleves = $query->orderBy('prenom_eleve')->orderBy('nom_eleve')->paginate(30)->withQueryString();

        return response()->json($eleves);
    }

    public function show($id)
    {
        $user = request()->user();
        $query = Eleve::with(['classe', 'ecole', 'parents', 'plansPaiement.echeances'])
            ->where('id_ecole', session('idEcole'));

        if ($user?->droit === 'parent') {
            $parentId = $user->id_parent ?: $user->parent?->id_parent;
            if (!$parentId) {
                abort(403, 'Aucun profil parent n’est lié à ce compte.');
            }
            $query->whereHas('parents', fn ($parentQuery) => $parentQuery->where('parents.id_parent', $parentId));
        } elseif (!$this->canOpenStudentDossiers($user)) {
            abort(403, 'Permission insuffisante.');
        }

        $eleve = $query->findOrFail($id);
        $annee = AnneeScolaire::where('id_anneeScolaire', $eleve->id_annee)->first();
        $paiements = Paiement::where('idEcole', session('idEcole'))
            ->where('id_eleve', $eleve->id_eleve)
            ->latest('date_paiement')
            ->get();

        return response()->json([
            'eleve' => $eleve,
            'annee' => $annee,
            'paiements_recents' => $paiements->take(8)->values(),
            'payment_summary' => $this->studentPaymentSummary($eleve, $paiements),
            'echeances_resume' => $this->studentEcheancesResume($eleve, $paiements),
            'evaluations_recentes' => $this->studentRecentEvaluations($eleve),
            'moyennes' => $this->studentMoyennes($eleve),
            'transferts' => $this->studentTransfers($eleve),
        ]);
    }

    public function update(Request $request, $id)
    {
        $eleve = Eleve::where('id_ecole', session('idEcole'))->findOrFail($id);
        $data = $request->validate([
            'prenom_eleve' => 'required|string|max:255',
            'nom_eleve' => 'required|string|max:255',
            'matricule' => 'nullable|string|max:50',
            'genre_eleve' => 'required|string|in:Masculin,Féminin',
            'date_naissance' => 'nullable|date',
            'lieu_naiss' => 'nullable|string|max:255',
            'adresse_eleve' => 'nullable|string|max:255',
            'cas_social' => 'nullable|string|max:255',
            'mode_paiement' => 'nullable|string|max:255',
            'statut_paiement' => 'nullable|string|in:normal,subventionne,boursier,gratuit',
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'date_inscription' => 'nullable|date',
        ]);

        Classe::where('idEcole', session('idEcole'))->findOrFail($data['id_classe']);

        $eleve->update([
            'prenom_eleve' => $data['prenom_eleve'],
            'nom_eleve' => $data['nom_eleve'],
            'matricule' => $data['matricule'] ?: $eleve->matricule,
            'genre_eleve' => $data['genre_eleve'],
            'date_naissance' => $data['date_naissance'] ?? null,
            'lieu_naiss' => $data['lieu_naiss'] ?: 'Non renseigné',
            'adresse_eleve' => $data['adresse_eleve'] ?? null,
            'cas_social' => $data['cas_social'] ?: 'normal',
            'mode_paiement' => $data['mode_paiement'] ?? null,
            'statut_paiement' => $data['statut_paiement'] ?? 'normal',
            'id_classe' => $data['id_classe'],
            'id_annee' => $data['id_annee'],
            'date_inscription' => $data['date_inscription'] ?? $eleve->date_inscription,
        ]);

        return response()->json($eleve->fresh('classe'));
    }

    public function destroy($id)
    {
        $eleve = Eleve::where('id_ecole', session('idEcole'))->findOrFail($id);
        $eleve->etat_dossier = 2;
        $eleve->save();

        return response()->json(['success' => true]);
    }

    public function cartes(Request $request)
    {
        $idEcole = session('idEcole');
        $eleves = collect();

        if ($request->filled('id_classe') && $request->filled('id_annee')) {
            $eleves = $this->filteredEleves($request)->get();
        }

        return response()->json([
            'classes' => Classe::where('idEcole', $idEcole)->orderBy('nom_classe')->get(),
            'annees' => AnneeScolaire::orderByDesc('id_anneeScolaire')->get(),
            'eleves' => $eleves,
        ]);
    }

    public function transfer(Request $request, $id)
    {
        $eleve = Eleve::where('id_ecole', session('idEcole'))->where('etat_dossier', 0)->findOrFail($id);
        $data = $request->validate([
            'motif' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'travail' => 'nullable|string|max:255',
            'conduite' => 'required|string|max:255',
        ]);

        if (!\Illuminate\Support\Facades\Schema::hasTable('transfert')) {
            return response()->json(['message' => 'La table des transferts n’est pas disponible.'], 422);
        }

        $transferId = DB::transaction(function () use ($eleve, $data) {
            $transferId = DB::table('transfert')->insertGetId([
                'id_eleve' => $eleve->id_eleve,
                'id_ecole' => session('idEcole'),
                'motif' => $data['motif'],
                'destination' => $data['destination'],
                'travail' => $data['travail'] ?? null,
                'conduite' => $data['conduite'],
                ...$this->transferOptionalColumns(['date_transfert' => now()]),
            ]);

            $eleve->etat_dossier = 1;
            $eleve->save();

            return $transferId;
        });

        return response()->json(['success' => true, 'id_transfert' => $transferId], 201);
    }

    public function reintegrate(Request $request, $id)
    {
        $eleve = Eleve::where('id_ecole', session('idEcole'))->where('etat_dossier', 1)->findOrFail($id);
        $data = $request->validate([
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'motif_retour' => 'nullable|string|max:255',
        ]);

        Classe::where('idEcole', session('idEcole'))->findOrFail($data['id_classe']);

        DB::transaction(function () use ($eleve, $data) {
            $eleve->update([
                'etat_dossier' => 0,
                'id_classe' => $data['id_classe'],
                'id_annee' => $data['id_annee'],
            ]);

            if (\Illuminate\Support\Facades\Schema::hasTable('transfert')) {
                $latestTransfer = DB::table('transfert')
                    ->where('id_eleve', $eleve->id_eleve)
                    ->where('id_ecole', session('idEcole'))
                    ->when(\Illuminate\Support\Facades\Schema::hasColumn('transfert', 'date_retour'), fn ($query) => $query->whereNull('date_retour'))
                    ->orderByDesc('id_transfert')
                    ->first();

                if ($latestTransfer) {
                    $returnColumns = $this->transferOptionalColumns([
                        'date_retour' => now(),
                        'motif_retour' => $data['motif_retour'] ?: 'Réintégration',
                        'retour_effectue_par' => request()->user()->idUtilisateur,
                    ]);

                    if (!empty($returnColumns)) {
                        DB::table('transfert')->where('id_transfert', $latestTransfer->id_transfert)->update($returnColumns);
                    }
                }
            }
        });

        return response()->json($eleve->fresh('classe'));
    }
}
