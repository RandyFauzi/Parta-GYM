@extends('layouts.app')
@section('title','Paket membership')
@section('content')
<style>
/* Modern "Card On Top Of Card" Design (matching reference mockup) */
.plan-grid-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 30px;
    align-items: stretch;
    margin-bottom: 40px;
}

/* Base / Outer Card */
.pricing-outer-card {
    background: #141416;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 32px;
    padding: 16px 16px 28px 16px;
    box-shadow: 0 24px 50px -12px rgba(0, 0, 0, 0.7);
    display: flex;
    flex-direction: column;
    position: relative;
    isolation: isolate;
    color: #f4f4f5;
    transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.22s ease, box-shadow 0.22s ease;
}
.pricing-outer-card:hover {
    transform: translateY(-4px);
    border-color: rgba(255, 255, 255, 0.18);
    box-shadow: 0 32px 64px -14px rgba(0, 0, 0, 0.85);
}

/* Inner Floating Card (Card di Atas Card) */
.pricing-inner-card {
    background: linear-gradient(180deg, #27272c 0%, #1c1c20 100%);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 24px;
    padding: 24px 22px 20px 22px;
    box-shadow: 0 14px 30px -6px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.18);
    display: flex;
    flex-direction: column;
    position: relative;
    margin-bottom: 8px;
}

/* Top Header Bar */
.pricing-card-topbar {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 18px;
}

.pricing-title-area {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
    flex: 1;
}

.pricing-title-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.pricing-badge-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #e4e4e7;
    flex-shrink: 0;
}
.pricing-badge-icon svg {
    width: 18px;
    height: 18px;
}

/* Judul Paket - Sangat Jelas, Menonjol, dan Tidak Terpotong */
.pricing-plan-name {
    font-size: 1.22rem;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: -0.3px;
    line-height: 1.25;
    margin: 0;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
    word-break: break-word;
}

.pricing-plan-sep {
    color: #71717a;
    font-size: 0.85rem;
    font-weight: 300;
}

.pricing-plan-category {
    font-size: 0.82rem;
    color: #a1a1aa;
    font-weight: 500;
}

/* Header Right: Pill & 3-Dots */
.pricing-actions-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
    margin-top: -2px;
}

.pricing-pill-glow {
    display: inline-flex;
    align-items: center;
    padding: 5px 13px;
    border-radius: 9999px;
    font-size: 0.72rem;
    font-weight: 600;
    color: #ffffff;
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.5), rgba(168, 85, 247, 0.5));
    border: 1px solid rgba(244, 114, 182, 0.5);
    box-shadow: 0 0 18px rgba(236, 72, 153, 0.3);
    white-space: nowrap;
    letter-spacing: 0.2px;
}

.pricing-pill-ghost {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 0.72rem;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.08);
    color: #d4d4d8;
    border: 1px solid rgba(255, 255, 255, 0.12);
    white-space: nowrap;
}

/* 3-Dots Menu */
.pricing-menu-wrap {
    position: relative;
    display: inline-block;
}
.pricing-menu-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid transparent;
    background: rgba(255, 255, 255, 0.05);
    color: #d4d4d8;
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
    list-style: none;
}
.pricing-menu-btn::-webkit-details-marker,
.pricing-menu-btn::marker {
    display: none !important;
}
.pricing-menu-btn:hover,
.pricing-menu-btn:focus-visible {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.14);
    border-color: rgba(255, 255, 255, 0.18);
}
.pricing-dropdown-box {
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    min-width: 165px;
    background: #242429;
    border: 1px solid rgba(255, 255, 255, 0.16);
    border-radius: 14px;
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.7);
    padding: 6px;
    z-index: 60;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.pricing-dropdown-item {
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
.pricing-dropdown-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
}
.pricing-dropdown-item.is-danger {
    color: #f87171;
}
.pricing-dropdown-item.is-danger:hover {
    background: rgba(239, 68, 68, 0.18);
    color: #fca5a5;
}
.pricing-dropdown-item svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

/* Price Big Block */
.pricing-price-row {
    display: flex;
    align-items: baseline;
    gap: 4px;
    margin: 14px 0 20px;
    flex-wrap: wrap;
}
.pricing-curr-symbol {
    font-size: 1.35rem;
    font-weight: 700;
    color: #e4e4e7;
    align-self: flex-start;
    margin-top: 4px;
}
.pricing-number {
    font-size: clamp(2.3rem, 3.4vw, 2.9rem);
    font-weight: 800;
    line-height: 1;
    color: #ffffff;
    letter-spacing: -2px;
}
.pricing-duration-label {
    font-size: 0.88rem;
    color: #9ca3af;
    font-weight: 500;
    margin-left: 6px;
}

/* High Contrast Pill CTA Button ("Buy" style) */
.pricing-pill-cta {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 48px;
    padding: 12px 24px;
    border-radius: 9999px;
    background: #ffffff !important;
    color: #0c0c0e !important;
    font-size: 0.96rem;
    font-weight: 700;
    text-decoration: none;
    border: 0;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35);
    transition: transform 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
}
.pricing-pill-cta:hover {
    background: #f4f4f5 !important;
    transform: translateY(-1px);
    box-shadow: 0 6px 22px rgba(255, 255, 255, 0.22);
    color: #000000 !important;
}
.pricing-pill-cta:active {
    transform: translateY(0);
}

