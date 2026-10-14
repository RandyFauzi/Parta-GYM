<!doctype html><html lang="id"><head><link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><meta name="theme-color" content="#0c0c0d"><title>@yield('title','Workspace') · Parta GYM</title>@vite(['resources/css/app.css','resources/css/admin.css','resources/js/app.js'])</head>
<body class="{{ auth()->user()->role==='member'?'member-theme':(auth()->user()->role==='owner'?'admin-app':'staff-app') }}"><a class="skip-link" href="#main">Langsung ke konten</a>
<aside class="sidebar"><a class="brand" href="{{ route('home') }}"><img class="parta-logo" src="{{ asset('images/Logos/Logo Parta GYM (putih).webp') }}" alt="Parta GYM" width="1361" height="413" decoding="async"></a><div class="workspace"><span class="workspace-monogram">P</span><div>{{ config('gym.name') }}<small>Gym management workspace</small></div><span class="online-dot"></span></div>
<p class="nav-label">WORKSPACE</p><nav aria-label="Navigasi utama">
@if(auth()->user()->role==='owner')<a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard')?'selected':'' }}"><x-icon name="grid"/>Ringkasan</a>@endif
@if(auth()->user()->role!=='member')
@foreach([['checkin','scan','Check-in'],['members','users','Anggota'],['payments','card','Pembayaran'],['shifts','clock','Shift kasir']] as [$route,$icon,$label])<a href="{{ route($route) }}" class="{{ request()->routeIs($route,$route.'.*')?'selected':'' }}"><x-icon :name="$icon"/>{{ $label }}</a>@endforeach
@if(auth()->user()->role==='owner')<p class="nav-label">MANAGEMENT</p>@foreach([['plans','layers','Paket membership'],['reports','chart','Laporan'],['audit','clock','Log aktivitas'],['staff','users','Tim gym']] as [$route,$icon,$label])<a href="{{ route($route) }}" class="{{ request()->routeIs($route)?'selected':'' }}"><x-icon :name="$icon"/>{{ $label }}</a>@endforeach @endif
@else<a class="selected" aria-current="page" href="{{ route('portal') }}"><x-icon name="card"/>Membership saya</a><a href="#visits"><x-icon name="clock"/>Riwayat kunjungan</a><a href="#billing"><x-icon name="layers"/>Tagihan saya</a>@endif
</nav>

<div class="sidebar-bottom"><div class="system-note"><span class="online-dot"></span> Connected to your gym<small>Parta GYM · Operations, simplified.</small></div><div class="user-row"><span class="avatar">{{ mb_substr(auth()->user()->name,0,1) }}</span><div>{{ auth()->user()->name }}<small>{{ ucfirst(auth()->user()->role) }}</small></div><form action="{{ route('logout') }}" method="post">@csrf<button class="icon-button" aria-label="Keluar"><x-icon name="logout"/></button></form></div></div></aside>
<div class="main-shell"><header class="topbar"><div><a class="workspace-mobile-brand" href="{{ route('home') }}"><img class="parta-logo" src="{{ asset('images/Logos/Logo Parta GYM (putih).webp') }}" alt="Parta GYM" width="1361" height="413" decoding="async"></a><span class="breadcrumb">Workspace</span><span class="separator">/</span><strong>@yield('title','Ringkasan')</strong></div><div class="topbar-right"><span class="live-chip"><span class="online-dot"></span> Operasional</span><span>{{ now()->timezone(config('gym.timezone'))->translatedFormat('d M Y') }}</span><form class="mobile-logout" action="{{ route('logout') }}" method="post">@csrf<button class="icon-button" aria-label="Keluar akun"><x-icon name="logout"/></button></form><span class="avatar small">{{ mb_substr(auth()->user()->name,0,1) }}</span></div></header><main id="main">@if(session('success'))<div class="alert success" role="status"><x-icon name="check"/>{{ session('success') }}</div>@endif @if(session('error'))<div class="alert error" role="alert">{{ session('error') }}</div>@endif @if($errors->any())<div class="alert error" role="alert" tabindex="-1" data-error-summary><strong>Periksa kembali data Anda.</strong><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif @yield('content')</main><footer class="page-footer"><span>Parta GYM <span class="separator">/</span> Built for better operations.</span><span>{{ config('gym.timezone') }} · IDR</span></footer></div>
@if(auth()->user()->role==='member')
<nav class="bottom-nav" aria-label="Navigasi anggota mobile">
<a href="{{ route('portal') }}" data-member-nav="home" aria-current="page"><span class="bottom-nav-icon"><x-icon name="grid"/></span><span>Beranda</span></a>
<a href="#visits" data-member-nav="visits"><span class="bottom-nav-icon"><x-icon name="scan"/></span><span>Kunjungan</span></a>
<a href="#billing" data-member-nav="billing"><span class="bottom-nav-icon"><x-icon name="card"/></span><span>Tagihan</span></a>
<a href="#profile" data-member-nav="profile"><span class="bottom-nav-icon"><x-icon name="users"/></span><span>Profil</span></a>
</nav>
@endif
@if(in_array(auth()->user()->role,['owner','receptionist'],true))
@php
$owner=auth()->user()->role==='owner';
$dockLinks=$owner ? [['dashboard','grid','Beranda'],['checkin','scan','Check-in'],['members','users','Anggota'],['payments','card','Tagihan']] : [['checkin','scan','Check-in'],['members','users','Anggota'],['payments','card','Tagihan'],['shifts','clock','Shift']];
$moreLinks=[['shifts','clock','Shift kasir'],['plans','layers','Paket'],['reports','chart','Laporan'],['audit','clock','Log aktivitas'],['staff','users','Tim gym']];
@endphp
<nav class="workspace-dock {{ $owner?'has-more':'' }}" aria-label="Navigasi workspace mobile" data-mobile-nav>
@foreach($dockLinks as [$route,$icon,$label])<a href="{{ route($route) }}" @if(request()->routeIs($route,$route.'.*')) aria-current="page" @endif><span class="workspace-dock-icon"><x-icon :name="$icon"/></span><span>{{ $label }}</span></a>@endforeach
@if($owner)<details class="workspace-more"><summary @if(request()->routeIs('shifts','plans','reports','audit','staff')) class="is-active" @endif><span class="workspace-dock-icon"><x-icon name="layers"/></span><span>Lainnya</span></summary><div class="workspace-more-panel"><strong>Kelola gym</strong><nav aria-label="Menu tambahan owner">@foreach($moreLinks as [$route,$icon,$label])<a href="{{ route($route) }}" @if(request()->routeIs($route,$route.'.*')) aria-current="page" @endif><x-icon :name="$icon"/>{{ $label }}</a>@endforeach</nav></div></details>@endif
</nav>
@endif
</body></html>
