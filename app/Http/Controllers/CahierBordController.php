<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\AppNotification;
use App\Models\CahierBord;
use App\Models\Ecole;
use App\Support\CahierBordDonnees as Donnees;
use App\Support\Ordres;
use App\Support\RapportSuccinctDonnees;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Cahier de bord : une fiche par cycle (jardin, 1er cycle, 2e cycle), pré-remplie, téléchargeable à tout moment et remise
 * au CAP (par KalanNet si le CAP a un compte, sinon sur papier). Le DCAP suit les fiches de son CAP.
 */
class CahierBordController extends Controller
{
    public static function autorise($user): bool
    {
        return $user && ($user->droit === 'SupAdmin' || $user->userHasPermission('cahier_bord_gerer'));
    }

    private function ecole(): Ecole
    {
        abort_unless(self::autorise(Auth::user()), 403);
        $ecole = Ecole::withoutGlobalScopes()->findOrFail(session('idEcole') ?: Auth::user()->idEcole);
        abort_unless(Donnees::cycles((int) $ecole->idEcole) !== [], 403, 'Le cahier de bord concerne le jardin d\'enfants et le fondamental.');

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

    private function cycle(Request $request, Ecole $ecole): string
    {
        $cycles = Donnees::cycles((int) $ecole->idEcole);

        return in_array($request->input('cycle'), $cycles, true) ? $request->input('cycle') : $cycles[0];
    }

    private function fiche(Ecole $ecole, AnneeScolaire $annee, string $cycle): ?CahierBord
    {
        return CahierBord::where('id_ecole', $ecole->idEcole)->where('id_annee', $annee->id_anneeScolaire)->where('cycle', $cycle)->first();
    }

    private function calcul(Ecole $ecole, AnneeScolaire $annee, string $cycle): array
    {
        return Donnees::calculer($ecole, (int) $annee->id_anneeScolaire, $cycle, (string) Auth::user()->nomPrenom);
    }

    public function index(Request $request)
    {
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole);
        $cycle = $this->cycle($request, $ecole);
        $fiche = $this->fiche($ecole, $annee, $cycle);
        $donnees = $fiche?->donnees ?? $this->calcul($ecole, $annee, $cycle);

        // « Reprendre les chiffres de KalanNet » : effectifs et liste des enseignants recalculés, saisies gardées.
        if ($request->boolean('recalculer')) {
            $calcul = $this->calcul($ecole, $annee, $cycle);
            $donnees['effectifs'] = $calcul['effectifs'];
            $anciens = collect($donnees['enseignants'] ?? [])->keyBy('id');
            $donnees['enseignants'] = collect($calcul['enseignants'])->map(fn ($e) => array_merge($e, collect($anciens[$e['id']] ?? [])->only(Donnees::CHAMPS_ENSEIGNANT)->filter(fn ($v) => filled($v))->all()))->all();
        }

        return view('cahiers-bord.index', [
            'ecole' => $ecole, 'annee' => $annee, 'annees' => $this->annees(), 'fiche' => $fiche, 'donnees' => $donnees,
            'cycle' => $cycle, 'cycles' => Donnees::cycles((int) $ecole->idEcole),
            'capSurKalanNet' => RapportSuccinctDonnees::capSurKalanNet($ecole->id_cap),
            'nonReconnues' => Donnees::classesNonReconnues((int) $ecole->idEcole, $cycle),
            'manquants' => Donnees::champsManquants($donnees),
        ]);
    }

    /** Boutons du formulaire : enregistrer, enregistrer et télécharger, enregistrer et remettre au CAP. */
    public function store(Request $request)
    {
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole);
        $cycle = $this->cycle($request, $ecole);
        $donnees = $this->valider($request, $ecole, $annee, $cycle);

        $fiche = CahierBord::firstOrNew(['id_ecole' => $ecole->idEcole, 'id_annee' => $annee->id_anneeScolaire, 'cycle' => $cycle]);
        $fiche->donnees = $donnees;
        $fiche->statut ??= 'brouillon';
        $fiche->save();

