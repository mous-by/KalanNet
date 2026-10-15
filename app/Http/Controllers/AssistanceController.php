<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Assistance\AccesAssistance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Accès d'assistance aux comptes des clients (SupAdmin), et son journal. */
class AssistanceController extends Controller
{
    public function __construct(private AccesAssistance $assistance)
    {
    }

    public function ouvrir(Request $request, int $id)
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

        $this->assistance->ouvrir($request, $superviseur, $cible, trim($data['motif']));

        return redirect()->route('dashboard')->with('success', __('assistance.ouvert', ['nom' => $cible->nomPrenom, 'minutes' => AccesAssistance::DUREE_MINUTES]));
    }

    public function fermer(Request $request)
    {
        $this->assistance->fermer($request);

        return redirect()->route('configuration.utilisateurs')->with('success', __('assistance.ferme'));
    }

    /** Journal interne : réservé aux SupAdmin (jamais visible des écoles). */
    public function journal()
    {
        abort_unless(Auth::user()->droit === 'SupAdmin' && !session()->has('assistance'), 403);

        $acces = DB::table('acces_assistance as a')
            ->leftJoin('utilisateurs as s', 's.idUtilisateur', '=', 'a.id_superviseur')
            ->leftJoin('utilisateurs as c', 'c.idUtilisateur', '=', 'a.id_utilisateur')
            ->leftJoin('ecole as e', 'e.idEcole', '=', 'a.id_ecole')
            ->orderByDesc('a.debut_at')
            ->select('a.*', 's.nomPrenom as superviseur', 'c.nomPrenom as compte', 'c.droit as role', 'e.nomEcole as ecole')
            ->paginate(50);

        return view('assistance.journal', compact('acces'));
    }
}
