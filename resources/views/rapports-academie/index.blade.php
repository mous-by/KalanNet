@extends('layouts.app')

@section('content')
    @php $C = \App\Services\Rapports\CanevasEsg::class; @endphp
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Rapport succinct : secondaire</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $annee->annee }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(\App\Support\RapportSuccinctDonnees::ecoleConcernee((int) $ecole->idEcole))
        <p class="mb-3"><a href="{{ route('rapport-succinct.index', ['annee' => $annee->id_anneeScolaire]) }}"><i class="bi bi-arrow-left me-1"></i>Voir le rapport succinct du fondamental (remis au CAP)</a></p>
    @endif

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <div class="alert {{ $academieSurKalanNet ? 'alert-info' : 'alert-warning' }} border-0 border-start border-4">
        @if($academieSurKalanNet)
            <i class="bi bi-send me-1"></i>Votre académie utilise KalanNet : téléchargez le canevas pré-rempli, complétez-le dans Excel, puis envoyez-le ici.
        @else
            <i class="bi bi-printer me-1"></i>Votre académie n'utilise pas encore KalanNet : téléchargez le canevas pré-rempli, complétez-le dans Excel, <strong>remettez-le à votre académie</strong>, puis marquez-le comme remis.
        @endif
    </div>

    @if($technique)
        <div class="alert alert-warning border-0 border-start border-warning border-4">
            <i class="bi bi-info-circle me-1"></i>Ces canevas sont ceux du secondaire général : leurs tableaux sont organisés par séries (TSE, TAL…). Pour le technique et professionnel, KalanNet n'y écrit que ce qui ne dépend pas d'une série ; adaptez les autres tableaux à vos filières.
        </div>
    @endif
    @foreach($aVerifier as $message)
        <div class="alert alert-warning border-0 border-start border-warning border-4"><i class="bi bi-exclamation-triangle me-1"></i>{{ $message }}</div>
    @endforeach

    <form method="GET" action="{{ route('rapport-academie.index') }}" class="row g-2 align-items-end mb-3">
        <div class="col-sm-4 col-md-3">
            <label class="form-label small fw-bold text-uppercase" for="annee">Année scolaire</label>
            <select name="annee" id="annee" class="form-select" onchange="this.form.submit()">
                @foreach($annees as $a)
                    <option value="{{ $a->id_anneeScolaire }}" @selected($a->id_anneeScolaire === $annee->id_anneeScolaire)>{{ $a->annee }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="row g-3">
        @foreach($C::TYPES as $type => $titre)
            @php $rapport = $rapports->get($type); @endphp
            <div class="col-lg-6">
                <div class="card theme-card shadow-sm h-100">
                    <div class="card-header theme-header d-flex justify-content-between align-items-center gap-2">
                        <h6 class="mb-0 fw-bold">{{ $titre }}</h6>
                        @if($rapport)
                            <span class="badge {{ $rapport->statut === 'envoye' ? 'bg-success' : 'bg-secondary' }}">{{ $rapport->statut === 'envoye' ? 'Envoyé' : 'Remis' }} le {{ $rapport->envoye_at?->format('d/m/Y') }}</span>
                        @endif
                    </div>
                    <div class="card-body d-flex flex-column">
                        <p class="small fw-bold mb-1">KalanNet remplit pour vous :</p>
                        <ul class="small text-muted mb-3 ps-3">
                            @foreach(($technique ? $C::REMPLIS_TECHNIQUE : $C::REMPLIS)[$type] as $tableau)
                                <li>{{ $tableau }}</li>
                            @endforeach
                        </ul>
                        <p class="small text-muted">Le reste se complète dans le fichier. Vérifiez les cases « VERIFICATION » du canevas avant de le remettre.</p>
                        <div class="mt-auto">
                            <a href="{{ route('rapport-academie.canevas', ['type' => $type, 'annee' => $annee->id_anneeScolaire]) }}" class="btn btn-primary mb-3"><i class="bi bi-file-earmark-excel me-1"></i>Télécharger le canevas pré-rempli</a>
                            @if($rapport?->fichier)
                                <a href="{{ route('rapport-academie.fichier', $rapport->id) }}" class="btn btn-outline-secondary mb-3"><i class="bi bi-download me-1"></i>Fichier remis</a>
                            @endif
                            <form method="POST" enctype="multipart/form-data" action="{{ route('rapport-academie.remettre', ['type' => $type, 'annee' => $annee->id_anneeScolaire]) }}">
                                @csrf
                                @if($academieSurKalanNet)
                                    <label class="form-label small fw-bold" for="fichier-{{ $type }}">Fichier Excel complété</label>
                                    <input type="file" name="fichier" id="fichier-{{ $type }}" class="form-control mb-2" accept=".xlsx,.xls,.ods" required>
                                    <button type="submit" class="btn btn-success fw-semibold"><i class="bi bi-send me-1"></i>{{ $rapport ? 'Renvoyer à l\'académie' : 'Envoyer à l\'académie' }}</button>
                                @else
                                    <button type="submit" class="btn btn-success fw-semibold"><i class="bi bi-check2-circle me-1"></i>{{ $rapport ? 'Marquer de nouveau comme remis' : 'Marquer comme remis à l\'académie' }}</button>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
