@php
    $currentLocale = $currentLocale ?? app()->getLocale();
    $currentDirection = $currentDirection ?? config("app.supported_locales.$currentLocale.dir", 'ltr');
@endphp
<!DOCTYPE html>
<html lang="{{ $currentLocale }}" dir="{{ $currentDirection }}" data-theme="vert">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.auth.login_title') }} - KalanNet</title>
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

    <script>
        (function () {
            const serverTheme = @json($selected_theme ?? null);
            const saved = serverTheme || localStorage.getItem('kalannet_theme') || 'vert';
            localStorage.setItem('kalannet_theme', saved);
            document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>

    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --theme-accent: #14532d;
            --theme-soft: rgba(20, 83, 45, .1);
            --theme-ring: rgba(20, 83, 45, .22);
            --theme-dark: #0f3d23;
        }
        [data-theme="bleu-sombre"] { --theme-accent: #001529; --theme-soft: rgba(0,21,41,.1);   --theme-ring: rgba(0,21,41,.22);   --theme-dark: #00101f; }
        [data-theme="light"]       { --theme-accent: #475569; --theme-soft: rgba(71,85,105,.1);  --theme-ring: rgba(71,85,105,.22);  --theme-dark: #334155; }
        [data-theme="vert"]        { --theme-accent: #14532d; --theme-soft: rgba(20,83,45,.1);   --theme-ring: rgba(20,83,45,.22);   --theme-dark: #0f3d23; }
        [data-theme="dark"]        { --theme-accent: #831843; --theme-soft: rgba(131,24,67,.1);  --theme-ring: rgba(131,24,67,.22);  --theme-dark: #641336; }
        [data-theme="rouge"]       { --theme-accent: #450a0a; --theme-soft: rgba(69,10,10,.1);   --theme-ring: rgba(69,10,10,.22);   --theme-dark: #320707; }
        [data-theme="violet"]      { --theme-accent: #2e1065; --theme-soft: rgba(46,16,101,.1);  --theme-ring: rgba(46,16,101,.22);  --theme-dark: #230c4d; }
        [data-theme="orange"]      { --theme-accent: #431407; --theme-soft: rgba(67,20,7,.1);    --theme-ring: rgba(67,20,7,.22);    --theme-dark: #321006; }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body { height: 100%; overflow: hidden; }

        body {
            font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif;
            color: #172033;
        }

        /* ══════════════════════════════════
           FULL-SCREEN CAROUSEL BACKGROUND
        ══════════════════════════════════ */
        .bg-carousel {
            position: fixed;
            inset: 0;
            z-index: 0;
        }

        .bg-slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            z-index: 1;
            transition: opacity 1.6s cubic-bezier(.4,0,.2,1);
            overflow: hidden;
        }
        .bg-slide.active {
            opacity: 1;
            z-index: 2;
        }

        .bg-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }
        .bg-slide.active img {
            animation: kenBurns 9s ease-in-out forwards;
        }
        @keyframes kenBurns {
            from { transform: scale(1)    translate(0,    0);    }
            to   { transform: scale(1.08) translate(-1%,  -.5%); }
        }

        /* Dark gradient overlay on every slide */
        .bg-slide::after {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg,
                    rgba(10,18,35,.55) 0%,
                    rgba(10,18,35,.2)  40%,
                    rgba(10,18,35,.45) 75%,
                    rgba(10,18,35,.72) 100%
                ),
                linear-gradient(105deg,
                    rgba(10,18,35,.35) 0%,
                    transparent        55%
                );
        }

        /* Slide caption — bottom-left area */
        .bg-caption {
            position: absolute;
            left: clamp(28px, 4vw, 64px);
            bottom: clamp(110px, 16vh, 180px);
            z-index: 10;
            max-width: min(420px, 42vw);
            pointer-events: none;
        }
        .bg-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--theme-accent);
            color: #fff;
            font-size: .72rem;
            font-weight: 700;
            padding: 5px 14px;
            border-radius: 999px;
            margin-bottom: 12px;
            letter-spacing: .3px;
            opacity: 0;
            transform: translateY(12px);
            transition: opacity .5s .35s, transform .5s .35s;
        }
        .bg-slide.active .bg-badge { opacity: 1; transform: translateY(0); }

        .bg-title {
            font-size: clamp(1.4rem, 2.2vw, 2rem);
            font-weight: 800;
            color: #fff;
            line-height: 1.22;
            margin-bottom: 10px;
            text-shadow: 0 3px 20px rgba(0,0,0,.5);
            opacity: 0;
            transform: translateY(16px);
            transition: opacity .55s .5s, transform .55s .5s;
        }
        .bg-slide.active .bg-title { opacity: 1; transform: translateY(0); }

        .bg-quote {
            font-size: clamp(.78rem, .9vw, .9rem);
            color: rgba(255,255,255,.8);
            font-style: italic;
            line-height: 1.6;
            opacity: 0;
            transform: translateY(12px);
            transition: opacity .55s .65s, transform .55s .65s;
        }
        .bg-slide.active .bg-quote { opacity: 1; transform: translateY(0); }

        /* ── Dot indicators ── */
        .bg-dots {
            position: fixed;
            left: clamp(28px, 4vw, 64px);
            bottom: clamp(72px, 9vh, 110px);
            z-index: 20;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .bg-dot {
            height: 5px;
            border-radius: 3px;
            background: rgba(255,255,255,.38);
            border: none;
            padding: 0;
            cursor: pointer;
            width: 5px;
            transition: width .4s ease, background .4s ease;
        }
        .bg-dot.active { width: 26px; background: #fff; }

        /* ── Progress bar ── */
        .bg-progress {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 3px;
            z-index: 20;
            background: rgba(255,255,255,.15);
        }
        .bg-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--theme-accent), rgba(255,255,255,.7));
            width: 0%;
        }

        /* ══════════════════════════════════
           PAGE LAYOUT (on top of bg)
        ══════════════════════════════════ */
        .login-page {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: clamp(16px,3vh,36px) clamp(16px,5vw,72px) clamp(60px,10vh,120px) clamp(16px,3vw,40px);
            overflow: auto;
        }

        [dir="rtl"] .login-page {
            justify-content: flex-start;
            padding: clamp(16px,3vh,36px) clamp(16px,3vw,40px) clamp(60px,10vh,120px) clamp(16px,5vw,72px);
        }

        /* ══════════════════════════════════
           WAVE + MODULE BAR (bottom)
        ══════════════════════════════════ */
        .module-wave {
            position: fixed;
            left: 0; right: 0; bottom: 0;
            height: clamp(72px, 11vh, 108px);
            z-index: 15;
            pointer-events: none;
        }
        .module-wave svg {
            position: absolute;
            inset: 0; width: 100%; height: 100%;
            overflow: visible;
        }
        .module-wave path {
            fill: var(--theme-accent);
            fill-opacity: .90;
        }
        .module-list {
            position: absolute;
            left: clamp(12px,2.5vw,48px);
            right: clamp(12px,2.5vw,48px);
            bottom: 10px;
            display: grid;
            grid-template-columns: repeat(12, minmax(0,1fr));
            gap: clamp(4px,.6vw,12px);
            align-items: center;
        }
        .module-node {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            color: #fff;
            font-size: clamp(.52rem,.62vw,.74rem);
            font-weight: 700;
            text-align: center;
            text-shadow: 0 1px 4px rgba(0,0,0,.3);
            line-height: 1.2;
        }
        .module-node i { font-size: clamp(.72rem,.82vw,.96rem); }

        /* ══════════════════════════════════
           LOGIN CARD
        ══════════════════════════════════ */
        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 32px 32px 26px;
            background: rgba(255,255,255,.93);
            backdrop-filter: blur(18px) saturate(1.4);
            -webkit-backdrop-filter: blur(18px) saturate(1.4);
            border: 1px solid rgba(255,255,255,.7);
            border-radius: 16px;
            box-shadow:
                0 32px 80px rgba(10,18,35,.28),
                0 0 0 1px rgba(255,255,255,.18) inset;
            position: relative;
            overflow: hidden;
        }
        .login-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--theme-accent), #2563eb 60%, var(--theme-accent));
            border-radius: 16px 16px 0 0;
        }

        /* Brand */
        .brand-area { text-align: center; margin-bottom: 20px; }
        .brand-logo {
            width: 82px; height: 74px;
            margin: 0 auto 12px;
            display: flex; align-items: center; justify-content: center;
        }
        .brand-logo svg { width: 82px; height: 74px; display: block; filter: drop-shadow(0 4px 10px rgba(15,23,42,.18)); }
        .brand-title { margin: 0; font-size: 1.9rem; font-weight: 800; color: #0f172a; letter-spacing: -.5px; line-height: 1; }
        .brand-title-kal { color: #16a34a; }
        .brand-title-an  { color: #eab308; }
        .brand-title-red { color: #dc2626; }
        .brand-subtitle    { margin: 7px 0 0; color: #64748b; font-size: .78rem; font-weight: 700; letter-spacing: .2px; }

        /* Language row */
        .login-language-row { display: flex; justify-content: center; margin: 0 0 14px; }
        .login-language-row .kalannet-language-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            min-height: 36px; border: 1px solid #dfe5ee; border-radius: 999px;
            background: #fff; color: var(--theme-accent); font-weight: 700;
            padding: 0 14px; font-size: .82rem;
        }

        /* Theme dots */
        .theme-row { display: flex; justify-content: center; gap: 8px; margin: 0 0 20px; }
        .theme-dot {
            width: 20px; height: 20px; border-radius: 50%;
            border: 2px solid #fff; outline: 1.5px solid #d1d5db;
            cursor: pointer; transition: outline-color .2s, box-shadow .2s;
        }
        .theme-dot.active { outline: 2px solid var(--theme-accent); box-shadow: 0 0 0 3.5px var(--theme-soft); }

        /* Divider */
        .form-divider {
            display: flex; align-items: center; gap: 10px; margin-bottom: 18px;
        }
        .form-divider::before, .form-divider::after {
            content: ""; flex: 1; height: 1px; background: #e5e7eb;
        }
        .form-divider span { font-size: .75rem; font-weight: 600; color: #94a3b8; white-space: nowrap; }

        /* Form */
        .form-label { font-size: .84rem; font-weight: 700; color: #334155; margin-bottom: 7px; }
        .input-group { flex-wrap: nowrap; }
        .input-group-text {
            width: 44px; justify-content: center;
            color: var(--theme-accent); background: #f1f5f9;
            border-color: #dfe5ee; border-radius: 8px 0 0 8px;
        }
        .form-control {
            min-height: 46px; border-color: #dfe5ee;
            border-radius: 0 8px 8px 0; color: #0f172a;
            font-weight: 500; min-width: 0; background: #fff;
        }
        .form-control::placeholder { font-size: .87rem; }
        .form-control:focus { border-color: var(--theme-accent); box-shadow: 0 0 0 .2rem var(--theme-ring); }
        .password-toggle {
            width: 44px; border-color: #dfe5ee; border-left: 0;
            border-radius: 0 8px 8px 0; color: var(--theme-accent); background: #fff;
        }
        .password-toggle:hover, .password-toggle:focus {
            color: #fff; background: var(--theme-accent); border-color: var(--theme-accent);
        }
        .password-input { border-radius: 0; }
        .input-group:focus-within .input-group-text { border-color: var(--theme-accent); }
        .input-group:focus-within .password-toggle  { border-color: var(--theme-accent); }

        .login-button {
            min-height: 48px; border-radius: 8px; border: 0;
            background: linear-gradient(135deg, var(--theme-accent) 0%, var(--theme-dark) 100%);
            color: #fff; font-weight: 800; font-size: .95rem;
            box-shadow: 0 8px 24px var(--theme-ring);
            transition: opacity .2s, transform .1s, box-shadow .2s;
        }
        .login-button:hover  { opacity: .92; box-shadow: 0 12px 32px var(--theme-ring); color: #fff; }
        .login-button:active { transform: scale(.98); }
        .login-button:focus  { color: #fff; }

        .login-footer { margin-top: 18px; text-align: center; color: #94a3b8; font-size: .78rem; }

        /* School modal */
        .school-card {
            background: #fff; border: 1px solid #dfe5ee; border-radius: 8px;
            transition: all .2s; margin-bottom: 10px; cursor: pointer;
        }
        .school-card:hover { border-color: var(--theme-accent); background: var(--theme-soft); }
        .school-logo { width: 48px; height: 48px; border-radius: 8px; overflow: hidden; background: #f1f5f9; }
        .modal-header { border-top: 4px solid var(--theme-accent); }

        /* RTL */
        [dir="rtl"] .input-group-text  { border-radius: 0 8px 8px 0; }
        [dir="rtl"] .form-control       { border-radius: 8px 0 0 8px; text-align: right; }
        [dir="rtl"] .password-toggle    { border-left: 1px solid #dfe5ee; border-right: 0; border-radius: 8px 0 0 8px; }

        /* ── Responsive ── */
        @media (max-width: 600px) {
            /* Allow scroll when virtual keyboard opens */
            html, body {
                height: auto;
                min-height: 100%;
                overflow-y: auto;
                overflow-x: hidden;
            }

            .login-page {
                align-items: flex-start;
                justify-content: center;
                padding: 20px 14px 148px;
                min-height: 100svh;
            }

            .login-card {
                padding: 22px 18px 20px;
                border-radius: 14px;
                max-width: 100%;
                width: 100%;
            }

            .brand-area { margin-bottom: 12px; }
            .brand-logo { width: 56px; height: 50px; margin-bottom: 6px; }
            .brand-logo svg { width: 56px; height: 50px; }
            .brand-title { font-size: 1.5rem; }
            .brand-subtitle { font-size: .7rem; }

            .theme-row { gap: 7px; margin-bottom: 14px; }
            .theme-dot { width: 26px; height: 26px; }

            .login-language-row { margin-bottom: 10px; }

            .bg-caption { display: none; }
            /* Hide nav dots — swipe is supported */
            .bg-dots { display: none; }

            /* Module bar: icons only in a single compact row */
            .module-wave { height: clamp(58px, 9vh, 72px); }
            .module-list {
                grid-template-columns: repeat(12, minmax(0, 1fr));
                bottom: 6px;
                gap: 2px;
            }
            .module-node { flex-direction: column; gap: 0; font-size: 0; }
            .module-node span { display: none; }
            .module-node i { font-size: .82rem; }
        }

        @media (max-width: 900px) {
            .module-node { font-size: .48rem; flex-direction: row; gap: 3px; }
            .module-node i { font-size: .68rem; }
        }
    </style>
</head>
<body>

{{-- ════ FULL-SCREEN CAROUSEL BACKGROUND ════ --}}
<div class="bg-carousel" id="bgCarousel" aria-hidden="true">

    <div class="bg-slide active" data-index="0">
        <img src="{{ asset('assets/images/fond1.png') }}" alt="" loading="eager">
        <div class="bg-caption">
            <span class="bg-badge"><i class="bi bi-mortarboard-fill me-1"></i>{{ __('messages.modules.students') }}</span>
            <h2 class="bg-title">{{ __('messages.auth.slide1_title') }}</h2>
            <p class="bg-quote">{{ __('messages.auth.slide1_quote') }}</p>
        </div>
    </div>

    <div class="bg-slide" data-index="1">
        <img src="{{ asset('assets/images/fond2.png') }}" alt="" loading="lazy">
        <div class="bg-caption">
            <span class="bg-badge"><i class="bi bi-clipboard-check me-1"></i>{{ __('messages.modules.attendance') }}</span>
            <h2 class="bg-title">{{ __('messages.auth.slide2_title') }}</h2>
            <p class="bg-quote">{{ __('messages.auth.slide2_quote') }}</p>
        </div>
    </div>

    <div class="bg-slide" data-index="2">
        <img src="{{ asset('assets/images/fond3.png') }}" alt="" loading="lazy">
        <div class="bg-caption">
            <span class="bg-badge"><i class="bi bi-file-earmark-text me-1"></i>{{ __('messages.modules.bulletins') }}</span>
            <h2 class="bg-title">{{ __('messages.auth.slide3_title') }}</h2>
            <p class="bg-quote">{{ __('messages.auth.slide3_quote') }}</p>
        </div>
    </div>

    <div class="bg-slide" data-index="3">
        <img src="{{ asset('assets/images/fond4.png') }}" alt="" loading="lazy">
        <div class="bg-caption">
            <span class="bg-badge"><i class="bi bi-cash-coin me-1"></i>{{ __('messages.modules.finances') }}</span>
            <h2 class="bg-title">{{ __('messages.auth.slide4_title') }}</h2>
            <p class="bg-quote">{{ __('messages.auth.slide4_quote') }}</p>
        </div>
    </div>

    <div class="bg-slide" data-index="4">
        <img src="{{ asset('assets/images/fond5.png') }}" alt="" loading="lazy">
        <div class="bg-caption">
            <span class="bg-badge"><i class="bi bi-calendar-week me-1"></i>{{ __('messages.modules.timetable') }}</span>
            <h2 class="bg-title">{{ __('messages.auth.slide5_title') }}</h2>
            <p class="bg-quote">{{ __('messages.auth.slide5_quote') }}</p>
        </div>
    </div>

    <div class="bg-slide" data-index="5">
        <img src="{{ asset('assets/images/fond6.png') }}" alt="" loading="lazy">
        <div class="bg-caption">
            <span class="bg-badge"><i class="bi bi-people-fill me-1"></i>{{ __('messages.modules.parents') }}</span>
            <h2 class="bg-title">{{ __('messages.auth.slide6_title') }}</h2>
            <p class="bg-quote">{{ __('messages.auth.slide6_quote') }}</p>
        </div>
    </div>

    <div class="bg-slide" data-index="6">
        <img src="{{ asset('assets/images/fond7.png') }}" alt="" loading="lazy">
        <div class="bg-caption">
            <span class="bg-badge"><i class="bi bi-pencil-square me-1"></i>{{ __('messages.modules.grades') }}</span>
            <h2 class="bg-title">{{ __('messages.auth.slide7_title') }}</h2>
            <p class="bg-quote">{{ __('messages.auth.slide7_quote') }}</p>
        </div>
    </div>
</div>

{{-- Progress bar --}}
<div class="bg-progress" aria-hidden="true">
    <div class="bg-progress-bar" id="bgProgressBar"></div>
</div>

{{-- Dot indicators --}}
<div class="bg-dots" aria-hidden="true">
    <button class="bg-dot active" data-slide="0"></button>
    <button class="bg-dot" data-slide="1"></button>
    <button class="bg-dot" data-slide="2"></button>
    <button class="bg-dot" data-slide="3"></button>
    <button class="bg-dot" data-slide="4"></button>
    <button class="bg-dot" data-slide="5"></button>
    <button class="bg-dot" data-slide="6"></button>
</div>

{{-- ════ WAVE + MODULE BAR ════ --}}
<div class="module-wave" aria-hidden="true">
    <svg viewBox="0 0 900 100" preserveAspectRatio="none">
        <path d="M900 38 C790 52 755 88 630 76 C500 64 450 18 330 38 C205 60 175 90 0 68 L0 100 L900 100 Z" />
    </svg>
    <div class="module-list">
        <div class="module-node"><i class="bi bi-people"></i><span>{{ __('messages.modules.students') }}</span></div>
        <div class="module-node"><i class="bi bi-person-plus"></i><span>{{ __('messages.modules.registrations') }}</span></div>
        <div class="module-node"><i class="bi bi-people-fill"></i><span>{{ __('messages.modules.parents') }}</span></div>
        <div class="module-node"><i class="bi bi-person-badge"></i><span>{{ __('messages.modules.teachers') }}</span></div>
        <div class="module-node"><i class="bi bi-clipboard-check"></i><span>{{ __('messages.modules.attendance') }}</span></div>
        <div class="module-node"><i class="bi bi-journal-text"></i><span>{{ __('messages.modules.emargements') }}</span></div>
        <div class="module-node"><i class="bi bi-pencil-square"></i><span>{{ __('messages.modules.grades') }}</span></div>
        <div class="module-node"><i class="bi bi-file-earmark-text"></i><span>{{ __('messages.modules.bulletins') }}</span></div>
        <div class="module-node"><i class="bi bi-cash-coin"></i><span>{{ __('messages.modules.payments') }}</span></div>
        <div class="module-node"><i class="bi bi-wallet2"></i><span>{{ __('messages.modules.finances') }}</span></div>
        <div class="module-node"><i class="bi bi-calendar-week"></i><span>{{ __('messages.modules.timetable') }}</span></div>
        <div class="module-node"><i class="bi bi-gear"></i><span>{{ __('messages.modules.configuration') }}</span></div>
    </div>
</div>

{{-- ════ LOGIN CARD (floating) ════ --}}
<main class="login-page">
    <section class="login-card">

        <div class="brand-area">
            <div class="brand-logo" aria-hidden="true">
                <svg viewBox="0 0 120 104" role="img" focusable="false">
                    <path d="M25 76c14-7 28-7 42 0V42c-14-7-28-7-42 0v34z" fill="#ffffff" stroke="#0b1f3a" stroke-width="3" stroke-linejoin="round"/>
                    <path d="M95 76c-14-7-28-7-42 0V42c14-7 28-7 42 0v34z" fill="#f8fafc" stroke="#0b1f3a" stroke-width="3" stroke-linejoin="round"/>
                    <path d="M60 47v34" stroke="#d4af37" stroke-width="4" stroke-linecap="round"/>
                    <path d="M22 79c15-8 31-8 45 0M98 79c-15-8-31-8-45 0" stroke="#d4af37" stroke-width="4" stroke-linecap="round" fill="none"/>
                    <path d="M35 30l25-11 25 11-25 11-25-11z" fill="#0b1f3a"/>
                    <path d="M46 36v11c8 6 20 6 28 0V36l-14 6-14-6z" fill="#d4af37"/>
                    <path d="M84 31v17" stroke="#0b1f3a" stroke-width="3" stroke-linecap="round"/>
                    <circle cx="84" cy="52" r="3.5" fill="#d4af37"/>
                    <circle cx="60" cy="55" r="9" fill="#0b1f3a"/>
                    <path d="M49 72c5-11 17-11 22 0" fill="#0b1f3a"/>
                    <g transform="translate(10 10) scale(.78)">
                        <path d="M16 7c9 2 15 8 15 17 0 5 4 9 7 13-5 7-12 11-21 9-8-2-12-10-10-19 1-6 4-13 9-20z" fill="#ffffff" stroke="#0b1f3a" stroke-width="2"/>
                        <clipPath id="mali-flag-clip">
                            <path d="M16 7c9 2 15 8 15 17 0 5 4 9 7 13-5 7-12 11-21 9-8-2-12-10-10-19 1-6 4-13 9-20z"/>
                        </clipPath>
                        <g clip-path="url(#mali-flag-clip)">
                            <rect x="5"  y="5" width="11" height="44" fill="#14b53a"/>
                            <rect x="16" y="5" width="11" height="44" fill="#fcd116"/>
                            <rect x="27" y="5" width="13" height="44" fill="#ce1126"/>
                        </g>
                        <path d="M16 7c9 2 15 8 15 17 0 5 4 9 7 13-5 7-12 11-21 9-8-2-12-10-10-19 1-6 4-13 9-20z" fill="none" stroke="#0b1f3a" stroke-width="2"/>
                    </g>
                    <path d="M88 16c9 8 13 21 9 35M93 17c-6 4-11 10-14 19M97 51c-8-4-16-5-25-2" stroke="#0b1f3a" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                    <circle cx="88" cy="16" r="3" fill="#d4af37"/>
                    <circle cx="79" cy="36" r="3" fill="#d4af37"/>
                    <circle cx="72" cy="49" r="3" fill="#d4af37"/>
                    <circle cx="97" cy="51" r="3" fill="#d4af37"/>
                </svg>
            </div>
            <h1 class="brand-title"><span class="brand-title-kal">Kal</span><span class="brand-title-an">an</span><span class="brand-title-red">Net</span></h1>
            <p class="brand-subtitle">{{ __('messages.auth.subtitle') }}</p>
        </div>

        <div class="login-language-row">
            @include('partials.language-switcher', [
                'switcherId'    => 'loginLanguageSelector',
                'switcherClass' => 'kalannet-language-btn',
                'showLabel'     => true,
                'menuClass'     => 'dropdown-menu-end',
            ])
        </div>

        <div class="theme-row" aria-label="Choix du thème">
            <button type="button" class="theme-dot" data-theme-key="vert"        style="background:#14532d" title="Vert"></button>
            <button type="button" class="theme-dot" data-theme-key="bleu-sombre" style="background:#001529" title="Bleu sombre"></button>
            <button type="button" class="theme-dot" data-theme-key="dark"        style="background:#831843" title="Rose sombre"></button>
            <button type="button" class="theme-dot" data-theme-key="violet"      style="background:#2e1065" title="Violet"></button>
            <button type="button" class="theme-dot" data-theme-key="rouge"       style="background:#450a0a" title="Rouge"></button>
            <button type="button" class="theme-dot" data-theme-key="orange"      style="background:#431407" title="Orange"></button>
            <button type="button" class="theme-dot" data-theme-key="light"       style="background:#475569" title="Clair"></button>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger border-0 rounded-3 py-2 mb-3">
                <ul class="mb-0 list-unstyled small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger border-0 rounded-3 py-2 small mb-3">{{ session('error') }}</div>
        @endif

        <div class="form-divider"><span>{{ __('messages.auth.login') }}</span></div>

        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <input type="hidden" name="theme_preference" id="theme_preference" value="{{ old('theme_preference', $selected_theme ?? 'vert') }}">

            <div class="mb-3">
                <label class="form-label" for="identifier">{{ __('messages.auth.identifier') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                    <input id="identifier" type="text" name="identifier" class="form-control"
                           placeholder="{{ __('messages.auth.identifier_placeholder') }}"
                           value="{{ old('identifier') }}" required autofocus autocomplete="username">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" for="pwd">{{ __('messages.auth.password') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input id="pwd" type="password" name="pwd" class="form-control password-input"
                           placeholder="{{ __('messages.auth.password_placeholder') }}" required>
                    <button class="btn password-toggle" type="button" id="toggle-password"
                            aria-label="{{ __('messages.auth.show_password') }}">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn login-button" id="login-submit-button">
                    <i class="bi bi-box-arrow-in-right me-2"></i>{{ __('messages.auth.login') }}
                </button>
            </div>
        </form>

        <div class="login-footer">&copy; {{ date('Y') }} <span style="color:#16a34a;font-weight:800;">Kal</span><span style="color:#eab308;font-weight:800;">an</span><span style="color:#dc2626;font-weight:800;">Net</span></div>
    </section>
</main>

{{-- School modal --}}
@if(isset($ecoles_modal))
    <div class="modal fade" id="schoolModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-3 shadow">
                <div class="modal-header pb-2">
                    <h5 class="modal-title fw-bold">{{ __('messages.auth.choose_school') }}</h5>
                </div>
                <div class="modal-body">
                    @foreach($ecoles_modal as $school)
                        <form action="{{ route('login.select-school') }}" method="POST">
                            @csrf
                            <input type="hidden" name="idUtilisateur" value="{{ $school->idUtilisateur }}">
                            <input type="hidden" name="idEcole" value="{{ $school->idEcole }}">
                            <input type="hidden" name="theme_preference" class="school-theme-preference" value="{{ $selected_theme ?? 'vert' }}">
                            <button type="submit" class="btn w-100 p-3 school-card text-start">
                                <div class="d-flex align-items-center">
                                    <div class="school-logo me-3 d-flex align-items-center justify-content-center text-muted fw-bold">
                                        @if($school->ecole && $school->ecole->logoEcole)
                                            <img src="{{ asset($school->ecole->logoEcole) }}" class="w-100 h-100 object-fit-cover" alt="">
                                        @else
                                            <i class="bi bi-building"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $school->ecole ? $school->ecole->nomEcole : 'N/A' }}</div>
                                        <small class="text-muted">{{ $school->ecole ? $school->ecole->typeEcole : 'N/A' }}</small>
                                    </div>
                                </div>
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif

<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/mon_js/save-button-spinner.js') }}?v={{ filemtime(public_path('assets/mon_js/save-button-spinner.js')) }}"></script>
@include('partials.localized-inputs-script')

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ── THEME ── */
    const dots = document.querySelectorAll('.theme-dot');
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('kalannet_theme', theme);
        document.getElementById('theme_preference').value = theme;
        document.querySelectorAll('.school-theme-preference').forEach(i => i.value = theme);
        dots.forEach(d => d.classList.toggle('active', d.dataset.themeKey === theme));
    }
    applyTheme(@json($selected_theme ?? null) || localStorage.getItem('kalannet_theme') || 'vert');
    dots.forEach(d => d.addEventListener('click', () => applyTheme(d.dataset.themeKey)));

    /* ── PASSWORD TOGGLE ── */
    const pwdInput  = document.getElementById('pwd');
    const pwdToggle = document.getElementById('toggle-password');
    const pwdIcon   = pwdToggle.querySelector('i');
    pwdToggle.addEventListener('click', function () {
        const visible = pwdInput.type === 'text';
        pwdInput.type  = visible ? 'password' : 'text';
        pwdIcon.className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
    });

    /* ── BACKGROUND CAROUSEL ── */
    const slides   = document.querySelectorAll('.bg-slide');
    const dotBtns  = document.querySelectorAll('.bg-dot');
    const progBar  = document.getElementById('bgProgressBar');
    const DURATION = 6000;
    let current = 0;
    let timer   = null;

    function goTo(idx) {
        slides[current].classList.remove('active');
        dotBtns[current].classList.remove('active');
        current = (idx + slides.length) % slides.length;
        slides[current].classList.add('active');
        dotBtns[current].classList.add('active');
        animateProgress();
    }

    function animateProgress() {
        progBar.style.transition = 'none';
        progBar.style.width = '0%';
        requestAnimationFrame(() => requestAnimationFrame(() => {
            progBar.style.transition = `width ${DURATION}ms linear`;
            progBar.style.width = '100%';
        }));
    }

    function startTimer() {
        clearInterval(timer);
        timer = setInterval(() => goTo(current + 1), DURATION);
    }

    dotBtns.forEach((btn, i) => btn.addEventListener('click', () => { goTo(i); startTimer(); }));

    /* Swipe support */
    let touchX = 0;
    document.addEventListener('touchstart', e => { touchX = e.touches[0].clientX; }, { passive: true });
    document.addEventListener('touchend', e => {
        const diff = touchX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 50) { goTo(diff > 0 ? current + 1 : current - 1); startTimer(); }
    });

    animateProgress();
    startTimer();

    /* ── SCHOOL MODAL ── */
    @if(isset($ecoles_modal))
    new bootstrap.Modal(document.getElementById('schoolModal')).show();
    @endif

    /* ── LOGIN THROTTLE COUNTDOWN ── */
    @if (session('login_retry_after'))
    (function () {
        let remaining = {{ (int) session('login_retry_after') }};
        const button = document.getElementById('login-submit-button');
        if (!button || remaining <= 0) return;

        const originalHTML = button.innerHTML;
        button.disabled = true;
        button.classList.add('disabled');

        const retryTemplate = @json(__('messages.auth.retry_in'));
        function render() {
            button.textContent = retryTemplate.replace(':seconds', remaining);
        }
        render();

        const interval = setInterval(function () {
            remaining--;
            if (remaining <= 0) {
                clearInterval(interval);
                button.disabled = false;
                button.classList.remove('disabled');
                button.innerHTML = originalHTML;
                return;
            }
            render();
        }, 1000);
    })();
    @endif
});
</script>
</body>
</html>
