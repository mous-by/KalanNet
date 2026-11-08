<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\AppNotification;
use App\Models\Ecole;
use App\Models\RapportSecondaire;
use App\Support\ModelesBulletin;
use App\Support\RapportSecondaireDefinitions as Def;
use App\Support\RapportSecondaireDonnees as Donnees;
use App\Support\RapportSuccinctDonnees;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Rapports de rentrée et de fin d'année du secondaire (général, technique et professionnel) : pré-remplis, téléchargeables
 * à tout moment, remis à l'académie (par KalanNet si elle a un compte, sinon sur papier). Le DAE suit ceux de son académie.
 */
class RapportSecondaireController extends Controller
{
    private function ecole(): Ecole
    {
        abort_unless(RapportSuccinctController::autorise(Auth::user()), 403);
        $ecole = Ecole::withoutGlobalScopes()->findOrFail(session('idEcole') ?: Auth::user()->idEcole);
        abort_unless(RapportSuccinctDonnees::ecoleConcernee((int) $ecole->idEcole, 'secondaire'), 403, 'Ces rapports concernent le secondaire.');

        return $ecole;
    }

    private function type(string $type): string
    {
        abort_unless(isset(Def::TYPES[$type]), 404);

        return $type;
    }

    private function annees()
    {
        return AnneeScolaire::whereDate('date_debut', '<=', now()->toDateString())->orderByDesc('date_debut')->get();
    }

    private function annee(Request $request, Ecole $ecole): AnneeScolaire
    {
        $annee = $this->annees()->firstWhere('id_anneeScolaire', $request->integer('annee')) ?? AnneeScolaire::enCours((int) $ecole->idEcole);
        abort_unless($annee, 404, "Aucune année scolaire n'est disponible.");

        return $annee;
    }

    private function rapport(Ecole $ecole, AnneeScolaire $annee, string $type): ?RapportSecondaire
    {
        return RapportSecondaire::where('id_ecole', $ecole->idEcole)->where('id_annee', $annee->id_anneeScolaire)->where('type', $type)->first();
    }

    public function index(Request $request, string $type)
    {
        $type = $this->type($type);
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole);
        $rapport = $this->rapport($ecole, $annee, $type);
        $donnees = $rapport?->donnees ?? Donnees::calculer($ecole, $annee, $type);

        // « Reprendre les chiffres de KalanNet » : tableaux pré-remplis recalculés, le reste est gardé.
        if ($request->boolean('recalculer')) {
            $calcul = Donnees::calculer($ecole, $annee, $type);
            $donnees['t'] = array_replace($donnees['t'] ?? [], $calcul['t']);
            foreach (['effectif_f', 'effectif_g', 'groupes', 'enseignants_f', 'enseignants_h'] as $champ) {
                $donnees['infos'][$champ] = $calcul['infos'][$champ];
            }
        }

