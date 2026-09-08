<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ConfigurationController as WebConfigurationController;
use App\Models\Academie;
use App\Models\AnneeScolaire;
use App\Models\Cap;
use App\Models\ClasseOfficielle;
use App\Models\Controle;
use App\Models\Ecole;
use App\Models\Enseignant;
use App\Models\Note;
use App\Models\ParentModel;
use App\Models\Permission;
use App\Models\User;
use App\Support\SchoolOrderAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Covers the higher-value config screens for a mobile client: écoles,
 * années scolaires, utilisateurs, permissions. Académies/CAP/types de
 * notes/classes officielles/statuts de contrôle are rarely-changed
 * reference data managed at setup time — left for a later pass.
 */
class ConfigurationController extends WebConfigurationController
{
    public function ecoles(Request $request)
    {
        $user = $request->user();
        $this->authorizeAnyPermission($user, ['ecoles_apercu']);

        $idEcole = session('idEcole');
        $search = $request->get('search');

        $ecoles = $this->ecoleScope(Ecole::with(['academieRef', 'capRef']), $user, $idEcole)
            ->when($search, fn ($q) => $q->where(fn ($inner) => $inner
                ->where('nomEcole', 'like', "%{$search}%")
                ->orWhere('typeEcole', 'like', "%{$search}%")))
            ->orderBy('nomEcole')
            ->paginate(15)
            ->withQueryString();

        return response()->json($ecoles);
    }

    public function storeEcole(Request $request)
    {
        $this->authorizeSupAdminOnly();

        $data = $this->validateEcole($request);
        $data['logoEcole'] = $this->storeEcoleLogo($request);
        $this->hydrateEcoleLegacyLabels($data);

        $ecole = Ecole::create($data);
        $this->activateInitialSubscription($request, $ecole);

        return response()->json($ecole, 201);
    }

    public function updateEcole(Request $request, int $id)
    {
        $this->authorizeSupAdminOnly();

        $ecole = Ecole::findOrFail($id);
        $this->authorizeEcoleMutation($ecole);

        $data = $this->validateEcole($request);
        $data['logoEcole'] = $this->storeEcoleLogo($request, $ecole->logoEcole);
        $this->hydrateEcoleLegacyLabels($data);

        $ecole->update($data);
        $this->activateInitialSubscription($request, $ecole);

        return response()->json($ecole->fresh());
    }

    public function destroyEcole(int $id)
    {
        $this->authorizeSupAdminOnly();

        $ecole = Ecole::withCount(['utilisateurs'])->findOrFail($id);
        $this->authorizeEcoleMutation($ecole);

        if ($ecole->utilisateurs_count > 0) {
            return response()->json(['message' => 'Impossible de supprimer une école liée à des utilisateurs.'], 422);
        }

        $ecole->delete();

        return response()->json(['success' => true]);
    }

    public function annees(Request $request)
    {
        $user = $request->user();
        $this->authorizeAnyPermission($user, ['annees_scolaires_apercu']);

        $idEcole = session('idEcole');
        $this->ensureCurrentAcademicYearExists();

        $annees = $this->anneeScope(AnneeScolaire::query(), $user, $idEcole)
            ->when($request->get('search'), fn ($q, $search) => $q->where('annee', 'like', "%{$search}%"))
            ->orderByDesc('date_debut')
            ->orderByDesc('id_anneeScolaire')
            ->paginate(15)
            ->withQueryString();

        return response()->json([
            'annees' => $annees,
            'annee_en_cours' => $this->currentAcademicYear(),
        ]);
    }

    public function storeAnnee(Request $request)
    {
        $user = $request->user();
        $this->authorizeAnyPermission($user, ['annees_scolaires_apercu']);

        $data = $request->validate([
            'annee' => 'required|string|max:100',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
        ]);

        $data['id_ecole'] = session('idEcole') ?: $user->idEcole;

        $annee = AnneeScolaire::create($data);

        return response()->json($annee, 201);
    }

