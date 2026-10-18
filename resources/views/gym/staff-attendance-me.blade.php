@extends('layouts.app')
@section('title', 'Absensi Saya')
@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">WORK LOG</span>
        <h1>Presensi Saya<span class="gold">.</span></h1>
        <p>{{ $user->name }} &bull; {{ config('gym.account_roles')[$user->role] ?? ucfirst($user->role) }} &bull; {{ $month->translatedFormat('F Y') }}</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        @if(in_array($user->role, ['owner', 'admin'], true))
            <a class="button secondary" href="{{ route('staff.attendance.index') }}">
                <x-icon name="arrow-left"/> Rekap Tim
            </a>
        @endif
    </div>
</div>

<div class="two-columns" style="margin-bottom:24px;">
    {{-- Card Presensi Hari Ini --}}
    <section class="panel" style="padding:24px 28px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px;">
            <div>
                <span class="eyebrow" style="color:var(--gold);margin-bottom:6px;">HARI INI</span>
                <h2 style="font-size:20px;letter-spacing:-0.5px;">{{ \Carbon\CarbonImmutable::now(config('gym.timezone', 'Asia/Makassar'))->translatedFormat('l, d F Y') }}</h2>
            </div>
            @if($todayRecord)
                <span class="badge {{ $todayRecord->status_badge_class }}">
                    {{ $todayRecord->status_label }}
                </span>
            @else
                <span class="badge pending">Belum Absen</span>
            @endif
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;background:#131315;padding:16px 18px;border-radius:14px;border:1px solid var(--border);margin-bottom:20px;">
            <div>
                <small style="color:var(--muted);display:block;margin-bottom:4px;font-size:11px;">Masuk (Clock In)</small>
                <strong style="font-size:20px;letter-spacing:-0.5px;color:{{ $todayRecord?->clock_in_at ? '#8ed7a2' : 'var(--muted)' }};">
                    {{ $todayRecord?->clock_in_at ? $todayRecord->clock_in_at->timezone(config('gym.timezone', 'Asia/Makassar'))->format('H:i') . ' WITA' : '--:--' }}
                </strong>
            </div>
            <div>
                <small style="color:var(--muted);display:block;margin-bottom:4px;font-size:11px;">Pulang (Clock Out)</small>
                <strong style="font-size:20px;letter-spacing:-0.5px;color:{{ $todayRecord?->clock_out_at ? 'var(--gold)' : 'var(--muted)' }};">
                    {{ $todayRecord?->clock_out_at ? $todayRecord->clock_out_at->timezone(config('gym.timezone', 'Asia/Makassar'))->format('H:i') . ' WITA' : '--:--' }}
                </strong>
            </div>
        </div>

        @if($todayRecord?->clock_in_at && $todayRecord?->clock_out_at)
            <div style="font-size:12px;color:var(--muted);margin-bottom:18px;display:flex;align-items:center;gap:6px;">
                <x-icon name="clock"/> Total Kerja: <strong style="color:var(--ink);">{{ $todayRecord->work_duration_human }}</strong>
            </div>
        @endif

        @if($todayRecord?->notes)
            <div style="font-size:12px;color:var(--muted);background:#131315;border:1px solid var(--border);padding:10px 14px;border-radius:10px;margin-bottom:18px;">
                <em>{{ $todayRecord->notes }}</em>
            </div>
        @endif

        <div>
            @if(! $todayRecord || ! $todayRecord->clock_in_at)
                <form method="post" action="{{ route('staff.clock-in') }}" style="margin:0;">
                    @csrf
                    <button class="button" type="submit" style="width:100%;">
                        <x-icon name="check"/> Clock In
                    </button>
                </form>
            @elseif(! $todayRecord->clock_out_at)
                <form method="post" action="{{ route('staff.clock-out') }}" style="margin:0;">
                    @csrf
                    <button class="button secondary" type="submit" style="width:100%;border-color:var(--gold);color:var(--gold);">
                        <x-icon name="clock"/> Clock Out
                    </button>
                </form>
            @else
                <div style="padding:13px;text-align:center;background:rgba(142,215,162,0.1);color:#8ed7a2;border:1px solid rgba(142,215,162,0.3);border-radius:12px;width:100%;font-size:12px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:6px;">
                    <x-icon name="check"/> Presensi hari ini lengkap
                </div>
            @endif
        </div>
    </section>

    {{-- Panduan Singkat --}}
    <section class="panel" style="padding:24px 28px;">
        <span class="eyebrow" style="color:var(--gold);margin-bottom:6px;">PANDUAN</span>
        <h2 style="font-size:20px;letter-spacing:-0.5px;margin-bottom:14px;">Ketentuan Presensi</h2>
        <ul style="margin:0;padding-left:18px;color:var(--muted);font-size:12px;line-height:1.9;">
            <li><strong>Clock In</strong> saat tiba di gym sebelum memulai tugas.</li>
            <li><strong>Clock Out</strong> saat jam kerja atau sesi pelatihan selesai.</li>
            <li>Jika izin atau sakit, hubungi Owner/Admin untuk dicatat di sistem.</li>
            <li>Data kehadiran menjadi dasar rekap jam kerja dan komisi sesi PT.</li>
        </ul>
    </section>
</div>

{{-- Riwayat Presensi Bulan Ini --}}
<section class="panel">
    <div class="panel-heading">
        <div>
            <h2>Riwayat {{ $month->translatedFormat('F Y') }}</h2>
            <p>Daftar presensi harian Anda.</p>
        </div>

        <form method="get" action="{{ route('staff.attendance.me') }}" style="display:flex;gap:8px;align-items:center;margin:0;">
            <label for="month-select" class="sr-only">Bulan</label>
            <input type="month" id="month-select" name="month" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()" style="min-height:38px;padding:6px 12px;font-size:12px;">
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Masuk</th>
                    <th>Pulang</th>
                    <th>Durasi</th>
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
                                <strong style="color:#8ed7a2;">{{ $item->clock_in_at->timezone(config('gym.timezone', 'Asia/Makassar'))->format('H:i') }}</strong>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($item->clock_out_at)
                                <strong style="color:var(--gold);">{{ $item->clock_out_at->timezone(config('gym.timezone', 'Asia/Makassar'))->format('H:i') }}</strong>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>{{ $item->work_duration_human }}</td>
                        <td>
                            <small class="muted">{{ $item->notes ?? '—' }}</small>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">
                            Belum ada riwayat presensi bulan ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($history->hasPages())
        <div class="pagination">
            {{ $history->links() }}
        </div>
    @endif
</section>
@endsection
