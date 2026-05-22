@php
    $user = Auth::user();
    $idEcole = session('idEcole');
    $anneeEnCours = \App\Models\AnneeScolaire::orderBy('id_anneeScolaire', 'desc')->first();
    $notifications = collect();
    $unreadNotificationsCount = 0;
    if ($user && \Illuminate\Support\Facades\Schema::hasTable('app_notifications')) {
        $notifications = $user->appNotifications()
            ->latest()
            ->limit(6)
            ->get();
        $unreadNotificationsCount = $user->appNotifications()
            ->whereNull('read_at')
            ->count();
    }
@endphp



<header class="top-header">
    <nav class="navbar navbar-expand gap-3">
        <div class="top-navbar-right ms-auto">
            <ul class="navbar-nav align-items-center">
                <!-- Academic Year Badge -->
                <li class="nav-item d-none d-sm-flex align-items-center me-3">
                    <div class="d-flex align-items-center px-3 py-1 border rounded-pill shadow-sm" style="border-color: rgba(128, 128, 128, 0.3) !important;">
                        <i class="bi bi-calendar3 me-2 opacity-75"></i> 
                        <span class="fw-bold fs-6">Année Scolaire: {{ $anneeEnCours ? $anneeEnCours->annee : 'N/A' }}</span>
                    </div>
                </li>


                <!-- Theme Selector Dropdown -->
                <li class="nav-item dropdown dropdown-large">
                    <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown" title="Changer de thème">
                        <div class="theme-switcher-btn">
                            <span class="theme-swatch swatch-bleu-sombre" id="current-theme-swatch"></span>
                            <i class="bi bi-palette-fill"></i>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width: 200px;">
                        <li><h6 class="dropdown-header text-uppercase fw-bold small mb-2">Thèmes Disponibles</h6></li>
                        <li><a class="dropdown-item theme-option d-flex align-items-center gap-2 rounded-2 mb-1 {{ ($user->theme_preference ?? 'bleu-sombre') == 'bleu-sombre' ? 'active-theme' : '' }}" href="javascript:;" onclick="changeTheme('bleu-sombre')" style="color: #3b82f6 !important;">
                            <span class="theme-swatch swatch-bleu-sombre"></span> Bleu Sombre (Défaut)
                        </a></li>
                        <li><a class="dropdown-item theme-option d-flex align-items-center gap-2 rounded-2 mb-1 {{ ($user->theme_preference ?? '') == 'light' ? 'active-theme' : '' }}" href="javascript:;" onclick="changeTheme('light')" style="color: #64748b !important;">
                            <span class="theme-swatch swatch-light"></span> Clair
                        </a></li>
                        <li><a class="dropdown-item theme-option d-flex align-items-center gap-2 rounded-2 mb-1 {{ ($user->theme_preference ?? '') == 'dark' ? 'active-theme' : '' }}" href="javascript:;" onclick="changeTheme('dark')" style="color: #831843 !important;">
                            <span class="theme-swatch swatch-dark"></span> Rose Sombre
                        </a></li>
                        <li><a class="dropdown-item theme-option d-flex align-items-center gap-2 rounded-2 mb-1 {{ ($user->theme_preference ?? '') == 'vert' ? 'active-theme' : '' }}" href="javascript:;" onclick="changeTheme('vert')" style="color: #16a34a !important;">
                            <span class="theme-swatch swatch-vert"></span> Nature (Vert)
                        </a></li>
                        <li><a class="dropdown-item theme-option d-flex align-items-center gap-2 rounded-2 mb-1 {{ ($user->theme_preference ?? '') == 'violet' ? 'active-theme' : '' }}" href="javascript:;" onclick="changeTheme('violet')" style="color: #7c3aed !important;">
                            <span class="theme-swatch swatch-violet"></span> Améthyste
                        </a></li>
                        <li><a class="dropdown-item theme-option d-flex align-items-center gap-2 rounded-2 mb-1 {{ ($user->theme_preference ?? '') == 'rouge' ? 'active-theme' : '' }}" href="javascript:;" onclick="changeTheme('rouge')" style="color: #dc2626 !important;">
                            <span class="theme-swatch swatch-rouge"></span> Bordeaux
                        </a></li>
                        <li><a class="dropdown-item theme-option d-flex align-items-center gap-2 rounded-2 mb-1 {{ ($user->theme_preference ?? '') == 'orange' ? 'active-theme' : '' }}" href="javascript:;" onclick="changeTheme('orange')" style="color: #ea580c !important;">
                            <span class="theme-swatch swatch-orange"></span> Crépuscule
                        </a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown dropdown-large">
                    <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown">
                        <div class="notifications">
                            <span class="notify-badge">{{ $unreadNotificationsCount }}</span>
                            <i class="bi bi-bell-fill"></i>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end p-0">
                        <div class="p-2 border-bottom m-2">
                            <h5 class="h5 mb-0">Notifications</h5>
                        </div>
                        <div class="header-notifications-list p-2">
                            @forelse($notifications as $notification)
                                <a href="{{ $notification->link ?: '#' }}" class="dropdown-item d-flex align-items-start gap-3 rounded-2 py-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 {{ $notification->read_at ? 'bg-light text-muted' : 'bg-primary bg-opacity-10 text-primary' }}" style="width: 36px; height: 36px;">
                                        <i class="bi bi-bell"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold small">{{ $notification->title }}</div>
                                        <div class="text-muted small">{{ \Illuminate\Support\Str::limit($notification->message, 90) }}</div>
                                        <div class="text-muted" style="font-size: 11px;">{{ optional($notification->created_at)->diffForHumans() }}</div>
                                    </div>
                                </a>
                            @empty
                                <div class="dropdown-item text-center text-secondary">Aucune notification</div>
                            @endforelse
                        </div>
                    </div>
                </li>
                <li class="nav-item dropdown dropdown-user-setting">
                    <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown">
                        <div class="user-setting d-flex align-items-center gap-3">
                            <img src="{{ asset('assets/images/avatars/avatar-1.png') }}" class="user-img" alt="">
                            <div class="d-none d-sm-block">
                                <p class="user-name mb-0">{{ $user->nomPrenom }}</p>
                                <small class="mb-0 dropdown-user-designation text-secondary">{{ $user->droit }}</small>
                            </div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="#">
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset('assets/images/avatars/avatar-1.png') }}" alt="" class="rounded-circle" width="54" height="54">
                                    <div class="ms-3">
                                        <h6 class="mb-0 dropdown-user-name">{{ $user->nomPrenom }}</h6>
                                        <small class="mb-0 dropdown-user-designation text-secondary">{{ $user->email }}</small>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <div class="d-flex align-items-center">
                                    <div class=""><i class="bi bi-person-fill"></i></div>
                                    <div class="ms-3"><span>Profil</span></div>
                                </div>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="px-3 py-1">
                                @csrf
                                <button type="submit" class="dropdown-item d-flex align-items-center rounded-2 py-2 text-center" style="background-color: var(--theme-accent) !important; color: var(--text-on-accent) !important; border: none; font-weight: 600; justify-content: center; gap: 8px;">
                                    <i class="bi bi-box-arrow-right fs-5"></i>
                                    <span>Déconnexion</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</header>

