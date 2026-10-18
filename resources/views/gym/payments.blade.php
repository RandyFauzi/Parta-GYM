@extends('layouts.app')
@section('title','Pembayaran')
@section('content')
<style>
/* Modern Billing & Payments UI (matching user reference mockup) */
.billing-page-wrap {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Toolbar: Tabs di kiri, Search & Export di kanan */
.billing-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    padding: 14px 18px;
    background: #141417;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 20px;
}

.billing-tabs {
    display: flex;
    align-items: center;
    gap: 8px;
}
.billing-tab-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 12px;
    font-size: 0.88rem;
    font-weight: 600;
    color: #a1a1aa;
    text-decoration: none;
    transition: all 0.15s ease;
    border: 1px solid transparent;
}
.billing-tab-link:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.05);
}
.billing-tab-link.is-active {
    color: #ffffff;
    background: #24242a;
    border-color: rgba(255, 255, 255, 0.12);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
}

.billing-controls {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.billing-search-form {
    display: flex;
    align-items: center;
    position: relative;
    min-width: 260px;
}
.billing-search-input {
    width: 100%;
    padding: 9px 14px 9px 38px;
    background: #1c1c20;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: #ffffff;
    font-size: 0.86rem;
    outline: none;
    transition: border-color 0.15s ease;
}
.billing-search-input:focus {
    border-color: var(--gold, #ff812f);
}
.billing-search-icon {
    position: absolute;
    left: 12px;
    color: #71717a;
    pointer-events: none;
    display: flex;
}
.billing-search-icon svg {
    width: 16px;
    height: 16px;
}

/* Modern Data Table */
.billing-table-card {
    background: #151518;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4);
}
.billing-table-wrap {
    width: 100%;
    overflow-x: auto;
}
.billing-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    text-align: left;
}
.billing-table th {
    padding: 16px 20px;
    font-size: 0.72rem;
    font-weight: 700;
    color: #8e8e93;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    background: #18181c;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    white-space: nowrap;
}
.billing-table td {
    padding: 16px 20px;
    font-size: 0.88rem;
    color: #e4e4e7;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    vertical-align: middle;
}
.billing-row {
    transition: background 0.15s ease;
}
.billing-row:hover {
    background: rgba(255, 255, 255, 0.025);
}

/* Column Styles */
.billing-id-cell {
    font-weight: 700;
    font-family: monospace;
    font-size: 0.92rem;
    color: #ffffff;
    white-space: nowrap;
}
.billing-new-tag {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 6px;
    background: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
    border: 1px solid rgba(59, 130, 246, 0.35);
    font-size: 0.68rem;
    font-weight: 700;
    margin-left: 6px;
    vertical-align: middle;
}

.billing-member-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}
.billing-member-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ff812f, #d65a18);
    color: #ffffff;
    font-weight: 700;
    font-size: 0.88rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.billing-member-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.billing-member-name {
    font-weight: 700;
    color: #ffffff;
    text-decoration: none;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.billing-member-name:hover {
    color: var(--gold, #ff812f);
}
.billing-member-sub {
    font-size: 0.76rem;
    color: #8e8e93;
}

.billing-amount-cell {
    font-weight: 800;
    font-size: 0.98rem;
    color: #ffffff;
    letter-spacing: -0.3px;
    white-space: nowrap;
}

/* Badges Status */
.billing-status-badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 12px;
    border-radius: 9999px;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    white-space: nowrap;
}
.billing-status-badge.paid {
    background: rgba(16, 185, 129, 0.16);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.3);
}
.billing-status-badge.unpaid {
    background: rgba(245, 158, 11, 0.16);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

/* Action Controls in Row */
.billing-actions-cell {
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}
.billing-pay-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 9999px;
    background: #ffffff;
    color: #0c0c0e;
    font-size: 0.8rem;
    font-weight: 700;
    border: 0;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
    transition: all 0.15s ease;
}
.billing-pay-btn:hover {
    background: #f4f4f5;
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(255, 255, 255, 0.2);
}

.billing-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.04);
    color: #a1a1aa;
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
}
.billing-icon-btn:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.15);
}
.billing-icon-btn.is-active-expand {
    transform: rotate(180deg);
}

