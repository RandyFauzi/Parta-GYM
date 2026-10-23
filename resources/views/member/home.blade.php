@extends('layouts.member')
@section('title', 'Dashboard '.ucfirst($dashboardActivity))
@section('content')
<div class="m-page-heading"><div><span class="m-kicker">{{ now()->timezone(config('gym.timezone'))->locale('id')->isoFormat('dddd, D MMMM') }}</span><h1>Halo, {{ explode(' ',$member->full_name)[0] }}<span class="gold">.</span></h1><p>{{ ucfirst($dashboardActivity) }}, manfaat paket, dan aktivitas Anda dalam satu tempat.</p></div><a class="m-subtle-link" href="{{ route('portal.card') }}">Lihat kartu anggota <x-icon name="arrow"/></a></div>
<div class="m-home-grid">
    <section class="m-hero {{ $dashboardActivity === 'pilates' ? 'm-hero-pilates' : '' }}">
        @if($dashboardActivity === 'gym')
        <img src="{{ asset('images/gym-athlete.webp') }}" alt="" aria-hidden="true" width="1024" height="1536" fetchpriority="high" decoding="async">
        @else
        <span class="m-pilates-emblem" aria-hidden="true"><x-icon name="layers"/></span>
        @endif
        <div class="m-hero-content"><span class="m-kicker">{{ config('gym.name') }}</span><h2>{{ $dashboardActivity === 'pilates' ? 'Gerak terkendali.' : 'Satu sesi.' }}<br>{{ $dashboardActivity === 'pilates' ? 'Progres setiap sesi.' : 'Satu langkah maju.' }}</h2><p>{{ $member->status !== 'active' ? 'Akses Anda ditangguhkan. Hubungi front desk untuk bantuan.' : ($benefits->isNotEmpty() ? 'Lihat manfaat, kuota, dan jadwal '.ucfirst($dashboardActivity).' Anda sebelum latihan.' : 'Temukan paket '.ucfirst($dashboardActivity).' yang sesuai sebelum sesi berikutnya.') }}</p><a class="button" href="{{ route('portal.membership', ['activity' => $dashboardActivity]) }}">Lihat manfaat {{ ucfirst($dashboardActivity) }}<x-icon name="arrow"/></a></div>
    </section>
    <aside class="m-home-side">
        <section class="m-surface m-current-plan"><div class="m-surface-top"><span class="m-icon-tile"><x-icon name="card"/></span><x-status :status="$member->status==='suspended'?'suspended':($activityPeriod?'active':'expired')"/></div><small>PAKET {{ strtoupper($dashboardActivity) }} SAAT INI</small><h2>{{ $activityPeriod?->invoice?->snapshot['name'] ?? 'Belum aktif' }}</h2><p>{{ $activityPeriod ? 'Berlaku sampai '.$activityPeriod->ends_at->timezone(config('gym.timezone'))->format('d M Y, H:i') : 'Belum ada manfaat '.ucfirst($dashboardActivity).' yang aktif.' }}</p><a class="m-subtle-link" href="{{ route('portal.membership', ['activity' => $dashboardActivity]) }}">Kelola membership <x-icon name="arrow"/></a></section>
        <a class="m-dues-card" href="{{ route('portal.payments') }}"><span class="m-icon-tile"><x-icon name="layers"/></span><div><small>Tagihan terbuka</small><strong>Rp {{ number_format($outstanding,0,',','.') }}</strong></div><x-icon name="arrow"/></a>
    </aside>
</div>
<div class="m-stat-grid"><section class="m-stat"><small>{{ $dashboardActivity === 'gym' ? 'Kunjungan Gym minggu ini' : 'Hadir Pilates minggu ini' }}</small><strong>{{ $weekVisits }}<span> sesi</span></strong></section><section class="m-stat"><small>{{ $dashboardActivity === 'gym' ? 'Total kunjungan Gym' : 'Total hadir Pilates' }}</small><strong>{{ $totalVisits }}<span> sesi</span></strong></section><section class="m-stat"><small>{{ $dashboardActivity === 'gym' ? 'Kunjungan Gym terakhir' : 'Hadir Pilates terakhir' }}</small><strong class="m-stat-date">{{ $latestActivityAt?->timezone(config('gym.timezone'))->format('d M') ?? '—' }}</strong></section></div>
<section class="m-surface m-activity"><div class="m-section-heading"><div><span class="m-kicker">{{ strtoupper($dashboardActivity) }} ACTIVITY</span><h2>7 hari terakhir</h2></div><a class="m-subtle-link" href="{{ $dashboardActivity === 'gym' ? route('portal.visits') : route('classes', ['activity' => 'pilates', 'mine' => 1]) }}">Riwayat <x-icon name="arrow"/></a></div><div class="m-week-strip">@foreach($activity as $day)<div class="m-week-day {{ $day['count']?'has-visit':'' }}"><small>{{ $day['label'] }}</small><div class="m-week-indicator">@if($day['count'])<x-icon name="check"/>@else<span></span>@endif</div><span>{{ $day['date'] }}</span><small class="m-week-count">{{ $day['count'] }} sesi</small></div>@endforeach</div></section>
@include('member.benefits')
@include('member.upcoming-sessions')
<div class="m-home-footer"><x-icon name="scan"/><div><strong>{{ $dashboardActivity === 'gym' ? 'Check-in melalui front desk' : 'Kehadiran Pilates dicatat petugas' }}</strong><p>Siapkan kartu anggota saat tiba. Jadwal Pilates dan PT mengikuti konfirmasi booking Anda.</p></div></div>
<div class="m-home-footer"><x-icon name="chart"/><div><strong>Lihat perkembangan latihan Anda</strong><p>Evaluasi dan target berikutnya dari PT.</p><a class="m-subtle-link" href="{{ route('portal.progress', ['activity' => $dashboardActivity]) }}">Progres {{ ucfirst($dashboardActivity) }} <x-icon name="arrow"/></a></div></div>
@endsection
