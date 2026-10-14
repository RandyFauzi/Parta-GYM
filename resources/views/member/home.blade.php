@extends('layouts.member')
@section('title','Beranda')
@section('content')
<div class="m-page-heading"><div><span class="m-kicker">{{ now()->timezone(config('gym.timezone'))->locale('id')->isoFormat('dddd, D MMMM') }}</span><h1>Halo, {{ explode(' ',$member->full_name)[0] }}<span class="gold">.</span></h1><p>Membership dan aktivitas Anda, sekilas.</p></div><a class="m-subtle-link" href="{{ route('portal.membership') }}">Lihat kartu anggota <x-icon name="arrow"/></a></div>
<div class="m-home-grid">
    <section class="m-hero">
        <img src="{{ asset('images/gym-athlete.webp') }}" alt="" aria-hidden="true" width="1024" height="1536" fetchpriority="high" decoding="async">
        <div class="m-hero-content"><span class="m-kicker">{{ config('gym.name') }}</span><h2>Satu sesi.<br>Satu langkah maju.</h2><p>{{ $member->currentPeriod && $member->status==='active' ? 'Membership Anda aktif. Tunjukkan kartu anggota ke front desk untuk mulai berlatih.' : 'Siapkan membership Anda sebelum sesi berikutnya.' }}</p><a class="button" href="{{ route('portal.membership') }}">{{ $member->currentPeriod?'Buka kartu anggota':'Pilih membership' }}<x-icon name="arrow"/></a></div>
    </section>
    <aside class="m-home-side">
        <section class="m-surface m-current-plan"><div class="m-surface-top"><span class="m-icon-tile"><x-icon name="card"/></span><x-status :status="$member->status==='suspended'?'suspended':($member->currentPeriod?'active':'expired')"/></div><small>MEMBERSHIP SAAT INI</small><h2>{{ $member->currentPeriod?->invoice->snapshot['name'] ?? 'Belum aktif' }}</h2><p>{{ $member->currentPeriod ? 'Berlaku sampai '.$member->currentPeriod->ends_at->timezone(config('gym.timezone'))->format('d M Y, H:i') : 'Temukan paket yang sesuai untuk Anda.' }}</p><a class="m-subtle-link" href="{{ route('portal.membership') }}">Kelola membership <x-icon name="arrow"/></a></section>
        <a class="m-dues-card" href="{{ route('portal.payments') }}"><span class="m-icon-tile"><x-icon name="layers"/></span><div><small>Tagihan terbuka</small><strong>Rp {{ number_format($outstanding,0,',','.') }}</strong></div><x-icon name="arrow"/></a>
    </aside>
</div>
<div class="m-stat-grid"><section class="m-stat"><small>Kunjungan minggu ini</small><strong>{{ $weekVisits }}<span> sesi</span></strong></section><section class="m-stat"><small>Total kunjungan</small><strong>{{ $totalVisits }}<span> sesi</span></strong></section><section class="m-stat"><small>Kunjungan terakhir</small><strong class="m-stat-date">{{ $latestVisit?->checked_in_at->timezone(config('gym.timezone'))->format('d M') ?? '—' }}</strong></section></div>
<section class="m-surface m-activity"><div class="m-section-heading"><div><span class="m-kicker">YOUR ACTIVITY</span><h2>7 hari terakhir</h2></div><a class="m-subtle-link" href="{{ route('portal.visits') }}">Riwayat <x-icon name="arrow"/></a></div><div class="m-week-strip">@foreach($activity as $day)<div class="m-week-day {{ $day['count']?'has-visit':'' }}"><small>{{ $day['label'] }}</small><div class="m-week-indicator">@if($day['count'])<x-icon name="check"/>@else<span></span>@endif</div><span>{{ $day['date'] }}</span><small class="m-week-count">{{ $day['count'] }} sesi</small></div>@endforeach</div></section>
<div class="m-home-footer"><x-icon name="scan"/><div><strong>Check-in melalui front desk</strong><p>Siapkan nomor anggota dan identitas Anda saat tiba di gym.</p></div></div>
@endsection
