@php
    $user = Auth::user();
    $idEcole = session('idEcole');
    $anneeEnCours = \App\Models\AnneeScolaire::orderBy('id_anneeScolaire', 'desc')->first();
    $notifications = collect();
    $unreadNotificationsCount = 0;
    $subscriptionReminderState = null;
    if ($user && \Illuminate\Support\Facades\Schema::hasTable('app_notifications')) {
        if ($idEcole && $user->droit !== 'SupAdmin') {
            $subscription = \App\Models\Abonnement::with('offre')
                ->where('ecole_id', $idEcole)
                ->orderByRaw("CASE WHEN statut = 'actif' THEN 0 ELSE 1 END")
                ->orderByDesc('fin_at')
                ->orderByDesc('id')
                ->first();

            $daysRemaining = $subscription?->fin_at
                ? now()->startOfDay()->diffInDays($subscription->fin_at->copy()->startOfDay(), false)
                : -1;

            if (!$subscription || $daysRemaining <= 7) {
                $message = !$subscription || $daysRemaining < 0
                    ? "Votre abonnement a expiré. Veuillez renouveler l'abonnement pour continuer."
                    : "Votre abonnement expire bientôt ({$daysRemaining} jour(s) restant(s)).";

                $subscriptionReminderState = [
                    'message' => $message,
                    'days_remaining' => $daysRemaining,
                    'blocked' => !$subscription || $daysRemaining < 0,
                    'show_modal' => !session()->has('subscription_reminder_seen_'.$idEcole.'_'.now()->format('Ymd')),
                ];

                if ($subscriptionReminderState['show_modal']) {
                    session(['subscription_reminder_seen_'.$idEcole.'_'.now()->format('Ymd') => true]);
                }

                $alreadyNotified = $user->appNotifications()
                    ->whereNull('read_at')
                    ->where('type', 'abonnement')
                    ->where('title', 'Avertissement abonnement')
                    ->exists();

                if (!$alreadyNotified) {
                    \App\Models\AppNotification::create([
                        'user_id' => $user->idUtilisateur,
                        'type' => 'abonnement',
                        'title' => 'Avertissement abonnement',
                        'message' => $message,
                        'link' => route('abonnements.index'),
                        'data' => [
                            'event' => $subscriptionReminderState['blocked'] ? 'SUBSCRIPTION_BLOCKED' : 'SUBSCRIPTION_REMINDER',
                            'ecole_id' => $idEcole,
                            'days_remaining' => $daysRemaining,
                        ],
                    ]);
                }
            }
        }

        $notifications = $user->appNotifications()
            ->whereNull('read_at')
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
                    <div class="dropdown-menu dropdown-menu-end p-0" style="width: 350px; max-width: 100vw;">
                        <div class="p-2 border-bottom m-2">
                            <h5 class="h5 mb-0">Notifications</h5>
                        </div>
                        <div class="header-notifications-list p-2" style="max-height: 400px; overflow-y: auto;">
                            @forelse($notifications as $notification)
                                <a href="{{ $notification->link ?: '#' }}" class="dropdown-item d-flex align-items-start gap-3 rounded-2 py-2 text-wrap notification-item" data-id="{{ $notification->id }}">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 bg-primary bg-opacity-10 text-primary" style="width: 36px; height: 36px;">
                                        <i class="bi bi-bell"></i>
                                    </div>
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <div class="fw-bold small">{{ $notification->title }}</div>
                                        <div class="text-muted small" style="word-break: break-word;">{{ \Illuminate\Support\Str::limit($notification->message, 150) }}</div>
                                        <div class="text-muted mt-1" style="font-size: 11px;">{{ optional($notification->created_at)->diffForHumans() }}</div>
                                    </div>
                                    <button type="button" class="btn-close btn-close-sm mt-1 mark-read-btn" aria-label="Marquer comme lu" title="Marquer comme lu" style="font-size: 10px;"></button>
                                </a>
                            @empty
                                <div class="dropdown-item text-center text-secondary empty-notifications">Aucune notification</div>
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

@if($subscriptionReminderState && $subscriptionReminderState['show_modal'])
    <div class="modal fade" id="subscriptionReminderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header {{ $subscriptionReminderState['blocked'] ? 'bg-danger' : 'bg-warning' }} text-white">
                    <h5 class="modal-title fw-bold">Avertissement abonnement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="fw-bold mb-0">{{ $subscriptionReminderState['message'] }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Plus tard</button>
                    <a href="{{ route('abonnements.index') }}" class="btn btn-primary">Renouveler</a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalEl = document.getElementById('subscriptionReminderModal');
                if (modalEl && window.bootstrap) {
                    new bootstrap.Modal(modalEl).show();
                }
            });
        </script>
    @endpush
@endif

<script>
    function changeTheme(themeName) {
        // Update DOM
        document.documentElement.setAttribute('data-theme', themeName);
        document.documentElement.classList.remove('light-theme', 'dark-theme', 'minimal-theme', 'semi-dark');
        localStorage.setItem('kalannet_theme', themeName);
        
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

        // Notification reading logic
        const notificationItems = document.querySelectorAll('.notification-item');
        const notifyBadge = document.querySelector('.notify-badge');
        
        function markAsRead(item, id, redirectUrl = null) {
            fetch(`/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      item.style.transition = 'opacity 0.3s ease';
                      item.style.opacity = '0';
                      setTimeout(() => {
                          item.remove();
                          // Update badge
                          if (notifyBadge) {
                              let count = parseInt(notifyBadge.innerText) || 0;
                              count = Math.max(0, count - 1);
                              notifyBadge.innerText = count;
                              if (count === 0) {
                                  notifyBadge.style.display = 'none';
                                  const list = document.querySelector('.header-notifications-list');
                                  if (list) {
                                      list.innerHTML = '<div class="dropdown-item text-center text-secondary empty-notifications">Aucune notification</div>';
                                  }
                              }
                          }
                          if (redirectUrl && redirectUrl !== '#' && redirectUrl !== window.location.href) {
                              window.location.href = redirectUrl;
                          }
                      }, 300);
                  }
              }).catch(err => {
                  if (redirectUrl && redirectUrl !== '#' && redirectUrl !== window.location.href) {
                      window.location.href = redirectUrl;
                  }
              });
        }

        notificationItems.forEach(item => {
            const btnClose = item.querySelector('.mark-read-btn');
            
            // If they click the close button, mark as read without redirecting
            if (btnClose) {
                btnClose.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const id = item.getAttribute('data-id');
                    markAsRead(item, id);
                });
            }

            // If they click the notification itself, mark as read and redirect
            item.addEventListener('click', function(e) {
                // If they didn't click the close button
                if (!e.target.closest('.mark-read-btn')) {
                    e.preventDefault();
                    const id = item.getAttribute('data-id');
                    const url = item.getAttribute('href');
                    markAsRead(item, id, url);
                }
            });
        });
    });
</script>
