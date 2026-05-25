<!DOCTYPE html>
<html lang="fr" data-theme="vert">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - KalanNet</title>

    <script>
        (function () {
            const serverTheme = @json($selected_theme ?? null);
            const saved = serverTheme || localStorage.getItem('kalannet_theme') || 'vert';
            localStorage.setItem('kalannet_theme', saved);
            document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>

    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --theme-accent: #14532d;
            --theme-soft: rgba(20, 83, 45, .1);
            --theme-ring: rgba(20, 83, 45, .22);
            --theme-dark: #0f3d23;
        }
        [data-theme="bleu-sombre"] { --theme-accent: #001529; --theme-soft: rgba(0, 21, 41, .1); --theme-ring: rgba(0, 21, 41, .22); --theme-dark: #00101f; }
        [data-theme="light"] { --theme-accent: #475569; --theme-soft: rgba(71, 85, 105, .1); --theme-ring: rgba(71, 85, 105, .22); --theme-dark: #334155; }
        [data-theme="vert"] { --theme-accent: #14532d; --theme-soft: rgba(20, 83, 45, .1); --theme-ring: rgba(20, 83, 45, .22); --theme-dark: #0f3d23; }
        [data-theme="dark"] { --theme-accent: #831843; --theme-soft: rgba(131, 24, 67, .1); --theme-ring: rgba(131, 24, 67, .22); --theme-dark: #641336; }
        [data-theme="rouge"] { --theme-accent: #450a0a; --theme-soft: rgba(69, 10, 10, .1); --theme-ring: rgba(69, 10, 10, .22); --theme-dark: #320707; }
        [data-theme="violet"] { --theme-accent: #2e1065; --theme-soft: rgba(46, 16, 101, .1); --theme-ring: rgba(46, 16, 101, .22); --theme-dark: #230c4d; }
        [data-theme="orange"] { --theme-accent: #431407; --theme-soft: rgba(67, 20, 7, .1); --theme-ring: rgba(67, 20, 7, .22); --theme-dark: #321006; }

        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #172033;
            background:
                linear-gradient(120deg, rgba(255,255,255,.52), rgba(255,255,255,.28)),
                url('{{ asset('assets/images/logo_kalanNet.jpeg') }}') center/cover no-repeat fixed;
        }
        .login-page {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: flex-end;
            justify-content: flex-end;
            padding: 22px clamp(22px, 8vw, 120px) clamp(44px, 9vh, 92px) 22px;
            overflow: hidden;
        }
        .module-wave {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: clamp(285px, 38vh, 380px);
            z-index: 1;
            pointer-events: none;
        }
        .module-wave svg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            overflow: visible;
        }
        .module-wave path {
            fill: var(--theme-accent);
            fill-opacity: .88;
            stroke: var(--theme-accent);
            stroke-width: 5;
            stroke-linecap: round;
            filter: drop-shadow(0 10px 18px rgba(15, 23, 42, .2));
        }
        .module-node {
            display: inline-flex;
            flex-direction: row;
            align-items: center;
            gap: 5px;
            justify-content: center;
            min-width: 0;
            min-height: 30px;
            padding: 0;
            color: #fff;
            font-size: clamp(.58rem, .7vw, .78rem);
            font-weight: 800;
            line-height: 1.15;
            text-align: center;
            text-shadow: 0 2px 8px rgba(0, 0, 0, .28);
        }
        .module-node i {
            color: #fff;
            flex: 0 0 auto;
            font-size: clamp(.78rem, .85vw, 1rem);
        }
        .module-list {
            position: absolute;
            left: clamp(18px, 3vw, 54px);
            right: clamp(18px, 3vw, 54px);
            bottom: 24px;
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: clamp(5px, .8vw, 14px);
            align-items: center;
        }
        .login-card {
            position: relative;
            z-index: 2;
            width: min(100%, 430px);
            padding: 34px 34px 28px;
            background: rgba(255,255,255,.92);
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .18);
            overflow: hidden;
        }
        .login-card::before {
            content: "";
            position: absolute;
            inset: 0 0 0 auto;
            width: 100%;
            border-top: 5px solid var(--theme-accent);
            border-right: 5px solid var(--theme-accent);
            border-bottom: 5px solid var(--theme-accent);
            border-left: 0;
            border-radius: 0 8px 8px 0;
            pointer-events: none;
        }
        .login-card::after {
            content: "";
            position: absolute;
            right: 18px;
            bottom: 18px;
            width: 72px;
            height: 72px;
            border-right: 2px solid var(--theme-accent);
            border-bottom: 2px solid var(--theme-accent);
            border-radius: 0 0 8px 0;
            opacity: .18;
            pointer-events: none;
        }
        .login-content {
            position: relative;
            z-index: 1;
        }
        .brand-area {
            text-align: center;
            margin-bottom: 24px;
        }
        .brand-logo {
            width: 96px;
            height: 86px;
            margin: 0 auto 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .brand-logo svg {
            width: 96px;
            height: 86px;
            display: block;
            filter: drop-shadow(0 5px 8px rgba(15, 23, 42, .16));
        }
        .brand-title {
            margin: 0;
            font-size: 2rem;
            line-height: 1;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0;
        }
        .brand-title-theme {
            color: var(--theme-accent);
        }
        .brand-title-alt {
            color: #2563eb;
        }
        .brand-subtitle {
            margin: 8px 0 0;
            color: #64748b;
            font-size: .82rem;
            font-weight: 800;
            letter-spacing: 0;
        }
        .theme-row {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 18px 0 24px;
        }
        .theme-dot {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 2px solid #fff;
            outline: 1px solid #d1d5db;
            cursor: pointer;
        }
        .theme-dot.active {
            outline: 2px solid var(--theme-accent);
            box-shadow: 0 0 0 4px var(--theme-soft);
        }
        .form-label {
            font-size: .86rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 8px;
        }
        .input-group-text {
            width: 46px;
            justify-content: center;
            color: var(--theme-accent);
            background: #f8fafc;
            border-color: #dfe5ee;
            border-radius: 8px 0 0 8px;
        }
        .form-control {
            min-height: 48px;
            border-color: #dfe5ee;
            border-radius: 0 8px 8px 0;
            color: #0f172a;
            font-weight: 500;
        }
        .form-control:focus {
            border-color: var(--theme-accent);
            box-shadow: 0 0 0 .22rem var(--theme-ring);
        }
        .password-toggle {
            width: 46px;
            border-color: #dfe5ee;
            border-left: 0;
            border-radius: 0 8px 8px 0;
            color: var(--theme-accent);
            background: #fff;
        }
        .password-toggle:hover,
        .password-toggle:focus {
            color: #fff;
            background: var(--theme-accent);
            border-color: var(--theme-accent);
        }
        .password-input {
            border-radius: 0;
        }
        .input-group:focus-within .input-group-text {
            border-color: var(--theme-accent);
        }
        .input-group:focus-within .password-toggle {
            border-color: var(--theme-accent);
        }
        .login-button {
            min-height: 50px;
            border-radius: 8px;
            border: 0;
            background: var(--theme-accent);
            color: #fff;
            font-weight: 800;
            box-shadow: 0 16px 30px var(--theme-ring);
        }
        .login-button:hover,
        .login-button:focus {
            background: var(--theme-dark);
            color: #fff;
        }
        .login-footer {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #edf2f7;
            text-align: center;
            color: #94a3b8;
            font-size: .82rem;
        }
        .school-card {
            background-color: #ffffff;
            border: 1px solid #dfe5ee;
            border-radius: 8px;
            transition: all .2s ease;
            margin-bottom: 12px;
            cursor: pointer;
        }
        .school-card:hover {
            border-color: var(--theme-accent);
            background-color: var(--theme-soft);
        }
        .school-logo {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            overflow: hidden;
            background: #f1f5f9;
        }
        .modal-header {
            border-top: 5px solid var(--theme-accent);
        }
        @media (max-width: 520px) {
            .login-page {
                align-items: center;
                justify-content: center;
                padding: 14px;
            }
            .module-wave { display: none; }
            .login-card { padding: 28px 20px 24px; }
            .brand-title { font-size: 1.75rem; }
        }
        @media (max-width: 1100px) {
            .module-list {
                left: 18px;
                right: 18px;
                grid-template-columns: repeat(12, minmax(0, 1fr));
                gap: 4px;
            }
            .module-node {
                flex-direction: column;
                gap: 2px;
                font-size: .48rem;
            }
            .module-node i { font-size: .72rem; }
        }
    </style>
</head>
<body>
    <main class="login-page">
        <div class="module-wave" aria-hidden="true">
            <svg viewBox="0 0 900 250" preserveAspectRatio="none">
                <path d="M900 155 C790 168 755 218 630 205 C500 192 450 122 330 150 C205 180 175 232 0 210 L0 250 L900 250 Z" />
            </svg>
            <div class="module-list">
                <div class="module-node"><i class="bi bi-people"></i><span>Élèves</span></div>
                <div class="module-node"><i class="bi bi-person-plus"></i><span>Inscriptions</span></div>
                <div class="module-node"><i class="bi bi-people-fill"></i><span>Parents</span></div>
                <div class="module-node"><i class="bi bi-person-badge"></i><span>Enseignants</span></div>
                <div class="module-node"><i class="bi bi-clipboard-check"></i><span>Présences</span></div>
                <div class="module-node"><i class="bi bi-journal-text"></i><span>Émargements</span></div>
                <div class="module-node"><i class="bi bi-pencil-square"></i><span>Notes</span></div>
                <div class="module-node"><i class="bi bi-file-earmark-text"></i><span>Bulletins</span></div>
                <div class="module-node"><i class="bi bi-cash-coin"></i><span>Paiements</span></div>
                <div class="module-node"><i class="bi bi-wallet2"></i><span>Finances</span></div>
                <div class="module-node"><i class="bi bi-calendar-week"></i><span>Emploi du temps</span></div>
                <div class="module-node"><i class="bi bi-gear"></i><span>Configuration</span></div>
            </div>
        </div>
        <section class="login-card">
            <div class="login-content">
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
                                    <rect x="5" y="5" width="11" height="44" fill="#14b53a"/>
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
                    <h1 class="brand-title"><span class="brand-title-theme">Kalan</span><span class="brand-title-alt">Net</span></h1>
                    <p class="brand-subtitle">SYSTEME DE GESTION SCOLAIRE</p>
                </div>

                <div class="theme-row" aria-label="Choix du thème">
                    <button type="button" class="theme-dot" data-theme-key="vert" style="background:#14532d" title="Vert"></button>
                    <button type="button" class="theme-dot" data-theme-key="bleu-sombre" style="background:#001529" title="Bleu sombre"></button>
                    <button type="button" class="theme-dot" data-theme-key="dark" style="background:#831843" title="Rose sombre"></button>
                    <button type="button" class="theme-dot" data-theme-key="violet" style="background:#2e1065" title="Violet"></button>
                    <button type="button" class="theme-dot" data-theme-key="rouge" style="background:#450a0a" title="Rouge"></button>
                    <button type="button" class="theme-dot" data-theme-key="orange" style="background:#431407" title="Orange"></button>
                    <button type="button" class="theme-dot" data-theme-key="light" style="background:#475569" title="Clair"></button>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger border-0 rounded-3 py-2">
                        <ul class="mb-0 list-unstyled small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger border-0 rounded-3 py-2 small">
                        {{ session('error') }}
                    </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST">
                    @csrf
                    <input type="hidden" name="theme_preference" id="theme_preference" value="{{ old('theme_preference', $selected_theme ?? 'vert') }}">
                    <div class="mb-3">
                        <label class="form-label" for="identifier">Email ou téléphone</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                            <input id="identifier" type="text" name="identifier" class="form-control" placeholder="exemple@ecole.com ou 70000000" value="{{ old('identifier') }}" required autofocus autocomplete="username">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="pwd">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input id="pwd" type="password" name="pwd" class="form-control password-input" placeholder="Votre mot de passe" required>
                            <button class="btn password-toggle" type="button" id="toggle-password" aria-label="Afficher le mot de passe">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn login-button">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Connexion
                        </button>
                    </div>
                </form>

                <div class="login-footer">
                    &copy; {{ date('Y') }} KalanNet
                </div>
            </div>
        </section>
    </main>

    @if(isset($ecoles_modal))
        <div class="modal fade" id="schoolModal" data-bs-backdrop="static" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-3 shadow">
                    <div class="modal-header pb-2">
                        <h5 class="modal-title fw-bold">Choisir un établissement</h5>
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

        <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = new bootstrap.Modal(document.getElementById('schoolModal'));
                modal.show();
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dots = document.querySelectorAll('.theme-dot');

            function applyTheme(theme) {
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('kalannet_theme', theme);
                document.getElementById('theme_preference').value = theme;
                document.querySelectorAll('.school-theme-preference').forEach(input => {
                    input.value = theme;
                });
                dots.forEach(dot => dot.classList.toggle('active', dot.dataset.themeKey === theme));
            }

            applyTheme(@json($selected_theme ?? null) || localStorage.getItem('kalannet_theme') || 'vert');

            dots.forEach(dot => {
                dot.addEventListener('click', () => applyTheme(dot.dataset.themeKey));
            });

            const passwordInput = document.getElementById('pwd');
            const togglePassword = document.getElementById('toggle-password');
            const toggleIcon = togglePassword.querySelector('i');

            togglePassword.addEventListener('click', function () {
                const visible = passwordInput.type === 'text';
                passwordInput.type = visible ? 'password' : 'text';
                toggleIcon.className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
                togglePassword.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
            });
        });
    </script>
</body>
</html>
