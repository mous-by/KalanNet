@extends('layouts.app')

@section('content')
    @php
        use App\Support\Satisfaction;
        $max = max(1, max($rapport['repartition']));
        $nom = fn ($cle) => Satisfaction::POINTS_FORTS[$cle] ?? Satisfaction::POINTS_FAIBLES[$cle] ?? $cle;
        $periodeParams = array_filter(['depuis' => $filtres['depuis'], 'jusqua' => $filtres['jusqua']]);
    @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Avis de satisfaction</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Rapport</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ session('error') }}</div>
    @endif

    <div class="card theme-card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-sm-4 col-lg-3">
                    <label class="form-label small mb-1">Du</label>
                    <input type="date" name="depuis" class="form-control" value="{{ $filtres['depuis'] }}">
                </div>
                <div class="col-sm-4 col-lg-3">
                    <label class="form-label small mb-1">Au</label>
                    <input type="date" name="jusqua" class="form-control" value="{{ $filtres['jusqua'] }}">
                </div>
                <div class="col-sm-4 col-lg-2">
                    <label class="form-label small mb-1">Note</label>
                    <select name="note" class="form-select">
                        <option value="">Toutes</option>
                        @foreach(Satisfaction::LIBELLES_NOTE as $n => $l)
                            <option value="{{ $n }}" @selected((string) $filtres['note'] === (string) $n)>{{ $n }} — {{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-4 d-flex gap-2">
                    <button class="btn btn-primary">Afficher</button>
                    <a href="{{ route('satisfaction.index') }}" class="btn btn-light">Douze derniers mois</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Chiffres clés --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100"><div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Note moyenne</div>
                <div class="display-6 fw-bold">{{ $rapport['moyenne'] !== null ? number_format($rapport['moyenne'], 1, ',', '') : '—' }}<span class="fs-5 text-muted"> / 5</span></div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100"><div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Satisfaits (4 ou 5)</div>
                <div class="display-6 fw-bold">{{ $rapport['pct_satisfaits'] !== null ? $rapport['pct_satisfaits'].' %' : '—' }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100"><div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Avis reçus</div>
                <div class="display-6 fw-bold">{{ $rapport['total'] }}</div>
                <div class="small text-muted">{{ $relances['plus_tard'] ?? 0 }} « plus tard », {{ $relances['jamais'] ?? 0 }} « ne plus demander »</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100"><div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Écoles</div>
                <div class="display-6 fw-bold">{{ $rapport['nb_ecoles'] }}</div>
            </div></div>
        </div>
    </div>

    {{-- Partage et publication --}}
    <div class="card theme-card shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-2">
            <div class="me-auto">
                <div class="fw-bold">Partager le rapport</div>
                <div class="small text-muted">
                    PDF à envoyer aux partenaires et clients, ou publication immédiate sur computerservicebarry.com (chiffres de la période affichée).
                    @if($dernierePublication)
                        Dernière publication : {{ \Carbon\Carbon::parse($dernierePublication->publie_at)->format('d/m/Y à H:i') }}.
                    @endif
                </div>
            </div>
            <a href="{{ route('satisfaction.pdf', $periodeParams) }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-pdf me-1"></i>Rapport PDF</a>
            @if($csbConfigure)
                <form method="POST" action="{{ route('satisfaction.rapport.publier') }}" onsubmit="return confirm('Publier ces chiffres sur computerservicebarry.com ? Ils remplacent le rapport déjà en ligne.');">
                    @csrf
                    @foreach($periodeParams as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                    <button class="btn btn-primary" @disabled($rapport['total'] < 1)><i class="bi bi-globe2 me-1"></i>Publier sur le site</button>
                </form>
            @else
                <span class="small text-muted">Publication indisponible : la liaison avec le site de CSB n'est pas configurée.</span>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- Répartition des notes : une seule série, valeurs écrites --}}
        <div class="col-lg-6">
            <div class="card theme-card shadow-sm h-100"><div class="card-body">
                <h6 class="fw-bold mb-3">Répartition des notes</h6>
                @foreach(array_reverse($rapport['repartition'], true) as $note => $n)
                    <div class="d-flex align-items-center gap-2 mb-2" title="{{ $note }} — {{ Satisfaction::LIBELLES_NOTE[$note] }} : {{ $n }} avis">
                        <div class="small text-nowrap" style="width: 120px;">{{ $note }} — {{ Satisfaction::LIBELLES_NOTE[$note] }}</div>
                        <div class="flex-grow-1" style="background: var(--border-color, #eef0f3); border-radius: 4px; height: 14px;">
                            <div style="width: {{ round(100 * $n / $max) }}%; height: 14px; border-radius: 4px; background: var(--theme-accent, #831843);"></div>
                        </div>
                        <div class="small fw-bold text-end" style="width: 34px;">{{ $n }}</div>
                    </div>
                @endforeach
            </div></div>
        </div>
        {{-- Ce qui plaît / ce qui gêne --}}
        <div class="col-lg-6">
            <div class="card theme-card shadow-sm h-100"><div class="card-body">
                <div class="row">
                    <div class="col-sm-6">
                        <h6 class="fw-bold mb-2"><i class="bi bi-hand-thumbs-up me-1"></i>Ce qui plaît</h6>
                        @forelse($rapport['points_forts'] as $p)
                            <div class="d-flex justify-content-between small border-bottom py-1"><span>{{ $p['libelle'] }}</span><strong>{{ $p['nombre'] }}</strong></div>
                        @empty
                            <div class="small text-muted">Rien pour l'instant.</div>
                        @endforelse
                    </div>
                    <div class="col-sm-6 mt-3 mt-sm-0">
                        <h6 class="fw-bold mb-2"><i class="bi bi-tools me-1"></i>À améliorer</h6>
                        @forelse($rapport['points_faibles'] as $p)
                            <div class="d-flex justify-content-between small border-bottom py-1"><span>{{ $p['libelle'] }}</span><strong>{{ $p['nombre'] }}</strong></div>
                        @empty
                            <div class="small text-muted">Rien pour l'instant.</div>
                        @endforelse
                    </div>
                </div>
                @if($rapport['par_mois'])
                    <h6 class="fw-bold mt-3 mb-2">Par mois</h6>
                    <table class="table table-sm small mb-0">
                        <thead><tr><th>Mois</th><th class="text-end">Avis</th><th class="text-end">Moyenne</th></tr></thead>
                        <tbody>
                            @foreach($rapport['par_mois'] as $mois => $m)
                                <tr><td>{{ \Carbon\Carbon::parse($mois.'-01')->translatedFormat('F Y') }}</td><td class="text-end">{{ $m['nombre'] }}</td><td class="text-end">{{ number_format($m['moyenne'], 1, ',', '') }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div></div>
        </div>
    </div>

    {{-- Les avis --}}
    <div class="card theme-card shadow-sm">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Avis reçus</h6>
            <div class="table-responsive">
                <table class="table table-actions-fixes align-middle">
                    <thead><tr><th>Date</th><th>Note</th><th>Auteur</th><th>Avis</th><th>Après</th><th class="text-center">Site CSB</th></tr></thead>
                    <tbody>
                        @forelse($avis as $a)
                            <tr>
                                <td class="text-nowrap small">{{ \Carbon\Carbon::parse($a->created_at)->format('d/m/Y') }}</td>
                                <td class="text-nowrap"><strong>{{ $a->note }}</strong>/5 <span class="small text-muted">{{ Satisfaction::LIBELLES_NOTE[$a->note] ?? '' }}</span></td>
                                <td class="small"><strong>{{ $a->nomPrenom }}</strong><br>{{ $a->fonction }}{{ $a->nomEcole ? ' — '.$a->nomEcole : '' }}</td>
                                <td class="small" style="min-width: 220px;">
                                    @if($a->points)
                                        <div class="mb-1">@foreach((array) json_decode($a->points, true) as $p)<span class="badge bg-light text-dark border me-1">{{ $nom($p) }}</span>@endforeach</div>
                                    @endif
                                    {{ $a->commentaire ?: '—' }}
                                </td>
                                <td class="small text-muted">{{ $a->contexte }}</td>
                                <td class="text-center">
                                    @if($a->publie_at)
                                        <span class="badge bg-success mb-1">En ligne</span>
                                        @if($csbConfigure)
                                            <form method="POST" action="{{ route('satisfaction.avis.publier', $a->id) }}">@csrf<input type="hidden" name="retirer" value="1"><button class="btn btn-sm btn-outline-danger">Retirer</button></form>
                                        @endif
                                    @elseif($a->accord_publication && $a->commentaire && $csbConfigure)
                                        <form method="POST" action="{{ route('satisfaction.avis.publier', $a->id) }}">@csrf<button class="btn btn-sm btn-outline-primary"><i class="bi bi-globe2 me-1"></i>Publier</button></form>
                                    @else
                                        <span class="small text-muted">{{ $a->accord_publication ? 'Sans commentaire' : 'Pas d\'accord' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Aucun avis sur cette période.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $avis->links() }}
        </div>
    </div>
@endsection
