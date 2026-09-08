<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\EleveController as WebEleveController;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Ecole;
use App\Models\Eleve;
use App\Models\Paiement;
use App\Models\ParentModel;
use App\Models\Planification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EleveController extends WebEleveController
{
    public function inscriptionOptions(Request $request)
    {
        $idEcole = session('idEcole');
        $classes = Classe::where('idEcole', $idEcole)->orderBy('nom_classe')->get();
        $classeIds = $classes->pluck('id_classe');
        $planificationRequired = $this->schoolRequiresPlanification();

        return response()->json([
            'classes' => $classes,
            'annees' => AnneeScolaire::orderByDesc('id_anneeScolaire')->get(),
            'parents' => ParentModel::where('idEcole', $idEcole)->orderBy('nom_prenom_parent')->get(),
            'planifications' => Planification::whereIn('id_classe', $classeIds)->orderBy('motif')->get(),
            'planification_required' => $planificationRequired,
            'planification_label' => $planificationRequired ? 'Planification' : 'Coopérative',
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('inscriptions_inscrire');
        $data = $request->validate([
            'prenom_eleve' => 'required',
            'nom_eleve' => 'required',
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'genre_eleve' => ['required', Rule::in(['Masculin', 'Féminin'])],
            'date_naissance' => ['nullable', 'date_format:Y-m-d'],
            'lieu_naiss' => 'nullable',
            'adresse_eleve' => 'nullable',
            'cas_social' => 'nullable',
            'mode_paiement' => 'nullable',
            'date_inscription' => ['nullable', 'date_format:Y-m-d'],
            'matricule' => 'nullable|string|max:50',
            'image' => 'nullable|image|max:5120',
            'parent_id' => 'nullable|exists:parents,id_parent',
            'lien_parent' => 'nullable|string|max:100',
            'informer' => 'nullable|string|in:Oui,Non',
            'id_planification' => [$this->schoolRequiresPlanification() ? 'required' : 'nullable', 'integer', 'exists:planification,id_planification'],
        ]);

        Classe::where('idEcole', session('idEcole'))->findOrFail($data['id_classe']);

        $planificationId = $data['id_planification'] ?? null;
        if ($planificationId) {
            Planification::where('id_classe', $data['id_classe'])
                ->where('id_annee', $data['id_annee'])
                ->findOrFail($planificationId);
        }

        $eleve = DB::transaction(function () use ($request, $data, $planificationId) {
            $eleve = new Eleve();
            $eleve->prenom_eleve = $data['prenom_eleve'];
            $eleve->nom_eleve = $data['nom_eleve'];
            $eleve->date_naissance = $this->validDateOrNull($data['date_naissance'] ?? null, 'date_naissance');
            $eleve->lieu_naiss = ($data['lieu_naiss'] ?? null) ?: 'Non renseigné';
            $eleve->adresse_eleve = $data['adresse_eleve'] ?? null;
            $eleve->id_classe = $data['id_classe'];
            $eleve->id_annee = $data['id_annee'];
            $eleve->genre_eleve = $data['genre_eleve'];
            $eleve->matricule = $this->normalizeMatricule($data['matricule'] ?? null) ?: $this->generateMatricule($data);
            $eleve->date_inscription = $data['date_inscription'] ?? now()->toDateString();
            $eleve->image = $this->storeImage($request);
            $eleve->cas_social = ($data['cas_social'] ?? null) ?: 'normal';
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

            return $eleve;
        });

        return response()->json($eleve->fresh('classe'), 201);
    }

    private function schoolRequiresPlanification(): bool
    {
        $ecole = Ecole::withoutGlobalScopes()->find(session('idEcole'));
        $statut = Str::lower(Str::ascii((string) ($ecole->statut ?? '')));

        return $statut !== 'public';
    }

    private function validDateOrNull(?string $value, string $field): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)
            || !checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            throw ValidationException::withMessages([
                $field => 'La date de naissance est invalide.',
            ]);
        }

        return $value;
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
        $this->authorizePermission('eleves_modification');
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
            'matricule' => ($data['matricule'] ?? null) ?: $eleve->matricule,
            'genre_eleve' => $data['genre_eleve'],
            'date_naissance' => $data['date_naissance'] ?? null,
            'lieu_naiss' => ($data['lieu_naiss'] ?? null) ?: 'Non renseigné',
            'adresse_eleve' => $data['adresse_eleve'] ?? null,
            'cas_social' => ($data['cas_social'] ?? null) ?: 'normal',
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
        $this->authorizePermission('eleves_supprimer');
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
        $this->authorizePermission('eleves_modification');
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
        $this->authorizePermission('eleves_modification');
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
