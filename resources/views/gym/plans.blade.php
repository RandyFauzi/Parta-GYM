@extends('layouts.app')
@section('title','Paket membership')
@section('content')
<style>
/* Modern Pricing Cards Styles */
.plan-grid-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
    align-items: stretch;
    margin-bottom: 32px;
}

.pricing-plan-card {
    background: #18181b !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    border-radius: 26px !important;
    padding: 28px 24px !important;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.35) !important;
    display: flex;
    flex-direction: column;
    position: relative;
    isolation: isolate;
    color: #f4f4f5;
    transition: transform 0.2s ease, border-color 0.2s ease;
}
.pricing-plan-card:hover {
    border-color: rgba(255, 255, 255, 0.22) !important;
    transform: translateY(-2px);
}
.pricing-plan-card::before {
    display: none !important;
}

/* Card Header */
.pricing-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 22px;
}
.pricing-card-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}
.pricing-card-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.08);
    color: #f4f4f5;
    flex-shrink: 0;
}
.pricing-card-icon svg {
    width: 18px;
    height: 18px;
}
.pricing-card-titles {
    display: flex;
    align-items: baseline;
    gap: 8px;
    flex-wrap: wrap;
    min-width: 0;
}
.pricing-card-name {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: -0.3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pricing-card-subtitle {
    font-size: 0.8rem;
    color: #a1a1aa;
    font-weight: 500;
}

/* Header Right: Badges & Menu */
.pricing-card-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}
.pricing-pill-popular {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 0.72rem;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.4), rgba(249, 115, 22, 0.5));
    border: 1px solid rgba(249, 115, 22, 0.6);
    box-shadow: 0 0 12px rgba(249, 115, 22, 0.25);
    white-space: nowrap;
}
.pricing-pill-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 0.72rem;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.08);
    color: #d1d1d6;
    border: 1px solid rgba(255, 255, 255, 0.12);
    white-space: nowrap;
}

/* 3-Dots Action Menu */
.plan-menu-container {
    position: relative;
    display: inline-block;
}
.plan-menu-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid transparent;
    background: transparent;
    color: #a1a1aa;
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
    list-style: none;
}
.plan-menu-trigger::-webkit-details-marker {
    display: none !important;
}
.plan-menu-trigger::marker {
    display: none !important;
}
.plan-menu-trigger:hover,
.plan-menu-trigger:focus-visible {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.16);
}
.plan-menu-dropdown {
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    min-width: 160px;
    background: #242429;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.6);
    padding: 6px;
    z-index: 50;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.plan-menu-item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 8px 12px;
    border-radius: 8px;
    border: 0;
    background: transparent;
    color: #e4e4e7;
    font-size: 0.85rem;
    font-weight: 500;
    text-align: left;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.15s ease, color 0.15s ease;
}
.plan-menu-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
}
.plan-menu-item.danger {
    color: #f87171;
}
.plan-menu-item.danger:hover {
    background: rgba(239, 68, 68, 0.18);
    color: #fca5a5;
}
.plan-menu-item svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

/* Big Price Display */
.pricing-hero-price {
    display: flex;
    align-items: baseline;
    gap: 6px;
    margin-bottom: 22px;
    flex-wrap: wrap;
}
.pricing-currency {
    font-size: 1.25rem;
    font-weight: 600;
    color: #ffffff;
    align-self: flex-start;
    margin-top: 4px;
}
.pricing-amount {
    font-size: clamp(2rem, 3.2vw, 2.5rem);
    font-weight: 800;
    line-height: 1;
    color: #ffffff;
    letter-spacing: -1.2px;
}
.pricing-duration {
    font-size: 0.88rem;
    color: #a1a1aa;
    font-weight: 500;
    margin-left: 2px;
}

