@extends('layouts.member')
@section('title','Check-in')
@section('content')
<div class="page-heading"><div><span class="eyebrow">KARTU ANGGOTA</span><h1>Siap latihan<span class="gold">?</span></h1><p>Tunjukkan QR Code ke petugas, lalu mulai sesi Anda.</p></div></div>
<div class="member-pass-layout"><section class="member-pass panel"><div class="member-pass-cover"><span>SPARTA</span><small>GYM & PILATES · MEMBER PASS</small></div><div class="member-pass-body"><div class="trainer-card-top"><span class="trainer-avatar">{{ mb_substr($member->full_name,0,1) }}</span><span class="badge {{ $eligible?'success':'pending' }}">{{ $eligible?'Akses aktif':'Akses belum tersedia' }}</span></div><h2>{{ $member->full_name }}</h2><p class="muted">{{ $member->member_number }}</p><div class="member-pass-qr"><svg xmlns="http://www.w3.org/2000/svg" role="img" aria-label="QR Code kartu anggota" viewBox="0 0 29 29" shape-rendering="crispEdges"><rect width="29" height="29" fill="#fff"/>
@foreach($qrCode as $y => $row)
@foreach($row as $x => $dark)
@if($dark)
<rect x="{{ $x+4 }}" y="{{ $y+4 }}" width="1" height="1" fill="#000"/>
@endif
@endforeach
@endforeach
</svg><span>{{ $code }}</span></div><p class="muted">Kode identitas anggota · Verifikasi oleh petugas</p></div></section><section class="panel form-panel"><span class="eyebrow">MEMBERSHIP ANDA</span><h2>{{ $member->currentPeriod?->invoice?->snapshot['name'] ?? 'Belum ada paket aktif' }}</h2><dl class="pass-details"><dt>Mulai berlaku</dt><dd>{{ $member->currentPeriod?->starts_at->timezone(config('gym.timezone'))->format('d M Y H:i') ?? '—' }}</dd><dt>Berlaku sampai</dt><dd>{{ $member->currentPeriod?->ends_at->timezone(config('gym.timezone'))->format('d M Y H:i') ?? '—' }}</dd><dt>Sisa kunjungan</dt><dd>{{ !$member->currentPeriod?'—':($member->currentPeriod->visit_limit===null?'Tanpa batas':max(0,$member->currentPeriod->visit_limit-$usedVisits)) }}</dd></dl><p class="muted">Petugas memeriksa identitas dan status paket saat mencatat check-in. Simpan kartu ini untuk penggunaan pribadi.</p><a class="button secondary" href="{{ route('portal.membership') }}">Kelola paket <x-icon name="arrow"/></a><a class="text-link" href="{{ route('portal.visits') }}">Riwayat kunjungan</a></section></div>
@endsection
