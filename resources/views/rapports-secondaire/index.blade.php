@extends('layouts.app')

@section('content')
    @php $D = \App\Support\RapportSecondaireDefinitions::class; @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ $D::TYPES[$type] }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $annee->annee }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('rapports-succincts._onglets', ['actif' => $type])

    @php $sansSerie = true ? \App\Http\Controllers\RapportSecondaireController::classesSansSerie((int) (session('idEcole') ?: auth()->user()->idEcole)) : 0; @endphp
    @if($sansSerie > 0)
        <div class="alert alert-danger border-0 border-start border-danger border-4 d-flex flex-wrap align-items-center gap-2">
            <i class="bi bi-exclamation-octagon me-1"></i>
            <span>{{ $sansSerie }} classe(s) du secondaire général sans série choisie : elles risquent de manquer dans les tableaux par série. Vérifiez les séries de vos classes (deux minutes, une seule fois).</span>
            <a class="btn btn-sm btn-danger ms-auto" href="{{ route('rapport-secondaire.series', ['retour' => $type]) }}"><i class="bi bi-list-check me-1"></i>Vérifier les séries de mes classes</a>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <div class="alert {{ $surKalanNet ? 'alert-info' : 'alert-warning' }} border-0 border-start border-4">
        @if($surKalanNet)
            <i class="bi bi-send me-1"></i>Votre académie utilise KalanNet : vous pouvez lui envoyer ce rapport directement. Vous pouvez aussi le télécharger à tout moment.
        @else
            <i class="bi bi-printer me-1"></i>Votre académie n'utilise pas encore KalanNet : <strong>téléchargez le rapport rempli, imprimez-le et remettez-le à votre académie</strong>, puis marquez-le comme remis.
        @endif
    </div>

    @if($rapport?->remis())
        <div class="alert alert-success border-0 border-start border-success border-4">
            <i class="bi bi-check-circle me-1"></i>{{ $rapport->statut === 'envoye' ? "Envoyé à l'académie" : "Remis à l'académie" }} le {{ $rapport->envoye_at?->format('d/m/Y à H:i') }}.
            @if($rapport->aModificationsNonEnvoyees())
                <strong>Vous avez modifié le rapport depuis : remettez-le de nouveau pour transmettre ces changements.</strong>
            @endif
        </div>
    @endif
    @if($type === 'rentree')
        <div class="alert {{ $sansDef > 0 ? 'alert-warning' : 'alert-light border' }} border-0 border-start border-4 d-flex flex-wrap align-items-center gap-2">
            <i class="bi bi-mortarboard me-1"></i>
            <span>{{ $sansDef > 0 ? $sansDef.' élève(s) sans année du DEF (CG) : leur scolarité (T9) est estimée par leur niveau et leurs redoublements.' : 'Scolarité (T9) comptée depuis l\'année du DEF de chaque élève.' }}</span>
            <a class="btn btn-sm btn-outline-primary ms-auto" href="{{ route('rapport-secondaire.def', ['annee' => $annee->id_anneeScolaire]) }}"><i class="bi bi-pencil-square me-1"></i>Année du DEF des élèves</a>
        </div>
    @endif
    @if($sansDate > 0)
        <div class="alert alert-warning border-0 border-start border-warning border-4"><i class="bi bi-exclamation-triangle me-1"></i>{{ $sansDate }} élève(s) sans date de naissance : absents du tableau des âges (T7). Complétez leur fiche, puis reprenez les chiffres de KalanNet.</div>
    @endif

    <form method="GET" action="{{ route('rapport-secondaire.index', $type) }}" class="row g-2 align-items-end mb-3">
        <div class="col-sm-5 col-md-3">
            <label class="form-label small fw-bold text-uppercase" for="annee">Année scolaire</label>
            <select name="annee" id="annee" class="form-select" onchange="this.form.submit()">
                @foreach($annees as $a)
                    <option value="{{ $a->id_anneeScolaire }}" @selected($a->id_anneeScolaire === $annee->id_anneeScolaire)>{{ $a->annee }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto ms-auto">
            <a href="{{ route('rapport-secondaire.index', [$type, 'annee' => $annee->id_anneeScolaire, 'recalculer' => 1]) }}" class="btn btn-light"><i class="bi bi-arrow-repeat me-1"></i>Reprendre les chiffres de KalanNet</a>
        </div>
    </form>

    {{-- Canevas officiels du ministère : tous leurs tableaux, remplis avec ce rapport. --}}
    @php $modeles = \App\Services\Rapports\CanevasOfficiel::MODELES; @endphp
    <div class="card theme-card shadow-sm mb-3">
        <div class="card-header theme-header"><h6 class="mb-0 fw-bold">Canevas officiels du ministère</h6></div>
        <div class="card-body">
            <p class="small text-muted mb-3">Le vrai fichier Excel du ministère, avec tous ses tableaux et ses cases de vérification, rempli avec ce que vous saisissez ici et ce que KalanNet connaît. Les tableaux qui ne figurent pas sur cette page se complètent directement dans le fichier.</p>
            <form method="POST" action="{{ route('rapport-secondaire.maquette') }}" class="row g-2 align-items-end mb-3">
                @csrf
                <div class="col-md-7">
                    <label class="form-label small fw-bold" for="maquette">Modèle de rapport demandé par votre académie</label>
                    <select name="maquette" id="maquette" class="form-select" required>
                        <option value="">— Choisir le modèle —</option>
                        @foreach($modeles as $v => $libelle)
                            <option value="{{ $v }}" @selected($maquette === $v)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto"><button type="submit" class="btn btn-outline-primary">Enregistrer le modèle</button></div>
            </form>
            <div class="d-flex flex-wrap gap-2">
                @if(isset($modeles[$maquette]))
                    <button type="submit" form="rapport-secondaire-form" name="action" value="canevas_choisi" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i>Canevas officiel : {{ $modeles[$maquette] }}</button>
                @endif
                @if($avecGeneral && $maquette !== 'esg')
                    {{-- Établissement qui a aussi des classes du général : le canevas ESG reste disponible. --}}
                    <button type="submit" form="rapport-secondaire-form" name="action" value="canevas_esg" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i>Canevas officiel : {{ $modeles['esg'] }}</button>
                @endif
            </div>
            @unless(isset($modeles[$maquette]))
                <div class="small text-muted mt-2">Choisissez d'abord votre modèle : KalanNet s'en souviendra pour les années suivantes.</div>
            @endunless
        </div>
    </div>

    <form method="POST" action="{{ route('rapport-secondaire.store', [$type, 'annee' => $annee->id_anneeScolaire]) }}" id="rapport-secondaire-form">
        @csrf
        <input type="hidden" name="tableaux_json" id="tableaux_json">

        <div class="card theme-card shadow-sm mb-3">
            <div class="card-header theme-header"><h6 class="mb-0 fw-bold">Partie administrative</h6></div>
            <div class="card-body d-grid gap-3">
                <div class="alert alert-light border small mb-0"><i class="bi bi-stars me-1"></i><strong>Kalanbot</strong> peut proposer un brouillon de chaque rubrique à partir des chiffres de votre rapport (aucun nom de personne ne lui est envoyé). Il laisse « [à compléter] » ce que les chiffres ne disent pas : relisez, corrigez et complétez avant d'enregistrer.</div>
                <p class="small text-muted mb-0">À rédiger par l'établissement. Les annexes nominatives (élèves par classe{{ $type === 'fin' ? ', décisions de passage, de redoublement et d\'exclusion' : ' et par commune' }}) sont ajoutées automatiquement au PDF et à l'Excel.</p>
                @foreach($redaction as $cle => [$titre, $aide])
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <label class="form-label small fw-bold mb-0 me-auto" for="redaction-{{ $cle }}">{{ $titre }}</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-kalanbot="{{ $cle }}" title="Kalanbot rédige un brouillon à partir des chiffres du rapport ; relisez-le et complétez-le.">
                                <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span><i class="bi bi-stars me-1"></i>Proposer avec Kalanbot
                            </button>
                        </div>
                        @if($aide)<div class="form-text mt-0 mb-1">{{ $aide }}</div>@endif
                        <div class="small mb-1 d-none" data-kalanbot-retour="{{ $cle }}"></div>
                        <textarea class="form-control" rows="4" maxlength="20000" name="redaction[{{ $cle }}]" id="redaction-{{ $cle }}">{{ old("redaction.$cle", $donnees['redaction'][$cle] ?? '') }}</textarea>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card theme-card shadow-sm mb-3">
            <div class="card-header theme-header"><h6 class="mb-0 fw-bold">T0 : Informations générales</h6></div>
            <div class="card-body">
                <p class="small text-muted">Effectifs, groupes pédagogiques et enseignants sont repris de KalanNet. Ce qui ne change pas d'une année à l'autre (création, arrêtés, superficie…) est repris de votre rapport précédent.</p>
                <div class="row g-3">
                    @foreach($infos as $champ => $libelle)
                        <div class="col-sm-6 col-lg-4">
                            <label class="form-label small fw-bold" for="info-{{ $champ }}">{{ $libelle }}</label>
                            <input type="text" maxlength="255" class="form-control" name="infos[{{ $champ }}]" id="info-{{ $champ }}" value="{{ old("infos.$champ", $donnees['infos'][$champ] ?? null) }}">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @foreach($tableaux as $tableau)
            @include('rapports-secondaire._tableau', ['tableau' => $tableau])
        @endforeach

        <div class="card shadow-sm form-actions-sticky d-flex flex-row flex-wrap justify-content-between align-items-center gap-2">
            <div class="small text-muted">
                @if($manquants !== [])
                    <i class="bi bi-exclamation-circle me-1"></i>À compléter avant la remise : {{ implode(', ', $manquants) }}.
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" name="action" value="enregistrer" class="btn btn-outline-primary">Enregistrer</button>
                <button type="submit" name="action" value="telecharger" class="btn btn-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Télécharger le PDF</button>
                <button type="submit" name="action" value="excel" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</button>
                <button type="submit" name="action" value="remettre" class="btn btn-success fw-semibold">
                    @if($surKalanNet)
                        <i class="bi bi-send me-1"></i>{{ $rapport?->remis() ? "Renvoyer à l'académie" : "Envoyer à l'académie" }}
                    @else
                        <i class="bi bi-check2-circle me-1"></i>{{ $rapport?->remis() ? 'Marquer de nouveau comme remis' : "Marquer comme remis à l'académie" }}
                    @endif
                </button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('rapport-secondaire-form');
            const nombre = (el) => parseInt(el.value, 10) || 0;
            // Totaux de chaque tableau, recalculés pendant la saisie.
            const totaux = () => form.querySelectorAll('[data-totaux]').forEach((table) => {
                const lignes = [...table.querySelectorAll('[data-ligne]')];
                const parColonne = [];
                lignes.forEach((ligne) => {
                    const cases = [...ligne.querySelectorAll('[data-t]')];
                    cases.forEach((c, i) => { parColonne[i] = (parColonne[i] || 0) + nombre(c); });
                    const total = ligne.querySelector('[data-total-ligne]');
                    if (total) total.textContent = cases.reduce((s, c) => s + nombre(c), 0);
                });
                table.querySelectorAll('[data-total-colonne]').forEach((c) => { c.textContent = parColonne[c.dataset.totalColonne] || 0; });
                const general = table.querySelector('[data-total-general]');
                if (general) general.textContent = parColonne.reduce((s, n) => s + (n || 0), 0);
            });
            form.addEventListener('input', totaux);
            totaux();

            // Kalanbot : brouillon d'une rubrique, à partir de ce qui est à l'écran (même non enregistré).
            const saisieTableaux = () => {
                const t = {};
                form.querySelectorAll('[data-t]').forEach((champ) => {
                    if (champ.value === '') return;
                    const [code, ligne, colonne] = champ.dataset.t.split('|');
                    ((t[code] ??= {})[ligne] ??= {})[colonne] = champ.value;
                });
                return JSON.stringify(t);
            };
            form.querySelectorAll('[data-kalanbot]').forEach((bouton) => bouton.addEventListener('click', async () => {
                const cle = bouton.dataset.kalanbot;
                const zone = document.getElementById('redaction-' + cle);
                const retour = form.querySelector('[data-kalanbot-retour="' + cle + '"]');
                if (zone.value.trim() !== '' && !confirm('Cette rubrique contient déjà du texte. Le remplacer par la proposition de Kalanbot ?')) return;
                const donnees = new FormData(form);
                donnees.set('rubrique', cle);
                donnees.set('tableaux_json', saisieTableaux());
                bouton.disabled = true;
                bouton.querySelector('.spinner-border').classList.remove('d-none');
                retour.className = 'small mb-1 text-muted';
                retour.textContent = 'Kalanbot rédige… (quelques secondes)';
                try {
                    const reponse = await fetch(@json(route('rapport-secondaire.kalanbot', [$type, 'annee' => $annee->id_anneeScolaire])), { method: 'POST', body: donnees, headers: { 'Accept': 'application/json' } });
                    const json = await reponse.json().catch(() => ({}));
                    if (reponse.ok && json.ok) {
                        zone.value = json.texte;
                        zone.rows = Math.max(6, Math.min(18, json.texte.split('\n').length + 4));
                        retour.className = 'small mb-1 text-success fw-semibold';
                        retour.textContent = 'Brouillon de Kalanbot : relisez-le, remplacez les « [à compléter] », puis enregistrez le rapport.';
                    } else {
                        retour.className = 'small mb-1 text-danger';
                        retour.textContent = reponse.status === 429 ? 'Trop de demandes : patientez une minute.' : (json.message || 'Kalanbot est momentanément indisponible. Votre texte n\'a pas été modifié.');
                    }
                } catch (e) {
                    retour.className = 'small mb-1 text-danger';
                    retour.textContent = 'Connexion impossible. Votre texte n\'a pas été modifié.';
                } finally {
                    bouton.disabled = false;
                    bouton.querySelector('.spinner-border').classList.add('d-none');
                }
            }));

            // Tous les tableaux partent dans un seul champ : la limite de champs de PHP ne peut rien tronquer.
            form.addEventListener('submit', () => {
                const t = {};
                form.querySelectorAll('[data-t]').forEach((champ) => {
                    if (champ.value === '') return;
                    const [code, ligne, colonne] = champ.dataset.t.split('|');
                    ((t[code] ??= {})[ligne] ??= {})[colonne] = champ.value;
                });
                document.getElementById('tableaux_json').value = JSON.stringify(t);
            });
        })();
    </script>
@endpush
