@extends('layouts.app')
@section('title','Buat Paket Baru')
@section('content')
<div class="page-heading">
    <div>
        <a href="{{ route('plans') }}" class="button secondary compact" style="margin-bottom:12px;">
            <x-icon name="arrow"/> Kembali ke Daftar Paket
        </a>
        <span class="eyebrow">MEMBERSHIP &amp; PT</span>
        <h1>Buat Paket Baru<span class="gold">.</span></h1>
        <p>Atur harga dan durasi, lalu pilih manfaat Gym, Pilates, grup, atau privat dengan kuota masing-masing.</p>
    </div>
</div>

<section class="panel form-panel" style="max-width:880px;">
    {{-- Rekomendasi Format Paket PT --}}
    <div style="margin-bottom:24px;padding:16px 20px;border-radius:14px;background:#131316;border:1px solid rgba(255,255,255,0.1);">
        <strong style="color:var(--gold);font-size:13px;display:block;margin-bottom:8px;">💡 Rekomendasi Format Paket PT:</strong>
        <ul style="margin:0;padding-left:20px;font-size:12.5px;color:var(--muted);line-height:1.9;">
            <li><strong>Paket PT Full 30 Hari:</strong> Rp 700.000 · Durasi: 1 Bulan / 30 Hari · Manfaat: Gym privat / PT · Kuota kosong (tanpa batas)</li>
            <li><strong>Paket PT 15 Sesi:</strong> Rp 500.000 · Durasi: 2 Bulan / 60 Hari · Manfaat: Gym privat / PT · Kuota 15 sesi</li>
            <li><strong>Paket PT 10 Sesi:</strong> Rp 250.000 · Durasi: 1 Bulan / 30 Hari · Manfaat: Gym privat / PT · Kuota 10 sesi</li>
        </ul>
    </div>

    <form action="{{ route('plans.store') }}" method="post">
        @csrf
        @include('gym.plan-fields',['plan'=>null])
        <div style="display:flex;gap:12px;align-items:center;margin-top:20px;padding-top:16px;border-top:1px solid rgba(255,255,255,0.08);">
            <a href="{{ route('plans') }}" class="button secondary">Batal</a>
            <button class="button"><x-icon name="plus"/> Tambah Paket</button>
        </div>
    </form>
</section>
@endsection
