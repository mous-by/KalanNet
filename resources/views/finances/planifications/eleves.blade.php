@extends('layouts.app')

@section('content')
<a href="{{ route('finances.planifications', array_filter(['id_classe' => $idClasse ?: null, 'id_annee' => $idAnnee ?: null])) }}" class="btn btn-primary mb-3">
    <i class="bi bi-arrow-left"></i>
</a>

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Finances</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('finances.index') }}"><i class="bi bi-house"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Changer la formule des élèves</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if(session('planification_errors'))
    <div class="alert alert-warning">
        @foreach(session('planification_errors') as $message)
            <div>{{ $message }}</div>
        @endforeach
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $message)
            <div>{{ $message }}</div>
        @endforeach
    </div>
@endif

<div class="row g-0">
    <div class="col-12 col-md-3">
        @include('finances.partials.menu', ['active' => 'planifications'])
    </div>

    <div class="col-12 col-lg-9 pt-4 pt-lg-0 p-md-3">
        <div class="card theme-card w-100">
            <div class="card-header theme-header"><i class="bi bi-people me-1"></i> Changer la formule des élèves</div>
            <div class="card-body">
                <p class="small text-muted">
                    Chaque élève paie selon la formule choisie à son inscription. Une nouvelle formule ne change rien pour les élèves déjà inscrits :
                    choisissez ici la classe, cochez les élèves et donnez-leur la nouvelle formule. Un élève qui a déjà payé sur sa formule actuelle
                    n'est pas changé, pour ne pas perdre ses versements.
                </p>
                <form method="GET" action="{{ route('finances.planifications.eleves') }}" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="f_classe">Classe</label>
                        <select class="form-select" id="f_classe" name="id_classe" onchange="this.form.submit()">
                            <option value="">Choisir une classe</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id_classe }}" @selected($idClasse === (int) $classe->id_classe)>{{ $classe->nom_classe }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="f_annee">Année scolaire</label>
                        <select class="form-select" id="f_annee" name="id_annee" onchange="this.form.submit()">
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id_anneeScolaire }}" @selected($idAnnee === (int) $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
        </div>

        @if($idClasse)
            <div class="card theme-card w-100 mt-4">
                <div class="card-body">
                    @if($formules->isEmpty())
                        <div class="alert alert-info mb-0">
                            Cette classe n'a aucune formule de paiement cette année.
                            <a href="{{ route('finances.planifications.create') }}">Créez-la d'abord</a>.
                        </div>
                    @elseif($eleves->isEmpty())
                        <div class="text-muted text-center py-4">Aucun élève inscrit dans cette classe pour cette année.</div>
                    @else
                        <form method="POST" action="{{ route('finances.planifications.eleves.update') }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="id_classe" value="{{ $idClasse }}">
                            <input type="hidden" name="id_annee" value="{{ $idAnnee }}">

                            <div class="row g-3 align-items-end mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold" for="nouvelle_formule">Nouvelle formule <span class="text-danger">*</span></label>
                                    <select class="form-select" id="nouvelle_formule" name="id_planification" required>
                                        <option value="">Choisir la formule</option>
                                        @foreach($formules as $formule)
                                            <option value="{{ $formule->id_planification }}">{{ $isPublicSchool ? __('finances.th_cooperative') : $formule->motif }} — {{ number_format((float) $formule->montant_planification, 0, ',', ' ') }} F</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="tout-cocher">Tout cocher</button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-bordered align-middle mb-3">
                                    <thead>
                                        <tr><th style="width: 40px;"></th><th>Élève</th><th>Formule actuelle</th><th class="text-end">Déjà payé</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach($eleves as $ligne)
                                            @php
                                                $actuelle = $ligne->id_planification ? $formules->get($ligne->id_planification) : null;
                                                $bloque = $ligne->pris_en_charge || $ligne->deja_paye > 0;
                                            @endphp
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input eleve-case" name="eleves[]" value="{{ $ligne->eleve->id_eleve }}" @disabled($bloque) aria-label="Choisir {{ $ligne->eleve->prenom_eleve }} {{ $ligne->eleve->nom_eleve }}">
                                                </td>
                                                <td>{{ $ligne->eleve->prenom_eleve }} {{ $ligne->eleve->nom_eleve }}</td>
                                                <td>
                                                    @if($ligne->pris_en_charge)
                                                        <span class="badge bg-secondary">Pris en charge par l'État</span>
                                                    @elseif($actuelle)
                                                        {{ $isPublicSchool ? __('finances.th_cooperative') : $actuelle->motif }}
                                                    @elseif($ligne->id_planification)
                                                        <span class="badge bg-danger">Formule supprimée</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark">Aucune formule</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($ligne->deja_paye > 0)
                                                        <span class="text-danger fw-bold">{{ number_format($ligne->deja_paye, 0, ',', ' ') }} F</span>
                                                        <div class="small text-muted">ne peut pas être changé</div>
                                                    @else
                                                        0 F
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Donner cette formule aux élèves cochés</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('tout-cocher')?.addEventListener('click', function () {
    const cases = Array.from(document.querySelectorAll('.eleve-case:not(:disabled)'));
    const toutes = cases.length > 0 && cases.every((c) => c.checked);
    cases.forEach((c) => { c.checked = !toutes; });
    this.textContent = toutes ? 'Tout cocher' : 'Tout décocher';
});
</script>
@endpush