    public function utilisateurs(Request $request)
    {
        $user = $request->user();
        if ($user->droit !== 'Admin') {
            $this->authorizeAnyPermission($user, ['utilisateurs_apercu', 'administrateur_tabsConfig', 'enseignants_tabsConfig', 'parents_tabsConfig', 'dae_apercu', 'dcap_apercu']);
        }

        $idEcole = session('idEcole');
        $search = $request->get('search');

        $utilisateurs = $this->userScope(
            User::with(['ecole', 'academie', 'cap', 'enseignant.ecole', 'parent.ecole'])->withCount('permissions'),
            $user,
            $idEcole
        )
            ->when($search, fn ($q) => $q->where(fn ($inner) => $inner
                ->where('nomPrenom', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('nomPrenom')
            ->get();

        return response()->json(['data' => $utilisateurs]);
    }

    public function storeUtilisateur(Request $request)
    {
        $type = (int) $request->input('type_utilisateur', 1);
        $data = $this->validateUtilisateurByType($request, $type);
        $authUser = $request->user();
        $idEcole = session('idEcole');

        $this->authorizeUserCreation($authUser, $type, $data);
        $this->validateManagedOrdersForUser($authUser, $type, $data, $idEcole);

        $createdUser = DB::transaction(function () use ($type, $data, $authUser, $idEcole) {
            $password = $data['pwd'] ?? $this->generatePassword();
            $payload = ['pwd' => Hash::make($password), 'statut' => 1, 'image' => 'default.png'];

            if ($type === 0) {
                $enseignant = $this->enseignantsScope(Enseignant::query(), $authUser, $idEcole)
                    ->where('id_enseignant', $data['id_enseignant'])
                    ->firstOrFail();
                $this->ensureUniqueLinkedUser('id_enseignant', $enseignant->id_enseignant, $enseignant->id_ecole);
                $payload += [
                    'id_enseignant' => $enseignant->id_enseignant,
                    'nomPrenom' => $enseignant->nom_prenom_enseignant,
                    'email' => $enseignant->email_enseignant,
                    'fonction' => 'enseignant',
                    'telephone' => $enseignant->telephone_enseignant,
                    'genre' => $enseignant->genre_enseignant,
                    'droit' => 'enseignant',
                    'idEcole' => $enseignant->id_ecole,
                    'image' => $enseignant->avatar_enseignant ?: 'default.png',
                ];
            } elseif ($type === 2) {
                $parent = $this->parentsScope(ParentModel::query(), $authUser, $idEcole)
                    ->where('id_parent', $data['id_parent'])
                    ->firstOrFail();
                $this->ensureUniqueLinkedUser('id_parent', $parent->id_parent);
                $payload += [
                    'id_parent' => $parent->id_parent,
                    'nomPrenom' => $parent->nom_prenom_parent,
                    'email' => $parent->email_parent,
                    'fonction' => 'parent',
                    'telephone' => $parent->telephone_parent,
                    'genre' => $parent->genre,
                    'droit' => 'parent',
                    'idEcole' => $parent->idEcole,
                ];
            } elseif ($type === 3 || $type === 4) {
                $payload += [
                    'nomPrenom' => $data['nomPrenom'],
                    'email' => $data['email'],
                    'fonction' => $data['fonction'] ?? ($type === 3 ? 'DAE' : 'DCAP'),
                    'telephone' => $data['telephone'],
                    'genre' => $data['genre'],
                    'droit' => $type === 3 ? 'DAE' : 'DCAP',
                    'id_academie' => $type === 3 ? $data['id_academie'] : null,
                    'id_cap' => $type === 4 ? $data['id_cap'] : null,
                ];
            } else {
                $payload += [
                    'nomPrenom' => $data['nomPrenom'],
                    'email' => $data['email'],
                    'fonction' => $data['fonction'] ?? null,
                    'telephone' => $data['telephone'],
                    'genre' => $data['genre'],
                    'droit' => $data['droit'],
                    'idEcole' => $authUser->droit === 'SupAdmin' ? ($data['idEcole'] ?? null) : ($idEcole ?: $authUser->idEcole),
                    'managed_orders' => $data['droit'] === 'Gestionnaire' ? SchoolOrderAccess::normalizeMany($data['managed_orders'] ?? []) : null,
                ];
            }

            $user = User::create($payload);
            $this->syncDefaultPermissions($user, $type);

            return $user;
        });

        return response()->json($createdUser, 201);
    }

    public function updateUtilisateur(Request $request, int $id)
    {
        $authUser = $request->user();
        $idEcole = session('idEcole');

        $utilisateur = $this->userScope(User::query(), $authUser, $idEcole)->where('idUtilisateur', $id)->firstOrFail();
        $this->authorizeTargetUserEdit($authUser, $utilisateur);

        $type = $this->userFormType($utilisateur);
        $data = $this->validateUtilisateurByType($request, $type, $utilisateur);
        $this->validateManagedOrdersForUser($authUser, $type, $data, $idEcole);

        $payload = ['telephone' => $data['telephone'] ?? $utilisateur->telephone];

        if (!empty($data['pwd'])) {
            $payload['pwd'] = Hash::make($data['pwd']);
        }

        if ($type === 3 || $type === 4) {
            $payload += [
                'nomPrenom' => $data['nomPrenom'],
                'email' => $data['email'],
                'fonction' => $data['fonction'] ?? ($type === 3 ? 'DAE' : 'DCAP'),
                'genre' => $data['genre'],
                'droit' => $type === 3 ? 'DAE' : 'DCAP',
                'id_academie' => $type === 3 ? $data['id_academie'] : null,
                'id_cap' => $type === 4 ? $data['id_cap'] : null,
                'idEcole' => null,
            ];
        } elseif ($type === 1) {
            $payload += [
                'nomPrenom' => $data['nomPrenom'],
                'email' => $data['email'],
                'fonction' => $data['fonction'] ?? null,
                'genre' => $data['genre'],
                'droit' => $data['droit'],
                'idEcole' => $authUser->droit === 'SupAdmin' ? ($data['idEcole'] ?? null) : ($idEcole ?: $authUser->idEcole),
                'managed_orders' => $data['droit'] === 'Gestionnaire' ? SchoolOrderAccess::normalizeMany($data['managed_orders'] ?? []) : null,
            ];
        }

        $utilisateur->update($payload);

        return response()->json($utilisateur->fresh());
    }

    public function updateUserStatus(Request $request, int $id)
    {
        $authUser = $request->user();
        $idEcole = session('idEcole');

        if ($authUser->droit !== 'SupAdmin' && !$authUser->userHasPermission('utilisateurs_modification')) {
            abort(403, 'Permission insuffisante.');
        }

        $utilisateur = $this->userScope(User::query(), $authUser, $idEcole)->where('idUtilisateur', $id)->firstOrFail();
        $this->authorizeTargetUserGovernance($authUser, $utilisateur);

        if ($authUser->idUtilisateur === $utilisateur->idUtilisateur) {
            return response()->json(['message' => 'Vous ne pouvez pas désactiver votre propre compte.'], 422);
        }

        $data = $request->validate(['statut' => 'required|integer|in:0,1']);
        $utilisateur->update(['statut' => $data['statut']]);

        return response()->json($utilisateur->fresh());
    }

    public function destroyUtilisateur(int $id)
    {
        $authUser = request()->user();
        $idEcole = session('idEcole');

        $utilisateur = $this->userScope(User::query(), $authUser, $idEcole)->where('idUtilisateur', $id)->firstOrFail();
        $this->authorizeTargetUserGovernance($authUser, $utilisateur);

        if ($authUser->idUtilisateur === $utilisateur->idUtilisateur) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }

        if ($authUser->droit !== 'SupAdmin' && !$authUser->userHasPermission('utilisateurs_supprimer')) {
            abort(403);
        }

        DB::transaction(function () use ($utilisateur) {
            $utilisateur->permissions()->detach();
            DB::table('app_notifications')->where('user_id', $utilisateur->idUtilisateur)->delete();
            $utilisateur->delete();
        });

        return response()->json(['success' => true]);
    }

    public function editUserPermissions(int $id)
    {
        $authUser = request()->user();
        if (!in_array($authUser->droit, ['SupAdmin', 'Admin'], true)) {
            $this->authorizeAnyPermission($authUser, ['permissions_assigner', 'permission_assigner', 'dae_permission', 'dcap_permission']);
        }

        $idEcole = session('idEcole');
        $utilisateur = $this->userScope(User::with(['ecole', 'permissions']), $authUser, $idEcole)
            ->where('idUtilisateur', $id)
            ->firstOrFail();

        $this->authorizeTargetPermissionView($authUser, $utilisateur);

        $groupedPermissions = Permission::groupedByModule();
        $readOnly = !$this->canAssignPermissionsToTarget($authUser, $utilisateur);

        if ($utilisateur->droit === 'SupAdmin') {
            $all = collect($groupedPermissions)->flatten(1);
            $permissionIds = $all->pluck('id')->map(fn ($v) => (int) $v)->all();
        } else {
            $permissionIds = $utilisateur->permissions->pluck('id')->map(fn ($v) => (int) $v)->all();
        }

        return response()->json([
            'utilisateur' => $utilisateur,
            'grouped_permissions' => $groupedPermissions,
            'permission_ids' => $permissionIds,
            'read_only' => $readOnly,
        ]);
    }

    public function updateUserPermissions(Request $request, int $id)
    {
        $authUser = $request->user();
        if (!in_array($authUser->droit, ['SupAdmin', 'Admin'], true)) {
            $this->authorizeAnyPermission($authUser, ['permissions_assigner', 'permission_assigner', 'dae_permission', 'dcap_permission']);
        }

        $idEcole = session('idEcole');
        $utilisateur = $this->userScope(User::query(), $authUser, $idEcole)->where('idUtilisateur', $id)->firstOrFail();

        if (!$this->canAssignPermissionsToTarget($authUser, $utilisateur)) {
            abort(403);
        }

        $permissionIds = collect($request->input('permissions', []))->map(fn ($v) => (int) $v)->filter()->unique()->values()->all();
        $validPermissionIds = Permission::whereIn('id', $permissionIds)->pluck('id')->all();
        $utilisateur->permissions()->sync($validPermissionIds);

        $orders = SchoolOrderAccess::normalizeMany($request->input('managed_orders', []));
        if ($utilisateur->droit === 'Gestionnaire' && SchoolOrderAccess::isComplex($utilisateur->ecole)) {
            if (empty($orders)) {
                throw ValidationException::withMessages(['managed_orders' => 'Veuillez sélectionner au moins un ordre d’enseignement pour ce gestionnaire du complexe.']);
            }
            $utilisateur->managed_orders = $orders;
            $utilisateur->save();
        } elseif ($utilisateur->managed_orders) {
            $utilisateur->managed_orders = null;
            $utilisateur->save();
        }

        return response()->json($utilisateur->fresh('permissions'));
    }

    public function permissions(Request $request)
    {
        $this->authorizeAnyPermission($request->user(), ['permissions_apercu', 'permission_voir']);

        $search = $request->get('search');
        $permissions = Permission::query()
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($p) => Permission::canonicalName($p->name))
            ->map(function ($duplicates, $canonicalName) {
                $permission = $duplicates->sortBy('id')->first();
                $permission->name = $canonicalName;
                $permission->users_count = $duplicates->sum('users_count');
                return $permission;
            })
            ->filter(fn ($p) => !$search || str_contains(Permission::normalizeName($p->name), Permission::normalizeName($search)))
            ->sortBy('name')
            ->values();

        return response()->json(['data' => $permissions]);
    }

    public function storePermission(Request $request)
    {
        $this->authorizeSupAdminOnly();

        $data = $request->validate(['name' => 'required|string|max:100|unique:permissions,name']);
        $canonicalName = Permission::canonicalName($data['name']);

        if (Permission::query()->get()->contains(fn ($p) => Permission::canonicalName($p->name) === $canonicalName)) {
            throw ValidationException::withMessages(['name' => 'Cette permission existe déjà dans le référentiel sous un nom équivalent.']);
        }

        $permission = Permission::create(['name' => $canonicalName]);

        return response()->json($permission, 201);
    }

    public function academies(Request $request)
    {
        $user = $request->user();
        $this->authorizeAnyPermission($user, ['academies_apercu']);

        $idEcole = session('idEcole') ?: $user->idEcole;
        $ecole = !in_array($user->droit, ['SupAdmin', 'DAE', 'DCAP']) && $idEcole ? Ecole::find($idEcole) : null;

        $academies = Academie::withCount(['caps', 'ecoles'])
            ->when($user->droit === 'DAE' && $user->id_academie, fn ($q) => $q->where('id_academie', $user->id_academie))
            ->when($ecole?->id_academie, fn ($q) => $q->where('id_academie', $ecole->id_academie))
            ->when($request->get('search'), fn ($q, $search) => $q->where('nom_academie', 'like', "%{$search}%"))
            ->orderBy('nom_academie')
            ->paginate(15)
            ->withQueryString();

        return response()->json($academies);
    }

    public function storeAcademie(Request $request)
    {
        $this->authorizeSupAdminOnly();

        $academie = Academie::create($this->validateAcademie($request));

        return response()->json($academie, 201);
    }

    public function updateAcademie(Request $request, int $id)
    {
        $this->authorizeSupAdminOnly();

        $academie = Academie::findOrFail($id);
        $academie->update($this->validateAcademie($request, $id));

        return response()->json($academie->fresh());
    }

    public function destroyAcademie(int $id)
    {
        $this->authorizeSupAdminOnly();

        $academie = Academie::withCount(['caps', 'ecoles'])->findOrFail($id);
        if ($academie->caps_count > 0 || $academie->ecoles_count > 0) {
            return response()->json(['message' => 'Impossible de supprimer une académie déjà liée à des CAP ou écoles.'], 422);
        }

        $academie->delete();

        return response()->json(['success' => true]);
    }

    public function caps(Request $request)
    {
        $user = $request->user();
        $this->authorizeAnyPermission($user, ['dcap_apercu']);

        $idEcole = session('idEcole') ?: $user->idEcole;
        $ecole = !in_array($user->droit, ['SupAdmin', 'DAE', 'DCAP']) && $idEcole ? Ecole::find($idEcole) : null;

        $caps = Cap::with(['academie'])->withCount('ecoles')
            ->when($user->droit === 'DCAP' && $user->id_cap, fn ($q) => $q->where('id_cap', $user->id_cap))
            ->when($user->droit === 'DAE' && $user->id_academie, fn ($q) => $q->where('id_academie', $user->id_academie))
            ->when($ecole?->id_cap, fn ($q) => $q->where('id_cap', $ecole->id_cap))
            ->when($request->get('search'), fn ($q, $search) => $q->where('nom_cap', 'like', "%{$search}%"))
            ->orderBy('nom_cap')
            ->paginate(15)
            ->withQueryString();

        return response()->json($caps);
    }

    public function storeCap(Request $request)
    {
        $this->authorizeSupAdminOnly();

        $cap = Cap::create($this->validateCap($request));

        return response()->json($cap, 201);
    }

    public function updateCap(Request $request, int $id)
    {
        $this->authorizeSupAdminOnly();

        $cap = Cap::findOrFail($id);
        $cap->update($this->validateCap($request, $id));

        return response()->json($cap->fresh());
    }

    public function destroyCap(int $id)
    {
        $this->authorizeSupAdminOnly();

        $cap = Cap::withCount('ecoles')->findOrFail($id);
        if ($cap->ecoles_count > 0) {
            return response()->json(['message' => 'Impossible de supprimer un CAP déjà lié à des écoles.'], 422);
        }

        $cap->delete();

        return response()->json(['success' => true]);
    }

    public function typesNotes(Request $request)
    {
        $this->authorizeAnyPermission($request->user(), ['types_notes_apercu']);

        $typesNotes = Note::query()
            ->when($request->get('search'), fn ($q, $search) => $q->where('typeNote', 'like', "%{$search}%"))
            ->orderBy('typeNote')
            ->paginate(15)
            ->withQueryString();

        return response()->json($typesNotes);
    }

    public function storeTypeNote(Request $request)
    {
        $user = $request->user();
        $this->authorizeAnyPermission($user, ['types_notes_apercu']);

        $data = $request->validate([
            'typeNote' => 'required|string|in:devoir,composition,NT10',
            'codeNote' => 'required|string|max:50',
            'valeur' => 'required|numeric|min:0',
        ]);
        $data['id_ecole'] = session('idEcole') ?: $user->idEcole;

        $note = Note::create($data);

        return response()->json($note, 201);
    }

    public function updateTypeNote(Request $request, $id)
    {
        $this->authorizeAnyPermission($request->user(), ['types_notes_apercu']);

        $note = Note::findOrFail($id);
        $data = $request->validate([
            'typeNote' => 'required|string|in:devoir,composition,NT10',
            'codeNote' => 'required|string|max:50',
            'valeur' => 'required|numeric|min:0',
        ]);
        $note->update($data);

        return response()->json($note->fresh());
    }

    public function destroyTypeNote($id)
    {
        $this->authorizeAnyPermission(request()->user(), ['types_notes_apercu']);

        Note::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    public function classesOfficielles(Request $request)
    {
        $this->authorizeAnyPermission($request->user(), ['classes_officielles_apercu']);

        $classesOfficielles = ClasseOfficielle::query()
            ->withCount('classes')
            ->when($request->get('search'), fn ($q, $search) => $q->where('nom_classe_officielle', 'like', "%{$search}%"))
            ->orderBy('ordre_enseignement')
            ->orderBy('nom_classe_officielle')
            ->paginate(15)
            ->withQueryString();

        return response()->json([
            'data' => $classesOfficielles,
            'ordres' => $this->ordresClassesOfficielles(),
        ]);
    }

    public function storeClasseOfficielle(Request $request)
    {
        $this->authorizeAnyPermission($request->user(), ['classes_officielles_apercu']);

        $classeOfficielle = ClasseOfficielle::create($this->validateClasseOfficielle($request));

        return response()->json($classeOfficielle, 201);
    }

    public function updateClasseOfficielle(Request $request, $id)
    {
        $this->authorizeAnyPermission($request->user(), ['classes_officielles_apercu']);

        $classeOfficielle = ClasseOfficielle::findOrFail($id);
        $classeOfficielle->update($this->validateClasseOfficielle($request));

        return response()->json($classeOfficielle->fresh());
    }

    public function destroyClasseOfficielle($id)
    {
        $this->authorizeAnyPermission(request()->user(), ['classes_officielles_apercu']);

        $classeOfficielle = ClasseOfficielle::withCount('classes')->findOrFail($id);
        if ($classeOfficielle->classes_count > 0) {
            return response()->json(['message' => 'Impossible de supprimer cette classe officielle : elle est utilisée par une classe.'], 422);
        }

        $classeOfficielle->delete();

        return response()->json(['success' => true]);
    }

    public function statusControles(Request $request)
    {
        $this->authorizeAnyPermission($request->user(), ['status_controles_apercu']);

        $statusControles = Controle::query()
            ->when($request->get('search'), fn ($q, $search) => $q->where('type_controle', 'like', "%{$search}%"))
            ->orderBy('type_controle')
            ->paginate(15)
            ->withQueryString();

        return response()->json($statusControles);
    }

    public function storeStatusControle(Request $request)
    {
        $user = $request->user();
        $this->authorizeAnyPermission($user, ['status_controles_apercu']);
        $request->merge(['penalite_conduite' => str_replace(',', '.', (string) $request->input('penalite_conduite'))]);

        $data = $request->validate([
            'controle' => 'required|string|max:150',
            'alert' => 'nullable|string|in:oui,non',
            'penalite_conduite' => 'required|numeric|min:0|max:18',
        ]);

        $controle = Controle::create([
            'type_controle' => $data['controle'],
            'alertControle' => ($request->has('alert') && $data['alert'] !== 'non') ? 'oui' : 'non',
            'penalite_conduite' => $data['penalite_conduite'],
            'id_ecole' => session('idEcole') ?: $user->idEcole,
        ]);

        return response()->json($controle, 201);
    }

    public function updateStatusControle(Request $request, $id)
    {
        $this->authorizeAnyPermission($request->user(), ['status_controles_apercu']);
        $request->merge(['penalite_conduite' => str_replace(',', '.', (string) $request->input('penalite_conduite'))]);

        $controle = Controle::findOrFail($id);
        $data = $request->validate([
            'controle' => 'required|string|max:150',
            'alert' => 'required|string|in:oui,non',
            'penalite_conduite' => 'required|numeric|min:0|max:18',
        ]);

        $controle->update([
            'type_controle' => $data['controle'],
            'alertControle' => $data['alert'] === 'oui' ? 'oui' : 'non',
            'penalite_conduite' => $data['penalite_conduite'],
        ]);

        return response()->json($controle->fresh());
    }

    public function destroyStatusControle($id)
    {
        $this->authorizeAnyPermission(request()->user(), ['status_controles_apercu']);

        Controle::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
