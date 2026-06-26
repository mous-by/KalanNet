@php
    $active = request()->routeIs($route);
@endphp
<a href="{{ route($route) }}"
   class="nav-link d-flex align-items-center gap-2 rounded px-2 py-2 mb-1 {{ $active ? 'theme-pill-active fw-semibold' : 'config-menu-link' }}"
   style="{{ $active ? '' : '' }}">
    <i class="bi {{ $icon }} {{ $active ? '' : 'opacity-75' }}" style="font-size:.95rem;min-width:1.1rem;"></i>
    <span style="font-size:.875rem;">{{ $label }}</span>
</a>
