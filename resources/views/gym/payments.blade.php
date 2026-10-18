@extends('layouts.app')
@section('title','Pembayaran')
@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">EVERY TRANSACTION, ACCOUNTED FOR</span>
        <h1>Pembayaran<span class="gold">.</span></h1>
        <p>Catat tunai, transfer, QRIS, atau kartu setelah dana diterima.</p>
    </div>
    <div class="page-actions">
        <a class="button" href="{{ route('members.new') }}">Daftar &amp; bayar <x-icon name="plus"/></a>
        <a class="button secondary" href="{{ route('shifts') }}">Shift kasir <x-icon name="arrow"/></a>
    </div>
</div>

<section class="panel">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Invoice / anggota</th>
                    <th>Paket</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Metode</th>
                    <th>Konfirmasi pembayaran &amp; Struk WA</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $i)
                    <tr>
                        <td>
                            <a href="{{ route('invoices.show',$i) }}"><strong>{{ $i->invoice_number }}</strong></a>
                            <small>{{ $i->member->full_name }} ({{ $i->member->phone }})</small>
                        </td>
                        <td>{{ $i->snapshot['name'] }}</td>
                        <td class="money">Rp {{ number_format($i->total_idr,0,',','.') }}</td>
                        <td><x-status :status="$i->status"/></td>
                        <td>{{ $i->payment ? (config('gym.payment_methods')[$i->payment->method] ?? $i->payment->method) : '—' }}</td>
                        <td>
                            @if($i->status==='unpaid')
                                <details>
                                    <summary class="text-link">Terima pembayaran</summary>
                                    <form class="payment-form" action="{{ route('payments.store',$i) }}" method="post">
                                        @csrf
                                        <label for="method-{{ $i->id }}">Metode</label>
                                        <select id="method-{{ $i->id }}" name="method">
                                            @foreach(config('gym.payment_methods') as $value=>$label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <label for="reference-{{ $i->id }}">Referensi / catatan</label>
                                        <input id="reference-{{ $i->id }}" name="reference" placeholder="Wajib untuk transfer atau metode lainnya" maxlength="120">
                                        <label class="checkbox">
                                            <input type="checkbox" name="confirmed" value="1" required> Dana sudah diterima, jumlah sesuai invoice
                                        </label>
                                        <button class="button compact">Konfirmasi lunas</button>
                                    </form>
                                </details>
                            @else
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <a class="button compact secondary" href="{{ $whatsapp->getReceiptUrl($i) }}" target="_blank" rel="noopener noreferrer" title="Kirim bukti struk pembayaran resmi via WhatsApp">
                                        WA Struk
                                    </a>
                                    <a class="text-link" href="{{ route('invoices.show',$i) }}">Kuitansi <x-icon name="arrow"/></a>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">Belum ada invoice. Buat invoice dari profil anggota.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $invoices->links() }}</div>
</section>
@endsection
