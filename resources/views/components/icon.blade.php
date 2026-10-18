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
@case('more-vertical')<circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="19" r="1.5"/>@break
@case('edit')<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>@break
@case('trash')<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>@break
@case('x')<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>@break
@case('chevron-down')<path d="m6 9 6 6 6-6"/>@break
@case('chevron-up')<path d="m18 15-6-6-6 6"/>@break
@case('download')<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-5 5 5 5-5m-5 5V3"/>@break
@default<path d="M4 8V5m4 5V3m8 7V3m4 5V5M1 9v6m22-6v6M4 16v3m4-5v7m8-7v7m4-5v3M4 12h16"/>
@endswitch</svg>
