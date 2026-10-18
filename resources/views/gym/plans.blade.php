@extends('layouts.app')
@section('title','Paket membership')
@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">MEMBERSHIP &amp; PT</span>
        <h1>Paket Gym &amp; PT<span class="gold">.</span></h1>
        <p>Kelola harga, durasi, dan kuota sesi paket.</p>
    </div>
    <div>
        <a href="#form-buat-paket" class="button secondary compact">
            <x-icon name="plus"/> Buat Paket Baru
        </a>
    </div>
</div>

<div class="plan-grid">
    @foreach($plans as $plan)
        <section class="pricing-plan-card">
            {{-- Card Header: Icon + Titles + Badge + 3-Dots Action Menu --}}
            <header class="pricing-card-header">
                <div class="pricing-card-title-group">
                    <div class="pricing-card-icon" title="{{ $plan->plan_type_label }}">
                        <x-icon name="{{ $plan->plan_type === 'pt' ? 'users' : ($plan->plan_type === 'pilates' ? 'layers' : 'grid') }}"/>
                    </div>
                    <div class="pricing-card-titles">
                        <h2 class="pricing-card-name" title="{{ $plan->name }}">{{ $plan->name }}</h2>
                        <span class="pricing-card-subtitle">· {{ $plan->plan_type_label }}</span>
                    </div>
                </div>

                <div class="pricing-card-actions">
                    @if($plan->plan_type === 'pt' || $loop->first)
                        <span class="pricing-pill-popular">Paling Populer</span>
                    @elseif(!$plan->is_active)
                        <span class="pricing-pill-badge" style="color:#ef4444;border-color:rgba(239,68,68,0.3);">Nonaktif</span>
                    @endif

                    {{-- 3-Dots Vertical Action Menu --}}
                    <details class="plan-menu-container" data-plan-menu>
                        <summary class="plan-menu-trigger" aria-label="Menu paket {{ $plan->name }}" title="Opsi paket">
                            <x-icon name="more-vertical"/>
                        </summary>
                        <div class="plan-menu-dropdown">
                            <button type="button" class="plan-menu-item" onclick="openPlanModal('modal-edit-{{ $plan->id }}'); this.closest('details').removeAttribute('open');">
                                <x-icon name="edit"/> Edit Paket
                            </button>
                            <form action="{{ route('plans.destroy', $plan) }}" method="post" onsubmit="return confirm('Hapus / nonaktifkan paket {{ $plan->name }}?')" style="margin:0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="plan-menu-item danger">
                                    <x-icon name="trash"/> Hapus Paket
                                </button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>

            {{-- Big Price Area --}}
            <div class="pricing-hero-price">
                <span class="pricing-currency">Rp</span>
                <span class="pricing-amount">{{ number_format($plan->price_idr, 0, ',', '.') }}</span>
                <span class="pricing-duration">/ {{ $plan->duration_value }} {{ ['day'=>'hari','month'=>'bulan','year'=>'tahun'][$plan->duration_unit] }}</span>
            </div>

            {{-- High Contrast Action Button --}}
            <a href="{{ route('members.new', ['plan_id' => $plan->id]) }}" class="pricing-cta-button">
                Daftar dengan Paket Ini
            </a>

            {{-- Feature Checklist List --}}
            <ul class="pricing-features-list">
                <li class="pricing-feature-item">
                    <span class="pricing-check-icon"><x-icon name="check"/></span>
                    <span>{{ $plan->visit_limit ? 'Kuota '.$plan->visit_limit.' Sesi Latihan' : 'Akses Tanpa Batas Sesi' }}</span>
                </li>
                <li class="pricing-feature-item">
                    <span class="pricing-check-icon"><x-icon name="check"/></span>
                    <span>Masa Aktif: {{ $plan->duration_value }} {{ ['day'=>'Hari','month'=>'Bulan','year'=>'Tahun'][$plan->duration_unit] }}</span>
                </li>
                <li class="pricing-feature-item">
                    <span class="pricing-check-icon"><x-icon name="check"/></span>
                    <span>Kategori Fasilitas: {{ $plan->plan_type_label }}</span>
                </li>
                @if($plan->plan_type === 'pt')
                    <li class="pricing-feature-item">
                        <span class="pricing-check-icon"><x-icon name="check"/></span>
                        <span>Pendampingan Personal Trainer 1-on-1</span>
                    </li>
                @endif
                <li class="pricing-feature-item">
                    <span class="pricing-check-icon"><x-icon name="check"/></span>
                    <span>Status: {{ $plan->is_active ? 'Aktif & Siap Digunakan' : 'Nonaktif' }}</span>
                </li>
                @if($plan->description)
                    <li class="pricing-feature-item" style="color:#9ca3af;font-size:0.82rem;">
                        <span class="pricing-check-icon" style="background:rgba(255,255,255,0.08);"><x-icon name="check"/></span>
                        <span>{{ $plan->description }}</span>
                    </li>
                @endif
            </ul>

            {{-- Edit Dialog Modal (Desktop: Center Pop-up | Mobile: Bottom Sheet Tab) --}}
            <dialog id="modal-edit-{{ $plan->id }}" class="plan-sheet-modal" aria-labelledby="modal-title-{{ $plan->id }}">
                <div class="plan-modal-container">
                    <div class="sheet-handle-bar" aria-hidden="true"></div>
                    <div class="plan-modal-header">
                        <h3 id="modal-title-{{ $plan->id }}" class="plan-modal-title">Edit Paket: {{ $plan->name }}</h3>
                        <button type="button" class="plan-modal-close" onclick="closePlanModal('modal-edit-{{ $plan->id }}')" aria-label="Tutup pop-up">
                            <x-icon name="x"/>
                        </button>
                    </div>
                    <form action="{{ route('plans.update', $plan) }}" method="post">
                        @csrf
                        @method('PUT')
                        <div class="plan-modal-body">
                            @include('gym.plan-fields', ['plan' => $plan])
                        </div>
                        <div class="plan-modal-footer">
                            <button type="button" class="button secondary compact" onclick="closePlanModal('modal-edit-{{ $plan->id }}')">
                                Batal
                            </button>
                            <button type="submit" class="button compact">
                                <x-icon name="check"/> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </dialog>
        </section>
    @endforeach
