<?php

namespace App\Http\Controllers;

use App\Models\Academie;
use App\Rules\MaliPhone;
use App\Models\Abonnement;
use App\Models\AbonnementOffre;
use App\Models\AnneeScolaire;
use App\Models\Cap;
use App\Models\ClasseOfficielle;
use App\Models\Ecole;
use App\Models\Enseignant;
use App\Models\ParentModel;
use App\Models\Permission;
use App\Models\User;
use App\Models\Note;
use App\Models\Controle;
use App\Support\SchoolOrderAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConfigurationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->droit !== 'SupAdmin') {
            return redirect()->route($this->firstAvailableConfigurationRoute($user));
        }

        $idEcole = session('idEcole');

        $stats = [
            'ecoles' => $this->ecoleScope(Ecole::query(), $user, $idEcole)->count(),
            'academies' => Academie::count(),
            'caps' => Cap::count(),
            'annees' => $this->anneeScope(AnneeScolaire::query(), $user, $idEcole)->count(),
            'utilisateurs' => $this->userScope(User::query(), $user, $idEcole)->count(),
            'permissions' => Permission::count(),
        ];

        $onlineThreshold = now()->subMinutes(15);

        $recentUsers = $this->userScope(User::with('ecole'), $user, $idEcole)
            ->orderByRaw('CASE WHEN last_activity IS NOT NULL AND last_activity >= ? THEN 0 ELSE 1 END', [$onlineThreshold])
            ->orderByDesc('last_activity')
            ->orderBy('nomPrenom')
            ->get();

        $annees = $this->anneeScope(AnneeScolaire::with('ecole'), $user, $idEcole)
            ->orderByDesc('id_anneeScolaire')
            ->limit(6)
            ->get();

        return view('configuration.index', compact('stats', 'recentUsers', 'annees', 'onlineThreshold'));
    }

    public function ecoles(Request $request)
    {
        $user = Auth::user();
        $this->authorizeAnyPermission($user, ['ecoles_apercu']);

        $idEcole = session('idEcole');
        $search = $request->get('search');

        $ecoles = $this->ecoleScope(Ecole::with(['academieRef', 'capRef']), $user, $idEcole)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('nomEcole', 'like', "%{$search}%")
                        ->orWhere('typeEcole', 'like', "%{$search}%")
                        ->orWhere('cap', 'like', "%{$search}%")
                        ->orWhere('academie', 'like', "%{$search}%");
                });
            })
            ->orderBy('nomEcole')
            ->paginate(15)
            ->withQueryString();

        $academies = Academie::orderBy('nom_academie')->get();
        $caps = Cap::with('academie')->orderBy('nom_cap')->get();
        $abonnementOffres = Auth::user()->droit === 'SupAdmin'
            ? AbonnementOffre::where('actif', true)->orderBy('montant')->get()
            : collect();

        return view('configuration.ecoles', compact('ecoles', 'academies', 'caps', 'abonnementOffres'));
    }

    public function storeEcole(Request $request)
    {
        $this->authorizeSupAdminOnly();

        $data = $this->validateEcole($request);
        $data['logoEcole'] = $this->storeEcoleLogo($request);
        $this->hydrateEcoleLegacyLabels($data);

        $ecole = Ecole::create($data);
        $this->activateInitialSubscription($request, $ecole);

        return redirect()->route('configuration.ecoles')->with('success', 'École ajoutée avec succès.');
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

        return redirect()->route('configuration.ecoles')->with('success', 'École modifiée avec succès.');
    }

    public function destroyEcole(int $id)
    {
        $this->authorizeSupAdminOnly();

        $ecole = Ecole::withCount(['utilisateurs'])->findOrFail($id);
        $this->authorizeEcoleMutation($ecole);

        if ($ecole->utilisateurs_count > 0) {
            return redirect()->route('configuration.ecoles')->with('error', 'Impossible de supprimer une école liée à des utilisateurs.');
        }

        $ecole->delete();

        return redirect()->route('configuration.ecoles')->with('success', 'École supprimée avec succès.');
    }

    public function academies(Request $request)
    {
        $user = Auth::user();
        $this->authorizeAnyPermission($user, ['academies_apercu']);

        $idEcole = session('idEcole') ?: $user->idEcole;
        $search = $request->get('search');

        $ecole = !in_array($user->droit, ['SupAdmin', 'DAE', 'DCAP']) && $idEcole
            ? Ecole::find($idEcole)
            : null;

        $academies = Academie::withCount(['caps', 'ecoles'])
            ->when($user->droit === 'DAE' && $user->id_academie, fn ($q) => $q->where('id_academie', $user->id_academie))
            ->when($ecole?->id_academie, fn ($q) => $q->where('id_academie', $ecole->id_academie))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('nom_academie', 'like', "%{$search}%")
                        ->orWhere('code_academie', 'like', "%{$search}%")
                        ->orWhere('localite_academie', 'like', "%{$search}%");
                });
            })
            ->orderBy('nom_academie')
            ->paginate(15)
            ->withQueryString();

        return view('configuration.academies', compact('academies'));
    }

    public function storeAcademie(Request $request)
    {
        $this->authorizeSupAdminOnly();

        Academie::create($this->validateAcademie($request));

        return redirect()->route('configuration.academies')->with('success', 'Académie ajoutée avec succès.');
    }

    public function updateAcademie(Request $request, int $id)
    {
        $this->authorizeSupAdminOnly();

        $academie = Academie::findOrFail($id);
        $academie->update($this->validateAcademie($request, $id));

        return redirect()->route('configuration.academies')->with('success', 'Académie modifiée avec succès.');
    }

    public function destroyAcademie(int $id)
    {
        $this->authorizeSupAdminOnly();

        $academie = Academie::withCount(['caps', 'ecoles'])->findOrFail($id);

        if ($academie->caps_count > 0 || $academie->ecoles_count > 0) {
            return redirect()->route('configuration.academies')->with('error', 'Impossible de supprimer une académie déjà liée à des CAP ou écoles.');
        }

        $academie->delete();

        return redirect()->route('configuration.academies')->with('success', 'Académie supprimée avec succès.');
    }

    public function caps(Request $request)
    {
        $user = Auth::user();
        $this->authorizeAnyPermission($user, ['dcap_apercu']);

        $idEcole = session('idEcole') ?: $user->idEcole;
        $search = $request->get('search');

        $ecole = !in_array($user->droit, ['SupAdmin', 'DAE', 'DCAP']) && $idEcole
            ? Ecole::find($idEcole)
            : null;

        $caps = Cap::with(['academie'])->withCount('ecoles')
            ->when($user->droit === 'DCAP' && $user->id_cap, fn ($q) => $q->where('id_cap', $user->id_cap))
            ->when($user->droit === 'DAE' && $user->id_academie, fn ($q) => $q->where('id_academie', $user->id_academie))
            ->when($ecole?->id_cap, fn ($q) => $q->where('id_cap', $ecole->id_cap))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('nom_cap', 'like', "%{$search}%")
                        ->orWhere('code_cap', 'like', "%{$search}%")
                        ->orWhere('localite_cap', 'like', "%{$search}%");
                });
            })
            ->orderBy('nom_cap')
            ->paginate(15)
            ->withQueryString();

        $academies = Academie::orderBy('nom_academie')->get();

        return view('configuration.caps', compact('caps', 'academies'));
    }

    public function storeCap(Request $request)
    {
        $this->authorizeSupAdminOnly();

        Cap::create($this->validateCap($request));

        return redirect()->route('configuration.caps')->with('success', 'CAP ajouté avec succès.');
    }

    public function updateCap(Request $request, int $id)
    {
        $this->authorizeSupAdminOnly();

        $cap = Cap::findOrFail($id);
        $cap->update($this->validateCap($request, $id));

        return redirect()->route('configuration.caps')->with('success', 'CAP modifié avec succès.');
    }

    public function destroyCap(int $id)
    {
        $this->authorizeSupAdminOnly();

        $cap = Cap::withCount('ecoles')->findOrFail($id);

        if ($cap->ecoles_count > 0) {
            return redirect()->route('configuration.caps')->with('error', 'Impossible de supprimer un CAP déjà lié à des écoles.');
        }

        $cap->delete();

        return redirect()->route('configuration.caps')->with('success', 'CAP supprimé avec succès.');
    }

    public function annees(Request $request)
    {
        $user = Auth::user();
        $this->authorizeAnyPermission($user, ['annees_scolaires_apercu']);

        $idEcole = session('idEcole');
        $search = $request->get('search');

        $this->ensureCurrentAcademicYearExists();
        $anneeEnCours = $this->currentAcademicYear();

        $annees = $this->anneeScope(AnneeScolaire::query(), $user, $idEcole)
            ->when($anneeEnCours, fn ($query) => $query->where('id_anneeScolaire', $anneeEnCours->id_anneeScolaire))
            ->when($search, fn ($query) => $query->where('annee', 'like', "%{$search}%"))
            ->orderByDesc('date_debut')
            ->orderByDesc('id_anneeScolaire')
            ->paginate(15)
            ->withQueryString();

        return view('configuration.annees', compact('annees', 'anneeEnCours'));
    }

    public function storeAnnee(Request $request)
    {
        $user = Auth::user();
        $this->authorizeAnyPermission($user, ['annees_scolaires_apercu']);

        $data = $request->validate([
            'annee' => 'required|string|max:100',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
        ]);

        $data['id_ecole'] = session('idEcole') ?: $user->idEcole;

        AnneeScolaire::create($data);

        return redirect()->route('configuration.annees')->with('success', 'Année scolaire ajoutée avec succès.');
    }

    public function utilisateurs(Request $request)
    {
        $user = Auth::user();
        if ($user->droit !== 'Admin') {
            $this->authorizeAnyPermission($user, ['utilisateurs_apercu', 'administrateur_tabsConfig', 'enseignants_tabsConfig', 'parents_tabsConfig', 'dae_apercu', 'dcap_apercu']);
        }

        $idEcole = session('idEcole');
        $search = $request->get('search');
        $availableSchools = $user->droit === 'SupAdmin'
            ? $this->ecoleScope(Ecole::query(), $user, $idEcole)->orderBy('nomEcole')->get()
            : collect();
        $schoolFilter = $user->droit === 'SupAdmin' ? ($request->integer('idEcole') ?: null) : null;

        $utilisateurs = $this->userScope(
            User::with(['ecole', 'academie', 'cap', 'enseignant.ecole', 'parent.ecole'])->withCount('permissions'),
            $user,
            $idEcole
        )
            ->when($schoolFilter && $availableSchools->contains('idEcole', $schoolFilter), function ($query) use ($schoolFilter) {
                $query->where(function ($inner) use ($schoolFilter) {
                    $inner->where('idEcole', $schoolFilter)
                        ->orWhereHas('enseignant', fn ($enseignant) => $enseignant->where('id_ecole', $schoolFilter))
                        ->orWhereHas('parent', fn ($parent) => $parent->where('idEcole', $schoolFilter));
                });
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('nomPrenom', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('fonction', 'like', "%{$search}%")
                        ->orWhere('droit', 'like', "%{$search}%");
                });
            })
            ->orderBy('nomPrenom')
            ->get();

        return view('configuration.utilisateurs', compact('utilisateurs', 'availableSchools', 'schoolFilter'));
    }

    public function createUtilisateur()
    {
        $authUser = Auth::user();
        if (!in_array($authUser->droit, ['SupAdmin', 'Admin'], true) && !$authUser->userHasPermission('utilisateurs_creation')) {
            abort(403);
        }

        $idEcole = session('idEcole');

        return view('configuration.utilisateur-form', [
            'ecoles' => $this->ecoleScope(Ecole::query(), $authUser, $idEcole)->orderBy('nomEcole')->get(),
            'enseignants' => $this->enseignantsScope(Enseignant::query(), $authUser, $idEcole)->orderBy('nom_prenom_enseignant')->get(),
            'parents' => $this->parentsScope(ParentModel::query(), $authUser, $idEcole)->orderBy('nom_prenom_parent')->get(),
            'academies' => Academie::orderBy('nom_academie')->get(),
            'caps' => Cap::with('academie')->orderBy('nom_cap')->get(),
            'complexeOrders' => SchoolOrderAccess::ORDERS,
        ]);
    }

    public function storeUtilisateur(Request $request)
    {
        $type = (int) $request->input('type_utilisateur', 1);
        $data = $this->validateUtilisateurByType($request, $type);
        $authUser = Auth::user();
        $idEcole = session('idEcole');

        $this->authorizeUserCreation($authUser, $type, $data);
        $this->validateManagedOrdersForUser($authUser, $type, $data, $idEcole);

        $createdUser = DB::transaction(function () use ($type, $data, $authUser, $idEcole) {
            $password = $data['pwd'] ?? $this->generatePassword();
            $payload = [
                'pwd' => Hash::make($password),
                'statut' => 1,
                'image' => 'default.png',
            ];

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

        $redirectRoute = $this->canAssignPermissionsToTarget($authUser, $createdUser)
            ? 'configuration.utilisateurs.permissions.assigner'
            : 'configuration.utilisateurs';
        $redirectParameters = $redirectRoute === 'configuration.utilisateurs.permissions.assigner'
            ? ['user_id' => $createdUser->idUtilisateur]
            : [];

        return redirect()
            ->route($redirectRoute, $redirectParameters)
            ->with('success', 'Utilisateur créé. Les permissions de base ont été attribuées.');
    }

    public function editUtilisateur(int $id)
    {
        $authUser = Auth::user();
        $idEcole = session('idEcole');

        $utilisateur = $this->userScope(User::with(['enseignant', 'parent', 'ecole']), $authUser, $idEcole)
            ->where('idUtilisateur', $id)
            ->firstOrFail();

        $this->authorizeTargetUserEdit($authUser, $utilisateur);

        return view('configuration.utilisateur-form', [
            'utilisateur' => $utilisateur,
            'selectedType' => $this->userFormType($utilisateur),
            'ecoles' => $this->ecoleScope(Ecole::query(), $authUser, $idEcole)->orderBy('nomEcole')->get(),
            'enseignants' => $this->enseignantsScope(Enseignant::query(), $authUser, $idEcole)->orderBy('nom_prenom_enseignant')->get(),
            'parents' => $this->parentsScope(ParentModel::query(), $authUser, $idEcole)->orderBy('nom_prenom_parent')->get(),
            'academies' => Academie::orderBy('nom_academie')->get(),
            'caps' => Cap::with('academie')->orderBy('nom_cap')->get(),
            'complexeOrders' => SchoolOrderAccess::ORDERS,
        ]);
    }

    public function updateUtilisateur(Request $request, int $id)
    {
        $authUser = Auth::user();
        $idEcole = session('idEcole');

        $utilisateur = $this->userScope(User::query(), $authUser, $idEcole)
            ->where('idUtilisateur', $id)
            ->firstOrFail();

        $this->authorizeTargetUserEdit($authUser, $utilisateur);

        $type = $this->userFormType($utilisateur);
        $data = $this->validateUtilisateurByType($request, $type, $utilisateur);
        $this->validateManagedOrdersForUser($authUser, $type, $data, $idEcole);

        $payload = [
            'telephone' => $data['telephone'] ?? $utilisateur->telephone,
        ];

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

        return redirect()
            ->route('configuration.utilisateurs')
            ->with('success', 'Utilisateur modifié avec succès.');
    }

    public function updateUserStatus(Request $request, int $id)
    {
        $authUser = Auth::user();
        $idEcole = session('idEcole');

        $utilisateur = $this->userScope(User::query(), $authUser, $idEcole)
            ->where('idUtilisateur', $id)
            ->firstOrFail();

        $this->authorizeTargetUserGovernance($authUser, $utilisateur);

        if ($authUser->idUtilisateur === $utilisateur->idUtilisateur) {
            return redirect()->route('configuration.utilisateurs')->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $data = $request->validate([
            'statut' => 'required|integer|in:0,1',
        ]);

        $utilisateur->update(['statut' => $data['statut']]);

        return redirect()->route('configuration.utilisateurs')->with('success', 'Statut utilisateur modifié avec succès.');
    }

    public function destroyUtilisateur(int $id)
    {
        $authUser = Auth::user();
        $idEcole = session('idEcole');

        $utilisateur = $this->userScope(User::query(), $authUser, $idEcole)
            ->where('idUtilisateur', $id)
            ->firstOrFail();

        $this->authorizeTargetUserGovernance($authUser, $utilisateur);

        if ($authUser->idUtilisateur === $utilisateur->idUtilisateur) {
            return redirect()->route('configuration.utilisateurs')->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        if ($authUser->droit !== 'SupAdmin' && !$authUser->userHasPermission('utilisateurs_supprimer')) {
            abort(403);
        }

        DB::transaction(function () use ($utilisateur) {
            $utilisateur->permissions()->detach();

            if (DB::getSchemaBuilder()->hasTable('app_notifications')) {
                DB::table('app_notifications')->where('user_id', $utilisateur->idUtilisateur)->delete();
            }

            if (DB::getSchemaBuilder()->hasTable('sessions') && DB::getSchemaBuilder()->hasColumn('sessions', 'user_id')) {
                DB::table('sessions')->where('user_id', $utilisateur->idUtilisateur)->delete();
            }

            $utilisateur->delete();
        });

        return redirect()->route('configuration.utilisateurs')->with('success', 'Compte utilisateur supprimé. Les fiches métier restent conservées.');
    }

    public function editUserPermissions(int $id)
    {
        $authUser = Auth::user();
        if (!in_array($authUser->droit, ['SupAdmin', 'Admin'], true)) {
            $this->authorizeAnyPermission($authUser, ['permissions_assigner', 'permission_assigner', 'dae_permission', 'dcap_permission']);
        }

        $idEcole = session('idEcole');

        $utilisateur = $this->userScope(User::with(['ecole', 'permissions']), $authUser, $idEcole)
            ->where('idUtilisateur', $id)
            ->firstOrFail();

        $this->authorizeTargetPermissionView($authUser, $utilisateur);

        $groupedPermissions = Permission::groupedByModule();
        $permissionsReadOnly = !$this->canAssignPermissionsToTarget($authUser, $utilisateur);
        if ($utilisateur->droit === 'SupAdmin') {
            $allPermissions = collect($groupedPermissions)->flatten(1);
            $userPermissionIds = $allPermissions->pluck('id')->map(fn ($id) => (int) $id)->all();
            $userPermissionNames = $allPermissions->pluck('name')->map(fn ($name) => Permission::canonicalName($name))->all();
        } else {
            $userPermissionIds = $utilisateur->permissions->pluck('id')->map(fn ($id) => (int) $id)->all();
            $userPermissionNames = $utilisateur->permissions
                ->pluck('name')
                ->map(fn ($name) => Permission::canonicalName($name))
                ->all();
        }

        $availableUsers = $this->permissionAssignableUsers($authUser, $idEcole);
        $complexeOrders = SchoolOrderAccess::ORDERS;

        return view('configuration.user-permissions', compact(
            'utilisateur',
            'availableUsers',
            'groupedPermissions',
            'userPermissionIds',
            'userPermissionNames',
            'permissionsReadOnly',
            'complexeOrders'
        ));
    }

    public function assignUserPermissions(Request $request)
    {
        $authUser = Auth::user();
        if (!in_array($authUser->droit, ['SupAdmin', 'Admin'], true)) {
            $this->authorizeAnyPermission($authUser, ['permissions_assigner', 'permission_assigner', 'dae_permission', 'dcap_permission']);
        }

        $idEcole = session('idEcole');
        $availableSchools = $authUser->droit === 'SupAdmin'
            ? $this->ecoleScope(Ecole::query(), $authUser, $idEcole)->orderBy('nomEcole')->get()
            : collect();
        $schoolFilter = $authUser->droit === 'SupAdmin' ? ($request->integer('idEcole') ?: null) : null;
        $availableUsers = $this->permissionAssignableUsers($authUser, $idEcole, $schoolFilter);
        $selectedUserId = (int) $request->query('user_id');
        $utilisateur = null;
        $groupedPermissions = Permission::groupedByModule();
        $userPermissionIds = [];
        $userPermissionNames = [];
        $complexeOrders = SchoolOrderAccess::ORDERS;
        $permissionsReadOnly = false;

        if ($selectedUserId > 0) {
            if (!$availableUsers->contains('idUtilisateur', $selectedUserId)) {
                abort(404);
            }

            $utilisateur = $this->userScope(User::with(['ecole', 'permissions']), $authUser, $idEcole)
                ->where('idUtilisateur', $selectedUserId)
                ->firstOrFail();

            $this->authorizeTargetPermissionView($authUser, $utilisateur);
            $permissionsReadOnly = !$this->canAssignPermissionsToTarget($authUser, $utilisateur);

            if ($utilisateur->droit === 'SupAdmin') {
                $allPermissions = collect($groupedPermissions)->flatten(1);
                $userPermissionIds = $allPermissions->pluck('id')->map(fn ($id) => (int) $id)->all();
                $userPermissionNames = $allPermissions->pluck('name')->map(fn ($name) => Permission::canonicalName($name))->all();
            } else {
                $userPermissionIds = $utilisateur->permissions->pluck('id')->map(fn ($id) => (int) $id)->all();
                $userPermissionNames = $utilisateur->permissions
                    ->pluck('name')
                    ->map(fn ($name) => Permission::canonicalName($name))
                    ->all();
            }
        }

        return view('configuration.user-permissions', compact(
            'utilisateur',
            'availableUsers',
            'groupedPermissions',
            'userPermissionIds',
            'userPermissionNames',
            'permissionsReadOnly',
            'availableSchools',
            'schoolFilter',
            'complexeOrders'
        ));
    }

    public function updateUserPermissions(Request $request, int $id)
    {
        $authUser = Auth::user();
        if (!in_array($authUser->droit, ['SupAdmin', 'Admin'], true)) {
            $this->authorizeAnyPermission($authUser, ['permissions_assigner', 'permission_assigner', 'dae_permission', 'dcap_permission']);
        }

        $idEcole = session('idEcole');

        $utilisateur = $this->userScope(User::query(), $authUser, $idEcole)
            ->where('idUtilisateur', $id)
            ->firstOrFail();

        if (!$this->canAssignPermissionsToTarget($authUser, $utilisateur)) {
            abort(403);
        }

        $permissionIds = collect($request->input('permissions', []))
            ->map(fn ($permissionId) => (int) $permissionId)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $validPermissionIds = Permission::whereIn('id', $permissionIds)->pluck('id')->all();
        $utilisateur->permissions()->sync($validPermissionIds);

        $orders = SchoolOrderAccess::normalizeMany($request->input('managed_orders', []));
        if ($utilisateur->droit === 'Gestionnaire' && SchoolOrderAccess::isComplex($utilisateur->ecole)) {
            if (empty($orders)) {
                throw ValidationException::withMessages([
                    'managed_orders' => 'Veuillez sélectionner au moins un ordre d’enseignement pour ce gestionnaire du complexe.',
                ]);
            }
            $utilisateur->managed_orders = $orders;
            $utilisateur->save();
        } elseif ($utilisateur->managed_orders) {
            $utilisateur->managed_orders = null;
            $utilisateur->save();
        }

        return redirect()
            ->route('configuration.utilisateurs.permissions.assigner', ['user_id' => $utilisateur->idUtilisateur])
            ->with('success', 'Permissions enregistrées avec succès pour ' . $utilisateur->nomPrenom . '.');
    }

    public function permissions(Request $request)
    {
        $this->authorizeAnyPermission(Auth::user(), ['permissions_apercu', 'permission_voir']);

        $search = $request->get('search');

        $canonicalPermissions = Permission::query()
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($permission) => Permission::canonicalName($permission->name))
            ->map(function ($duplicates, $canonicalName) {
                $permission = $duplicates->sortBy('id')->first();
                $permission->name = $canonicalName;
                $permission->users_count = $duplicates->sum('users_count');

                return $permission;
            })
            ->filter(fn ($permission) => !$search || str_contains(Permission::normalizeName($permission->name), Permission::normalizeName($search)))
            ->sortBy('name')
            ->values();

        $page = $request->integer('page', 1);
        $perPage = 20;
        $permissions = new \Illuminate\Pagination\LengthAwarePaginator(
            $canonicalPermissions->forPage($page, $perPage)->values(),
            $canonicalPermissions->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('configuration.permissions', compact('permissions'));
    }

    public function storePermission(Request $request)
    {
        $this->authorizeSupAdminOnly();

        $data = $request->validate([
            'name' => 'required|string|max:100|unique:permissions,name',
        ]);

        $canonicalName = Permission::canonicalName($data['name']);

        if (Permission::query()->get()->contains(fn ($permission) => Permission::canonicalName($permission->name) === $canonicalName)) {
            return back()
                ->withInput()
                ->withErrors(['name' => 'Cette permission existe déjà dans le référentiel sous un nom équivalent.']);
        }

        Permission::create(['name' => $canonicalName]);

        return redirect()->route('configuration.permissions')->with('success', 'Permission ajoutée avec succès.');
    }

    private function ecoleScope($query, User $user, ?int $idEcole)
    {
        if ($user->droit === 'SupAdmin') {
            return $query;
        }

        if ($user->droit === 'DAE' && $user->id_academie) {
            return $query->where(function ($inner) use ($user) {
                $inner->where('id_academie', $user->id_academie)
                    ->orWhereHas('capRef', fn ($cap) => $cap->where('id_academie', $user->id_academie));
            });
        }

        if ($user->droit === 'DCAP' && $user->id_cap) {
            return $query->where('id_cap', $user->id_cap);
        }

        return $query->where('idEcole', $idEcole ?: $user->idEcole);
    }

    private function anneeScope($query, User $user, ?int $idEcole)
    {
        if ($user->droit === 'SupAdmin') {
            return $query;
        }

        return $query->where(function ($inner) use ($idEcole, $user) {
            $inner->whereNull('id_ecole')
                ->orWhere('id_ecole', $idEcole ?: $user->idEcole);
        });
    }

    private function userScope($query, User $user, ?int $idEcole)
    {
        if ($user->droit === 'SupAdmin') {
            return $query->where(function ($inner) use ($user) {
                $inner->where('droit', '!=', 'SupAdmin')
                    ->orWhere('idUtilisateur', $user->idUtilisateur);
            });
        }

        if ($user->droit === 'DAE' && $user->id_academie) {
            return $query->where(function ($inner) use ($user) {
                $inner->where('id_academie', $user->id_academie)
                    ->orWhereHas('ecole', function ($ecole) use ($user) {
                        $ecole->where('id_academie', $user->id_academie)
                            ->orWhereHas('capRef', fn ($cap) => $cap->where('id_academie', $user->id_academie));
                    })
                    ->orWhereHas('cap', fn ($cap) => $cap->where('id_academie', $user->id_academie));
            });
        }

        if ($user->droit === 'DCAP' && $user->id_cap) {
            return $query->where(function ($inner) use ($user) {
                $inner->where('id_cap', $user->id_cap)
                    ->orWhereHas('ecole', fn ($ecole) => $ecole->where('id_cap', $user->id_cap))
                    ->orWhere(function ($dae) use ($user) {
                        $dae->where('droit', 'DAE')
                            ->whereHas('academie', function ($academie) use ($user) {
                                $academie->whereHas('caps', fn ($cap) => $cap->where('id_cap', $user->id_cap));
                            });
                    });
            });
        }

        $query->where('idEcole', $idEcole ?: $user->idEcole);

        if ($user->droit === 'Gestionnaire') {
            $query->where('droit', '!=', 'Admin');
        }

        return $query;
    }

    private function enseignantsScope($query, User $user, ?int $idEcole)
    {
        if ($user->droit === 'SupAdmin') {
            return $query;
        }

        if ($user->droit === 'DAE' && $user->id_academie) {
            return $query->whereHas('ecole', function ($ecole) use ($user) {
                $ecole->where('id_academie', $user->id_academie)
                    ->orWhereHas('capRef', fn ($cap) => $cap->where('id_academie', $user->id_academie));
            });
        }

        if ($user->droit === 'DCAP' && $user->id_cap) {
            return $query->whereHas('ecole', fn ($ecole) => $ecole->where('id_cap', $user->id_cap));
        }

        return $query->where('id_ecole', $idEcole ?: $user->idEcole);
    }

    private function parentsScope($query, User $user, ?int $idEcole)
    {
        if ($user->droit === 'SupAdmin') {
            return $query;
        }

        if ($user->droit === 'DAE' && $user->id_academie) {
            return $query->whereHas('ecole', function ($ecole) use ($user) {
                $ecole->where('id_academie', $user->id_academie)
                    ->orWhereHas('capRef', fn ($cap) => $cap->where('id_academie', $user->id_academie));
            });
        }

        if ($user->droit === 'DCAP' && $user->id_cap) {
            return $query->whereHas('ecole', fn ($ecole) => $ecole->where('id_cap', $user->id_cap));
        }

        return $query->where('idEcole', $idEcole ?: $user->idEcole);
    }

    private function permissionAssignableUsers(User $user, ?int $idEcole, ?int $schoolFilter = null)
    {
        $query = $this->userScope(
            User::with(['ecole', 'academie', 'cap'])->withCount('permissions'),
            $user,
            $idEcole
        );

        if ($user->droit === 'SupAdmin') {
            $query->where(function ($inner) use ($user) {
                $inner->where('idUtilisateur', $user->idUtilisateur)
                    ->orWhere('droit', '!=', 'SupAdmin');
            });

            if ($schoolFilter) {
                $query->where(function ($inner) use ($user, $schoolFilter) {
                    $inner->where('idUtilisateur', $user->idUtilisateur)
                        ->orWhere(function ($schoolUsers) use ($schoolFilter) {
                            $schoolUsers->where('idEcole', $schoolFilter)
                                ->orWhereHas('enseignant', fn ($enseignant) => $enseignant->where('id_ecole', $schoolFilter))
                                ->orWhereHas('parent', fn ($parent) => $parent->where('idEcole', $schoolFilter));
                        });
                });
            }
        } else {
            $query->where('idUtilisateur', '!=', $user->idUtilisateur)
                ->whereNotIn('droit', ['SupAdmin', 'Admin']);
        }

        return $query->orderBy('nomPrenom')->get();
    }

    private function authorizeSupAdminOnly(): void
    {
        if (Auth::user()->droit !== 'SupAdmin') {
            abort(403);
        }
    }

    private function authorizeAnyPermission(User $user, array $permissions): void
    {
        if ($user->droit === 'SupAdmin') {
            return;
        }

        foreach ($permissions as $permission) {
            if ($user->userHasPermission($permission)) {
                return;
            }
        }

        abort(403);
    }

    private function firstAvailableConfigurationRoute(User $user): string
    {
        $routes = [
            'utilisateurs_apercu' => 'configuration.utilisateurs',
            'ecoles_apercu' => 'configuration.ecoles',
            'academies_apercu' => 'configuration.academies',
            'dcap_apercu' => 'configuration.caps',
            'annees_scolaires_apercu' => 'configuration.annees',
            'types_notes_apercu' => 'configuration.types-notes',
            'classes_officielles_apercu' => 'configuration.classes-officielles',
            'status_controles_apercu' => 'configuration.status-controles',
            'administrateur_tabsConfig' => 'configuration.utilisateurs',
            'permissions_apercu' => 'configuration.permissions',
            'permission_voir' => 'configuration.permissions',
            'permissions_assigner' => 'configuration.utilisateurs.permissions.assigner',
            'permission_assigner' => 'configuration.utilisateurs.permissions.assigner',
            'dae_permission' => 'configuration.utilisateurs.permissions.assigner',
            'dcap_permission' => 'configuration.utilisateurs.permissions.assigner',
        ];

        if ($user->droit === 'Admin') {
            return 'configuration.utilisateurs';
        }

        foreach ($routes as $permission => $route) {
            if ($user->userHasPermission($permission)) {
                return $route;
            }
        }

        abort(403);
    }

    private function ensureCurrentAcademicYearExists(): void
    {
        $today = now();
        $startYear = (int) $today->format('m') >= 9 ? (int) $today->format('Y') : (int) $today->format('Y') - 1;
        $endYear = $startYear + 1;
        $label = $startYear . '-' . $endYear;

        AnneeScolaire::withoutGlobalScopes()->firstOrCreate(
            ['annee' => $label, 'id_ecole' => null],
            [
                'date_debut' => $startYear . '-09-01',
                'date_fin' => $endYear . '-08-31',
            ]
        );
    }

    private function currentAcademicYear(): ?AnneeScolaire
    {
        $today = now()->toDateString();

        return AnneeScolaire::withoutGlobalScopes()
            ->whereDate('date_debut', '<=', $today)
            ->whereDate('date_fin', '>=', $today)
            ->orderByRaw('id_ecole IS NOT NULL')
            ->orderByDesc('date_debut')
            ->first();
    }

    private function authorizeUserCreation(User $authUser, int $type, array $data): void
    {
        if (!in_array($authUser->droit, ['SupAdmin', 'Admin'], true) && !$authUser->userHasPermission('utilisateurs_creation')) {
            abort(403);
        }

        if ($authUser->droit !== 'SupAdmin') {
            $canCreateSchoolUser = in_array($type, [0, 2], true);
            $canCreateGestionnaire = $type === 1 && ($data['droit'] ?? null) === 'Gestionnaire';

            if (!$canCreateSchoolUser && !$canCreateGestionnaire) {
                abort(403);
            }
        }
    }

    private function authorizeTargetUserGovernance(User $authUser, User $target): void
    {
        if ($target->droit === 'SupAdmin' && $target->idUtilisateur !== $authUser->idUtilisateur) {
            abort(403);
        }
    }

    private function authorizeTargetPermissionAssignment(User $authUser, User $target): void
    {
        if (!$this->canAssignPermissionsToTarget($authUser, $target)) {
            abort(403);
        }

        $this->authorizeTargetUserGovernance($authUser, $target);
    }

    private function authorizeTargetPermissionView(User $authUser, User $target): void
    {
        if ($target->idUtilisateur === $authUser->idUtilisateur && $authUser->droit === 'SupAdmin') {
            return;
        }

        $this->authorizeTargetPermissionAssignment($authUser, $target);
    }

    private function canAssignPermissionsToTarget(User $authUser, User $target): bool
    {
        if ($target->idUtilisateur === $authUser->idUtilisateur) {
            return false;
        }

        if ($target->droit === 'SupAdmin') {
            return false;
        }

        if ($authUser->droit === 'SupAdmin') {
            return true;
        }

        return $target->droit !== 'Admin';
    }

    private function authorizeTargetUserEdit(User $authUser, User $target): void
    {
        if (!in_array($authUser->droit, ['SupAdmin', 'Admin'], true) && !$authUser->userHasPermission('utilisateurs_modification')) {
            abort(403);
        }

        if ($target->idUtilisateur === $authUser->idUtilisateur || $target->droit === 'SupAdmin') {
            abort(403);
        }

        if ($authUser->droit !== 'SupAdmin' && $target->droit === 'Admin') {
            abort(403);
        }
    }

    private function canEditTargetUser(User $authUser, User $target): bool
    {
        if ($target->idUtilisateur === $authUser->idUtilisateur || $target->droit === 'SupAdmin') {
            return false;
        }

        if (!in_array($authUser->droit, ['SupAdmin', 'Admin'], true) && !$authUser->userHasPermission('utilisateurs_modification')) {
            return false;
        }

        return $authUser->droit === 'SupAdmin' || $target->droit !== 'Admin';
    }

    private function userFormType(User $user): int
    {
        return match (true) {
            !empty($user->id_enseignant) => 0,
            !empty($user->id_parent) => 2,
            $user->droit === 'DAE' => 3,
            $user->droit === 'DCAP' => 4,
            default => 1,
        };
    }

    private function validateUtilisateurByType(Request $request, int $type, ?User $existingUser = null): array
    {
        if ($request->filled('telephone')) {
            $request->merge(['telephone' => MaliPhone::normalize($request->input('telephone'))]);
        }

        $base = [
            'type_utilisateur' => 'required|integer|in:0,1,2,3,4',
            'pwd' => 'nullable|string|min:4',
        ];

        $emailRule = Rule::unique('utilisateurs', 'email');
        if ($existingUser) {
            $emailRule->ignore($existingUser->idUtilisateur, 'idUtilisateur');
        }

        if ($type === 0) {
            return $request->validate($base + [
                'id_enseignant' => 'required|integer|exists:enseignants,id_enseignant',
            ]);
        }

        if ($type === 2) {
            return $request->validate($base + [
                'id_parent' => 'required|integer|exists:parents,id_parent',
            ]);
        }

        if ($type === 3) {
            return $request->validate($base + [
                'nomPrenom' => 'required|string|max:150',
                'email' => ['required', 'email', 'max:150', $emailRule],
                'telephone' => ['required', 'string', 'max:20', new MaliPhone()],
                'genre' => 'required|string|max:20',
                'fonction' => 'nullable|string|max:50',
                'id_academie' => 'required|integer|exists:academie,id_academie',
            ]);
        }

        if ($type === 4) {
            return $request->validate($base + [
                'nomPrenom' => 'required|string|max:150',
                'email' => ['required', 'email', 'max:150', $emailRule],
                'telephone' => ['required', 'string', 'max:20', new MaliPhone()],
                'genre' => 'required|string|max:20',
                'fonction' => 'nullable|string|max:50',
                'id_cap' => 'required|integer|exists:cap,id_cap',
            ]);
        }

        return $request->validate($base + [
            'nomPrenom' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', $emailRule],
            'telephone' => ['required', 'string', 'max:20', new MaliPhone()],
            'genre' => 'required|string|max:20',
            'fonction' => 'nullable|string|max:50',
            'droit' => 'required|string|in:' . (Auth::user()->droit === 'SupAdmin' ? 'SupAdmin,Admin,Gestionnaire' : 'Gestionnaire'),
            'idEcole' => 'nullable|integer|exists:ecole,idEcole',
            'managed_orders' => 'nullable|array',
            'managed_orders.*' => 'required|string|in:' . implode(',', array_keys(SchoolOrderAccess::ORDERS)),
        ]);
    }

    private function validateManagedOrdersForUser(User $authUser, int $type, array $data, ?int $idEcole): void
    {
        if ($type !== 1 || ($data['droit'] ?? null) !== 'Gestionnaire') {
            return;
        }

        $targetSchoolId = $authUser->droit === 'SupAdmin' ? ($data['idEcole'] ?? null) : ($idEcole ?: $authUser->idEcole);
        $school = $targetSchoolId ? Ecole::withoutGlobalScopes()->find($targetSchoolId) : null;

        if (SchoolOrderAccess::isComplex($school) && empty(SchoolOrderAccess::normalizeMany($data['managed_orders'] ?? []))) {
            throw ValidationException::withMessages([
                'managed_orders' => 'Veuillez sélectionner au moins un ordre d’enseignement pour ce gestionnaire du complexe.',
            ]);
        }
    }

    private function ensureUniqueLinkedUser(string $column, int $id, ?int $idEcole = null): void
    {
        $query = User::where($column, $id);
        if ($idEcole) {
            $query->where('idEcole', $idEcole);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                $column => 'Un compte existe déjà pour ce profil.',
            ]);
        }
    }

    private function syncDefaultPermissions(User $user, int $type): void
    {
        $names = $this->defaultPermissionNames($user, $type);

        $permissionIds = Permission::query()
            ->selectRaw('MIN(id) as id')
            ->whereIn('name', $names)
            ->groupBy('name')
            ->pluck('id')
            ->all();

        $user->permissions()->syncWithoutDetaching($permissionIds);
    }

    private function defaultPermissionNames(User $user, int $type): array
    {
        if ($user->droit === 'SupAdmin') {
            return Permission::query()->distinct()->pluck('name')->all();
        }

        if ($user->droit === 'Admin') {
            return Permission::query()
                ->whereNotIn('name', [
                    'matieres_creation',
                    'matieres_action',
                    'dae_voiraction',
                    'dae_activer',
                    'dae_modifier',
                    'dae_permission',
                    'dcap_voiraction',
                    'dcap_activer',
                    'dcap_modifier',
                    'dcap_permission',
                    'dcap_apercu',
                    'academies_apercu',
                    'classes_officielles_apercu',
                    'permissions_apercu',
                    'permission_voir',
                    'permission_apercu',
                    'programmes_pdf',
                    'programme_pdf',
                    'programmes_creation',
                    'programme_creation',
                    'programme_création',
                    'programmes_modification',
                    'programme_modification',
                    'programmes_supprimer',
                    'programme_supprimer',
                    'programmes_suppression',
                    'programme_suppression',
                    'abonnements_configuration',
                    'abonnements_validation',
                ])
                ->distinct()
                ->pluck('name')
                ->all();
        }

        return match ($user->droit) {
            'enseignant' => [
                'enseignants_apercu',
                'enseignants_modification',
                'evaluation_apercu',
                'evaluation_création',
                'evaluation_modification',
                'evaluation_supprimer',
                'controle_apercu',
                'controle_creation',
                'controle_modification',
                'emargement_faire',
                'enseignants_emploi',
                'presence_apercu',
            ],
            'parent' => [
                'eleves_dossier',
            ],
            'DAE' => [
                'dae_apercu',
                'dae_voiraction',
                'dae_modifier',
                'dae_activer',
                'dae_permission',
                'academies_apercu',
                'ecoles_apercu',
                'enseignants_apercu',
                'eleves_apercu',
            ],
            'DCAP' => [
                'dcap_apercu',
                'dcap_voiraction',
                'dcap_modifier',
                'dcap_activer',
                'dcap_permission',
                'ecoles_apercu',
                'enseignants_apercu',
                'eleves_apercu',
            ],
            default => [
                'eleves_apercu',
                'dossiers_eleves_apercu',
                'inscriptions_apercu',
                'reinscriptions_apercu',
                'parents_apercu',
                'classes_apercu',
                'matieres_apercu',
                'programmes_apercu',
                'planifications_apercu',
            ],
        };
    }

    private function generatePassword(int $length = 10): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }

    private function validateAcademie(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'nom_academie' => 'required|string|max:100',
            'code_academie' => 'required|string|max:20|unique:academie,code_academie,' . $ignoreId . ',id_academie',
            'localite_academie' => 'required|string|max:100',
        ]);
    }

    private function validateCap(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'nom_cap' => 'required|string|max:100',
            'code_cap' => 'required|string|max:20|unique:cap,code_cap,' . $ignoreId . ',id_cap',
            'localite_cap' => 'required|string|max:100',
            'id_academie' => 'required|integer|exists:academie,id_academie',
        ]);
    }

    private function validateEcole(Request $request): array
    {
        if ($request->filled('telephone')) {
            $request->merge(['telephone' => MaliPhone::normalize($request->input('telephone'))]);
        }

        $data = $request->validate([
            'nomEcole' => 'required|string|max:100',
            'typeEcole' => 'required|string|in:Complexe Scolaire,Fondamentale I,Fondamentale II,Collège,Secondaire Generale,Secondaire Technique et Professionnel',
            'statut' => 'required|in:public,prive',
            'id_academie' => 'required|integer|exists:academie,id_academie',
            'id_cap' => 'nullable|integer|exists:cap,id_cap',
            'adresse' => 'nullable|string|max:1000',
            'telephone' => ['nullable', 'string', 'max:20', new MaliPhone()],
            'email' => 'nullable|email|max:100',
            'nomFondamental' => 'nullable|string|max:255',
            'nomLycee' => 'nullable|string|max:255',
            'nomProfessionnel' => 'nullable|string|max:255',
            'nomComplexe' => 'nullable|string|max:255',
            'notification_sms' => 'nullable|boolean',
            'notification_email' => 'nullable|boolean',
            'logoEcole' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'abonnement_offre_id' => 'nullable',
        ]);

        unset($data['abonnement_offre_id']);

        $needsCap = in_array($data['typeEcole'], ['Fondamentale I', 'Fondamentale II', 'Collège'], true)
            || ($data['typeEcole'] === 'Complexe Scolaire' && !empty($data['nomFondamental']));

        if ($needsCap && empty($data['id_cap'])) {
            throw ValidationException::withMessages([
                'id_cap' => 'Le CAP est obligatoire pour une école fondamentale ou un complexe avec fondamentale.',
            ]);
        }

        if ($data['typeEcole'] === 'Fondamentale I' || $data['typeEcole'] === 'Fondamentale II' || $data['typeEcole'] === 'Collège') {
            $data['nomFondamental'] = $data['nomEcole'];
            $data['nomLycee'] = null;
            $data['nomProfessionnel'] = null;
            $data['nomComplexe'] = null;
        } elseif ($data['typeEcole'] === 'Secondaire Generale') {
            $data['id_cap'] = null;
            $data['nomFondamental'] = null;
            $data['nomLycee'] = $data['nomEcole'];
            $data['nomProfessionnel'] = null;
            $data['nomComplexe'] = null;
        } elseif ($data['typeEcole'] === 'Secondaire Technique et Professionnel') {
            $data['id_cap'] = null;
            $data['nomFondamental'] = null;
            $data['nomLycee'] = null;
            $data['nomProfessionnel'] = $data['nomEcole'];
            $data['nomComplexe'] = null;
        }

        return $data;
    }

    private function storeEcoleLogo(Request $request, ?string $currentLogo = null): ?string
    {
        if (!$request->hasFile('logoEcole')) {
            return $currentLogo;
        }

        $directory = public_path('images_ecoles');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $file = $request->file('logoEcole');
        $name = uniqid('ecole_', true) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $name);

        if ($currentLogo) {
            $currentPath = public_path(ltrim($currentLogo, '/'));
            if (str_starts_with($currentPath, $directory . DIRECTORY_SEPARATOR) && File::exists($currentPath)) {
                File::delete($currentPath);
            }
        }

        return 'images_ecoles/' . $name;
    }

    private function hydrateEcoleLegacyLabels(array &$data): void
    {
        $academie = !empty($data['id_academie']) ? Academie::find($data['id_academie']) : null;
        $cap = !empty($data['id_cap']) ? Cap::find($data['id_cap']) : null;

        $data['academie'] = $academie?->nom_academie ?? ($data['academie'] ?? '');
        $data['cap'] = $cap?->nom_cap ?? ($data['cap'] ?? null);
        $data['notification_sms'] = !empty($data['notification_sms']) ? 1 : 0;
        $data['notification_email'] = !empty($data['notification_email']) ? 1 : 0;
    }

    private function authorizeEcoleMutation(Ecole $ecole): void
    {
        $this->authorizeSupAdminOnly();
    }

    private function activateInitialSubscription(Request $request, Ecole $ecole): void
    {
        $offreId = $request->input('abonnement_offre_id');

        if (!$offreId || $offreId === '__KEEP__') {
            return;
        }

        $offre = AbonnementOffre::where('actif', true)->find($offreId);
        if (!$offre) {
            return;
        }

        $latestEnd = Abonnement::query()
            ->where('ecole_id', $ecole->idEcole)
            ->where('statut', 'actif')
            ->whereNotNull('fin_at')
            ->max('fin_at');

        $start = now()->startOfDay();
        if ($latestEnd && Carbon::parse($latestEnd)->isFuture()) {
            $start = Carbon::parse($latestEnd)->startOfDay();
        }

        // duree_jours <= 0 (ex: offre ACHAT) => licence à vie : pas de date de fin.
        $dureeJours = (int) $offre->duree_jours;

        Abonnement::create([
            'ecole_id' => $ecole->idEcole,
            'offre_id' => $offre->id,
            'statut' => 'actif',
            'debut_at' => $start,
            'fin_at' => $dureeJours > 0 ? $start->copy()->addDays($dureeJours) : null,
        ]);
    }

    public function typesNotes(Request $request)
    {
        $user = Auth::user();
        $this->authorizeAnyPermission($user, ['types_notes_apercu']);

        $search = $request->get('search');
        $typesNotes = Note::query()
            ->when($search, function ($query) use ($search) {
                $query->where('typeNote', 'like', "%{$search}%")
                      ->orWhere('codeNote', 'like', "%{$search}%");
            })
            ->orderBy('typeNote')
            ->paginate(15)
            ->withQueryString();

        return view('configuration.types-notes', compact('typesNotes'));
    }

    public function classesOfficielles(Request $request)
    {
        $user = Auth::user();
        $this->authorizeAnyPermission($user, ['classes_officielles_apercu']);

        $search = $request->get('search');
        $classesOfficielles = ClasseOfficielle::query()
            ->withCount('classes')
            ->when($search, function ($query) use ($search) {
                $query->where('nom_classe_officielle', 'like', "%{$search}%")
                    ->orWhere('ordre_enseignement', 'like', "%{$search}%");
            })
            ->orderBy('ordre_enseignement')
            ->orderBy('nom_classe_officielle')
            ->paginate(15)
            ->withQueryString();

        $ordres = $this->ordresClassesOfficielles();

        return view('configuration.classes-officielles', compact('classesOfficielles', 'ordres'));
    }

    public function storeClasseOfficielle(Request $request)
    {
        $user = Auth::user();
        $this->authorizeAnyPermission($user, ['classes_officielles_apercu']);

        $data = $this->validateClasseOfficielle($request);

        ClasseOfficielle::create($data);

        return redirect()->route('configuration.classes-officielles')->with('success', 'Classe officielle ajoutée avec succès.');
    }

    public function updateClasseOfficielle(Request $request, $id)
    {
        $user = Auth::user();
        $this->authorizeAnyPermission($user, ['classes_officielles_apercu']);

        $classeOfficielle = ClasseOfficielle::findOrFail($id);
        $classeOfficielle->update($this->validateClasseOfficielle($request));

        return redirect()->route('configuration.classes-officielles')->with('success', 'Classe officielle modifiée avec succès.');
    }

    public function destroyClasseOfficielle($id)
    {
        $user = Auth::user();
        $this->authorizeAnyPermission($user, ['classes_officielles_apercu']);

        $classeOfficielle = ClasseOfficielle::withCount('classes')->findOrFail($id);
        if ($classeOfficielle->classes_count > 0) {
            return redirect()->route('configuration.classes-officielles')
                ->with('error', 'Impossible de supprimer cette classe officielle : elle est utilisée par une classe.');
        }

        $classeOfficielle->delete();

        return redirect()->route('configuration.classes-officielles')->with('success', 'Classe officielle supprimée avec succès.');
    }

    public function storeTypeNote(Request $request)
    {
        $user = Auth::user();
        $this->authorizeAnyPermission($user, ['types_notes_apercu']);

        $data = $request->validate([
            'typeNote' => 'required|string|in:devoir,composition,NT10',
            'codeNote' => 'required|string|max:50',
            'valeur' => 'required|numeric|min:0',
        ]);

        $data['id_ecole'] = session('idEcole') ?: $user->idEcole;

        Note::create($data);

        return redirect()->route('configuration.types-notes')->with('success', 'Type de note ajouté avec succès.');
    }

    public function updateTypeNote(Request $request, $id)
    {
        $user = Auth::user();
        $this->authorizeAnyPermission($user, ['types_notes_apercu']);

        $note = Note::findOrFail($id);

        $data = $request->validate([
            'typeNote' => 'required|string|in:devoir,composition,NT10',
            'codeNote' => 'required|string|max:50',
            'valeur' => 'required|numeric|min:0',
        ]);

        $note->update($data);

        return redirect()->route('configuration.types-notes')->with('success', 'Type de note modifié avec succès.');
    }

    public function destroyTypeNote($id)
    {
        $user = Auth::user();
        $this->authorizeAnyPermission($user, ['types_notes_apercu']);

        $note = Note::findOrFail($id);
        $note->delete();

        return redirect()->route('configuration.types-notes')->with('success', 'Type de note supprimé avec succès.');
    }

    private function validateClasseOfficielle(Request $request): array
    {
        return $request->validate([
            'nom_classe_officielle' => 'required|string|max:255',
            'ordre_enseignement' => ['required', 'string', Rule::in(array_keys($this->ordresClassesOfficielles()))],
        ]);
    }

    private function ordresClassesOfficielles(): array
    {
        return [
            'Fondamentale I' => 'Fondamentale I',
            'Fondamentale II' => 'Fondamentale II',
            'Secondaire Generale' => 'Secondaire Général',
            'Secondaire Technique et Professionnel' => 'Secondaire Technique et Professionnel',
        ];
    }

    public function statusControles(Request $request)
    {
        $user = Auth::user();
        $this->authorizeAnyPermission($user, ['status_controles_apercu']);

        $search = $request->get('search');
        $statusControles = Controle::query()
            ->when($search, function ($query) use ($search) {
                $query->where('type_controle', 'like', "%{$search}%");
            })
            ->orderBy('type_controle')
            ->paginate(15)
            ->withQueryString();

        return view('configuration.status-controles', compact('statusControles'));
    }

    public function storeStatusControle(Request $request)
    {
        $user = Auth::user();
        $this->authorizeAnyPermission($user, ['status_controles_apercu']);
        $request->merge([
            'penalite_conduite' => str_replace(',', '.', (string) $request->input('penalite_conduite')),
        ]);

        $data = $request->validate([
            'controle' => 'required|string|max:150',
            'alert' => 'nullable|string|in:oui,non',
            'penalite_conduite' => 'required|numeric|min:0|max:18',
        ]);

        $payload = [
            'type_controle' => $data['controle'],
            'alertControle' => ($request->has('alert') && $data['alert'] !== 'non') ? 'oui' : 'non',
            'penalite_conduite' => $data['penalite_conduite'],
            'id_ecole' => session('idEcole') ?: $user->idEcole,
        ];

        Controle::create($payload);

        return redirect()->route('configuration.status-controles')->with('success', 'Statut de contrôle ajouté avec succès.');
    }

    public function updateStatusControle(Request $request, $id)
    {
        $user = Auth::user();
        $this->authorizeAnyPermission($user, ['status_controles_apercu']);
        $request->merge([
            'penalite_conduite' => str_replace(',', '.', (string) $request->input('penalite_conduite')),
        ]);

        $controle = Controle::findOrFail($id);

        $data = $request->validate([
            'controle' => 'required|string|max:150',
            'alert' => 'required|string|in:oui,non',
            'penalite_conduite' => 'required|numeric|min:0|max:18',
        ]);

        $payload = [
            'type_controle' => $data['controle'],
            'alertControle' => $data['alert'] === 'oui' ? 'oui' : 'non',
            'penalite_conduite' => $data['penalite_conduite'],
        ];

        $controle->update($payload);

        return redirect()->route('configuration.status-controles')->with('success', 'Statut de contrôle modifié avec succès.');
    }

    public function destroyStatusControle($id)
    {
        $user = Auth::user();
        $this->authorizeAnyPermission($user, ['status_controles_apercu']);

        $controle = Controle::findOrFail($id);
        $controle->delete();

        return redirect()->route('configuration.status-controles')->with('success', 'Statut de contrôle supprimé avec succès.');
    }
}
