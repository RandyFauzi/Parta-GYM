@extends('layouts.app')
@section('title','Invoice')
@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">TRANSACTION RECORD</span>
        <h1>{{ $invoice->invoice_number }}</h1>
        <p>Dokumen transaksi {{ config('gym.name') }}</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        @if($payment)
            <a class="button" href="{{ $whatsapp->getReceiptUrl($invoice) }}" target="_blank" rel="noopener noreferrer">
                Kirim Struk via WA <x-icon name="arrow"/>
            </a>
        @endif
        <button class="button secondary" data-print>Cetak invoice</button>
    </div>
</div>

<section class="panel receipt">
    <div class="receipt-top">
        <div class="brand">SPARTA Gym &amp; Pilates</div>
        <x-status :status="$invoice->status"/>
    </div>
    <div class="two-columns">
        <div>
            <small>DITAGIHKAN KEPADA</small>
            <h2>{{ $invoice->member->full_name }}</h2>
            <p>{{ $invoice->member->member_number }}<br>{{ $invoice->member->phone }}</p>
        </div>
        <div>
            <small>TANGGAL INVOICE</small>
            <p>{{ $invoice->created_at->timezone(config('gym.timezone'))->format('d M Y') }}</p>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th>Durasi</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice->snapshot['name'] }}</td>
                <td>{{ $invoice->snapshot['duration_value'] }} {{ $invoice->snapshot['duration_unit'] }}</td>
                <td>Rp {{ number_format($invoice->total_idr,0,',','.') }}</td>
            </tr>
        </tbody>
    </table>
    <div class="receipt-total">
        <small>TOTAL IDR</small>
        <strong>Rp {{ number_format($invoice->total_idr,0,',','.') }}</strong>
    </div>
    @if($payment)
        <div class="alert success" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <div>
                Pembayaran {{ config('gym.payment_methods')[$payment->method] ?? $payment->method }} diterima {{ $payment->paid_at->timezone(config('gym.timezone'))->format('d M Y H:i') }}.
            </div>
            <a class="button compact secondary" href="{{ $whatsapp->getReceiptUrl($invoice) }}" target="_blank" rel="noopener noreferrer">
                Bagikan ke WhatsApp Anggota
            </a>
        </div>
    @else
        <p class="muted">Invoice belum dibayar. Hubungi front desk untuk menyelesaikan pembayaran.</p>
    @endif
</section>
@if(isset($invoice->snapshot['adjustment']))
<section class="panel form-panel spaced"><h2>Penyesuaian paket</h2><p>Harga paket Rp {{ number_format($invoice->snapshot['price_idr'],0,',','.') }} · Kredit diterapkan Rp {{ number_format($invoice->snapshot['adjustment']['applied_credit_idr'],0,',','.') }} · Selisih dibayar Rp {{ number_format($invoice->total_idr,0,',','.') }}</p><p>Kredit tersimpan Rp {{ number_format($invoice->snapshot['adjustment']['wallet_after_idr'],0,',','.') }}. Tidak ada refund tunai otomatis.</p></section>
@endif
@endsection
