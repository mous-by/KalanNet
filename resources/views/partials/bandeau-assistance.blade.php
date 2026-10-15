{{-- Accès d'assistance en cours : rappel permanent et retour au compte du SupAdmin. --}}
@if(is_array(session('assistance')))
    <div class="alert alert-danger border-0 rounded-0 shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 py-2" role="alert" style="position: sticky; top: 0; z-index: 1030;">
        <div>
            <i class="bx bx-support me-1"></i>
            <strong>{{ __('assistance.bandeau', ['nom' => session('assistance.nom_cible')]) }}</strong>
            @if(session('assistance.nom_ecole'))
                <span class="ms-2">{{ __('assistance.bandeau_ecole', ['ecole' => session('assistance.nom_ecole')]) }}</span>
            @endif
            <span class="ms-2 small">({{ __('assistance.bandeau_fin', ['heure' => \Illuminate\Support\Carbon::createFromTimestamp(session('assistance.expire'))->timezone(config('app.timezone'))->format('H:i')]) }})</span>
        </div>
        <form method="POST" action="{{ route('assistance.terminer') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-light btn-sm fw-bold"><i class="bx bx-log-out-circle me-1"></i>{{ __('assistance.revenir') }}</button>
        </form>
    </div>
@endif