/* Lower Section: Feature Checklist */
.pricing-bottom-section {
    padding: 14px 10px 4px 10px;
    display: flex;
    flex-direction: column;
}

.pricing-features-wrap {
    display: flex;
    flex-direction: column;
    gap: 13px;
    margin: 0;
    padding: 0;
    list-style: none;
}
.pricing-feature-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    font-size: 0.88rem;
    color: #d4d4d8;
    line-height: 1.45;
}
.pricing-check-bullet {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 19px;
    height: 19px;
    border-radius: 50%;
    background: #27272a;
    color: #ffffff;
    flex-shrink: 0;
    margin-top: 1px;
}
.pricing-check-bullet svg {
    width: 12px;
    height: 12px;
    stroke-width: 2.5;
}

/* Divider with Plus Badge */
.pricing-divider-plus {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    margin: 18px 0 14px;
}
.pricing-divider-plus::before {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    height: 1px;
    background: rgba(255, 255, 255, 0.08);
}
.pricing-plus-badge {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #1c1c20;
    border: 1px solid rgba(255, 255, 255, 0.14);
    color: #a1a1aa;
    font-size: 13px;
    font-weight: 600;
    line-height: 1;
}

/* Modal Edit / Mobile Bottom Sheet */
dialog.pricing-sheet-dialog {
    border: 0;
    background: transparent;
    padding: 0;
    color: #f4f4f5;
    max-width: 100vw;
    max-height: 100vh;
    overflow: visible;
}
dialog.pricing-sheet-dialog::backdrop {
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
.pricing-sheet-content {
    background: #19191d;
    border: 1px solid rgba(255, 255, 255, 0.14);
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.65);
    display: flex;
    flex-direction: column;
}
.pricing-sheet-handle {
    display: none;
}
.pricing-sheet-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.pricing-sheet-title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    color: #ffffff;
}
.pricing-sheet-close {
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
.pricing-sheet-close:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.15);
}
.pricing-sheet-body {
    padding: 22px 24px;
    overflow-y: auto;
    max-height: 70vh;
}
.pricing-sheet-footer {
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
    dialog.pricing-sheet-dialog {
        margin: auto;
    }
    .pricing-sheet-content {
        width: 580px;
        max-width: 90vw;
        border-radius: 26px;
    }
}

