<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Assistance\AccesAssistance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Accès d'assistance aux comptes des clients (SupAdmin, avec l'accord de l'école), et son journal. */
class AssistanceController extends Controller
{
    public function __construct(private AccesAssistance $assistance)
    {
    }

    /** SupAdmin : demande d'accès, puis page d'attente de l'accord. */
    public function demander(Request $request, int $id)
    {
        $superviseur = Auth::user();
        $cible = User::withoutGlobalScopes()->findOrFail($id);
        if ($refus = AccesAssistance::refus($superviseur, $cible)) {
            return back()->with('error', $refus);
        }
        $data = $request->validate(['motif' => 'required|string|min:10|max:255'], [
            'motif.required' => __('assistance.motif_requis'),
            'motif.min' => __('assistance.motif_court'),
        ]);

        $idAcces = $this->assistance->demander($request, $superviseur, $cible, trim($data['motif']));

        return redirect()->route('assistance.attente', $idAcces);
    }

    public function attente(int $id)
    {
        if (!$acces = $this->demandeDuSuperviseur($id)) {
            return redirect()->route('dashboard')->with('error', __('assistance.autre_compte'));
        }
        $compte = DB::table('utilisateurs')->where('idUtilisateur', $acces->id_utilisateur)->value('nomPrenom');

        return view('assistance.attente', ['acces' => $acces, 'compte' => $compte, 'restant' => max(0, now()->diffInSeconds($acces->expire_demande_at, false))]);
    }

    /** Statut de la demande, interrogé par la page d'attente. */
    public function statut(int $id)
    {
        if (!$acces = $this->demandeDuSuperviseur($id)) {
            return response()->json(['statut' => 'autre_compte', 'restant' => 0], 403);
        }

        return response()->json(['statut' => $acces->statut, 'restant' => (int) max(0, now()->diffInSeconds($acces->expire_demande_at, false))]);
    }

    public function ouvrir(Request $request, int $id)
    {
        // Autre compte connecté dans ce navigateur (ex. l'Admin de l'école pour accepter) : session SupAdmin remplacée.
        if (!$this->demandeDuSuperviseur($id)) {
            return redirect()->route('dashboard')->with('error', __('assistance.autre_compte'));
        }
        if (!$this->assistance->ouvrir($request, Auth::user(), $id)) {
            return redirect()->route('configuration.utilisateurs')->with('error', __('assistance.non_accepte'));
        }
        $acces = AccesAssistance::enCours();

        return redirect()->route('dashboard')->with('success', __('assistance.ouvert', ['nom' => $acces['nom_cible'], 'minutes' => AccesAssistance::DUREE_MINUTES]));
    }

    /** Admin de l'école : demande en attente (fenêtre d'accord). */
    public function demandeEnAttente()
    {
        $demande = AccesAssistance::demandePour(Auth::user());

        return response()->json(['demande' => $demande ? [
            'id' => $demande->id,
            'compte' => $demande->compte,
            'motif' => $demande->motif,
            'restant' => (int) max(0, now()->diffInSeconds($demande->expire_demande_at, false)),
        ] : null]);
    }

    public function repondre(Request $request, int $id)
    {
        $data = $request->validate(['decision' => 'required|in:accepter,refuser']);
        $ok = $this->assistance->repondre(Auth::user(), $id, $data['decision'] === 'accepter');

        return $request->expectsJson()
            ? response()->json(['ok' => $ok, 'message' => $ok ? __('assistance.reponse_'.$data['decision']) : __('assistance.reponse_trop_tard')], $ok ? 200 : 409)
            : back()->with($ok ? 'success' : 'error', $ok ? __('assistance.reponse_'.$data['decision']) : __('assistance.reponse_trop_tard'));
    }

    public function fermer(Request $request)
    {
        $this->assistance->fermer($request);

        return redirect()->route('configuration.utilisateurs')->with('success', __('assistance.ferme'));
    }

    /** Journal interne : réservé aux SupAdmin. */
    public function journal()
    {
        abort_unless(Auth::user()->droit === 'SupAdmin' && !session()->has('assistance'), 403);

        $acces = DB::table('acces_assistance as a')
            ->leftJoin('utilisateurs as s', 's.idUtilisateur', '=', 'a.id_superviseur')
            ->leftJoin('utilisateurs as c', 'c.idUtilisateur', '=', 'a.id_utilisateur')
            ->leftJoin('utilisateurs as d', 'd.idUtilisateur', '=', 'a.decide_par')
            ->leftJoin('ecole as e', 'e.idEcole', '=', 'a.id_ecole')
            ->orderByDesc('a.debut_at')
            ->select('a.*', 's.nomPrenom as superviseur', 'c.nomPrenom as compte', 'c.droit as role', 'd.nomPrenom as decideur', 'e.nomEcole as ecole')
            ->paginate(50);

        return view('assistance.journal', compact('acces'));
    }

    /** La demande, uniquement pour le SupAdmin qui l'a faite (sinon null). */
    private function demandeDuSuperviseur(int $id): ?object
    {
        $acces = AccesAssistance::demande($id);

        return $acces && Auth::user()->droit === 'SupAdmin' && (int) $acces->id_superviseur === (int) Auth::id() ? $acces : null;
    }
}