</div>

<section id="form-buat-paket" class="panel form-panel spaced">
    <div class="panel-heading">
        <div>
            <h2>Buat Paket Baru</h2>
            <p>Atur paket Gym reguler atau paket Personal Trainer (PT).</p>
        </div>
    </div>

    {{-- Rekomendasi Format Paket PT --}}
    <div style="margin-bottom:20px;padding:14px 18px;border-radius:12px;background:#131315;border:1px solid var(--border);">
        <strong style="color:var(--gold);font-size:12px;display:block;margin-bottom:6px;">Contoh Format Paket PT:</strong>
        <ul style="margin:0;padding-left:18px;font-size:12px;color:var(--muted);line-height:1.8;">
            <li><strong>PT 30 Hari:</strong> Rp 700.000 (1 Bulan, tanpa batas sesi)</li>
            <li><strong>PT 15 Sesi:</strong> Rp 500.000 (2 Bulan, kuota 15 sesi)</li>
            <li><strong>PT 10 Sesi:</strong> Rp 250.000 (1 Bulan, kuota 10 sesi)</li>
        </ul>
    </div>

    <form action="{{ route('plans.store') }}" method="post">
        @csrf
        @include('gym.plan-fields',['plan'=>null])
        <button class="button"><x-icon name="plus"/> Tambah paket</button>
    </form>
</section>

<script>
function openPlanModal(modalId) {
    const dialog = document.getElementById(modalId);
    if (dialog && typeof dialog.showModal === 'function') {
        dialog.showModal();
        document.body.style.overflow = 'hidden';
    }
}

function closePlanModal(modalId) {
    const dialog = document.getElementById(modalId);
    if (dialog && typeof dialog.close === 'function') {
        dialog.close();
        document.body.style.overflow = '';
    }
}

// Close dialog when clicking outside modal container (backdrop)
document.addEventListener('click', function(e) {
    if (e.target.tagName === 'DIALOG' && e.target.classList.contains('plan-sheet-modal')) {
        closePlanModal(e.target.id);
    }
});

// Close all open 3-dots menus when clicking anywhere outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('[data-plan-menu]')) {
        document.querySelectorAll('[data-plan-menu][open]').forEach(function(el) {
            el.removeAttribute('open');
        });
    }
});
</script>
@endsection
