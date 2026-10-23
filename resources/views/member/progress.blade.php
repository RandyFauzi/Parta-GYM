@extends('layouts.member')
@section('title', 'Progres Latihan')
@section('content')
<div class="m-portal-header">
    <div>
        <span class="m-eyebrow">HASIL &amp; PERKEMBANGAN DIRI</span>
        <h1>Catatan Progres PT<span class="gold">.</span></h1>
        <p>Evaluasi fisik, catatan sesi, dan arahan latihan dari Personal Trainer Anda.</p>
    </div>
</div>

<form method="get" action="{{ route('portal.progress') }}" class="form-grid">
    <div class="field"><label for="progress_activity">Aktivitas</label><select id="progress_activity" name="activity"><option value="">Semua, termasuk catatan lama</option>@foreach(['gym' => 'Gym', 'pilates' => 'Pilates'] as $value => $label)<option value="{{ $value }}" @selected(request('activity') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div><button class="button secondary">Tampilkan</button></div>
</form>
@include('gym.progress-measurements')

@if($latest)
    <section class="m-summary-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px;">
        <div class="m-stat-card" style="padding:16px;border-radius:12px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);">
            <small class="muted" style="display:block;margin-bottom:4px;">BERAT BADAN TERBARU</small>
            <strong style="font-size:1.6rem;color:#f3f4f6;">{{ isset($measurements['weight_kg']) ? $measurements['weight_kg']['latest'].' kg' : '—' }}</strong>
            <small class="muted" style="display:block;margin-top:4px;">@isset($measurements['weight_kg']) Diukur {{ $measurements['weight_kg']['to']->timezone(config('gym.timezone'))->format('d M Y') }} @else Belum ada pengukuran @endisset</small>
        </div>

        <div class="m-stat-card" style="padding:16px;border-radius:12px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);">
            <small class="muted" style="display:block;margin-bottom:4px;">BODY FAT</small>
            <strong style="font-size:1.6rem;color:#f3f4f6;">{{ isset($measurements['body_fat_percentage']) ? $measurements['body_fat_percentage']['latest'].'%' : '—' }}</strong>
            <small class="muted" style="display:block;margin-top:4px;">Massa otot: {{ isset($measurements['muscle_mass_kg']) ? $measurements['muscle_mass_kg']['latest'].' kg' : '—' }}</small>
        </div>

        <div class="m-stat-card" style="padding:16px;border-radius:12px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);">
            <small class="muted" style="display:block;margin-bottom:4px;">INDEKS BMI</small>
            <strong style="font-size:1.6rem;color:#10b981;">{{ $latest->bmi ?? '—' }}</strong>
            <small class="muted" style="display:block;margin-top:4px;">{{ $latest->bmi_category ?? 'Belum ada data tinggi' }}</small>
        </div>

        <div class="m-stat-card" style="padding:16px;border-radius:12px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);">
            <small class="muted" style="display:block;margin-bottom:4px;">TOTAL LAPORAN TERCATAT</small>
            <strong style="font-size:1.6rem;color:#f59e0b;">{{ $records->total() }} Laporan</strong>
            <small class="muted" style="display:block;margin-top:4px;">Evaluasi oleh trainer</small>
        </div>
    </section>
@endif

<section class="m-panel" style="padding:24px;border-radius:16px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.08);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
            <h2>Riwayat Evaluasi Latihan</h2>
            <p class="muted">Catatan program, beban latihan, dan arahan nutrisi.</p>
        </div>
        <span class="badge positive">Tercatat Resmi</span>
    </div>

    @forelse($records as $r)
        <article style="padding:20px;border-radius:12px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:14px;border-bottom:1px solid rgba(255,255,255,0.08);padding-bottom:12px;">
                <div>
                    <strong style="font-size:1.1rem;color:#fff;">
                        Sesi Tanggal {{ $r->recorded_at->timezone(config('gym.timezone'))->format('d F Y, H:i') }}
                    </strong>
                    <div class="muted" style="font-size:0.85rem;margin-top:2px;">
                        Trainer Pelatih: <strong>{{ $r->trainer->name }}</strong>
                        @if($r->session)
                            · Kelas: {{ $r->session->title }}
                        @endif
                    </div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    @if($r->weight_kg)
                        <span class="badge neutral">BB: {{ $r->weight_kg }} kg</span>
                    @endif
                    @if($r->body_fat_percentage)
                        <span class="badge neutral">Fat: {{ $r->body_fat_percentage }}%</span>
                    @endif
                    @if($r->bmi)
                        <span class="badge positive">BMI: {{ $r->bmi }}</span>
                    @endif
                </div>
            </div>

            <p><strong>{{ $r->program_name ?? 'Evaluasi latihan' }}</strong> · {{ $r->activity ? ucfirst($r->activity) : 'Catatan lama' }}</p>
            <a class="button secondary" href="{{ route('members.progress.show', [$member, $r]) }}">Lihat laporan lengkap</a>
            @include('gym.progress-exercises', ['record' => $r])
            {{-- Ringkasan Pengukuran Fisik jika ada --}}
            @if($r->chest_cm || $r->waist_cm || $r->hips_cm || $r->arms_cm)
                <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:0.85rem;margin-bottom:14px;padding:8px 12px;background:rgba(255,255,255,0.03);border-radius:6px;">
                    @if($r->chest_cm) <span>Dada: <strong>{{ $r->chest_cm }} cm</strong></span> @endif
                    @if($r->waist_cm) <span>Pinggang: <strong>{{ $r->waist_cm }} cm</strong></span> @endif
                    @if($r->hips_cm) <span>Pinggul: <strong>{{ $r->hips_cm }} cm</strong></span> @endif
                    @if($r->arms_cm) <span>Lengan: <strong>{{ $r->arms_cm }} cm</strong></span> @endif
                </div>
            @endif

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;">
                <div>
                    <h4 style="font-size:0.85rem;color:#9ca3af;text-transform:uppercase;margin-bottom:4px;">Program &amp; Catatan Latihan</h4>
                    <p style="margin:0;line-height:1.6;white-space:pre-line;">{{ $r->workout_summary }}</p>
                </div>

                @if($r->coach_notes)
                    <div>
                        <h4 style="font-size:0.85rem;color:#9ca3af;text-transform:uppercase;margin-bottom:4px;">Saran / Evaluasi Trainer</h4>
                        <p style="margin:0;line-height:1.6;white-space:pre-line;color:#e5e7eb;">{{ $r->coach_notes }}</p>
                    </div>
                @endif

                @if($r->diet_summary)
                    <div>
                        <h4 style="font-size:0.85rem;color:#9ca3af;text-transform:uppercase;margin-bottom:4px;">Evaluasi Pola Makan / Nutrisi</h4>
                        <p style="margin:0;line-height:1.6;white-space:pre-line;">{{ $r->diet_summary }}</p>
                    </div>
                @endif

                @if($r->next_goals)
                    <div>
                        <h4 style="font-size:0.85rem;color:#9ca3af;text-transform:uppercase;margin-bottom:4px;">Target Sesi Berikutnya</h4>
                        <p style="margin:0;line-height:1.6;white-space:pre-line;color:#f59e0b;">🎯 {{ $r->next_goals }}</p>
                    </div>
                @endif
            </div>
        </article>
    @empty
        <div class="empty" style="text-align:center;padding:48px 16px;">
            <p>Belum ada catatan progres atau evaluasi latihan yang dicatat oleh Personal Trainer Anda.</p>
            <p class="muted">Trainer akan mengisi catatan perkembangan Anda setelah sesi latihan selesai.</p>
        </div>
    @endforelse

    <div style="margin-top:20px;">
        {{ $records->links() }}
    </div>
</section>
@endsection
