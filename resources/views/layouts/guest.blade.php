<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('app.supported_locales.'.app()->getLocale().'.dir', 'ltr') }}" data-theme="vert">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - KalanNet</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <meta name="theme-color" content="#146c43">
    <script>
        (function () {
            try {
                document.documentElement.setAttribute('data-theme', localStorage.getItem('kalannet_theme') || 'vert');
            } catch (e) {}
        })();
    </script>
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --theme-accent: #14532d; --theme-dark: #0f3d23; --theme-ring: rgba(20,83,45,.22); }
        [data-theme="bleu-sombre"] { --theme-accent: #001529; --theme-dark: #00101f; --theme-ring: rgba(0,21,41,.22); }
        [data-theme="light"]       { --theme-accent: #475569; --theme-dark: #334155; --theme-ring: rgba(71,85,105,.22); }
        [data-theme="vert"]        { --theme-accent: #14532d; --theme-dark: #0f3d23; --theme-ring: rgba(20,83,45,.22); }
        [data-theme="dark"]        { --theme-accent: #831843; --theme-dark: #641336; --theme-ring: rgba(131,24,67,.22); }
        [data-theme="rouge"]       { --theme-accent: #450a0a; --theme-dark: #320707; --theme-ring: rgba(69,10,10,.22); }
        [data-theme="violet"]      { --theme-accent: #2e1065; --theme-dark: #230c4d; --theme-ring: rgba(46,16,101,.22); }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            font-family: 'Inter', system-ui, -apple-system, "Segoe UI", sans-serif;
            color: #172033;
            background: linear-gradient(135deg, var(--theme-dark) 0%, var(--theme-accent) 100%);
        }
        .guest-card {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, .25);
            padding: 28px 24px;
        }
        .guest-brand { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; }
        .guest-brand img { width: 40px; height: 40px; }
        .guest-brand strong { font-size: 1.15rem; color: var(--theme-dark); }
        .guest-card h1 { font-size: 1.15rem; font-weight: 700; margin-bottom: 12px; }
        .form-control:focus { border-color: var(--theme-accent); box-shadow: 0 0 0 .2rem var(--theme-ring); }
        .btn-guest { background: var(--theme-accent); color: #fff; font-weight: 600; }
        .btn-guest:hover, .btn-guest:focus { background: var(--theme-dark); color: #fff; }
        /* Grand écran : la carte à droite, comme la connexion, pour laisser lisibles les textes du carrousel. */
        @media (min-width: 992px) {
            body { justify-content: flex-end; padding-inline-end: clamp(16px, 5vw, 72px); }
        }
    </style>
</head>
<body>
    @include('partials.fond-carrousel')
    <main class="guest-card">
        <div class="guest-brand">
            <img src="{{ asset('assets/images/favicon-32x32.png') }}" alt="" aria-hidden="true">
            <strong>KalanNet</strong>
        </div>
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