        $retour = ['annee' => $annee->id_anneeScolaire, 'cycle' => $cycle];
        if ($request->input('action') === 'telecharger') {
            return redirect()->route('cahier-bord.pdf', $retour);
        }
        if ($request->input('action') === 'remettre') {
            $manquants = Donnees::champsManquants($donnees);
            if ($manquants !== []) {
                throw ValidationException::withMessages(['cahier' => 'À compléter avant de remettre le cahier de bord : '.implode(', ', $manquants).'.']);
            }
            $surKalanNet = RapportSuccinctDonnees::capSurKalanNet($ecole->id_cap);
            $fiche->update(['statut' => $surKalanNet ? 'envoye' : 'remis', 'id_cap' => $ecole->id_cap, 'donnees_envoyees' => $donnees, 'envoye_at' => now(), 'envoye_par' => Auth::id()]);
            if (!$surKalanNet) {
                return redirect()->route('cahier-bord.index', $retour)->with('success', 'Cahier de bord marqué comme remis à votre CAP.');
            }
            DB::table('utilisateurs')->where('droit', 'DCAP')->where('id_cap', $ecole->id_cap)->where('statut', 1)->pluck('idUtilisateur')
                ->each(fn ($dcap) => AppNotification::create([
                    'user_id' => $dcap, 'type' => 'cahier_bord', 'title' => 'Cahier de bord reçu',
                    'message' => $ecole->nomEcole.' a envoyé son cahier de bord ('.Donnees::CYCLES[$cycle].', '.$annee->annee.').',
                    'link' => route('territoire.cahiers-bord', ['annee' => $annee->id_anneeScolaire], false),
                ]));

            return redirect()->route('cahier-bord.index', $retour)->with('success', 'Cahier de bord envoyé à votre CAP.');
        }

