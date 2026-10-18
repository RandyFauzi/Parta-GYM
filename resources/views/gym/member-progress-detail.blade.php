@extends(auth()->user()->role==='member'?'layouts.member':'layouts.app')
@section('title', 'Detail Progres Latihan')
@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">EVALUASI PERSONAL TRAINER</span>
        <h1>Progres: {{ $member->full_name }}<span class="gold">.</span></h1>
        <p>Sesi tanggal {{ $record->recorded_at->timezone(config('gym.timezone'))->format('d F Y, H:i') }} · Pelatih: {{ $record->trainer->name }}</p>
    </div>
    <div style="display:flex;gap:8px;">
        <button class="button secondary" data-print>Cetak Laporan</button>
        <a class="button" href="{{ (auth()->user()->role==='member'?route('portal.progress'):(auth()->user()->role==='personal_trainer'?route('coaching.member',$member):route('members.show',$member))) }}">Kembali ke Profil</a>
    </div>
</div>

<section class="panel receipt">
    <div class="receipt-top">
        <div class="brand">SPARTA Gym &amp; Pilates — PT Progress Report</div>
        <span class="badge positive">Tercatat</span>
    </div>

    <div class="two-columns" style="margin-bottom:20px;">
        <div>
            <small>ANGGOTA</small>
            <h2>{{ $member->full_name }}</h2>
            <p>{{ $member->member_number }} · {{ $member->phone }}</p>
        </div>
        <div>
            <small>PELATIH PERSONAL (PT)</small>
            <h2>{{ $record->trainer->name }}</h2>
            <p>{{ $record->trainer->email }}</p>
        </div>
    </div>

    <div style="margin-bottom:24px;">
        <h3>Pengukuran Fisik &amp; Komposisi Tubuh</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-top:12px;">
            <div style="padding:12px;border-radius:8px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);">
                <small class="muted">Berat Badan</small>
                <strong style="display:block;font-size:1.3rem;">{{ $record->weight_kg ? $record->weight_kg.' kg' : '—' }}</strong>
            </div>
            <div style="padding:12px;border-radius:8px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);">
                <small class="muted">Tinggi Badan</small>
                <strong style="display:block;font-size:1.3rem;">{{ $record->height_cm ? $record->height_cm.' cm' : '—' }}</strong>
            </div>
            <div style="padding:12px;border-radius:8px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);">
                <small class="muted">Body Fat</small>
                <strong style="display:block;font-size:1.3rem;">{{ $record->body_fat_percentage ? $record->body_fat_percentage.'%' : '—' }}</strong>
            </div>
            <div style="padding:12px;border-radius:8px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);">
                <small class="muted">Massa Otot</small>
                <strong style="display:block;font-size:1.3rem;">{{ $record->muscle_mass_kg ? $record->muscle_mass_kg.' kg' : '—' }}</strong>
            </div>
            <div style="padding:12px;border-radius:8px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);">
                <small class="muted">Indeks BMI</small>
                <strong style="display:block;font-size:1.3rem;color:#10b981;">{{ $record->bmi ?? '—' }}</strong>
                <small class="muted">{{ $record->bmi_category }}</small>
            </div>
        </div>
    </div>

    @if($record->chest_cm || $record->waist_cm || $record->hips_cm || $record->arms_cm)
        <div style="margin-bottom:24px;">
            <h3>Lingkar Badan</h3>
            <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:8px;">
                @if($record->chest_cm) <span>Dada: <strong>{{ $record->chest_cm }} cm</strong></span> @endif
                @if($record->waist_cm) <span>Pinggang: <strong>{{ $record->waist_cm }} cm</strong></span> @endif
                @if($record->hips_cm) <span>Pinggul: <strong>{{ $record->hips_cm }} cm</strong></span> @endif
                @if($record->arms_cm) <span>Lengan: <strong>{{ $record->arms_cm }} cm</strong></span> @endif
            </div>
        </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr;gap:20px;">
        <div style="padding:16px;border-radius:8px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);">
            <h4 style="color:#9ca3af;text-transform:uppercase;font-size:0.85rem;margin-bottom:8px;">Catatan &amp; Program Latihan Sesi Ini</h4>
            <p style="white-space:pre-line;line-height:1.6;margin:0;">{{ $record->workout_summary }}</p>
        </div>

        @if($record->coach_notes)
            <div style="padding:16px;border-radius:8px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);">
                <h4 style="color:#9ca3af;text-transform:uppercase;font-size:0.85rem;margin-bottom:8px;">Saran &amp; Arahan Khusus Pelatih</h4>
                <p style="white-space:pre-line;line-height:1.6;margin:0;">{{ $record->coach_notes }}</p>
            </div>
        @endif

        @if($record->diet_summary)
            <div style="padding:16px;border-radius:8px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);">
                <h4 style="color:#9ca3af;text-transform:uppercase;font-size:0.85rem;margin-bottom:8px;">Evaluasi Pola Makan / Nutrisi</h4>
                <p style="white-space:pre-line;line-height:1.6;margin:0;">{{ $record->diet_summary }}</p>
            </div>
        @endif

        @if($record->next_goals)
            <div style="padding:16px;border-radius:8px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);">
                <h4 style="color:#f59e0b;text-transform:uppercase;font-size:0.85rem;margin-bottom:8px;">Target Sesi Berikutnya</h4>
                <p style="white-space:pre-line;line-height:1.6;margin:0;color:#f3f4f6;">🎯 {{ $record->next_goals }}</p>
            </div>
        @endif
    </div>
</section>
@endsection
