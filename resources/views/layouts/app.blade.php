<!doctype html>
@php
    $userTheme = auth()->check() ? auth()->user()->theme_preference : null;
    $theme = $userTheme ?: 'bleu-sombre';
    $currentLocale = $currentLocale ?? app()->getLocale();
    $currentDirection = $currentDirection ?? config("app.supported_locales.$currentLocale.dir", 'ltr');
@endphp
<html lang="{{ $currentLocale }}" dir="{{ $currentDirection }}" data-theme="{{ $theme }}">

<head>
    <script>
        // Appliquer le thème depuis localStorage avant le chargement CSS (évite le flash)
        localStorage.removeItem('theme');
        (function () {
            var saved = localStorage.getItem('kalannet_theme');
            if (saved) {
                document.documentElement.setAttribute('data-theme', saved);
            }
        })();
    </script>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon & App Icons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/images/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/images/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#146c43">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="KalanNet">
    
    <!--plugins-->
    <link href="{{ asset('assets/plugins/simplebar/css/simplebar.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/metismenu/css/metisMenu.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/select2/css/select2-bootstrap4.css') }}" rel="stylesheet" />
    <style>
        /* Listes avec recherche (assets/js/selects-recherche.js) : même gabarit que les listes Bootstrap. */
        .select2-container--bootstrap4 .select2-selection--single { height: calc(1.5em + .75rem + 2px); }
        .select2-container--bootstrap4.select2-sm .select2-selection--single { height: calc(1.5em + .5rem + 2px); font-size: .875rem; }
        .select2-container--bootstrap4.select2-sm .select2-selection--single .select2-selection__rendered { line-height: calc(1.5em + .3rem); }
        .select2-container--bootstrap4 .select2-dropdown { z-index: 1060; }
        .select2-container--open .select2-dropdown { min-width: 12rem; }
    </style>

    <!-- Bootstrap CSS -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/bootstrap-extended.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/icons.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-icons.css') }}">
    
    <title>{{ config('app.name', 'KalanNet') }}</title>
    
    @stack('styles')
    <style>
        .kalannet-language-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 36px;
            border: 1px solid rgba(128,128,128,.28);
            background: #fff;
            color: var(--theme-accent, #14532d);
            font-weight: 800;
            border-radius: 999px;
            padding: 0 12px;
        }

        .kalannet-language-btn strong {
            font-size: 11px;
        }

        [dir="rtl"] input.arabic-input-ready,
        [dir="rtl"] textarea.arabic-input-ready {
            text-align: right;
        }

        input.arabic-input-ready,
        textarea.arabic-input-ready {
            text-align: right;
        }
    </style>
    <!-- Custom Overrides (KalanNet) - Loaded LAST to ensure priority -->
    <link href="{{ asset('css/themes.css') }}?v={{ time() }}" rel="stylesheet" />
    
</head>

<body>
    <!--wrapper-->
    <div class="wrapper">
        @auth
            @include('partials.sidebar')
            @include('partials.navbar')
        @endauth

        <!--start content-->
        <main class="page-content">
            @auth
                @include('partials.bandeau-assistance')
                @include('partials.demande-assistance')
                @include('partials.bienvenue-admin')
                @unless(request()->routeIs('dashboard') || View::hasSection('sans_retour'))
                    {{-- Flèche retour de toutes les pages : vers la page parente du fil d'Ariane (lien posé par le script plus bas), sinon le tableau de bord. --}}
                    <div class="page-retour mb-2" data-page-retour>
                        <a href="{{ route('dashboard') }}" class="page-retour-fleche" data-page-retour-lien title="{{ __('ui.retour') }}" aria-label="{{ __('ui.retour') }}"><i class="bi bi-arrow-left"></i></a>
                    </div>
                @endunless
            @endauth
            @yield('content')
        </main>
        <!--end content-->

        @auth
            @include('partials.footer')
            @include('annonces._unread-modal')
            {{-- KalanBot agit sur les données d'une école : pas pour le DAE / DCAP. --}}
            @unless(in_array(Auth::user()?->droit, ['DAE', 'DCAP'], true))
                @include('components.assistant')
            @endunless
            @include('components.pwa-install-modal')
        @endauth

        <!--start overlay-->
        <div class="overlay nav-toggle-icon"></div>
        <!--end overlay-->

        <!--Start Back To Top Button-->
        <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
        <!--End Back To Top Button-->

    </div>
    <!--end wrapper-->

    <!-- Bootstrap bundle JS -->
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <!--plugins-->
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/metismenu/js/metisMenu.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/js/selects-recherche.js') }}?v={{ filemtime(public_path('assets/js/selects-recherche.js')) }}"></script>
    <script src="{{ asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!--app JS-->
    <script src="{{ asset('assets/js/app.js') }}?v={{ filemtime(public_path('assets/js/app.js')) }}"></script>
    <script src="{{ asset('assets/mon_js/auto_filter.js') }}?v={{ filemtime(public_path('assets/mon_js/auto_filter.js')) }}"></script>
    
    @include('partials.localized-inputs-script')
    @include('partials.ui-translator-script')
    @include('partials.mot-de-passe-temporaire')
    @stack('scripts')
    <script>
        // Flèche retour : page parente du fil d'Ariane ; masquée si la page a déjà sa propre flèche (lien sans texte).
        (function () {
            const barre = document.querySelector('[data-page-retour]');
            if (!barre) return;
            const contenu = barre.parentElement;
            const autre = [...contenu.querySelectorAll('a .bi-arrow-left, button .bi-arrow-left')]
                .some((i) => !barre.contains(i) && i.closest('a, button').textContent.trim() === '');
            if (autre) { barre.remove(); return; }
            const liens = [...contenu.querySelectorAll('.page-breadcrumb .breadcrumb-item:not(.active) a[href]')];
            const parent = liens.length ? liens[liens.length - 1] : null;
            if (parent) barre.querySelector('[data-page-retour-lien]').href = parent.href;
        })();
    </script>
    <script src="{{ asset('assets/mon_js/save-button-spinner.js') }}?v={{ filemtime(public_path('assets/mon_js/save-button-spinner.js')) }}"></script>
    <script>
        // Langue et thème retenus pour la page hors connexion (public/offline.html), qui ne peut pas les demander au serveur.
        try {
            localStorage.setItem('kalannet_locale', @json($currentLocale));
            localStorage.setItem('kalannet_theme', document.documentElement.getAttribute('data-theme') || 'vert');
        } catch (e) {}
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js').catch(function () {});
            });
        }
    </script>
</body>

</html>
