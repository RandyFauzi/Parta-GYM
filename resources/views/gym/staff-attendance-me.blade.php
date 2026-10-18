@extends('layouts.app')
@section('title', 'Absensi Saya')
@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">MY WORK LOG</span>
        <h1>Presensi &amp; Riwayat Kehadiran Saya<span class="gold">.</span></h1>
        <p>{{ $user->name }} &bull; {{ config('gym.account_roles')[$user->role] ?? $user->role }} &bull; Periode {{ $month->translatedFormat('F Y') }}</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        @if(in_array($user->role, ['owner', 'admin'], true))
            <a class="button secondary" href="{{ route('staff.attendance.index') }}">
                <x-icon name="arrow-left" style="width:16px;height:16px;margin-right:6px;"/> Rekap Semua Karyawan
            </a>
        @endif
    </div>
</div>

{{-- Presensi Hari Ini Panel --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(min(100%,320px), 1fr));gap:20px;margin-bottom:28px;">
    <section class="panel" style="border-left:4px solid var(--accent, #eab308);">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;">
            <div>
                <span class="eyebrow">STATUS HARI INI</span>
                <h3 style="margin:4px 0 0;font-size:1.15rem;">{{ \Carbon\CarbonImmutable::now(config('gym.timezone', 'Asia/Makassar'))->translatedFormat('l, d F Y') }}</h3>
            </div>
            @if($todayRecord)
                <span class="badge {{ $todayRecord->status_badge_class }}">
                    {{ $todayRecord->status_label }}
                </span>
            @else
                <span class="badge badge-warning">Belum Presensi</span>
            @endif
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;background:rgba(255,255,255,0.02);padding:14px;border-radius:8px;border:1px solid rgba(255,255,255,0.06);margin-bottom:16px;">
            <div>
                <small style="color:#9ca3af;display:block;margin-bottom:4px;">Jam Masuk (Clock In)</small>
                <strong style="font-size:1.1rem;color:{{ $todayRecord?->clock_in_at ? '#10b981' : '#6b7280' }};">
                    {{ $todayRecord?->clock_in_at ? $todayRecord->clock_in_at->timezone(config('gym.timezone', 'Asia/Makassar'))->format('H:i') . ' WITA' : '--:--' }}
                </strong>
            </div>
            <div>
                <small style="color:#9ca3af;display:block;margin-bottom:4px;">Jam Pulang (Clock Out)</small>
                <strong style="font-size:1.1rem;color:{{ $todayRecord?->clock_out_at ? '#3b82f6' : '#6b7280' }};">
                    {{ $todayRecord?->clock_out_at ? $todayRecord->clock_out_at->timezone(config('gym.timezone', 'Asia/Makassar'))->format('H:i') . ' WITA' : '--:--' }}
                </strong>
            </div>
        </div>

        @if($todayRecord?->clock_in_at && $todayRecord?->clock_out_at)
            <div style="font-size:0.875rem;color:#9ca3af;margin-bottom:16px;">
                Total Jam Kerja: <strong style="color:#f3f4f6;">{{ $todayRecord->work_duration_human }}</strong>
            </div>
        @endif

        @if($todayRecord?->notes)
            <div style="font-size:0.85rem;color:#d1d5db;background:rgba(0,0,0,0.25);padding:10px 12px;border-radius:6px;margin-bottom:16px;">
                <em>Catatan: {{ $todayRecord->notes }}</em>
            </div>
        @endif

        <div style="display:flex;gap:10px;">
            @if(! $todayRecord || ! $todayRecord->clock_in_at)
                <form method="post" action="{{ route('staff.clock-in') }}" style="flex:1;">
                    @csrf
                    <button class="button primary" type="submit" style="width:100%;justify-content:center;">
                        <x-icon name="check" style="width:16px;height:16px;margin-right:6px;"/> Clock In Sekarang
                    </button>
                </form>
            @elseif(! $todayRecord->clock_out_at)
                <form method="post" action="{{ route('staff.clock-out') }}" style="flex:1;">
                    @csrf
                    <button class="button secondary" type="submit" style="width:100%;justify-content:center;background:#2563eb;color:#fff;border-color:#2563eb;">
                        <x-icon name="clock" style="width:16px;height:16px;margin-right:6px;"/> Clock Out Pulang
                    </button>
                </form>
            @else
                <div style="padding:10px;text-align:center;background:rgba(16,185,129,0.1);color:#10b981;border-radius:6px;width:100%;font-size:0.9rem;font-weight:600;">
                    Kehadiran hari ini telah lengkap &check;
                </div>
            @endif
        </div>
    </section>

    {{-- Info Shift & Peraturan Presensi --}}
    <section class="panel">
        <span class="eyebrow">KETENTUAN OPERASIONAL</span>
        <h3 style="margin:4px 0 12px;font-size:1.15rem;">Panduan Presensi Gym</h3>
        <ul style="margin:0;padding-left:20px;color:#9ca3af;font-size:0.9rem;line-height:1.7;">
            <li>Lakukan <strong>Clock In</strong> saat tiba di gym sebelum memulai shift / melatih member.</li>
            <li>Lakukan <strong>Clock Out</strong> saat jam kerja atau sesi pelatihan selesai sebelum meninggalkan gym.</li>
            <li>Jika berhalangan hadir karena sakit atau izin mendesak, segera laporkan ke Admin/Owner untuk dicatat statusnya.</li>
            <li>Riwayat kehadiran ini digunakan sebagai dasar evaluasi kinerja, insentif sesi PT, dan perhitungan operasional gym.</li>
        </ul>
    </section>
</div>

{{-- Riwayat Presensi Bulan Ini --}}
<section class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
        <div>
            <h3 style="margin:0;">Riwayat Presensi Periode {{ $month->translatedFormat('F Y') }}</h3>
            <p style="margin:4px 0 0;font-size:0.875rem;color:#9ca3af;">Daftar presensi harian Anda sepanjang bulan ini.</p>
        </div>

        <form method="get" action="{{ route('staff.attendance.me') }}" style="display:flex;gap:8px;align-items:center;">
            <label for="month-select" class="sr-only">Pilih Bulan</label>
            <input type="month" id="month-select" name="month" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()" style="background:#111827;border:1px solid rgba(255,255,255,0.15);color:#fff;padding:6px 12px;border-radius:6px;font-size:0.9rem;">
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Durasi Kerja</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($history as $item)
                    <tr>
                        <td>
                            <strong>{{ \Carbon\CarbonImmutable::parse($item->date)->translatedFormat('l, d M Y') }}</strong>
                        </td>
                        <td>
                            <span class="badge {{ $item->status_badge_class }}">{{ $item->status_label }}</span>
                        </td>
                        <td>
                            @if($item->clock_in_at)
                                <span style="color:#10b981;font-weight:600;">{{ $item->clock_in_at->timezone(config('gym.timezone', 'Asia/Makassar'))->format('H:i') }}</span>
                            @else
                                <span style="color:#6b7280;">-</span>
                            @endif
                        </td>
                        <td>
                            @if($item->clock_out_at)
                                <span style="color:#3b82f6;font-weight:600;">{{ $item->clock_out_at->timezone(config('gym.timezone', 'Asia/Makassar'))->format('H:i') }}</span>
                            @else
                                <span style="color:#6b7280;">-</span>
                            @endif
                        </td>
                        <td>{{ $item->work_duration_human }}</td>
                        <td style="color:#9ca3af;font-size:0.875rem;">{{ $item->notes ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:32px;color:#9ca3af;">
                            Belum ada catatan presensi untuk bulan ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($history->hasPages())
        <div style="margin-top:16px;">
            {{ $history->links() }}
        </div>
    @endif
</section>
@endsection
