@extends('layouts.app')
@section('title','Perubahan paket member')
@section('content')
<div class="page-heading"><div><h1>Ganti paket member</h1><p>{{ $member->full_name }} · Perhitungan nilai sisa dan selisih pembayaran.</p></div><a class="button secondary" href="{{ route('members.show', $member) }}">Profil member</a></div>
<section class="panel form-panel"><h2>Pratinjau perubahan</h2>
<form method="get"><div class="form-grid"><div class="field"><label for="source_period_id">Periode asal</label><select id="source_period_id" name="source_period_id" required>@foreach($periods as $period)<option value="{{ $period->id }}" @selected($source?->id===$period->id)>{{ $period->invoice->snapshot['name'] }} · {{ $period->ends_at->timezone(config('gym.timezone'))->format('d M Y') }}</option>@endforeach</select></div><div class="field"><label for="plan_id">Paket baru</label><select id="plan_id" name="plan_id" required>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected($target?->id===$plan->id)>{{ $plan->name }}</option>@endforeach</select></div></div><button class="button secondary">Hitung selisih</button></form>
@if($preview)
<div class="two-columns spaced"><section><h3>Paket lama</h3><strong>{{ $preview['source']['name'] }}</strong>@include('gym.entitlement-summary', ['entitlements' => $source->entitlements])</section><section><h3>Paket baru</h3><strong>{{ $preview['target']['name'] }}</strong>@foreach($preview['target']['benefits'] as $key => $quota)<p>{{ \App\Services\PackageBenefits::LABELS[$key] }}: {{ $quota === null ? 'Tanpa batas' : $quota }}</p>@endforeach</section></div>
<dl class="m-detail-list">
@foreach(['Harga paket baru'=>'price_idr'] as $label=>$field)<div><dt>{{ $label }}</dt><dd>Rp {{ number_format($preview['target'][$field],0,',','.') }}</dd></div>@endforeach
@foreach(['Nilai sisa paket lama'=>'source_credit_idr','Kredit tersimpan sebelumnya'=>'wallet_before_idr','Kredit dipakai'=>'applied_credit_idr','Selisih yang dibayar sekarang'=>'amount_due_idr','Kredit tersimpan setelah perubahan'=>'wallet_after_idr'] as $label=>$field)<div><dt>{{ $label }}</dt><dd>Rp {{ number_format($preview[$field],0,',','.') }}</dd></div>@endforeach
</dl>
<details><summary>Cara menghitung nilai sisa</summary><p>Harga pembelian lama dibagi rata ke setiap manfaat karena katalog belum memiliki harga per manfaat. Nilai tiap manfaat mengikuti bagian terkecil antara sisa masa berlaku dan sisa kuota yang belum terpakai. Booking terjadwal ikut dipindahkan, sehingga belum dianggap terpakai. Semua nilai dibulatkan ke bawah dalam rupiah.</p><p>Harga dan perhitungan ini berlaku 10 menit selama paket, pemakaian, dan saldo kredit tidak berubah. Selisih lebih disimpan sebagai kredit untuk perubahan paket berikutnya, bukan refund tunai. Riwayat pembelian lama dipertahankan dengan status Diganti.</p></details>
<p>Paket baru aktif sekarang setelah selisih pembayaran berhasil dicatat. Booking yang belum selesai harus cocok dengan manfaat, kuota, dan masa berlaku paket baru.</p>
<form class="spaced" method="post" action="{{ route('membership-changes.store', $member) }}">@csrf
<input type="hidden" name="source_period_id" value="{{ $source->id }}"><input type="hidden" name="plan_id" value="{{ $target->id }}"><input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="quote" value="{{ $preview['quote'] }}">
<div class="field"><label for="method">Metode pembayaran selisih</label><select id="method" name="method">@foreach(config('gym.payment_methods') as $method=>$label)<option value="{{ $method }}">{{ $label }}</option>@endforeach</select></div>
<x-field name="reference" label="Referensi (wajib untuk transfer / lainnya)" maxlength="120"/>
<x-field name="reason" label="Alasan perubahan" required minlength="5" maxlength="500"/>
<label class="checkbox"><input type="checkbox" name="confirmed" value="1" required> Saya sudah memeriksa perhitungan, booking, dan penerimaan pembayaran selisih.</label><button class="button">Ganti paket dan catat pembayaran</button></form>
@else<p>Perubahan membutuhkan periode aktif dan paket tujuan yang tersedia.</p>@endif</section>
<section class="panel form-panel spaced"><h2>Riwayat perubahan paket</h2>
@forelse($changes as $change)<div class="spaced"><strong>{{ $change->source_snapshot['name'] }} → {{ $change->invoice->snapshot['name'] }}</strong><p>{{ $change->reason }} · Nilai sisa Rp {{ number_format($change->source_credit_idr,0,',','.') }} · Selisih dibayar Rp {{ number_format($change->amount_due_idr,0,',','.') }}</p><a class="text-link" href="{{ route('invoices.show', $change->invoice_id) }}">{{ $change->invoice->invoice_number }}</a>
@if($change->activated_at)<p>Aktif {{ $change->activated_at->timezone(config('gym.timezone'))->format('d M Y H:i') }}</p>@endif
</div>@empty<p>Belum ada perubahan paket.</p>@endforelse
{{ $changes->links() }}</section>
@endsection
