@extends('layouts.app')
@section('title','Ketersediaan PT')
@section('content')
<div class="page-heading"><div><h1>Ketersediaan PT</h1><p>Jam kerja, cuti, jadwal terisi, dan jeda antarsesi diperiksa bersama.</p></div><a class="button secondary" href="{{ route('classes') }}">Jadwal kelas</a></div>
<form class="panel form-panel" method="get">
<div class="form-grid"><div class="field"><label for="availability-trainer">PT</label><select name="trainer_id" id="availability-trainer">@foreach($trainers as $item)<option value="{{ $item->id }}" @selected($trainer?->id===$item->id)>{{ $item->user->name }}</option>@endforeach</select></div>
<x-field name="date" label="Tanggal" type="date" :value="$day->toDateString()" required/><x-field name="duration" label="Durasi sesi (menit)" type="number" :value="$duration" min="15" max="180" required/></div><button class="button secondary">Cari slot</button></form>
@if($trainer)
<section class="panel form-panel spaced"><h2>{{ $trainer->user->name }} · {{ $day->format('d M Y') }}</h2>
@if($trainer->availability === null)<p class="muted">Jam kerja PT lama belum dibatasi. Simpan jam kerja untuk mengaktifkan pembatasan; semua hari kosong berarti tidak tersedia.</p>@endif
<p>Slot ini belum merupakan booking. Konfirmasi tetap melalui staf setelah permintaan WA.</p>
<div class="form-grid">@forelse($slots as $slot)<span class="badge neutral">{{ substr($slot['starts_at'], 11) }}–{{ substr($slot['ends_at'], 11) }}</span>@empty<p>Tidak ada slot tersedia.</p>@endforelse</div></section>
@can('schedule.manage')
<section class="panel form-panel spaced"><h2>Jam kerja mingguan</h2><form method="post" action="{{ route('trainer-availability.update', $trainer) }}">@csrf @method('PUT')
@foreach(['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $weekday => $label)
<fieldset><legend>{{ $label }}</legend><p class="muted">Kosongkan kedua jam untuk hari libur. Dapat diisi dua rentang; jam akhir lebih awal berarti lintas malam.</p>
@for($slotIndex=0; $slotIndex<max(2,count(old('availability.'.$weekday, $trainer->availability[$weekday] ?? []))); $slotIndex++)
<div class="form-grid"><div class="field"><label for="from-{{ $weekday }}-{{ $slotIndex }}">Mulai {{ $slotIndex+1 }}</label><input id="from-{{ $weekday }}-{{ $slotIndex }}" type="time" name="availability[{{ $weekday }}][{{ $slotIndex }}][from]" value="{{ old('availability.'.$weekday.'.'.$slotIndex.'.from', $trainer->availability[$weekday][$slotIndex]['from'] ?? '') }}"></div><div class="field"><label for="to-{{ $weekday }}-{{ $slotIndex }}">Selesai {{ $slotIndex+1 }}</label><input id="to-{{ $weekday }}-{{ $slotIndex }}" type="time" name="availability[{{ $weekday }}][{{ $slotIndex }}][to]" value="{{ old('availability.'.$weekday.'.'.$slotIndex.'.to', $trainer->availability[$weekday][$slotIndex]['to'] ?? '') }}"></div></div>
@endfor
</fieldset>
@endforeach
<x-field name="session_buffer_minutes" label="Jeda antarsesi (menit)" type="number" :value="$trainer->session_buffer_minutes" min="0" max="120" required/><button class="button">Simpan jam kerja</button></form></section>
<section class="panel form-panel spaced"><h2>Cuti / waktu tidak tersedia</h2><form method="post" action="{{ route('trainer-time-off.store', $trainer) }}">@csrf<div class="form-grid"><x-field name="starts_at" label="Mulai" type="datetime-local" required/><x-field name="ends_at" label="Selesai" type="datetime-local" required/></div><x-field name="reason" label="Alasan" required minlength="5" maxlength="500"/><button class="button secondary">Catat waktu tidak tersedia</button></form></section>
@endcan
<section class="panel form-panel spaced"><h2>Riwayat waktu tidak tersedia</h2>
@forelse($trainer->timeOffs as $timeOff)<div class="list-row"><div><strong>{{ $timeOff->starts_at->timezone(config('gym.timezone'))->format('d M Y H:i') }} — {{ $timeOff->ends_at->timezone(config('gym.timezone'))->format('d M Y H:i') }}</strong><small>{{ $timeOff->reason }}</small></div>
@can('schedule.manage')<form method="post" action="{{ route('trainer-time-off.destroy', $timeOff) }}">@csrf @method('DELETE')<button class="button secondary compact">Hapus</button></form>@endcan
</div>@empty<p>Belum ada catatan.</p>@endforelse
</section>
@endif
@endsection
