<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#080809">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <title>Masuk · Parta GYM</title>
    @vite(['resources/css/app.css','resources/css/login.css','resources/js/app.js'])
</head>
<body class="login-body">
    <img class="login-backdrop" src="{{ asset('images/gym-athlete.webp') }}" alt="" aria-hidden="true" width="1024" height="1536" fetchpriority="high" decoding="async">
    <div class="login-shade" aria-hidden="true"></div>
    <header class="login-masthead">
        <a href="{{ route('login') }}" aria-label="Parta GYM"><img class="parta-logo" src="{{ asset('images/Logos/Logo Parta GYM (putih).webp') }}" alt="Parta GYM" width="1361" height="413" decoding="async"></a>
        <span>LATIHAN HARI INI. PROGRES ESOK HARI.</span>
    </header>
    <main class="login-stage">
        <section class="login-message" aria-labelledby="login-headline">
            <span class="eyebrow">JADI LEBIH KUAT, SETIAP HARI.</span>
            <h1 id="login-headline">LATIH<br>LEBIH KUAT.<br>JALANI HARI<br><span>LEBIH BAIK.</span></h1>
        </section>
        <section class="login-card" aria-labelledby="login-form-title">
            <div class="login-form">
                <span class="eyebrow">AKUN PARTA GYM</span>
                <h2 id="login-form-title">Siap untuk sesi berikutnya?</h2>
                <p class="muted">Masuk untuk melihat membership dan aktivitas Anda.</p>
@if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif @if($errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif<form action="{{ route('login.store') }}" method="post">@csrf<x-field name="email" label="Email" type="email" required autocomplete="username" autofocus/><x-field name="password" label="Kata sandi" type="password" required autocomplete="current-password"/><label class="checkbox"><input type="checkbox" name="remember" value="1"> Tetap masuk di perangkat ini</label><button class="button wide">Masuk <x-icon name="arrow"/></button></form><p class="login-hint">Belum memiliki akun? Hubungi pengelola gym untuk mendapatkan akses.</p><div class="login-security"><x-icon name="check"/> Akses membership dan aktivitas gym Anda</div>
            </div>
        </section>
    </main>
    <footer class="login-bottom">{{ config('gym.name') }} <span>KONSISTENSI MEMBENTUK KEKUATAN.</span></footer>
</body>
</html>