/* High Contrast CTA Button (matching reference) */
.pricing-cta-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    min-height: 48px;
    padding: 12px 20px;
    border-radius: 9999px;
    background: #ffffff !important;
    color: #111113 !important;
    font-size: 0.95rem;
    font-weight: 700;
    text-decoration: none;
    border: 0;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
    transition: transform 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
    margin-bottom: 22px;
}
.pricing-cta-button:hover {
    background: #f4f4f5 !important;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(255, 255, 255, 0.2);
    color: #000000 !important;
}
.pricing-cta-button:active {
    transform: translateY(0);
}

/* Feature Benefits Checklist */
.pricing-features-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin: 0;
    padding: 18px 0 0;
    list-style: none;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}
.pricing-feature-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 0.86rem;
    color: #d1d1d6;
    line-height: 1.45;
}
.pricing-check-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.14);
    color: #ffffff;
    flex-shrink: 0;
    margin-top: 2px;
}
.pricing-check-icon svg {
    width: 12px;
    height: 12px;
    stroke-width: 2.4;
}

/* Pop-up Edit Modal / Mobile Bottom Sheet */
dialog.plan-sheet-modal {
    border: 0;
    background: transparent;
    padding: 0;
    color: #f4f4f5;
    max-width: 100vw;
    max-height: 100vh;
    overflow: visible;
}
dialog.plan-sheet-modal::backdrop {
    background: rgba(0, 0, 0, 0.72);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
}
.plan-modal-container {
    background: #19191d;
    border: 1px solid rgba(255, 255, 255, 0.14);
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.6);
    display: flex;
    flex-direction: column;
}
.sheet-handle-bar {
    display: none;
}
.plan-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.plan-modal-title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    color: #ffffff;
}
.plan-modal-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.06);
    color: #a1a1aa;
    cursor: pointer;
    transition: all 0.15s ease;
}
.plan-modal-close:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.15);
}
.plan-modal-body {
    padding: 22px 24px;
    overflow-y: auto;
    max-height: 70vh;
}
.plan-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(0, 0, 0, 0.15);
}

/* Desktop Modal View (Centered) */
@media (min-width: 769px) {
    dialog.plan-sheet-modal {
        margin: auto;
    }
    .plan-modal-container {
        width: 580px;
        max-width: 90vw;
        border-radius: 24px;
    }
}

/* Mobile View: Bottom Sheet Tab Drawer */
@media (max-width: 768px) {
    dialog.plan-sheet-modal {
        position: fixed;
        inset: auto 0 0 0;
        width: 100%;
        max-width: 100%;
        margin: 0;
        bottom: 0;
    }
    dialog.plan-sheet-modal[open] {
        display: block;
        animation: slideUpSheet 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    @keyframes slideUpSheet {
        from { transform: translateY(100%); }
        to { transform: translateY(0); }
    }
    .plan-modal-container {
        width: 100%;
        max-height: 88vh;
        border-radius: 26px 26px 0 0;
        border-bottom: 0;
        border-left: 0;
        border-right: 0;
        padding-bottom: max(16px, env(safe-area-inset-bottom));
    }
    .sheet-handle-bar {
        display: block;
        width: 44px;
        height: 5px;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.28);
        margin: 10px auto 4px;
    }
    .plan-modal-header { padding: 12px 20px 14px; }
    .plan-modal-body { padding: 16px 20px; max-height: 65vh; }
    .plan-modal-footer { padding: 14px 20px; }
}
</style>

<div class="page-heading">
    <div>
        <span class="eyebrow">MEMBERSHIP &amp; PT</span>
        <h1>Paket Gym &amp; PT<span class="gold">.</span></h1>
        <p>Kelola harga, durasi, dan kuota sesi paket.</p>
    </div>
    <div>
        <a href="{{ route('plans.create') }}" class="button">
            <x-icon name="plus"/> Tambah Paket
        </a>
    </div>
</div>

<div class="plan-grid-container">
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
                    <li class="pricing-feature-item" style="color:#a1a1aa;font-size:0.82rem;">
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
