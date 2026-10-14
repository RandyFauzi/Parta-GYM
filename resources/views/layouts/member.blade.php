<!doctype html>
<html lang="id">
<head><link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#080809">
    <title>@yield('title', 'Beranda') · Parta GYM</title>
    @vite(['resources/css/app.css', 'resources/css/member.css', 'resources/js/app.js'])
</head>
<body class="member-theme member-app">
@php
    $navigation = [['portal','grid','Beranda'],['portal.membership','card','Membership'],['portal.visits','scan','Kunjungan'],['portal.payments','layers','Tagihan'],['portal.account','users','Profil']];
    $currentRoute = request()->routeIs('portal.invoice') ? 'portal.payments' : request()->route()->getName();
@endphp
<a class="skip-link" href="#main">Langsung ke konten</a>
<aside class="member-rail">
    <a class="brand" href="{{ route('portal') }}"><img class="parta-logo" src="{{ asset('images/Logos/Logo Parta GYM (putih).webp') }}" alt="Parta GYM" width="1361" height="413" decoding="async"></a>
    <div class="m-gym-label"><span class="online-dot"></span>{{ config('gym.name') }}</div>
    <nav aria-label="Navigasi anggota desktop">
        @foreach($navigation as [$route,$icon,$label])
        <a href="{{ route($route) }}" @if($currentRoute===$route) aria-current="page" @endif><x-icon :name="$icon"/><span>{{ $label }}</span></a>
        @endforeach
    </nav>
    <div class="m-rail-note"><x-icon name="gym"/><p>Informasi gym Anda,<br>dalam satu tempat.</p></div>
    <form action="{{ route('logout') }}" method="post">@csrf<button class="m-logout"><x-icon name="logout"/>Keluar akun</button></form>
</aside>
<div class="member-shell">
    <header class="member-header">
        <a class="m-mobile-brand" href="{{ route('portal') }}"><img class="parta-logo" src="{{ asset('images/Logos/Logo Parta GYM (putih).webp') }}" alt="Parta GYM" width="1361" height="413" decoding="async"></a>
        <span class="m-desktop-breadcrumb">Member area <span>/</span> @yield('title')</span>
        <div class="m-header-user"><span class="m-header-gym">{{ config('gym.name') }}</span><a class="m-avatar" href="{{ route('portal.account') }}" aria-label="Buka profil">{{ mb_substr($member->full_name,0,1) }}</a></div>
    </header>
    <main class="member-main" id="main">
        @if(session('success'))<div class="alert success" role="status"><x-icon name="check"/>{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert error" role="alert" tabindex="-1" data-error-summary><strong>Periksa data Anda.</strong><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
    <footer class="member-footer"><span>{{ config('gym.name') }}</span><span>{{ config('gym.timezone') }} · IDR</span></footer>
</div>
<nav class="member-dock" aria-label="Navigasi anggota mobile" data-mobile-nav>
    @foreach($navigation as [$route,$icon,$label])
    <a href="{{ route($route) }}" @if($currentRoute===$route) aria-current="page" @endif><span class="m-dock-icon"><x-icon :name="$icon"/></span><span>{{ $route==='portal.membership'?'Paket':$label }}</span></a>
    @endforeach
</nav>
</body>
</html>
