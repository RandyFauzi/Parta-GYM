@extends('layouts.app')
@section('title','Check-in')
@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">FRONT DESK</span>
        <h1>Check-in Anggota<span class="gold">.</span></h1>
        <p>Cek identitas, sisa masa aktif, dan izin akses gym.</p>
    </div>
    <a class="button secondary" href="{{ route('members.new') }}"><x-icon name="plus"/>Pendaftaran</a>
</div>

<section class="panel scan-panel">
    <div class="scan-symbol"><x-icon name="scan"/></div>
    <h2>Check-in Anggota</h2>
    <p class="muted">Cari nomor anggota, nama, atau WhatsApp.</p>
    <form class="scan-search" method="get">
        <label class="sr-only" for="q">Cari anggota</label>
        <input id="q" name="q" placeholder="Nomor anggota, nama, atau nomor WA…" value="{{ request('q') }}" autofocus required maxlength="100">
        <button class="button"><x-icon name="search"/>Cari</button>
    </form>
    <div data-barcode-scanner>
        <button type="button" class="button secondary" data-scan-start>Scan Barcode / QR</button>
        <button type="button" class="button secondary" data-scan-stop hidden>Tutup Kamera</button>
        <video data-scan-video playsinline muted hidden></video>
        <p data-scan-feedback role="status" class="muted"></p>
    </div>
</section>

@if(request()->filled('q'))
    <div class="checkin-results">
        @forelse($members as $m)
            @php
                $tz = config('gym.timezone');
                $nowTz = \Carbon\CarbonImmutable::now($tz);
                $period = $m->currentPeriod;
                $remainingDays = null;
                if ($period) {
                    $remainingDays = (int) ceil($nowTz->diffInDays($period->ends_at->timezone($tz), false));
                }
                $todayCheckins = $m->today_checkins;
                $lastCheckinTime = $m->checkins_max_checked_in_at ? \Carbon\CarbonImmutable::parse($m->checkins_max_checked_in_at,'UTC') : null;
                $isRecentDuplicate = $lastCheckinTime && $lastCheckinTime->gt(now()->subMinutes(config('gym.duplicate_minutes')));
            @endphp
            <section class="panel result-card">
                <div class="table-person">
                    <span class="avatar">{{ mb_substr($m->full_name,0,1) }}</span>
                    <div>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <h2>{{ $m->full_name }}</h2>
                            <span class="badge neutral" style="font-size:0.75rem;">{{ $m->customer_type_label }}</span>
                        </div>
                        <small>{{ $m->member_number }} · {{ $m->phone }}</small>
                    </div>
                </div>

                <div style="margin: 12px 0;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <x-status :status="$m->currentPeriod?'active':'expired'"/>
                        <span style="font-weight:600;">{{ $m->currentPeriod?->invoice->snapshot['name'] ?? 'Tidak ada membership aktif' }}</span>
                    </div>

                    @if($period)
                        <div style="margin-top:6px;font-size:0.9rem;">
                            Sisa masa aktif:
                            <strong style="color: {{ $remainingDays <= 3 ? '#ef4444' : '#10b981' }};">
                                {{ $remainingDays > 0 ? $remainingDays.' hari lagi' : 'Berakhir hari ini' }}
                            </strong>
                            <span class="muted">(s.d. {{ $period->ends_at->timezone($tz)->format('d M Y, H:i') }})</span>
                        </div>
                        @if($period->visit_limit)
                            @php($usedVisits = \App\Models\Checkin::where('membership_period_id', $period->id)->count())
                            <div class="muted" style="font-size:0.85rem;">
                                Sisa kuota: <strong>{{ max(0, $period->visit_limit - $usedVisits) }}</strong> dari {{ $period->visit_limit }} sesi
                            </div>
                        @endif
                    @endif

                    {{-- Proteksi Check In Ganda & Catatan Kunjungan Hari Ini --}}
                    @if($todayCheckins > 0)
                        <div style="margin-top:10px;padding:8px 12px;border-radius:6px;background:rgba(217,119,6,0.12);border:1px solid rgba(217,119,6,0.3);font-size:0.85rem;">
                            <strong>Sudah check-in {{ $todayCheckins }}x hari ini.</strong>
                            @if($lastCheckinTime)
                                <span class="muted">(Terakhir {{ $lastCheckinTime->timezone($tz)->format('H:i') }})</span>
                            @endif
                            @if($isRecentDuplicate)
                                <div style="color:#ef4444;font-weight:600;margin-top:4px;">
                                    Baru saja check-in dalam {{ config('gym.duplicate_minutes') }} menit terakhir. Check-in ulang ditolak.
                                </div>
                            @endif
                        </div>
                    @else
                        <small class="muted" style="display:block;margin-top:8px;">Kunjungan pertama hari ini.</small>
                    @endif
                </div>

                <form method="post" action="{{ route('checkin.store',$m) }}">
                    @csrf
                    <label class="checkbox">
                        <input type="checkbox" name="verified" value="1" required> Identitas sesuai
                    </label>
                    <button class="button wide">Check-in <x-icon name="check"/></button>
                </form>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:12px;border-top:1px solid rgba(255,255,255,0.08);">
                    <a class="button compact secondary" href="{{ $whatsapp->getMemberPassUrl($m) }}" target="_blank" rel="noopener noreferrer" title="Kirim Kartu & Barcode ke WhatsApp">
                        Kirim Kartu WA
                    </a>
                    <a class="text-link" href="{{ route('members.show',$m) }}">Profil &amp; Perpanjangan <x-icon name="arrow"/></a>
                </div>
            </section>
        @empty
            <div class="empty panel">Anggota tidak ditemukan. Periksa nomor atau daftarkan anggota baru.</div>
        @endforelse
    </div>
@endif

@if(auth()->user()->role==='owner')<p><a class="button secondary" href="{{ route('operating-hours') }}">Atur jam tutup Gym</a></p>@endif
<section class="panel spaced">
    <div class="panel-heading">
        <div>
            <h2>Kunjungan terbaru</h2>
            <p>Hanya check-in yang berhasil</p>
        </div>
        <span class="badge positive">Live data</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Anggota</th>
                    <th>Nomor anggota</th>
                    <th>Kategori</th>
                    <th>Waktu check-in</th><th>Checkout</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recent as $v)
                    <tr>
                        <td>{{ $v->member->full_name }}</td>
                        <td>{{ $v->member->member_number }}</td>
                        <td><span class="badge neutral" style="font-size:0.75rem;">{{ $v->member->customer_type_label }}</span></td>
                        <td>{{ $v->checked_in_at->timezone(config('gym.timezone'))->format('d M Y · H:i') }}</td>
                        <td>@if($v->checked_out_at){{ $v->checked_out_at->timezone(config('gym.timezone'))->format('d M Y H:i') }}<small>{{ $v->checkout_method==='automatic'?'Otomatis saat tutup':'Dicatat staf' }}</small>@else<form method="post" action="{{ route('checkout.store', $v) }}">@csrf<button class="button compact secondary">Checkout</button></form><small>{{ $v->checkout_due_at ? 'Otomatis: '.$v->checkout_due_at->timezone(config('gym.timezone'))->format('d M H:i') : 'Batas otomatis belum diatur' }}</small>@endif</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty">Belum ada check-in. Kunjungan berikutnya akan tercatat di sini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
