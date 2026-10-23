@extends('layouts.app')
@section('title','Jam operasional')
@section('content')
<div class="page-heading"><div><h1>Jam operasional Gym</h1><p>Zona waktu {{ config('gym.timezone') }}. Checkout otomatis mengikuti jam tutup yang dicatat saat member masuk.</p></div><a class="button secondary" href="{{ route('checkin') }}">Front desk</a></div>
<section class="panel form-panel">
@if($hours->isEmpty())<div class="alert">Jam operasional belum diatur. Checkout otomatis belum aktif; kunjungan lama tetap tersimpan tanpa perkiraan jam keluar.</div>@endif
<form method="post" action="{{ route('operating-hours.update') }}">@csrf @method('PUT')
@foreach(['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $weekday => $label)
<fieldset><legend>{{ $label }}</legend><div class="form-grid">
<div class="field"><label for="closed-{{ $weekday }}">Operasional</label><select id="closed-{{ $weekday }}" name="days[{{ $weekday }}][closed]"><option value="1" @selected(old('days.'.$weekday.'.closed', $hours->get($weekday)?->closed ?? true))>Tutup</option><option value="0" @selected(!old('days.'.$weekday.'.closed', $hours->get($weekday)?->closed ?? true))>Buka</option></select></div>
<div class="field"><label for="opens-{{ $weekday }}">Buka</label><input id="opens-{{ $weekday }}" type="time" name="days[{{ $weekday }}][opens_at]" value="{{ old('days.'.$weekday.'.opens_at', $hours->get($weekday)?->opens_at) }}"></div>
<div class="field"><label for="closes-{{ $weekday }}">Tutup</label><input id="closes-{{ $weekday }}" type="time" name="days[{{ $weekday }}][closes_at]" value="{{ old('days.'.$weekday.'.closes_at', $hours->get($weekday)?->closes_at) }}"></div>
</div></fieldset>
@endforeach
<p>Jika jam tutup lebih awal dari jam buka, operasional berakhir pada hari berikutnya. Perubahan tidak menggeser batas checkout kunjungan yang sudah tercatat.</p>
<button class="button">Simpan jam operasional</button></form></section>
@endsection