<script>
    function changeTheme(themeName) {
        // Update DOM
        document.documentElement.setAttribute('data-theme', themeName);
        document.documentElement.classList.remove('light-theme', 'dark-theme', 'minimal-theme', 'semi-dark');
        
        // Update current swatch in navbar
        const currentSwatch = document.getElementById('current-theme-swatch');
        currentSwatch.className = 'theme-swatch swatch-' + themeName;

        // Update active class in dropdown
        document.querySelectorAll('.theme-option').forEach(el => {
            el.classList.remove('active-theme');
            if (el.getAttribute('onclick').includes("'" + themeName + "'")) {
                el.classList.add('active-theme');
            }
        });

        // Save to backend
        fetch('{{ route('theme.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ theme: themeName })
        }).then(response => response.json())
          .then(data => {
              if (data.status === 'ok') {
                  location.reload();
              } else {
                  console.error('Erreur lors de la sauvegarde du thème');
              }
          });
    }

    // Initialize current swatch on load
    document.addEventListener('DOMContentLoaded', () => {
        const activeTheme = document.documentElement.getAttribute('data-theme') || 'bleu-sombre';
        const currentSwatch = document.getElementById('current-theme-swatch');
        if (currentSwatch) currentSwatch.className = 'theme-swatch swatch-' + activeTheme;
    });
</script>
