@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Bulletins</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">Classes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $classe->nom_classe }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ session('error') }}</div>
    @endif

    <div class="card theme-card shadow-sm mb-4">
        <div class="card-header theme-header">
            <h5 class="mb-0 fw-bold">Liste des bulletins - {{ $classe->nom_classe }}</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Année scolaire</label>
                    <select class="form-select" id="id_annee">
                        <option value="">Sélectionner une année</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id_anneeScolaire }}">{{ $annee->annee }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 trimestre-field">
                    <label class="form-label">Période</label>
                    <select class="form-select" id="id_trimestre">
                        <option value="">Sélectionner une période</option>
                        @foreach($trimestres as $trimestre)
                            <option value="{{ $trimestre->id_trimestre }}">{{ $trimestre->nom_trimestre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mois-field">
                    <label class="form-label">Mois</label>
                    <select class="form-select" id="mois">
                        <option value="">Sélectionner un mois</option>
                        @foreach($moisOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card theme-card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom & Prénom</th>
                            <th>Genre</th>
                            <th>Moyenne</th>
                            <th>Rang</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="bulletins-body">
                        <tr><td colspan="6" class="text-center py-4 text-muted">Sélectionnez l'année scolaire et la période.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ordre = @json($classe->ordreEnseignement);
            const annee = document.getElementById('id_annee');
            const trimestre = document.getElementById('id_trimestre');
            const mois = document.getElementById('mois');
            const body = document.getElementById('bulletins-body');
            const trimestreField = document.querySelector('.trimestre-field');
            const moisField = document.querySelector('.mois-field');

            trimestreField.style.display = ordre === 'fondamentale1' ? 'none' : '';
            moisField.style.display = ordre === 'fondamentale1' ? '' : 'none';

            function rangLabel(row) {
                const suffix = row.rang === 1 ? '1er' : row.rang + 'e';
                return row.exaequo ? suffix + ' ex aequo' : suffix;
            }

            function loadBulletins() {
                const idAnnee = annee.value;
                const idTrimestre = trimestre.value;
                const moisValue = mois.value;
                if (!idAnnee || (ordre === 'fondamentale1' ? !moisValue : !idTrimestre)) {
                    body.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Sélectionnez l’année scolaire et la période.</td></tr>';
                    return;
                }

                const params = new URLSearchParams({id_annee: idAnnee});
                if (ordre === 'fondamentale1') params.set('mois', moisValue);
                else params.set('id_trimestre', idTrimestre);

                fetch("{{ route('pedagogie.bulletins.data', $classe->id_classe) }}?" + params.toString())
                    .then(response => response.json())
                    .then(rows => {
                        body.innerHTML = rows.map(row => {
                            const printParams = new URLSearchParams({id_annee: idAnnee});
                            if (ordre === 'fondamentale1') printParams.set('mois', moisValue);
                            else printParams.set('id_trimestre', idTrimestre);
                            return '<tr>' +
                                '<td>' + escapeHtml(row.matricule || '') + '</td>' +
                                '<td class="fw-bold">' + escapeHtml((row.nom_eleve || '') + ' ' + (row.prenom_eleve || '')) + '</td>' +
                                '<td>' + escapeHtml(row.genre_eleve || '') + '</td>' +
                                '<td>' + Number(row.moyenne || 0).toFixed(2) + '</td>' +
                                '<td>' + rangLabel(row) + '</td>' +
                                '<td class="text-center"><a class="btn btn-light btn-sm p-2" target="_blank" href="{{ url('/pedagogie/bulletins') }}/' + row.id_eleve + '/download?' + printParams.toString() + '"><i class="bi bi-printer text-primary"></i></a></td>' +
                            '</tr>';
                        }).join('') || '<tr><td colspan="6" class="text-center py-4 text-muted">Aucun bulletin trouvé pour cette période.</td></tr>';
                    });
            }

            function escapeHtml(value) {
                return String(value).replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[c]));
            }

            annee.addEventListener('change', loadBulletins);
            trimestre.addEventListener('change', loadBulletins);
            mois.addEventListener('change', loadBulletins);
        });
    </script>
@endpush
