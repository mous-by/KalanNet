@extends('layouts.guest')

@section('title', __('securite.titre'))

@section('content')
    <h1><i class="bi bi-shield-lock me-1"></i>{{ __('securite.titre') }}</h1>

    <div class="alert alert-warning py-2 d-flex align-items-start gap-2 small">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            {!! __('securite.temporaire') !!}
            @if(auth()->user()?->mdp_temporaire_expire_at)
                <div class="mt-1">{{ __('securite.temporaire_valable', ['date' => \App\Support\MotDePasse::finValidite(auth()->user())]) }}</div>
            @endif
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2 small">
            @foreach($errors->all() as $erreur)
                <div>{{ $erreur }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.force.update') }}">
        @csrf
        @method('PUT')
        @foreach([
            'current_password' => __('securite.actuel'),
            'password' => __('securite.nouveau'),
            'password_confirmation' => __('securite.confirmation'),
        ] as $champ => $libelle)
            <div class="mb-3">
                <label for="{{ $champ }}" class="form-label">{{ $libelle }}</label>
                <div class="input-group">
                    <input type="password" id="{{ $champ }}" name="{{ $champ }}" class="form-control @error($champ) is-invalid @enderror" required
                           autocomplete="{{ $champ === 'current_password' ? 'current-password' : 'new-password' }}" @if($loop->first) autofocus @endif @if($champ === 'password') data-force-mdp @endif>
                    <button type="button" class="btn btn-outline-secondary js-voir-mdp" data-cible="{{ $champ }}" title="{{ __('securite.afficher') }}" aria-label="{{ __('securite.afficher') }}"><i class="bi bi-eye"></i></button>
                </div>
            </div>
        @endforeach

        <div class="small text-muted mb-2" id="mdp-manque" aria-live="polite"></div>
        <div class="d-grid">
            <button type="submit" class="btn btn-guest" id="mdp-enregistrer" disabled><i class="bi bi-check-circle me-1"></i>{{ __('securite.bouton') }}</button>
        </div>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="text-center mt-3">
        @csrf
        <button type="submit" class="btn btn-link text-muted btn-sm">{{ __('securite.deconnexion') }}</button>
    </form>
@endsection

@push('scripts')
    @php
        $textesManque = [
            'actuel' => __('securite.manque_actuel'),
            'regles' => __('securite.manque_regles'),
            'different' => __('securite.manque_different'),
            'confirmation' => __('securite.manque_confirmation'),
        ];
    @endphp
    @include('partials.force-mot-de-passe')
    <script>
        // « Enregistrer » reste grisé tant que le nouveau mot de passe ne respecte pas les règles.
        (function () {
            const actuel = document.getElementById('current_password');
            const nouveau = document.getElementById('password');
            const confirmation = document.getElementById('password_confirmation');
            const bouton = document.getElementById('mdp-enregistrer');
            const manque = document.getElementById('mdp-manque');
            const textes = @json($textesManque);

            function verifier() {
                const mdp = nouveau.value;
                let probleme = '';
                if (!actuel.value) probleme = textes.actuel;
                else if (mdp.length < 8 || !/\p{L}/u.test(mdp) || !/\d/.test(mdp)) probleme = textes.regles;
                else if (mdp === actuel.value) probleme = textes.different;
                else if (confirmation.value !== mdp) probleme = textes.confirmation;
                bouton.disabled = probleme !== '';
                manque.textContent = (mdp || confirmation.value) ? probleme : '';
            }
            [actuel, nouveau, confirmation].forEach(champ => champ.addEventListener('input', verifier));
            verifier();
        })();

        document.querySelectorAll('.js-voir-mdp').forEach(function (bouton) {
            bouton.addEventListener('click', function () {
                const champ = document.getElementById(bouton.dataset.cible);
                const visible = champ.type === 'password';
                champ.type = visible ? 'text' : 'password';
                bouton.querySelector('i').className = visible ? 'bi bi-eye-slash' : 'bi bi-eye';
                bouton.title = visible ? @json(__('securite.masquer')) : @json(__('securite.afficher'));
                bouton.setAttribute('aria-label', bouton.title);
            });
        });
    </script>
@endpush