        return redirect()->route('cahier-bord.index', $retour)->with('success', 'Cahier de bord enregistré.');
    }

    /** @return array<string, mixed> */
    private function valider(Request $request, Ecole $ecole, AnneeScolaire $annee, string $cycle): array
    {
        // Les enseignants arrivent dans un seul champ : la limite de champs de PHP ne peut pas tronquer la liste.
        if ($request->filled('enseignants_json')) {
            $request->merge(['enseignants' => json_decode((string) $request->input('enseignants_json'), true) ?: []]);
        }
        $entier = ['required', 'integer', 'min:0', 'max:99999'];
        $nombre = ['nullable', 'integer', 'min:0', 'max:9999'];
        $texte = 'nullable|string|max:150';
        $data = $request->validate([
            'directeur' => $texte, 'contact' => 'nullable|string|max:60', 'depuis' => 'nullable|string|max:30',
            'regime' => ['nullable', Rule::in(array_keys(RapportSuccinctDonnees::RECRUTEMENTS))],
            'commune' => $texte, 'academie' => $texte, 'cap' => $texte,
            'date_creation' => 'nullable|string|max:30', 'date_ouverture' => 'nullable|string|max:30',
            'effectifs' => 'required|array', 'effectifs.*.groupes' => $entier, 'effectifs.*.g' => $entier, 'effectifs.*.f' => $entier,
            'effectifs.*.rg' => $entier, 'effectifs.*.rf' => $entier,
            'administratifs' => 'nullable|array|max:4', 'administratifs.*.*' => $texte,
            'enseignants' => 'nullable|array', 'enseignants.*.*' => 'nullable|string|max:150',
            'salles.*' => $nombre, 'tables.*.*' => $nombre, 'equipements.*' => $nombre,
        ]);

        // Lignes, identité des élèves et des enseignants : toujours celles de KalanNet, jamais du formulaire.
        $reference = $this->calcul($ecole, $annee, $cycle);
        $effectifs = collect($reference['effectifs'])->map(function ($ligne) use ($data) {
            $saisie = $data['effectifs'][$ligne['cle']] ?? [];
            foreach (['groupes', 'g', 'f', 'rg', 'rf'] as $champ) {
                $ligne[$champ] = (int) ($saisie[$champ] ?? 0);
            }
            if ($ligne['rg'] > $ligne['g'] || $ligne['rf'] > $ligne['f']) {
                throw ValidationException::withMessages(['effectifs' => $ligne['libelle'].' : il y a plus de redoublants que d\'élèves.']);
            }

            return $ligne;
        })->all();
        $enseignants = collect($reference['enseignants'])->map(function ($e) use ($data) {
            foreach (Donnees::CHAMPS_ENSEIGNANT as $champ) {
                if (array_key_exists($champ, $data['enseignants'][$e['id']] ?? [])) {
                    $e[$champ] = $data['enseignants'][$e['id']][$champ];
                }
            }

            return $e;
        })->all();
        $administratifs = collect(range(0, 3))->map(fn ($i) => collect(Donnees::CHAMPS_ADMINISTRATIF)->mapWithKeys(fn ($c) => [$c => $data['administratifs'][$i][$c] ?? null])->all())->all();
        $entiers = fn (array $cles, array $valeurs) => collect($cles)->mapWithKeys(fn ($c) => [$c => isset($valeurs[$c]) && $valeurs[$c] !== '' ? (int) $valeurs[$c] : null])->all();

        return [
            'cycle' => $cycle, 'ecole' => (string) $ecole->nomEcole,
            'directeur' => $data['directeur'] ?? null, 'contact' => $data['contact'] ?? null, 'depuis' => $data['depuis'] ?? null,
            'regime' => isset($data['regime']) ? (int) $data['regime'] : null, 'commune' => $data['commune'] ?? null,
            'academie' => $data['academie'] ?? null, 'cap' => $data['cap'] ?? null,
            'effectifs' => $effectifs, 'administratifs' => $administratifs, 'enseignants' => $enseignants,
            'salles' => $entiers(array_keys(Donnees::SALLES), $data['salles'] ?? []),
            'date_creation' => $data['date_creation'] ?? null, 'date_ouverture' => $data['date_ouverture'] ?? null,
            'tables' => collect(Donnees::niveaux($cycle))->map(fn ($l, $cle) => $entiers(array_keys(Donnees::TABLES), $data['tables'][$cle] ?? []))->all(),
            'equipements' => $entiers(array_keys(Donnees::EQUIPEMENTS), $data['equipements'] ?? []),
        ];
    }

    /** Toujours disponible : la fiche remplie, à imprimer et remettre. */
    public function pdf(Request $request)
    {
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole);
        $cycle = $this->cycle($request, $ecole);

        return $this->fichier($this->fiche($ecole, $annee, $cycle)?->donnees ?? $this->calcul($ecole, $annee, $cycle), (string) $annee->annee, !$request->boolean('voir'));
    }

    private function fichier(array $donnees, string $annee, bool $telecharger)
    {
        $pdf = Pdf::loadView('pdf.cahier_bord', ['d' => $donnees, 'annee' => $annee])->setPaper('a4', 'landscape');
        $nom = 'cahier-de-bord-'.Str::slug($donnees['ecole'] ?? 'ecole').'-'.($donnees['cycle'] ?? '').'-'.$annee.'.pdf';

        return $telecharger ? $pdf->download($nom) : $pdf->stream($nom);
    }

    /* ——— Espace du CAP ——— */

    private function dcap()
    {
        $user = Auth::user();
        abort_unless($user->droit === 'DCAP' && $user->id_cap, 403);

        return $user;
    }

    public function territoire(Request $request)
    {
        $user = $this->dcap();
        $annees = DB::table('anneescolaire')->whereNull('id_ecole')->whereDate('date_debut', '<=', now()->toDateString())->orderByDesc('date_debut')->get();
        $annee = $annees->firstWhere('id_anneeScolaire', $request->integer('annee')) ?? $annees->first();

        $ecoles = DB::table('ecole as e')->where('e.id_cap', $user->id_cap)
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('classe')->whereColumn('classe.idEcole', 'e.idEcole')->whereIn('classe.ordreEnseignement', Ordres::SOUS_CAP))
            ->orderBy('e.nomEcole')->get(['e.idEcole', 'e.nomEcole', 'e.adresse']);

        $idsAnnee = DB::table('anneescolaire')->where('annee', $annee?->annee)->pluck('id_anneeScolaire');
        $fiches = CahierBord::withoutGlobalScopes()->whereIn('id_annee', $idsAnnee)->where('statut', '!=', 'brouillon')
            ->whereIn('id_ecole', $ecoles->pluck('idEcole'))->get()->groupBy('id_ecole');

        $lignes = $ecoles->flatMap(fn ($e) => collect(Donnees::cycles((int) $e->idEcole))->map(fn ($cycle) => (object) [
            'ecole' => $e, 'cycle' => $cycle, 'fiche' => $fiches->get($e->idEcole)?->firstWhere('cycle', $cycle),
        ]));

        return view('territoire.cahiers-bord', compact('lignes', 'annees', 'annee'));
    }

    public function pdfTerritoire(Request $request, int $id)
    {
        $user = $this->dcap();
        $fiche = CahierBord::withoutGlobalScopes()->where('statut', '!=', 'brouillon')->findOrFail($id);
        abort_unless((int) DB::table('ecole')->where('idEcole', $fiche->id_ecole)->value('id_cap') === (int) $user->id_cap, 404);

        return $this->fichier($fiche->donnees_envoyees, (string) DB::table('anneescolaire')->where('id_anneeScolaire', $fiche->id_annee)->value('annee'), $request->boolean('telecharger'));
    }
}
