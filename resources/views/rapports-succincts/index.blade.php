@extends('layouts.app')

@section('content')
    @php
        $C = \App\Support\RapportSuccinctDonnees::class;
        $val = fn ($champ) => old($champ, $donnees[$champ] ?? null);
        $libellesCycles = ['prescolaire' => 'Préscolaire', 'premier' => 'Premier cycle', 'second' => 'Second cycle'];
        $selects = [
            'cycle' => ["Cycles d'enseignement", $C::CYCLES, true],
            'statut' => ["Statut administratif de l'école", $C::STATUTS, false],
            'enseignement' => ['Enseignement', $C::ENSEIGNEMENTS, true],
            'recrutement' => ['Mode de recrutement', $C::RECRUTEMENTS, false],
        ];
    @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Rapport succinct</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Rentrée {{ $annee->annee }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @if($aussiLycee)
        <p class="mb-3"><a href="{{ route('rapport-academie.index', ['annee' => $annee->id_anneeScolaire]) }}"><i class="bi bi-file-earmark-excel me-1"></i>Votre secondaire remet ses rapports à l'académie : voir les canevas de rentrée et de fin d'année</a></p>
    @endif

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <div class="alert {{ $capSurKalanNet ? 'alert-info' : 'alert-warning' }} border-0 border-start border-4">
        @if($capSurKalanNet)
            <i class="bi bi-send me-1"></i>Votre CAP utilise KalanNet : vous pouvez lui envoyer ce rapport directement. Vous pouvez aussi le télécharger à tout moment.
        @else
            <i class="bi bi-printer me-1"></i>Votre CAP n'utilise pas encore KalanNet : <strong>téléchargez le rapport rempli, imprimez-le et remettez-le à votre CAP</strong>, puis marquez-le comme remis.
        @endif
        <div class="small mt-1">À remettre dans les 15 jours qui suivent la rentrée scolaire.</div>
    </div>

    @if($rapport?->remis())
        <div class="alert alert-success border-0 border-start border-success border-4">
            <i class="bi bi-check-circle me-1"></i>{{ $rapport->statut === 'envoye' ? 'Envoyé au CAP' : 'Remis au CAP' }} le {{ $rapport->envoye_at?->format('d/m/Y à H:i') }}.
            @if($rapport->aModificationsNonEnvoyees())
                <strong>Vous avez modifié la fiche depuis : remettez-la de nouveau pour transmettre ces changements.</strong>
            @endif
        </div>
    @endif

    <form method="GET" action="{{ route('rapport-succinct.index') }}" class="row g-2 align-items-end mb-3">
        <div class="col-sm-5 col-md-3">
            <label class="form-label small fw-bold text-uppercase" for="annee">Année scolaire</label>
            <select name="annee" id="annee" class="form-select" onchange="this.form.submit()">
                @foreach($annees as $a)
                    <option value="{{ $a->id_anneeScolaire }}" @selected($a->id_anneeScolaire === $annee->id_anneeScolaire)>{{ $a->annee }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <form method="POST" action="{{ route('rapport-succinct.store', ['annee' => $annee->id_anneeScolaire]) }}" id="rapport-form">
        @csrf

        <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
            <button type="submit" name="action" value="enregistrer" class="btn btn-outline-primary"><i class="bi bi-save me-1"></i>Enregistrer</button>
            <button type="submit" name="action" value="telecharger" class="btn btn-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Enregistrer et télécharger le PDF</button>
        </div>

        <div class="card theme-card shadow-sm mb-3">
            <div class="card-header theme-header"><h6 class="mb-0 fw-bold">Tableau 1 : Renseignements généraux</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">École de</label>
                        <input type="text" class="form-control" value="{{ $ecole->nomEcole }}" disabled>
                    </div>
                    @foreach(['quartier' => 'Village / quartier', 'commune' => 'Commune de', 'cap' => 'CAP de', 'cercle' => 'Cercle de', 'academie' => 'AE de'] as $champ => $libelle)
                        <div class="col-md-4">
                            <label class="form-label small fw-bold" for="{{ $champ }}">{{ $libelle }}</label>
                            <input type="text" name="{{ $champ }}" id="{{ $champ }}" class="form-control" maxlength="150" value="{{ $val($champ) }}">
                        </div>
                    @endforeach
                    @foreach($selects as $champ => [$libelle, $options, $obligatoire])
                        <div class="col-md-3">
                            <label class="form-label small fw-bold" for="{{ $champ }}">{{ $libelle }}</label>
                            <select name="{{ $champ }}" id="{{ $champ }}" class="form-select" @required($obligatoire)>
                                @unless($obligatoire)<option value="">— À choisir —</option>@endunless
                                @foreach($options as $n => $option)
                                    <option value="{{ $n }}" @selected((int) $val($champ) === $n)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                    <div class="col-md-6">
                        <label class="form-label small fw-bold" for="langue">École à pédagogie convergente : langue</label>
                        <input type="text" name="langue" id="langue" class="form-control" maxlength="100" value="{{ $val('langue') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card theme-card shadow-sm mb-3">
            <div class="card-header theme-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="mb-0 fw-bold">Tableau 2 : Groupes pédagogiques et effectifs scolaires</h6>
                <a href="{{ route('rapport-succinct.index', ['annee' => $annee->id_anneeScolaire, 'recalculer' => 1]) }}" class="btn btn-sm btn-light"><i class="bi bi-arrow-repeat me-1"></i>Reprendre les chiffres de KalanNet</a>
            </div>
            <div class="card-body">
                <p class="small text-muted">Chiffres repris des élèves actifs de {{ $annee->annee }}. Les redoublants sont ceux enregistrés à la réinscription : vérifiez-les, surtout si votre école a commencé KalanNet cette année.</p>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center mb-0" data-rapport-effectifs>
                        <thead>
                            <tr>
                                <th rowspan="2">Année d'études</th><th rowspan="2">Groupes pédagogiques</th>
                                <th colspan="2">Élèves</th><th rowspan="2">Total élèves</th>
                                <th colspan="2">Redoublants</th><th rowspan="2">Total redoublants</th>
                            </tr>
                            <tr><th>G</th><th>F</th><th>G</th><th>F</th></tr>
                        </thead>
                        <tbody>
                            @foreach($donnees['groupes'] as $l)
                                <tr data-ligne>
                                    <td class="fw-semibold text-nowrap">{{ $l['libelle'] }}</td>
                                    @foreach(['groupes', 'g', 'f', 'total', 'rg', 'rf', 'rtotal'] as $c)
                                        @if($c === 'total')
                                            <td class="fw-bold" data-total>{{ $l['g'] + $l['f'] }}</td>
                                        @elseif($c === 'rtotal')
                                            <td class="fw-bold" data-rtotal>{{ $l['rg'] + $l['rf'] }}</td>
                                        @else
                                            <td style="min-width: 76px;"><input type="number" min="0" max="99999" required class="form-control form-control-sm text-center" name="groupes[{{ $l['cle'] }}][{{ $c }}]" data-c="{{ $c }}" value="{{ old("groupes.{$l['cle']}.{$c}", $l[$c]) }}" aria-label="{{ $l['libelle'] }} {{ $c }}"></td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="fw-bold">
                            <tr>
                                <td>Total école</td>
                                @foreach(['groupes', 'g', 'f', 'total', 'rg', 'rf', 'rtotal'] as $c)<td data-somme="{{ $c }}"></td>@endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="card theme-card shadow-sm mb-3">
            <div class="card-header theme-header"><h6 class="mb-0 fw-bold">Tableau 3 : Maîtres en classe et salles de classe utilisées</h6></div>
            <div class="card-body">
                <p class="small text-muted">Pré-rempli d'après le contrat des enseignants et les classes où ils interviennent : fonctionnaires et CDI en « permanents », CDD et vacataires en « contractuels ». Corrigez si la règle de votre CAP diffère. KalanNet ne connaît pas le nombre de salles : indiquez-le.</p>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center mb-0">
                        <thead><tr><th>Cycle d'enseignement</th><th>Permanents</th><th>Contractuels</th><th>Total</th><th>Nombre de salles de classe</th></tr></thead>
                        <tbody>
                            @foreach($donnees['maitres'] as $cycle => $m)
                                <tr data-maitres>
                                    <td class="fw-semibold">{{ $libellesCycles[$cycle] ?? $cycle }}</td>
                                    @foreach(['permanents', 'contractuels'] as $c)
                                        <td><input type="number" min="0" max="99999" required class="form-control form-control-sm text-center" name="maitres[{{ $cycle }}][{{ $c }}]" data-m value="{{ old("maitres.{$cycle}.{$c}", $m[$c]) }}" aria-label="{{ $libellesCycles[$cycle] ?? $cycle }} {{ $c }}"></td>
                                    @endforeach
                                    <td class="fw-bold" data-mtotal>{{ $m['permanents'] + $m['contractuels'] }}</td>
                                    <td><input type="number" min="0" max="9999" class="form-control form-control-sm text-center" name="maitres[{{ $cycle }}][salles]" value="{{ old("maitres.{$cycle}.salles", $m['salles']) }}" aria-label="{{ $libellesCycles[$cycle] ?? $cycle }} salles"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm form-actions-sticky d-flex flex-row flex-wrap justify-content-between align-items-center gap-2">
            <div class="small text-muted">
                @if($manquants !== [])
                    <i class="bi bi-exclamation-circle me-1"></i>À compléter avant la remise : {{ implode(', ', $manquants) }}.
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" name="action" value="enregistrer" class="btn btn-outline-primary">Enregistrer</button>
                <button type="submit" name="action" value="telecharger" class="btn btn-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Télécharger le PDF</button>
                <button type="submit" name="action" value="remettre" class="btn btn-success fw-semibold" data-rapport-remettre>
                    @if($capSurKalanNet)
                        <i class="bi bi-send me-1"></i>{{ $rapport?->remis() ? 'Renvoyer au CAP' : 'Envoyer au CAP' }}
                    @else
                        <i class="bi bi-check2-circle me-1"></i>{{ $rapport?->remis() ? 'Marquer de nouveau comme remis' : 'Marquer comme remis au CAP' }}
                    @endif
                </button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Totaux recalculés pendant la saisie.
        (function () {
            const table = document.querySelector('[data-rapport-effectifs]');
            const nombre = (el) => parseInt(el?.value, 10) || 0;
            const calculer = () => {
                const sommes = { groupes: 0, g: 0, f: 0, total: 0, rg: 0, rf: 0, rtotal: 0 };
                table.querySelectorAll('[data-ligne]').forEach((ligne) => {
                    const v = (c) => nombre(ligne.querySelector(`[data-c="${c}"]`));
                    ligne.querySelector('[data-total]').textContent = v('g') + v('f');
                    ligne.querySelector('[data-rtotal]').textContent = v('rg') + v('rf');
                    ['groupes', 'g', 'f', 'rg', 'rf'].forEach((c) => { sommes[c] += v(c); });
                    sommes.total += v('g') + v('f');
                    sommes.rtotal += v('rg') + v('rf');
                });
                Object.entries(sommes).forEach(([c, n]) => { table.querySelector(`[data-somme="${c}"]`).textContent = n; });
                document.querySelectorAll('[data-maitres]').forEach((ligne) => {
                    const [a, b] = ligne.querySelectorAll('[data-m]');
                    ligne.querySelector('[data-mtotal]').textContent = nombre(a) + nombre(b);
                });
            };
            document.getElementById('rapport-form').addEventListener('input', calculer);
            calculer();
        })();
    </script>
@endpush
