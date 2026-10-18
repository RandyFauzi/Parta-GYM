@extends('layouts.app')
@section('title','Paket membership')
@section('content')
<style>
/* Modern "Card On Top Of Card" Design (Pixel-perfect reference mockup) */
.plan-grid-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
    gap: 26px;
    align-items: stretch;
    margin-bottom: 40px;
}

/* Base Outer Card */
.pricing-outer-card {
    background: #111113;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 28px;
    padding: 12px 12px 24px 12px;
    box-shadow: 0 20px 48px -12px rgba(0, 0, 0, 0.7);
    display: flex;
    flex-direction: column;
    position: relative;
    isolation: isolate;
    color: #f4f4f5;
    transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.2s ease, box-shadow 0.2s ease;
}
.pricing-outer-card:hover {
    transform: translateY(-3px);
    border-color: rgba(255, 255, 255, 0.16);
    box-shadow: 0 28px 56px -12px rgba(0, 0, 0, 0.85);
}

/* Inner Elevated Card (Card di Atas Card) */
.pricing-inner-card {
    background: linear-gradient(180deg, #242428 0%, #17171a 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 22px 20px 20px 20px;
    box-shadow: 0 10px 24px -4px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.14);
    display: flex;
    flex-direction: column;
    position: relative;
}

/* Baris 1 Header: Kategori / Target di kiri, Badge & Titik 3 di kanan */
.pricing-top-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    min-height: 28px;
}

.pricing-kicker-group {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #a1a1aa;
    font-size: 0.82rem;
    font-weight: 500;
}
.pricing-kicker-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #d4d4d8;
}
.pricing-kicker-icon svg {
    width: 16px;
    height: 16px;
}
.pricing-kicker-sep {
    color: #52525b;
}

.pricing-actions-group {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

/* Badge Aurora Sunset Glow ("Most Popular") */
.pricing-pill-glow {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 0.72rem;
    font-weight: 600;
    color: #ffffff;
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.5), rgba(168, 85, 247, 0.5));
    border: 1px solid rgba(244, 114, 182, 0.45);
    box-shadow: 0 0 16px rgba(236, 72, 153, 0.28);
    white-space: nowrap;
    letter-spacing: 0.2px;
}

.pricing-pill-ghost {
    display: inline-flex;
    align-items: center;
    padding: 3px 9px;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.08);
    color: #d4d4d8;
    border: 1px solid rgba(255, 255, 255, 0.12);
    white-space: nowrap;
}

/* Tombol Titik 3 Vertikal */
.pricing-menu-wrap {
    position: relative;
    display: inline-block;
}
.pricing-menu-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 7px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.04);
    color: #a1a1aa;
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
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.18);
}
.pricing-dropdown-box {
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    min-width: 160px;
    background: #202025;
    border: 1px solid rgba(255, 255, 255, 0.16);
    border-radius: 12px;
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.7);
    padding: 5px;
    z-index: 60;
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.pricing-dropdown-item {
    display: flex;
    align-items: center;
    gap: 9px;
    width: 100%;
    padding: 8px 11px;
    border-radius: 7px;
    border: 0;
    background: transparent;
    color: #e4e4e7;
    font-size: 0.84rem;
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
    width: 15px;
    height: 15px;
    flex-shrink: 0;
}

/* Baris 2 Header: Nama Paket (Jelas, Menonjol, Bebas Wrap Rapi) */
.pricing-plan-title {
    font-size: 1.32rem;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: -0.4px;
    line-height: 1.25;
    margin: 0 0 16px 0;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
    word-break: break-word;
}

/* Blok Harga */
.pricing-price-row {
    display: flex;
    align-items: baseline;
    gap: 3px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.pricing-curr-symbol {
    font-size: 1.3rem;
    font-weight: 700;
    color: #e4e4e7;
    align-self: flex-start;
    margin-top: 4px;
}
.pricing-number {
    font-size: clamp(2.3rem, 3.4vw, 2.85rem);
    font-weight: 800;
    line-height: 1;
    color: #ffffff;
    letter-spacing: -1.8px;
}
.pricing-duration-label {
    font-size: 0.85rem;
    color: #8e8e93;
    font-weight: 500;
    margin-left: 6px;
}

/* Tombol CTA "Pilih Paket Ini" (Pure Solid White Pill) */
.pricing-pill-cta,
a.pricing-pill-cta {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 100% !important;
    min-height: 48px !important;
    padding: 12px 24px !important;
    border-radius: 9999px !important;
    background: #ffffff !important;
    color: #000000 !important;
    font-size: 0.95rem !important;
    font-weight: 700 !important;
    text-decoration: none !important;
    border: none !important;
    cursor: pointer !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35) !important;
    transition: transform 0.15s ease, background 0.15s ease, box-shadow 0.15s ease !important;
}
.pricing-pill-cta:hover,
a.pricing-pill-cta:hover {
    background: #f4f4f5 !important;
    color: #000000 !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 6px 22px rgba(255, 255, 255, 0.25) !important;
}
.pricing-pill-cta:active,
a.pricing-pill-cta:active {
    transform: translateY(0) !important;
}

/* Bagian Bawah: Feature Checklist */
.pricing-bottom-section {
    padding: 16px 8px 4px 8px;
    display: flex;
    flex-direction: column;
}

.pricing-features-wrap {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin: 0;
    padding: 0;
    list-style: none;
}
.pricing-feature-row {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    font-size: 0.86rem;
    color: #d4d4d8;
    line-height: 1.45;
}
.pricing-check-bullet {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #27272a;
    color: #ffffff;
    flex-shrink: 0;
    margin-top: 1px;
}
.pricing-check-bullet svg {
    width: 11px;
    height: 11px;
    stroke-width: 2.5;
}

/* Divider Plus */
.pricing-divider-plus {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    margin: 16px 0 12px;
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
    background: #19191d;
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
                {{-- Baris 1: Ikon + Kategori/Durasi di kiri | Badge & Titik 3 di kanan --}}
                <div class="pricing-top-meta">
                    <div class="pricing-kicker-group">
                        <span class="pricing-kicker-icon" title="{{ $plan->plan_type_label }}">
                            <x-icon name="{{ $plan->plan_type === 'pt' ? 'users' : ($plan->plan_type === 'pilates' ? 'layers' : 'grid') }}"/>
                        </span>
                        <span>{{ $plan->plan_type_label }}</span>
                        <span class="pricing-kicker-sep">·</span>
                        <span style="color:#71717a;">{{ $plan->duration_value }} {{ ['day'=>'hari','month'=>'bulan','year'=>'tahun'][$plan->duration_unit] }}</span>
                    </div>

                    <div class="pricing-actions-group">
                        {{-- Hanya paket pertama (unggulan) yang mendapat badge "Paling Populer" agar tidak redundant --}}
                        @if($loop->first)
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
                </div>

                {{-- Baris 2: Judul Paket (Sangat Jelas, Penuh, Tebal & Menonjol) --}}
                <h2 class="pricing-plan-title">{{ $plan->name }}</h2>

                {{-- Big Price Row --}}
                <div class="pricing-price-row">
                    <span class="pricing-curr-symbol">Rp</span>
                    <span class="pricing-number">{{ number_format($plan->price_idr, 0, ',', '.') }}</span>
                    <span class="pricing-duration-label">/ {{ $plan->duration_value }} {{ ['day'=>'hari','month'=>'bulan','year'=>'tahun'][$plan->duration_unit] }}</span>
                </div>

                {{-- High Contrast Pure White Pill CTA Button --}}
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

                {{-- Divider Plus for Extra Benefit / Description --}}
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