/* Expandable Sub-Row (Persis Esther Howard di Gambar 1) */
.billing-subrow {
    background: #111113;
}
.billing-subrow-inner {
    padding: 14px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    border-left: 3px solid var(--gold, #ff812f);
    margin: 8px 16px 12px 16px;
    background: #19191d;
    border-radius: 12px;
}
.billing-subrow-meta {
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
    font-size: 0.82rem;
    color: #d1d1d6;
}
.billing-subrow-item strong {
    color: #ffffff;
}

/* Slide-Over Payment Drawer (Persis Gambar 2) */
.billing-drawer-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(6px);
    z-index: 100;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.25s ease, visibility 0.25s ease;
}
.billing-drawer-backdrop.is-open {
    opacity: 1;
    visibility: visible;
}
.billing-drawer-panel {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 460px;
    max-width: 100vw;
    background: #161619;
    border-left: 1px solid rgba(255, 255, 255, 0.12);
    box-shadow: -16px 0 48px rgba(0, 0, 0, 0.75);
    z-index: 101;
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}
.billing-drawer-backdrop.is-open .billing-drawer-panel {
    transform: translateX(0);
}

.billing-drawer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 22px 24px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.billing-drawer-title-group {
    display: flex;
    flex-direction: column;
}
.billing-drawer-bill-id {
    font-size: 1.15rem;
    font-weight: 800;
    color: #ffffff;
    font-family: monospace;
}
.billing-drawer-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #a1a1aa;
    cursor: pointer;
    transition: all 0.15s ease;
}
.billing-drawer-close:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.15);
}

.billing-drawer-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Detail Card in Drawer */
.billing-drawer-card {
    background: #1c1c20;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 16px;
}
.billing-card-header-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: #8e8e93;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    margin-bottom: 8px;
    display: block;
}

.billing-breakdown-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    font-size: 0.86rem;
}
.billing-breakdown-row:last-child {
    border-bottom: 0;
}
.billing-breakdown-row.total-row {
    padding-top: 12px;
    margin-top: 4px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 1.05rem;
    font-weight: 800;
    color: #ffffff;
}

