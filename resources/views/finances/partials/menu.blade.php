@php
    $active = $active ?? '';
    $isPublicSchool = $isPublicSchool
        ?? (strtolower(trim((string) (\App\Models\Ecole::find(session('idEcole'))->statut ?? ''))) === 'public');
    $financeMenuItems = [
        'index' => ['route' => 'finances.index', 'icon' => 'bi-graph-up', 'label' => __('finances.menu_dashboard')],
        'planifications' => ['route' => 'finances.planifications', 'icon' => 'bi-calendar-check', 'label' => $isPublicSchool ? __('finances.menu_planifications_public') : __('finances.menu_planifications_private')],
        'paiements' => ['route' => 'finances.paiements', 'icon' => 'bi-cash-stack', 'label' => __('finances.menu_paiements')],
        'historique' => ['route' => 'finances.paiements.historique', 'icon' => 'bi-clock-history', 'label' => __('finances.menu_historique')],
    ];
@endphp

@once
    @push('styles')
    <style>
        .finance-menu .nav-link {
            color: var(--text-main);
            border-radius: 8px;
        }
        .finance-menu .nav-link.active {
            border-left: 4px solid var(--theme-accent);
            background: var(--accent-light);
            color: var(--theme-accent);
            font-weight: 700;
        }
        .finance-menu .menu-icon {
            width: 28px;
            height: 28px;
            background: var(--theme-primary);
            color: var(--text-on-accent);
        }
    </style>
    @endpush
@endonce

<div class="card theme-card h-100">
    <div class="card-header theme-header d-flex align-items-center">
        <i class="bi bi-list me-2"></i> {{ __('finances.menu_title') }}
    </div>
    <div class="card-body p-2">
        <ul class="nav flex-column gap-2 finance-menu">
            @foreach($financeMenuItems as $key => $item)
                <li class="nav-item">
                    <a class="nav-link {{ $active === $key ? 'active' : '' }} d-flex align-items-center py-2" href="{{ route($item['route']) }}">
                        <span class="menu-icon rounded-circle d-flex align-items-center justify-content-center me-2">
                            <i class="bi {{ $item['icon'] }}"></i>
                        </span>
                        <span>{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