        return view('rapports-secondaire.index', [
            'ecole' => $ecole, 'annee' => $annee, 'annees' => $this->annees(), 'rapport' => $rapport, 'donnees' => $donnees, 'type' => $type,
            'tableaux' => Donnees::tableaux($type, (int) $ecole->idEcole), 'infos' => Def::infos($type),
            'surKalanNet' => RapportSuccinctDonnees::academieSurKalanNet($ecole->id_academie),
            'sansDate' => $type === 'rentree' ? Donnees::sansDateDeNaissance((int) $ecole->idEcole, (int) $annee->id_anneeScolaire) : 0,
            'manquants' => Donnees::champsManquants($donnees),
        ]);
    }

    public function store(Request $request, string $type)
    {
        $type = $this->type($type);
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole);

        $request->validate(['infos' => 'nullable|array', 'infos.*' => 'nullable|string|max:255', 'tableaux_json' => 'nullable|string']);
        $infos = collect(Def::infos($type))->map(fn ($l, $champ) => filled($request->input("infos.$champ")) ? trim((string) $request->input("infos.$champ")) : null)->all();
        // Tous les tableaux arrivent dans un seul champ : la limite de champs de PHP ne peut pas tronquer la saisie.
        $saisie = json_decode((string) $request->input('tableaux_json', '{}'), true);
        $donnees = ['type' => $type, 'infos' => $infos, 't' => Donnees::assainir(is_array($saisie) ? $saisie : [], Donnees::tableaux($type, (int) $ecole->idEcole))];

        $rapport = RapportSecondaire::firstOrNew(['id_ecole' => $ecole->idEcole, 'id_annee' => $annee->id_anneeScolaire, 'type' => $type]);
        $rapport->donnees = $donnees;
        $rapport->statut ??= 'brouillon';
        $rapport->save();

        $retour = ['type' => $type, 'annee' => $annee->id_anneeScolaire];
        if (in_array($request->input('action'), ['telecharger', 'excel'], true)) {
            return redirect()->route('rapport-secondaire.pdf', $retour + ($request->input('action') === 'excel' ? ['format' => 'excel'] : []));
        }
        if ($request->input('action') !== 'remettre') {
            return redirect()->route('rapport-secondaire.index', $retour)->with('success', 'Rapport enregistré.');
        }

        $manquants = Donnees::champsManquants($donnees);
        if ($manquants !== []) {
            throw ValidationException::withMessages(['rapport' => 'À compléter avant de remettre le rapport : '.implode(', ', $manquants).'.']);
        }
        $surKalanNet = RapportSuccinctDonnees::academieSurKalanNet($ecole->id_academie);
        $rapport->update(['statut' => $surKalanNet ? 'envoye' : 'remis', 'id_academie' => $ecole->id_academie, 'donnees_envoyees' => $donnees, 'envoye_at' => now(), 'envoye_par' => Auth::id()]);
        if (!$surKalanNet) {
            return redirect()->route('rapport-secondaire.index', $retour)->with('success', 'Rapport marqué comme remis à votre académie.');
        }
        DB::table('utilisateurs')->where('droit', 'DAE')->where('id_academie', $ecole->id_academie)->where('statut', 1)->pluck('idUtilisateur')
            ->each(fn ($dae) => AppNotification::create([
                'user_id' => $dae, 'type' => 'rapport_secondaire', 'title' => Def::TYPES[$type].' reçu',
                'message' => $ecole->nomEcole.' a envoyé son '.Str::lower(Def::TYPES[$type]).' '.$annee->annee.'.',
                'link' => route('territoire.rapports-secondaire', ['type' => $type, 'annee' => $annee->id_anneeScolaire], false),
            ]));

        return redirect()->route('rapport-secondaire.index', $retour)->with('success', 'Rapport envoyé à votre académie.');
    }

    public function pdf(Request $request, string $type)
    {
        $type = $this->type($type);
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole);
        $donnees = $this->rapport($ecole, $annee, $type)?->donnees ?? Donnees::calculer($ecole, $annee, $type);

        return $this->fichier($donnees, $type, (int) $ecole->idEcole, (string) $annee->annee, !$request->boolean('voir'), $request->input('format') === 'excel');
    }

    private function fichier(array $donnees, string $type, int $idEcole, string $annee, bool $telecharger, bool $excel = false)
    {
        if ($excel) {
            return \App\Services\Rapports\ExportExcel::rapportSecondaire($donnees, $type, $annee, Donnees::tableaux($type, $idEcole))
                ->telecharger('rapport-'.$type.'-'.Str::slug($donnees['infos']['nom'] ?? 'etablissement').'-'.$annee);
        }
        $academie = DB::table('ecole as e')->leftJoin('academie as a', 'a.id_academie', '=', 'e.id_academie')->where('e.idEcole', $idEcole)->value(DB::raw('COALESCE(a.nom_academie, e.academie)'));
        $pdf = Pdf::loadView('pdf.rapport_secondaire', [
            'd' => $donnees, 'type' => $type, 'annee' => $annee, 'academie' => $academie,
            'tableaux' => Donnees::tableaux($type, $idEcole), 'infos' => Def::infos($type),
        ])->setPaper('a4', 'landscape');
        $nom = 'rapport-'.$type.'-'.Str::slug($donnees['infos']['nom'] ?? 'etablissement').'-'.$annee.'.pdf';

        return $telecharger ? $pdf->download($nom) : $pdf->stream($nom);
    }

    /* ——— Espace de l'académie ——— */

    private function dae()
    {
        $user = Auth::user();
        abort_unless($user->droit === 'DAE' && $user->id_academie, 403);

        return $user;
    }

    public function territoire(Request $request)
    {
        $user = $this->dae();
        $type = isset(Def::TYPES[$request->input('type')]) ? $request->input('type') : 'rentree';
        $annees = DB::table('anneescolaire')->whereNull('id_ecole')->whereDate('date_debut', '<=', now()->toDateString())->orderByDesc('date_debut')->get();
        $annee = $annees->firstWhere('id_anneeScolaire', $request->integer('annee')) ?? $annees->first();

        $ecoles = DB::table('ecole as e')->where('e.id_academie', $user->id_academie)
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('classe')->whereColumn('classe.idEcole', 'e.idEcole')->whereIn('classe.ordreEnseignement', RapportSuccinctDonnees::SECONDAIRE))
            ->orderBy('e.nomEcole')->get(['e.idEcole', 'e.nomEcole', 'e.adresse', 'e.typeEcole'])
            ->reject(fn ($e) => ModelesBulletin::estEcoleSante($e->typeEcole))->values();
        $idsAnnee = DB::table('anneescolaire')->where('annee', $annee?->annee)->pluck('id_anneeScolaire');
        $rapports = RapportSecondaire::withoutGlobalScopes()->whereIn('id_annee', $idsAnnee)->where('type', $type)->where('statut', '!=', 'brouillon')
            ->whereIn('id_ecole', $ecoles->pluck('idEcole'))->get()->keyBy('id_ecole');
        $lignes = $ecoles->map(fn ($e) => (object) ['ecole' => $e, 'rapport' => $rapports->get($e->idEcole)]);

        return view('territoire.rapports-secondaire', compact('lignes', 'annees', 'annee', 'type'));
    }

    public function pdfTerritoire(Request $request, int $id)
    {
        $user = $this->dae();
        $rapport = RapportSecondaire::withoutGlobalScopes()->where('statut', '!=', 'brouillon')->findOrFail($id);
        abort_unless((int) DB::table('ecole')->where('idEcole', $rapport->id_ecole)->value('id_academie') === (int) $user->id_academie, 404);

        return $this->fichier($rapport->donnees_envoyees, $rapport->type, (int) $rapport->id_ecole, (string) DB::table('anneescolaire')->where('id_anneeScolaire', $rapport->id_annee)->value('annee'), $request->boolean('telecharger'), $request->input('format') === 'excel');
    }
}
