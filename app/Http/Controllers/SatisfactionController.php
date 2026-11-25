<?php

namespace App\Http\Controllers;

use App\Services\Support\CsbSatisfaction;
use App\Support\Satisfaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class SatisfactionController extends Controller
{
    /** Réponse au petit modal (avis, « Plus tard » ou « Ne plus me demander »). */
    public function store(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && in_array($user->droit, Satisfaction::ROLES, true) && Satisfaction::disponible(), 403);

        $data = $request->validate([
            'statut' => ['required', Rule::in(['repondu', 'plus_tard', 'jamais'])],
            'note' => ['required_if:statut,repondu', 'nullable', 'integer', 'between:1,5'],
            'points' => ['nullable', 'array', 'max:6'],
            'points.*' => ['string', Rule::in(array_keys(Satisfaction::POINTS_FORTS + Satisfaction::POINTS_FAIBLES))],
            'commentaire' => ['nullable', 'string', 'max:1000'],
            'accord_publication' => ['nullable', 'boolean'],
            'contexte' => ['nullable', 'string', 'max:60'],
        ]);

        $repondu = $data['statut'] === 'repondu';
        DB::table('avis_satisfaction')->insert([
            'user_id' => $user->idUtilisateur,
            'id_ecole' => session('idEcole') ?: $user->idEcole,
            'statut' => $data['statut'],
            'note' => $repondu ? (int) $data['note'] : null,
            'points' => $repondu && !empty($data['points']) ? json_encode(array_values(array_unique($data['points']))) : null,
            'commentaire' => $repondu ? (trim((string) ($data['commentaire'] ?? '')) ?: null) : null,
            'contexte' => $data['contexte'] ?? null,
            'droit' => $user->droit,
            // Publication proposée seulement aux avis positifs.
            'accord_publication' => $repondu && (int) $data['note'] >= 4 && $request->boolean('accord_publication'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    /** Rapport de satisfaction (SupAdmin). */
    public function index(Request $request)
    {
        $this->supAdmin();
        [$depuis, $jusqua] = $this->periode($request);

        $rapport = Satisfaction::rapport($depuis, $jusqua);
        $avis = DB::table('avis_satisfaction as a')
            ->leftJoin('utilisateurs as u', 'u.idUtilisateur', '=', 'a.user_id')
            ->leftJoin('ecole as e', 'e.idEcole', '=', 'a.id_ecole')
            ->where('a.statut', 'repondu')
            ->when($depuis, fn ($q) => $q->where('a.created_at', '>=', $depuis->copy()->startOfDay()))
            ->when($jusqua, fn ($q) => $q->where('a.created_at', '<=', $jusqua->copy()->endOfDay()))
            ->when($request->filled('note'), fn ($q) => $q->where('a.note', (int) $request->query('note')))
            ->orderByDesc('a.created_at')
            ->select('a.*', 'u.nomPrenom', 'u.fonction', 'e.nomEcole')
            ->paginate(25)->withQueryString();

        $relances = DB::table('avis_satisfaction')->whereIn('statut', ['plus_tard', 'jamais'])
            ->when($depuis, fn ($q) => $q->where('created_at', '>=', $depuis->copy()->startOfDay()))
            ->selectRaw('statut, count(*) as n')->groupBy('statut')->pluck('n', 'statut');
        $dernierePublication = DB::table('satisfaction_publications')->latest('publie_at')->first();

        return view('satisfaction.index', [
            'rapport' => $rapport,
            'avis' => $avis,
            'relances' => $relances,
            'dernierePublication' => $dernierePublication,
            'csbConfigure' => CsbSatisfaction::configure(),
            'filtres' => ['depuis' => $depuis?->toDateString(), 'jusqua' => $jusqua?->toDateString(), 'note' => $request->query('note')],
        ]);
    }

    /** Met en ligne ou retire un témoignage sur le site de CSB. */
    public function publierAvis(Request $request, int $id, CsbSatisfaction $csb)
    {
        $this->supAdmin();
        $avis = DB::table('avis_satisfaction')->where('id', $id)->where('statut', 'repondu')->first();
        abort_unless($avis, 404);
        $enLigne = !$request->boolean('retirer');

        try {
            $csb->publierAvis($avis, $enLigne);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Le site de Computer Service Barry n\'a pas répondu : réessayez dans un instant.');
        }

        return back()->with('success', $enLigne ? 'Avis publié sur computerservicebarry.com.' : 'Avis retiré du site.');
    }

    /** Publie le rapport chiffré de la période sur le site de CSB. */
    public function publierRapport(Request $request, CsbSatisfaction $csb)
    {
        $this->supAdmin();
        [$depuis, $jusqua] = $this->periode($request);

        try {
            $url = $csb->publierRapport(Satisfaction::rapport($depuis, $jusqua), (int) Auth::id());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Le site de Computer Service Barry n\'a pas répondu : réessayez dans un instant.');
        }

        return back()->with('success', 'Rapport publié sur computerservicebarry.com.'.($url ? ' Lien à partager : '.$url : ''));
    }

    /** Rapport en PDF, à envoyer aux partenaires et clients. */
    public function pdf(Request $request)
    {
        $this->supAdmin();
        [$depuis, $jusqua] = $this->periode($request);
        $rapport = Satisfaction::rapport($depuis, $jusqua);
        $citations = DB::table('avis_satisfaction as a')
            ->join('utilisateurs as u', 'u.idUtilisateur', '=', 'a.user_id')
            ->leftJoin('ecole as e', 'e.idEcole', '=', 'a.id_ecole')
            ->where('a.statut', 'repondu')->where('a.accord_publication', true)->where('a.note', '>=', 4)->whereNotNull('a.commentaire')
            ->when($depuis, fn ($q) => $q->where('a.created_at', '>=', $depuis->copy()->startOfDay()))
            ->when($jusqua, fn ($q) => $q->where('a.created_at', '<=', $jusqua->copy()->endOfDay()))
            ->orderByDesc('a.note')->orderByDesc('a.created_at')->limit(6)
            ->get(['a.note', 'a.commentaire', 'u.nomPrenom', 'u.fonction', 'e.nomEcole']);

        return Pdf::loadView('pdf.rapport_satisfaction', compact('rapport', 'citations'))
            ->setPaper('a4', 'portrait')
            ->download('Rapport_satisfaction_KalanNet_'.now()->format('Y-m-d').'.pdf');
    }

    /** @return array{0: ?Carbon, 1: ?Carbon} période demandée (douze derniers mois par défaut) */
    private function periode(Request $request): array
    {
        $date = fn (?string $v) => $v && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? Carbon::parse($v) : null;
        $depuis = $request->has('depuis') ? $date($request->input('depuis')) : now()->subYear();
        $jusqua = $date($request->input('jusqua'));

        return [$depuis, $jusqua];
    }

    private function supAdmin(): void
    {
        abort_unless(Auth::user()?->droit === 'SupAdmin', 403);
    }
}
