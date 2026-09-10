@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ $revendeur->nom }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('revendeur.dashboard') }}">Mes écoles</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Mes tarifs</li>
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

    <div class="card theme-card shadow-sm">
        <div class="card-header theme-header">
            <h5 class="mb-0 fw-bold"><i class="bi bi-tags-fill me-2"></i>Mes tarifs de revente</h5>
        </div>
        <div class="card-body">
            <p class="text-muted small">Fixez le prix que vous facturez aux écoles que vous apportez, formule par formule. Le prix de gros est fixé par KalanNet et n'est pas modifiable.</p>

            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Formule</th>
                            <th class="text-end">Prix de gros</th>
                            <th>Mon prix de revente</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($offres as $revendeurOffre)
                            <tr>
                                <td>{{ $revendeurOffre->offre->nom }} <span class="text-muted small">({{ $revendeurOffre->offre->duree_jours }} jours)</span></td>
                                <td class="text-end">{{ number_format($revendeurOffre->offre->montant, 0, ',', ' ') }} {{ $revendeurOffre->offre->devise }}</td>
                                <td>
                                    <form method="POST" action="{{ route('revendeur.tarifs.update', $revendeurOffre) }}" class="d-flex gap-2 align-items-center">
                                        @csrf
                                        @method('PUT')
                                        <input name="montant_revente" type="number" min="0" step="1" class="form-control form-control-sm" style="max-width:160px" value="{{ (int) $revendeurOffre->montant_revente }}" required>
                                        <span>{{ $revendeurOffre->offre->devise }}</span>
                                        <button class="btn btn-sm btn-primary" type="submit">Enregistrer</button>
                                    </form>
                                </td>
                                <td>
                                    <span class="badge {{ $revendeurOffre->actif ? 'bg-success' : 'bg-secondary' }}">{{ $revendeurOffre->actif ? 'Disponible' : 'Retirée par KalanNet' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Aucune formule ne vous a encore été ouverte.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
