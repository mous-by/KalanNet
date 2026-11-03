<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\AppNotification;
use App\Models\Ecole;
use App\Models\RapportEsg;
use App\Services\Rapports\CanevasEsg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Rapport succinct côté lycée, rapports de rentrée et de fin d'année : canevas Excel officiel pré-rempli, complété par le lycée, puis envoyé
 * à l'académie si elle a un compte KalanNet, sinon remis directement.
 */
class RapportAcademieController extends Controller
{
    public static function autorise($user): bool
    {
        return $user && ($user->droit === 'SupAdmin' || $user->userHasPermission('rapport_succinct_gerer'));
    }

    private function ecole(): Ecole
    {
        abort_unless(self::autorise(Auth::user()), 403);
        $ecole = Ecole::withoutGlobalScopes()->findOrFail(session('idEcole') ?: Auth::user()->idEcole);
        abort_unless(CanevasEsg::ecoleConcernee((int) $ecole->idEcole), 403, 'Ces rapports concernent les lycées d\'enseignement secondaire général.');

        return $ecole;
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

    private function type(string $type): string
    {
        abort_unless(isset(CanevasEsg::TYPES[$type]), 404);

        return $type;
    }

    public function index(Request $request)
    {
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole);

        return view('rapports-academie.index', [
            'ecole' => $ecole, 'annee' => $annee, 'annees' => $this->annees(),
            'rapports' => RapportEsg::where('id_ecole', $ecole->idEcole)->where('id_annee', $annee->id_anneeScolaire)->get()->keyBy('type'),
            'academieSurKalanNet' => CanevasEsg::academieSurKalanNet($ecole->id_academie),
        ]);
    }

    /** Le canevas officiel rempli avec les chiffres de KalanNet : toujours disponible. */
    public function canevas(Request $request, string $type)
    {
        $type = $this->type($type);
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole);
        @ini_set('memory_limit', '512M');

        [$classeur] = (new CanevasEsg)->remplir($ecole, $annee, $type);
        $ecrivain = IOFactory::createWriter($classeur, 'Xlsx');
        // Excel recalcule les formules du canevas à l'ouverture.
        $ecrivain->setPreCalculateFormulas(false);
        $chemin = tempnam(sys_get_temp_dir(), 'canevas');
        $ecrivain->save($chemin);

        return response()->download($chemin, 'rapport-'.$type.'-'.Str::slug($ecole->nomEcole).'-'.$annee->annee.'.xlsx')->deleteFileAfterSend();
    }

    /** Envoi du fichier complété, ou simple marque « remis » quand l'académie n'est pas sur KalanNet. */
    public function remettre(Request $request, string $type)
    {
        $type = $this->type($type);
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole);
        $surKalanNet = CanevasEsg::academieSurKalanNet($ecole->id_academie);

        $request->validate(
            ['fichier' => [$surKalanNet ? 'required' : 'nullable', 'file', 'mimes:xlsx,xls,ods', 'max:10240']],
            ['fichier.required' => 'Choisissez le fichier Excel complété.', 'fichier.mimes' => 'Le fichier doit être un classeur Excel (.xlsx ou .xls).'],
        );

        $rapport = RapportEsg::firstOrNew(['id_ecole' => $ecole->idEcole, 'id_annee' => $annee->id_anneeScolaire, 'type' => $type]);
        if ($request->hasFile('fichier')) {
            $ancien = $rapport->fichier;
            $envoi = $request->file('fichier');
            // Rangé hors du dossier public : seuls l'école et son académie y accèdent.
            $rapport->fichier = $envoi->storeAs("rapports-esg/{$ecole->idEcole}", $type.'-'.$annee->id_anneeScolaire.'-'.Str::random(12).'.'.$envoi->guessExtension(), 'local');
            $rapport->fichier_nom = Str::limit($envoi->getClientOriginalName(), 200, '');
            if ($ancien) {
                Storage::disk('local')->delete($ancien);
            }
        }
        $rapport->fill(['statut' => $surKalanNet ? 'envoye' : 'remis', 'id_academie' => $ecole->id_academie, 'envoye_at' => now(), 'envoye_par' => Auth::id()])->save();

        $retour = redirect()->route('rapport-academie.index', ['annee' => $annee->id_anneeScolaire]);
        if (!$surKalanNet) {
            return $retour->with('success', 'Rapport marqué comme remis à votre académie.');
        }

        DB::table('utilisateurs')->where('droit', 'DAE')->where('id_academie', $ecole->id_academie)->where('statut', 1)->pluck('idUtilisateur')
            ->each(fn ($dae) => AppNotification::create([
                'user_id' => $dae, 'type' => 'rapport_academie', 'title' => CanevasEsg::TYPES[$type].' reçu',
                'message' => $ecole->nomEcole.' a envoyé son '.Str::lower(CanevasEsg::TYPES[$type]).' '.$annee->annee.'.',
                'link' => route('territoire.rapports-academie', ['annee' => $annee->id_anneeScolaire, 'type' => $type], false),
            ]));

        return $retour->with('success', 'Rapport envoyé à votre académie.');
    }

    private function telecharger(RapportEsg $rapport)
    {
        abort_unless($rapport->fichier && Storage::disk('local')->exists($rapport->fichier), 404);

        return Storage::disk('local')->download($rapport->fichier, $rapport->fichier_nom ?: basename($rapport->fichier));
    }

    public function fichier(int $id)
    {
        $ecole = $this->ecole();

        return $this->telecharger(RapportEsg::where('id_ecole', $ecole->idEcole)->findOrFail($id));
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
        $type = isset(CanevasEsg::TYPES[$request->input('type')]) ? $request->input('type') : 'rentree';
        $annees = DB::table('anneescolaire')->whereNull('id_ecole')->whereDate('date_debut', '<=', now()->toDateString())->orderByDesc('date_debut')->get();
        $annee = $annees->firstWhere('id_anneeScolaire', $request->integer('annee')) ?? $annees->first();

        $ecoles = DB::table('ecole as e')->where('e.id_academie', $user->id_academie)
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('classe')->whereColumn('classe.idEcole', 'e.idEcole')->where('classe.ordreEnseignement', 'secondairegenerale'))
            ->orderBy('e.nomEcole')->get(['e.idEcole', 'e.nomEcole', 'e.adresse']);

        // Même année, commune ou créée par l'école elle-même.
        $idsAnnee = DB::table('anneescolaire')->where('annee', $annee?->annee)->pluck('id_anneeScolaire');
        $rapports = RapportEsg::withoutGlobalScopes()->whereIn('id_annee', $idsAnnee)->where('type', $type)->whereIn('id_ecole', $ecoles->pluck('idEcole'))->get()->keyBy('id_ecole');
        $lignes = $ecoles->map(fn ($e) => (object) ['ecole' => $e, 'rapport' => $rapports->get($e->idEcole)]);

        return view('territoire.rapports-academie', compact('lignes', 'annees', 'annee', 'type'));
    }

    public function fichierTerritoire(int $id)
    {
        $user = $this->dae();
        $rapport = RapportEsg::withoutGlobalScopes()->findOrFail($id);
        abort_unless((int) DB::table('ecole')->where('idEcole', $rapport->id_ecole)->value('id_academie') === (int) $user->id_academie, 404);

        return $this->telecharger($rapport);
    }
}
