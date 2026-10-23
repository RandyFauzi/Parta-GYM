@extends(auth()->user()->role==='member'?'layouts.member':'layouts.app')
@section('title','Kelas')
@section('content')
<div class="page-heading"><div><span class="eyebrow">GYM & PILATES</span><h1>{{ auth()->user()->role==='personal_trainer'?'Jadwal saya':'Kelas' }}<span class="gold">.</span></h1><p>Jadwal latihan, kapasitas peserta, dan penugasan trainer.</p></div>
@can('schedule.manage')<a class="button" href="{{ route('classes.create') }}"><x-icon name="plus"/>Jadwalkan kelas</a>
@endcan</div>
<form id="class-filters" class="class-filters" method="get"><div class="field"><label for="class-date">Tanggal</label><input id="class-date" type="date" name="date" value="{{ request('date') }}"></div><select name="activity" aria-label="Aktivitas"><option value="">Gym & Pilates</option><option value="gym" @selected(request('activity')==='gym')>Gym</option><option value="pilates" @selected(request('activity')==='pilates')>Pilates</option></select>
@if(auth()->user()->role!=='member')<select name="status" aria-label="Status kelas"><option value="">Semua status</option>
@foreach(['scheduled'=>'Terjadwal','completed'=>'Selesai','cancelled'=>'Dibatalkan'] as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>
@endforeach</select>
@endif<button class="button secondary">Terapkan</button></form>
@if(auth()->user()->role==='member')
<section class="panel form-panel"><h2>Booking melalui WhatsApp</h2><p>Hubungi admin untuk memilih aktivitas dan waktu. Management menentukan PT dan staf mengonfirmasi jadwal.</p>
@if($bookingUrl)<a class="button" href="{{ $bookingUrl }}" target="_blank" rel="noopener noreferrer">Hubungi admin untuk booking</a>
@else<p>Hubungi front desk. Nomor WhatsApp admin belum tersedia.</p>@endif</section>
<div class="class-tabs"><a class="button secondary" href="{{ route('classes') }}">Jelajahi kelas</a><a class="button secondary" href="{{ route('classes',['mine'=>1]) }}">Kelas saya</a></div>
@if(request('mine'))<input form="class-filters" type="hidden" name="mine" value="1">@endif
@endif
<div class="training-grid">
@forelse($sessions as $session)<a class="panel training-card" href="{{ route('classes.show',$session) }}"><div class="training-card-top"><span class="badge neutral">{{ ucfirst($session->activity) }} · {{ $session->kind==='personal'?'Personal':'Group' }}</span><x-icon name="arrow"/></div><h2>{{ $session->title }}</h2><strong>{{ $session->starts_at->timezone(config('gym.timezone'))->translatedFormat('D, d M Y') }}</strong><p>{{ $session->starts_at->timezone(config('gym.timezone'))->format('H:i') }}–{{ $session->ends_at->timezone(config('gym.timezone'))->format('H:i') }} · {{ $session->room->name }}</p><div class="training-card-footer"><span>{{ $session->trainer->user->name }}<small>Personal Trainer</small></span><span>{{ $session->occupied }}/{{ $session->capacity }}<small>Peserta</small></span></div><span class="badge {{ $session->status==='scheduled'?'success':'neutral' }}">{{ ['scheduled'=>'Terjadwal','completed'=>'Selesai','cancelled'=>'Dibatalkan'][$session->status] }}</span></a>
@empty<section class="panel form-panel"><h2>Belum ada kelas</h2><p class="muted">Jadwal yang sesuai filter akan muncul di sini.</p></section>
@endforelse</div><div class="pagination">{{ $sessions->links() }}</div>
@if(auth()->user()->role==='owner')<section class="panel form-panel spaced"><h2>Studio & ruang latihan</h2><p class="muted">Ruang eksklusif tidak bisa digunakan dua sesi bersamaan. Gym Floor dapat dipakai beberapa sesi dengan trainer berbeda.</p>
@foreach($rooms as $room)<details class="room-editor"><summary>{{ $room->name }} · {{ $room->capacity }} orang · {{ $room->is_active?'Aktif':'Nonaktif' }}</summary>@include('gym.room-form',['room'=>$room])</details>
@endforeach<details class="room-editor"><summary>Tambah ruang</summary>@include('gym.room-form',['room'=>null])</details></section>
@endif
@endsection
