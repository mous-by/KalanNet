<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use App\Models\AbonnementOffre;
use App\Models\AbonnementPaiement;
use App\Models\Ecole;
use App\Models\Permission;
use App\Models\Revendeur;
use App\Models\RevendeurOffre;
use App\Models\User;
use App\Rules\MaliPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RevendeurController extends Controller
{
    /**
     * Permissions accordées automatiquement à tout compte revendeur créé —
     * strictement le périmètre abonnement de ses propres écoles, jamais leurs
     * données pédagogiques ou financières internes.
     */
    protected const DEFAULT_PERMISSIONS = ['revendeur_apercu', 'revendeur_tarifs'];

    /*
    |--------------------------------------------------------------------
    | Côté SupAdmin : gestion des fiches revendeur
    |--------------------------------------------------------------------
    */

    public function index()
    {
        $this->authorizeSupAdminOnly();

        $revendeurs = Revendeur::with(['utilisateur', 'offres.offre'])
            ->withCount('ecoles')
            ->orderBy('nom')
            ->get();

        $offres = AbonnementOffre::orderBy('montant')->get();

        // Un revendeur encaisse le prix de revente sur ses propres numéros —
        // le développeur reste créancier du prix de gros pour chaque paiement
        // validé, tant que ce n'est pas marqué reversé.
        $reversements = AbonnementPaiement::with(['offre', 'ecole'])
            ->whereNotNull('reverse_statut')
            ->whereHas('ecole', fn ($q) => $q->whereNotNull('id_revendeur'))
            ->orderByDesc('paye_at')
            ->get()
            ->groupBy(fn (AbonnementPaiement $p) => $p->ecole->id_revendeur);

        return view('configuration.revendeurs.index', compact('revendeurs', 'offres', 'reversements'));
    }

    /**
     * Le SupAdmin confirme avoir reçu, hors application, la part de gros que
     * le revendeur lui devait pour ce paiement.
     */
    public function markReversementRecu(AbonnementPaiement $paiement)
    {
        $this->authorizeSupAdminOnly();

        if ($paiement->reverse_statut !== 'en_attente') {
            return back()->with('error', 'Ce paiement n’a pas de reversement en attente.');
        }

        $paiement->update([
            'reverse_statut' => 'recu',
            'reverse_at' => now(),
            'reverse_par' => Auth::id(),
        ]);

        return back()->with('success', 'Reversement marqué comme reçu.');
    }

    public function store(Request $request)
    {
        $this->authorizeSupAdminOnly();

        $data = $request->validate([
            'nom' => 'required|string|max:150',
            'nomPrenom' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('utilisateurs', 'email')],
            'telephone' => ['required', 'string', 'max:20', new MaliPhone()],
            'pwd' => 'required|string|min:4',
            'numero_orange_wave' => 'nullable|string|max:30',
            'numero_mobicash' => 'nullable|string|max:30',
        ]);

        $revendeur = Revendeur::create([
            'nom' => $data['nom'],
            'numero_orange_wave' => $data['numero_orange_wave'] ?? null,
            'numero_mobicash' => $data['numero_mobicash'] ?? null,
            'actif' => true,
        ]);

        $user = User::create([
            'nomPrenom' => $data['nomPrenom'],
            'email' => $data['email'],
            'telephone' => MaliPhone::normalize($data['telephone']),
            'pwd' => Hash::make($data['pwd']),
            'droit' => 'revendeur',
            'id_revendeur' => $revendeur->id,
            'statut' => 1,
        ]);

        $permissionIds = Permission::whereIn('name', self::DEFAULT_PERMISSIONS)->pluck('id');
        $user->permissions()->syncWithoutDetaching($permissionIds);

        return back()->with('success', 'Revendeur créé avec succès.');
    }

    public function update(Request $request, Revendeur $revendeur)
    {
        $this->authorizeSupAdminOnly();
        $revendeur->loadMissing('utilisateur');

        $data = $request->validate([
            'nom' => 'required|string|max:150',
            'numero_orange_wave' => 'nullable|string|max:30',
            'numero_mobicash' => 'nullable|string|max:30',
            'nomPrenom' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('utilisateurs', 'email')->ignore($revendeur->utilisateur?->idUtilisateur, 'idUtilisateur')],
            'telephone' => ['required', 'string', 'max:20', new MaliPhone()],
            'pwd' => 'nullable|string|min:4',
        ]);

        $revendeur->update([
            'nom' => $data['nom'],
            'numero_orange_wave' => $data['numero_orange_wave'] ?? null,
            'numero_mobicash' => $data['numero_mobicash'] ?? null,
        ]);

        if ($revendeur->utilisateur) {
            $userPayload = [
                'nomPrenom' => $data['nomPrenom'],
                'email' => $data['email'],
                'telephone' => MaliPhone::normalize($data['telephone']),
            ];
            if (!empty($data['pwd'])) {
                $userPayload['pwd'] = Hash::make($data['pwd']);
            }
            $revendeur->utilisateur->update($userPayload);
        }

        return back()->with('success', 'Revendeur modifié avec succès.');
    }

    public function toggle(Revendeur $revendeur)
    {
        $this->authorizeSupAdminOnly();

        $revendeur->update(['actif' => !$revendeur->actif]);

        return back()->with('success', $revendeur->actif ? 'Revendeur activé.' : 'Revendeur désactivé.');
    }

    /**
     * Ouvre (ou met à jour) une formule pour un revendeur, au prix de gros du
     * catalogue par défaut — le revendeur ajustera ensuite son propre prix de
     * revente depuis son espace.
     */
    public function storeOffre(Request $request, Revendeur $revendeur)
    {
        $this->authorizeSupAdminOnly();

        $data = $request->validate([
            'id_offre' => 'required|integer|exists:abonnement_offres,id',
            'montant_revente' => 'required|numeric|min:0',
        ]);

        RevendeurOffre::updateOrCreate(
            ['id_revendeur' => $revendeur->id, 'id_offre' => $data['id_offre']],
            ['montant_revente' => $data['montant_revente'], 'actif' => true]
        );

        return back()->with('success', 'Formule ouverte pour ce revendeur.');
    }

    public function toggleOffre(RevendeurOffre $revendeurOffre)
    {
        $this->authorizeSupAdminOnly();

        $revendeurOffre->update(['actif' => !$revendeurOffre->actif]);

        return back()->with('success', 'Disponibilité de la formule mise à jour.');
    }

    /*
    |--------------------------------------------------------------------
    | Côté revendeur : espace en libre-service
    |--------------------------------------------------------------------
    */

    public function dashboard()
    {
        $revendeur = $this->currentRevendeur();

        $ecoles = Ecole::withoutGlobalScopes()
            ->where('id_revendeur', $revendeur->id)
            ->orderBy('nomEcole')
            ->get();

        $abonnementsByEcole = Abonnement::with('offre')
            ->whereIn('ecole_id', $ecoles->pluck('idEcole'))
            ->orderByDesc('id')
            ->get()
            ->groupBy('ecole_id');

        $paiementsByEcole = AbonnementPaiement::with('offre')
            ->whereIn('ecole_id', $ecoles->pluck('idEcole'))
            ->orderByDesc('id')
            ->get()
            ->groupBy('ecole_id');

        $rows = $ecoles->map(function (Ecole $ecole) use ($abonnementsByEcole, $paiementsByEcole) {
            return [
                'ecole' => $ecole,
                'abonnement' => $abonnementsByEcole->get($ecole->idEcole, collect())->first(),
                'paiements' => $paiementsByEcole->get($ecole->idEcole, collect())->take(10),
            ];
        });

        // Paiements de ses écoles en attente de sa propre validation — c'est
        // le revendeur qui valide les paiements des écoles qu'il a apportées,
        // pas le SupAdmin (qui garde néanmoins toujours accès à tout).
        $pendingValidations = AbonnementPaiement::with(['offre', 'ecole'])
            ->whereIn('ecole_id', $ecoles->pluck('idEcole'))
            ->where('statut', 'en_attente')
            ->orderByDesc('id')
            ->get();

        return view('revendeur.dashboard', compact('revendeur', 'rows', 'pendingValidations'));
    }

    public function updateNumeros(Request $request)
    {
        $revendeur = $this->currentRevendeur();

        $data = $request->validate([
            'numero_orange_wave' => 'nullable|string|max:30',
            'numero_mobicash' => 'nullable|string|max:30',
        ]);

        $revendeur->update($data);

        return back()->with('success', 'Numéros de dépôt mis à jour.');
    }

    public function tarifs()
    {
        $revendeur = $this->currentRevendeur();

        $offres = RevendeurOffre::with('offre')
            ->where('id_revendeur', $revendeur->id)
            ->get();

        return view('revendeur.tarifs', compact('revendeur', 'offres'));
    }

    public function updateTarif(Request $request, RevendeurOffre $revendeurOffre)
    {
        $revendeur = $this->currentRevendeur();

        if ((int) $revendeurOffre->id_revendeur !== (int) $revendeur->id) {
            abort(403);
        }

        $data = $request->validate([
            'montant_revente' => 'required|numeric|min:0',
        ]);

        $revendeurOffre->update(['montant_revente' => $data['montant_revente']]);

        return back()->with('success', 'Tarif de revente mis à jour.');
    }

    protected function currentRevendeur(): Revendeur
    {
        $user = Auth::user();

        if ($user->droit !== 'revendeur' || !$user->id_revendeur) {
            abort(403);
        }

        return Revendeur::findOrFail($user->id_revendeur);
    }

    protected function authorizeSupAdminOnly(): void
    {
        if (Auth::user()->droit !== 'SupAdmin') {
            abort(403);
        }
    }
}
