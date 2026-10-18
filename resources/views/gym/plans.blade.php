@extends('layouts.app')
@section('title','Paket membership')
@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">FLEXIBLE PLANS. LASTING PROGRESS.</span>
        <h1>Paket Membership &amp; Personal Trainer<span class="gold">.</span></h1>
        <p>Atur harga, kuota sesi, dan durasi untuk Gym, Personal Trainer (PT), dan Studio. Transaksi sebelumnya tetap menyimpan ketentuan saat pembelian.</p>
    </div>
</div>

<div class="plan-grid">
    @foreach($plans as $plan)
        <section class="panel plan-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <span class="eyebrow" style="margin:0;">{{ $plan->is_active ? 'AVAILABLE' : 'INACTIVE' }}</span>
                <span class="badge {{ $plan->plan_type === 'pt' ? 'positive' : ($plan->plan_type === 'pilates' ? 'pending' : 'neutral') }}" style="font-size:0.75rem;">
                    {{ $plan->plan_type_label }}
                </span>
            </div>
            <h2>{{ $plan->name }}</h2>
            <strong class="plan-price">Rp {{ number_format($plan->price_idr,0,',','.') }}</strong>
            <p class="muted">
                {{ $plan->duration_value }} {{ ['day'=>'hari','month'=>'bulan','year'=>'tahun'][$plan->duration_unit] }} ·
                {{ $plan->visit_limit ? $plan->visit_limit.' sesi / kunjungan' : 'Tanpa batas sesi' }}
            </p>
            <p>{{ $plan->description }}</p>
            <details>
                <summary class="text-link">Ubah paket</summary>
                <form action="{{ route('plans.update',$plan) }}" method="post" style="margin-top:12px;">
                    @csrf
                    @method('PUT')
                    @include('gym.plan-fields',['plan'=>$plan])
                    <button class="button">Simpan perubahan</button>
                </form>
            </details>
        </section>
    @endforeach
</div>

<section class="panel form-panel spaced">
    <div class="panel-heading">
        <div>
            <h2>Buat paket baru</h2>
            <p>Konfigurasi paket Gym reguler atau paket Personal Trainer (PT) berbasis durasi atau kuota sesi.</p>
        </div>
    </div>

    {{-- Info Card Contoh Paket PT yang sering digunakan --}}
    <div style="margin-bottom:20px;padding:14px;border-radius:8px;background:rgba(255,255,255,0.03);border:1px dashed rgba(255,255,255,0.15);">
        <strong>💡 Rekomendasi Format Paket PT:</strong>
        <ul style="margin:8px 0 0 20px;font-size:0.88rem;color:rgba(255,255,255,0.8);line-height:1.6;">
            <li><strong>Paket PT Full 30 Hari:</strong> Rp 700.000 · Durasi: 1 Bulan / 30 Hari · Kuota: Kosongkan (Unlimited)</li>
            <li><strong>Paket PT 15 Sesi:</strong> Rp 500.000 · Durasi: 2 Bulan / 60 Hari · Kuota: 15 sesi</li>
            <li><strong>Paket PT 10 Sesi:</strong> Rp 250.000 · Durasi: 1 Bulan / 30 Hari · Kuota: 10 sesi</li>
        </ul>
    </div>

    <form action="{{ route('plans.store') }}" method="post">
        @csrf
        @include('gym.plan-fields',['plan'=>null])
        <button class="button"><x-icon name="plus"/>Tambah paket</button>
    </form>
</section>
@endsection
