@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Rapport de rentrée</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('rapport-secondaire.index', ['type' => 'rentree', 'annee' => $annee->id_anneeScolaire]) }}">{{ $annee->annee }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Année du DEF des élèves</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <div class="alert alert-info border-0 border-start border-4">
        <i class="bi bi-info-circle me-1"></i>
        Indiquez l'année d'obtention du DEF (CG) de chaque élève. En {{ $annee->annee }} : CG{{ substr((string) $debut, -2) }} ({{ $debut }}) = 1 an,
        CG{{ substr((string) ($debut - 1), -2) }} ({{ $debut - 1 }}) = 2 ans, et ainsi de suite. Elle sert au tableau T9 (scolarité) et, l'année du Bac, au tableau T11.
        Elle est déjà remplie quand l'élève a eu son DEF dans votre établissement (résultats nationaux).
    </div>

    <form method="POST" action="{{ route('rapport-secondaire.def.store') }}">
        @csrf
        <input type="hidden" name="annee" value="{{ $annee->id_anneeScolaire }}">

        @forelse($parClasse as $classe => $eleves)
            <div class="card theme-card shadow-sm mb-3">
                <div class="card-header theme-header d-flex flex-wrap align-items-center gap-2">
                    <h6 class="mb-0 fw-bold me-auto">{{ $classe }} <span class="fw-normal small">({{ $eleves->count() }} élève(s), {{ $eleves->whereNull('annee_def')->count() }} sans année)</span></h6>
                    <div class="input-group input-group-sm" style="width: 260px;">
                        <span class="input-group-text">Toute la classe</span>
                        <input type="number" class="form-control" min="1980" max="{{ date('Y') + 1 }}" placeholder="{{ $debut }}" data-remplir aria-label="Année du DEF pour toute la classe {{ $classe }}">
                        <button type="button" class="btn btn-light" data-remplir-bouton title="Remplir les cases vides de la classe">Remplir</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead><tr><th>Élève</th><th>Matricule</th><th style="width: 180px;">Année du DEF</th><th>Scolarité</th></tr></thead>
                            <tbody>
                                @foreach($eleves as $e)
                                    <tr>
                                        <td>{{ $e->prenom_eleve }} {{ $e->nom_eleve }}</td>
                                        <td>{{ $e->matricule }}</td>
                                        <td><input type="number" name="def[{{ $e->id_eleve }}]" class="form-control form-control-sm" min="1980" max="{{ date('Y') + 1 }}" value="{{ old('def.'.$e->id_eleve, $e->annee_def) }}" data-def aria-label="Année du DEF de {{ $e->prenom_eleve }} {{ $e->nom_eleve }}"></td>
                                        <td class="small text-muted" data-scolarite>{{ $e->annee_def ? ($debut - $e->annee_def + 1).' an(s)' : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="card theme-card"><div class="card-body text-center text-muted py-4">Aucun élève du secondaire général cette année.</div></div>
        @endforelse

        @if($parClasse->isNotEmpty())
            <div class="text-end mb-4"><button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i>Enregistrer</button></div>
        @endif
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const debut = {{ $debut }};
            const scolarite = (input) => {
                const cellule = input.closest('tr').querySelector('[data-scolarite]');
                const annee = parseInt(input.value, 10);
                cellule.textContent = annee ? (debut - annee + 1) + ' an(s)' : '—';
            };
            document.querySelectorAll('[data-def]').forEach((input) => input.addEventListener('input', () => scolarite(input)));
            document.querySelectorAll('[data-remplir-bouton]').forEach((bouton) => bouton.addEventListener('click', () => {
                const carte = bouton.closest('.card');
                const valeur = carte.querySelector('[data-remplir]').value;
                if (!valeur) return;
                carte.querySelectorAll('[data-def]').forEach((input) => { if (input.value === '') { input.value = valeur; scolarite(input); } });
            }));
        })();
    </script>
@endpush
