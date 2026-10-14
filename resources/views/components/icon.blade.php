@props(['name'=>'grid'])
<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">@switch($name)
@case('grid')<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>@break
@case('users')<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87"/><circle cx="9" cy="7" r="4"/><path d="M16 3a4 4 0 0 1 0 8"/>@break
@case('scan')<path d="M8 3H3v5m13-5h5v5M3 16v5h5m13-5v5h-5M3 12h18"/>@break
@case('card')<rect x="2" y="4" width="20" height="16" rx="3"/><path d="M2 10h20m-15 5h3"/>@break
@case('chart')<path d="M3 3v18h18M7 15l5-6 4 3 5-8"/>@break
@case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
@case('layers')<path d="m12 3 10 6-10 6L2 9l10-6Zm-10 12 10 6 10-6M2 12l10 6 10-6"/>@break
@case('arrow')<path d="M5 12h14m-5-5 5 5-5 5"/>@break
@case('plus')<path d="M12 5v14M5 12h14"/>@break
@case('search')<circle cx="10.5" cy="10.5" r="7"/><path d="m16 16 5 5"/>@break
@case('logout')<path d="M9 21H4V3h5m4 4 5 5-5 5m-5-5h13"/>@break
@case('check')<path d="m5 12 4 4L19 6"/>@break
@default<path d="M4 8V5m4 5V3m8 7V3m4 5V5M1 9v6m22-6v6M4 16v3m4-5v7m8-7v7m4-5v3M4 12h16"/>
@endswitch</svg>