/* Mobile View: Bottom Sheet Tab Drawer */
@media (max-width: 768px) {
    dialog.pricing-sheet-dialog {
        position: fixed;
        inset: auto 0 0 0;
        width: 100%;
        max-width: 100%;
        margin: 0;
        bottom: 0;
    }
    dialog.pricing-sheet-dialog[open] {
        display: block;
        animation: slideUpSheet 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    @keyframes slideUpSheet {
        from { transform: translateY(100%); }
        to { transform: translateY(0); }
    }
    .pricing-sheet-content {
        width: 100%;
        max-height: 88vh;
        border-radius: 28px 28px 0 0;
        border-bottom: 0;
        border-left: 0;
        border-right: 0;
        padding-bottom: max(16px, env(safe-area-inset-bottom));
    }
    .pricing-sheet-handle {
        display: block;
        width: 44px;
        height: 5px;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.28);
        margin: 10px auto 4px;
    }
    .pricing-sheet-header { padding: 12px 20px 14px; }
    .pricing-sheet-body { padding: 16px 20px; max-height: 65vh; }
    .pricing-sheet-footer { padding: 14px 20px; }
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
        {{-- Base / Outer Card --}}
        <section class="pricing-outer-card">
            {{-- Inner Elevated Card (Card di Atas Card) --}}
            <div class="pricing-inner-card">
                {{-- Header: Icon + Judul Paket Jelas + Badge + 3-Dots Action Menu --}}
                <header class="pricing-card-topbar">
                    <div class="pricing-title-area">
                        <div class="pricing-title-row">
                            <span class="pricing-badge-icon" title="{{ $plan->plan_type_label }}">
                                <x-icon name="{{ $plan->plan_type === 'pt' ? 'users' : ($plan->plan_type === 'pilates' ? 'layers' : 'grid') }}"/>
                            </span>
                            <h2 class="pricing-plan-name">{{ $plan->name }}</h2>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;margin-top:2px;">
                            <span class="pricing-plan-category">{{ $plan->plan_type_label }}</span>
                            <span class="pricing-plan-sep">·</span>
                            <span class="pricing-plan-category" style="color:#71717a;">{{ $plan->duration_value }} {{ ['day'=>'hari','month'=>'bulan','year'=>'tahun'][$plan->duration_unit] }}</span>
                        </div>
                    </div>

                    <div class="pricing-actions-right">
                        @if($plan->plan_type === 'pt' || $loop->first)
                            <span class="pricing-pill-glow">Paling Populer</span>
                        @elseif(!$plan->is_active)
                            <span class="pricing-pill-ghost" style="color:#ef4444;border-color:rgba(239,68,68,0.3);">Nonaktif</span>
                        @endif

                        {{-- 3-Dots Vertical Action Menu --}}
                        <details class="pricing-menu-wrap" data-plan-menu>
                            <summary class="pricing-menu-btn" aria-label="Menu paket {{ $plan->name }}" title="Opsi paket">
                                <x-icon name="more-vertical"/>
                            </summary>
                            <div class="pricing-dropdown-box">
                                <button type="button" class="pricing-dropdown-item" onclick="openPlanModal('modal-edit-{{ $plan->id }}'); this.closest('details').removeAttribute('open');">
                                    <x-icon name="edit"/> Edit Paket
                                </button>
                                <form action="{{ route('plans.destroy', $plan) }}" method="post" onsubmit="return confirm('Hapus / nonaktifkan paket {{ $plan->name }}?')" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="pricing-dropdown-item is-danger">
                                        <x-icon name="trash"/> Hapus Paket
                                    </button>
                                </form>
                            </div>
                        </details>
                    </div>
                </header>

                {{-- Big Price Typography --}}
                <div class="pricing-price-row">
                    <span class="pricing-curr-symbol">Rp</span>
                    <span class="pricing-number">{{ number_format($plan->price_idr, 0, ',', '.') }}</span>
                    <span class="pricing-duration-label">/ {{ $plan->duration_value }} {{ ['day'=>'hari','month'=>'bulan','year'=>'tahun'][$plan->duration_unit] }}</span>
                </div>

                {{-- High Contrast Pill CTA Button ("Buy" style) --}}
                <a href="{{ route('members.new', ['plan_id' => $plan->id]) }}" class="pricing-pill-cta">
                    Pilih Paket Ini
                </a>
            </div>

            {{-- Bottom Section (Inside Outer Card): Feature Checklist --}}
            <div class="pricing-bottom-section">
                <ul class="pricing-features-wrap">
                    <li class="pricing-feature-row">
                        <span class="pricing-check-bullet"><x-icon name="check"/></span>
                        <span>{{ $plan->visit_limit ? 'Kuota '.$plan->visit_limit.' Sesi Latihan' : 'Akses Tanpa Batas Sesi' }}</span>
                    </li>
                    <li class="pricing-feature-row">
                        <span class="pricing-check-bullet"><x-icon name="check"/></span>
                        <span>Masa Aktif: {{ $plan->duration_value }} {{ ['day'=>'Hari','month'=>'Bulan','year'=>'Tahun'][$plan->duration_unit] }}</span>
                    </li>
                    <li class="pricing-feature-row">
                        <span class="pricing-check-bullet"><x-icon name="check"/></span>
                        <span>Kategori Fasilitas: {{ $plan->plan_type_label }}</span>
                    </li>
                    @if($plan->plan_type === 'pt')
                        <li class="pricing-feature-row">
                            <span class="pricing-check-bullet"><x-icon name="check"/></span>
                            <span>Pendampingan Personal Trainer 1-on-1</span>
                        </li>
                    @endif
                    <li class="pricing-feature-row">
                        <span class="pricing-check-bullet"><x-icon name="check"/></span>
                        <span>Status: {{ $plan->is_active ? 'Aktif & Siap Digunakan' : 'Nonaktif' }}</span>
                    </li>
                </ul>

                {{-- Optional Divider with Plus for Extra Benefit / Description --}}
                @if($plan->description)
                    <div class="pricing-divider-plus">
                        <span class="pricing-plus-badge">+</span>
                    </div>
                    <div class="pricing-feature-row" style="color:#a1a1aa;font-size:0.83rem;">
                        <span class="pricing-check-bullet" style="background:rgba(255,255,255,0.08);"><x-icon name="check"/></span>
                        <span>{{ $plan->description }}</span>
                    </div>
                @endif
            </div>

            {{-- Edit Dialog Modal (Desktop: Center Pop-up | Mobile: Bottom Sheet Tab) --}}
            <dialog id="modal-edit-{{ $plan->id }}" class="pricing-sheet-dialog" aria-labelledby="modal-title-{{ $plan->id }}">
                <div class="pricing-sheet-content">
                    <div class="pricing-sheet-handle" aria-hidden="true"></div>
                    <div class="pricing-sheet-header">
                        <h3 id="modal-title-{{ $plan->id }}" class="pricing-sheet-title">Edit Paket: {{ $plan->name }}</h3>
                        <button type="button" class="pricing-sheet-close" onclick="closePlanModal('modal-edit-{{ $plan->id }}')" aria-label="Tutup pop-up">
                            <x-icon name="x"/>
                        </button>
                    </div>
                    <form action="{{ route('plans.update', $plan) }}" method="post">
                        @csrf
                        @method('PUT')
                        <div class="pricing-sheet-body">
                            @include('gym.plan-fields', ['plan' => $plan])
                        </div>
                        <div class="pricing-sheet-footer">
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
    if (e.target.tagName === 'DIALOG' && e.target.classList.contains('pricing-sheet-dialog')) {
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