/* Method Selector Tiles */
.billing-methods-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}
.billing-method-tile {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 12px;
    background: #222228;
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #ffffff;
    cursor: pointer;
    transition: all 0.15s ease;
    font-size: 0.86rem;
    font-weight: 600;
}
.billing-method-tile:hover {
    background: #2a2a32;
    border-color: rgba(255, 255, 255, 0.2);
}
.billing-method-tile input[type="radio"] {
    accent-color: var(--gold, #ff812f);
}

.billing-drawer-footer {
    padding: 18px 24px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    background: #141416;
}

/* Mobile Bottom Sheet Adaptation for Drawer */
@media (max-width: 768px) {
    .billing-drawer-panel {
        top: auto;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        max-height: 90vh;
        border-radius: 26px 26px 0 0;
        border-left: 0;
        border-top: 1px solid rgba(255, 255, 255, 0.15);
        transform: translateY(100%);
    }
    .billing-drawer-backdrop.is-open .billing-drawer-panel {
        transform: translateY(0);
    }
}
</style>

<div class="page-heading">
    <div>
        <span class="eyebrow">TRANSAKSI &amp; BILLING</span>
        <h1>Pembayaran<span class="gold">.</span></h1>
        <p>Kelola tagihan, pantau status pembayaran, dan konfirmasi transaksi.</p>
    </div>
    <div class="page-actions">
        <a class="button" href="{{ route('members.new') }}">
            <x-icon name="plus"/> Daftar &amp; Bayar
        </a>
        <a class="button secondary" href="{{ route('shifts') }}">
            <x-icon name="clock"/> Shift Kasir
        </a>
    </div>
</div>

<div class="billing-page-wrap">
    {{-- Toolbar: Tabs di Kiri + Search & Export di Kanan (Persis Gambar 1) --}}
    <div class="billing-toolbar">
        <div class="billing-tabs">
            <a href="{{ route('payments') }}" class="billing-tab-link {{ $currentStatus==='all' && !$search ? 'is-active' : '' }}">
                Semua Tagihan
            </a>
            <a href="{{ route('payments', ['status' => 'unpaid']) }}" class="billing-tab-link {{ $currentStatus==='unpaid' ? 'is-active' : '' }}">
                Belum Bayar
            </a>
            <a href="{{ route('payments', ['status' => 'paid']) }}" class="billing-tab-link {{ $currentStatus==='paid' ? 'is-active' : '' }}">
                Lunas
            </a>
        </div>

        <div class="billing-controls">
            {{-- Search Bar --}}
            <form action="{{ route('payments') }}" method="get" class="billing-search-form">
                @if($currentStatus !== 'all')
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif
                <span class="billing-search-icon"><x-icon name="search"/></span>
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari invoice, member, no HP..." class="billing-search-input">
            </form>

            {{-- Export Button --}}
            <a href="{{ route('reports.export', ['type' => 'payments']) }}" class="button secondary compact" title="Ekspor rekap transaksi">
                <x-icon name="download"/> Export
            </a>
        </div>
    </div>

    {{-- Modern Data Table --}}
    <div class="billing-table-card">
        <div class="billing-table-wrap">
            <table class="billing-table">
                <thead>
                    <tr>
                        <th>Invoice ID</th>
                        <th>Member / Pelanggan</th>
                        <th>Paket &amp; Sesi</th>
                        <th>Tanggal</th>
                        <th>Total Tagihan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $i)
                        @php
                            $isRecent = $i->created_at->diffInHours(now()) < 24;
                        @endphp
                        <tr class="billing-row" id="row-{{ $i->id }}">
                            {{-- Invoice ID --}}
                            <td class="billing-id-cell">
                                <a href="{{ route('invoices.show', $i) }}" style="color:#ffffff;text-decoration:none;">
                                    {{ $i->invoice_number }}
                                </a>
                                @if($isRecent && $i->status === 'unpaid')
                                    <span class="billing-new-tag">BARU</span>
                                @endif
                            </td>

                            {{-- Member Name with Avatar --}}
                            <td>
                                <div class="billing-member-cell">
                                    <span class="billing-member-avatar">{{ mb_substr($i->member->full_name, 0, 1) }}</span>
                                    <div class="billing-member-info">
                                        <a href="{{ route('members.show', $i->member) }}" class="billing-member-name">
                                            {{ $i->member->full_name }}
                                        </a>
                                        <span class="billing-member-sub">{{ $i->member->phone }}</span>
                                    </div>
                                </div>
                            </td>

                            {{-- Plan & Sesi --}}
                            <td>
                                <strong style="color:#ffffff;display:block;">{{ $i->snapshot['name'] ?? 'Paket' }}</strong>
                                <span class="billing-member-sub">
                                    {{ !empty($i->snapshot['visit_limit']) ? $i->snapshot['visit_limit'].' sesi' : 'Unlimited sesi' }}
                                </span>
                            </td>

                            {{-- Tanggal --}}
                            <td style="color:#a1a1aa;white-space:nowrap;">
                                {{ $i->created_at->timezone(config('gym.timezone'))->format('d/m/Y') }}
                            </td>

                            {{-- Total Amount --}}
                            <td class="billing-amount-cell">
                                Rp {{ number_format($i->total_idr, 0, ',', '.') }}
                            </td>

                            {{-- Status Badge --}}
                            <td>
                                <span class="billing-status-badge {{ $i->status }}">
                                    {{ $i->status === 'paid' ? 'Lunas' : 'Belum Bayar' }}
                                </span>
                            </td>

                            {{-- Actions & Expand Accordion --}}
                            <td>
                                <div class="billing-actions-cell">
                                    @if($i->status === 'unpaid')
                                        <button type="button" class="billing-pay-btn" onclick="openPaymentDrawer('drawer-{{ $i->id }}')">
                                            Bayar
                                        </button>
                                    @else
                                        <a class="button compact secondary" href="{{ $whatsapp->getReceiptUrl($i) }}" target="_blank" rel="noopener noreferrer" title="Kirim bukti struk resmi via WhatsApp">
                                            WA Struk
                                        </a>
                                    @endif

                                    {{-- Titik 3 Menu --}}
                                    <details class="pricing-menu-wrap">
                                        <summary class="billing-icon-btn" title="Opsi lainnya">
                                            <x-icon name="more-vertical"/>
                                        </summary>
                                        <div class="pricing-dropdown-box">
                                            <a href="{{ route('invoices.show', $i) }}" class="pricing-dropdown-item">
                                                <x-icon name="card"/> Kuitansi
                                            </a>
                                            <a href="{{ route('members.show', $i->member) }}" class="pricing-dropdown-item">
                                                <x-icon name="users"/> Profil Member
                                            </a>
                                        </div>
                                    </details>

                                    {{-- Chevron Expand Button (Accordion Sub-Row seperti Gambar 1) --}}
                                    <button type="button" class="billing-icon-btn" onclick="toggleSubRow('subrow-{{ $i->id }}', this)" title="Rincian baris">
                                        <x-icon name="chevron-down"/>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        {{-- Expandable Accordion Sub-Row (Persis Esther Howard di Gambar 1) --}}
                        <tr id="subrow-{{ $i->id }}" class="billing-subrow" style="display:none;">
                            <td colspan="7" style="padding:0;border-bottom:1px solid rgba(255,255,255,0.06);">
                                <div class="billing-subrow-inner">
                                    <div class="billing-subrow-meta">
                                        <div class="billing-subrow-item">
                                            <span style="color:#8e8e93;display:block;font-size:0.75rem;">BILL ID</span>
                                            <strong>{{ $i->invoice_number }}</strong>
                                        </div>
                                        <div class="billing-subrow-item">
                                            <span style="color:#8e8e93;display:block;font-size:0.75rem;">RINCIAN PAKET</span>
                                            <span>{{ $i->snapshot['name'] ?? '-' }}</span>
                                        </div>
                                        <div class="billing-subrow-item">
                                            <span style="color:#8e8e93;display:block;font-size:0.75rem;">METODE</span>
                                            <span>{{ $i->payment ? (config('gym.payment_methods')[$i->payment->method] ?? $i->payment->method) : 'Belum Ditentukan' }}</span>
                                        </div>
                                        <div class="billing-subrow-item">
                                            <span style="color:#8e8e93;display:block;font-size:0.75rem;">STATUS</span>
                                            <strong style="color:{{ $i->status === 'paid' ? '#34d399' : '#fbbf24' }};">{{ strtoupper($i->status) }}</strong>
                                        </div>
                                    </div>

                                    <div>
                                        @if($i->status === 'unpaid')
                                            <button type="button" class="billing-pay-btn" onclick="openPaymentDrawer('drawer-{{ $i->id }}')">
                                                Konfirmasi Pembayaran
                                            </button>
                                        @else
                                            <a href="{{ route('invoices.show', $i) }}" class="button compact secondary">
                                                Lihat Kuitansi
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>

                        {{-- Slide-Over Payment Drawer (Persis Gambar 2) --}}
                        @if($i->status === 'unpaid')
                            <div id="drawer-{{ $i->id }}" class="billing-drawer-backdrop" onclick="if(event.target===this) closePaymentDrawer('drawer-{{ $i->id }}')">
                                <div class="billing-drawer-panel">
                                    <header class="billing-drawer-header">
                                        <div class="billing-drawer-title-group">
                                            <span class="billing-drawer-bill-id">{{ $i->invoice_number }}</span>
                                            <span style="font-size:0.78rem;color:#8e8e93;">Diterbitkan {{ $i->created_at->format('d M Y') }}</span>
                                        </div>
                                        <button type="button" class="billing-drawer-close" onclick="closePaymentDrawer('drawer-{{ $i->id }}')" aria-label="Tutup drawer">
                                            <x-icon name="x"/>
                                        </button>
                                    </header>

                                    <form action="{{ route('payments.store', $i) }}" method="post" style="display:flex;flex-direction:column;flex:1;">
                                        @csrf
                                        <div class="billing-drawer-body">
                                            {{-- Member Info Card --}}
                                            <div class="billing-drawer-card">
                                                <span class="billing-card-header-label">DITAGIHKAN KEPADA</span>
                                                <strong style="font-size:1rem;color:#ffffff;display:block;">{{ $i->member->full_name }}</strong>
                                                <span style="font-size:0.84rem;color:#a1a1aa;display:block;margin-top:2px;">{{ $i->member->phone }}</span>
                                                <span class="billing-status-badge unpaid" style="margin-top:8px;">BELUM BAYAR</span>
                                            </div>

                                            {{-- Bill Breakdown --}}
                                            <div class="billing-drawer-card">
                                                <span class="billing-card-header-label">RINCIAN TAGIHAN</span>
                                                <div class="billing-breakdown-row">
                                                    <span>{{ $i->snapshot['name'] ?? 'Paket Gym' }}</span>
                                                    <span>Rp {{ number_format($i->total_idr, 0, ',', '.') }}</span>
                                                </div>
                                                <div class="billing-breakdown-row">
                                                    <span style="color:#8e8e93;">Kuota Sesi</span>
                                                    <span style="color:#8e8e93;">{{ !empty($i->snapshot['visit_limit']) ? $i->snapshot['visit_limit'].' sesi' : 'Unlimited' }}</span>
                                                </div>
                                                <div class="billing-breakdown-row total-row">
                                                    <span>Total Tagihan</span>
                                                    <span style="color:var(--gold, #ff812f);">Rp {{ number_format($i->total_idr, 0, ',', '.') }}</span>
                                                </div>
                                            </div>

                                            {{-- Payment Method Selection (Tiles seperti Gambar 2) --}}
                                            <div>
                                                <span class="billing-card-header-label">PILIH METODE PEMBAYARAN</span>
                                                <div class="billing-methods-grid">
                                                    @foreach(config('gym.payment_methods') as $value => $label)
                                                        <label class="billing-method-tile">
                                                            <input type="radio" name="method" value="{{ $value }}" {{ $loop->first ? 'checked' : '' }}>
                                                            <span>{{ $label }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>

                                            {{-- Reference input --}}
                                            <div class="field" style="margin:0;">
                                                <label for="reference-{{ $i->id }}">Nomor Referensi / Catatan</label>
                                                <input id="reference-{{ $i->id }}" name="reference" placeholder="Wajib untuk transfer atau metode lainnya" maxlength="120">
                                            </div>

                                            {{-- Confirmation Checkbox --}}
                                            <label class="checkbox" style="font-size:0.86rem;color:#d1d1d6;margin-top:4px;">
                                                <input type="checkbox" name="confirmed" value="1" required>
                                                Dana sudah diterima secara sah sesuai jumlah invoice.
                                            </label>
                                        </div>

                                        <div class="billing-drawer-footer">
                                            <button type="submit" class="button" style="width:100%;min-height:48px;">
                                                <x-icon name="check"/> Konfirmasi Pembayaran Lunas
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="empty" style="text-align:center;padding:36px;color:#8e8e93;">
                                Belum ada data tagihan yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination" style="padding:16px 20px;">{{ $invoices->links() }}</div>
    </div>
</div>

<script>
function toggleSubRow(subRowId, btnEl) {
    const row = document.getElementById(subRowId);
    if (!row) return;
    const isHidden = row.style.display === 'none';
    row.style.display = isHidden ? 'table-row' : 'none';
    if (btnEl) {
        if (isHidden) {
            btnEl.classList.add('is-active-expand');
        } else {
            btnEl.classList.remove('is-active-expand');
        }
    }
}

function openPaymentDrawer(drawerId) {
    const drawer = document.getElementById(drawerId);
    if (drawer) {
        drawer.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }
}

function closePaymentDrawer(drawerId) {
    const drawer = document.getElementById(drawerId);
    if (drawer) {
        drawer.classList.remove('is-open');
        document.body.style.overflow = '';
    }
}

// Close dropdown on click outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('details.pricing-menu-wrap')) {
        document.querySelectorAll('details.pricing-menu-wrap[open]').forEach(function(el) {
            el.removeAttribute('open');
        });
    }
});
</script>
@endsection
