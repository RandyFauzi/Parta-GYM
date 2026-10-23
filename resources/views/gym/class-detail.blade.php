@extends(auth()->user()->role==='member'?'layouts.member':'layouts.app')
@section('title','Detail kelas')
@section('content')
<div class="page-heading"><div><span class="eyebrow">{{ strtoupper($session->activity) }} · {{ $session->kind==='personal'?'PERSONAL TRAINING':'GROUP CLASS' }}</span><h1>{{ $session->title }}<span class="gold">.</span></h1><p>{{ $session->starts_at->timezone(config('gym.timezone'))->translatedFormat('l, d M Y') }} · {{ $session->starts_at->timezone(config('gym.timezone'))->format('H:i') }}–{{ $session->ends_at->timezone(config('gym.timezone'))->format('H:i') }}</p></div><div class="page-actions"><a class="button secondary" href="{{ route('classes') }}">Kembali</a>
@can('schedule.manage')<a class="button secondary" href="{{ route('classes.create',['source'=>$session->id]) }}">Buat sesi serupa</a>
@endcan
@if(auth()->user()->can('schedule.manage') && $session->status==='scheduled' && $session->starts_at->isFuture())<a class="button" href="{{ route('classes.edit',$session) }}">Edit jadwal</a>
@endif</div></div>
<section class="panel form-panel"><div class="training-summary"><div><small>Trainer</small><strong>{{ $session->trainer->user->name }}</strong>
@if(auth()->user()->role==='owner')<a class="text-link" href="{{ route('users.show',$session->trainer->user) }}">Profil trainer</a>
@endif</div><div><small>Ruang</small><strong>{{ $session->room->name }}</strong></div><div><small>Kapasitas</small><strong>{{ $session->capacity }} peserta</strong></div><div><small>Status</small><strong>{{ ['scheduled'=>'Terjadwal','completed'=>'Selesai','cancelled'=>'Dibatalkan'][$session->status] }}</strong></div></div>
@if($session->notes)<p>{{ $session->notes }}</p>
@endif
@if($session->cancellation_reason)<div class="alert error">Alasan pembatalan: {{ $session->cancellation_reason }}</div>
@endif</section>
@if(auth()->user()->can('booking.manage') && $session->status==='scheduled' && $session->starts_at->isFuture())<section class="panel form-panel spaced"><h2>Konfirmasi booking WhatsApp</h2><p>Pastikan permintaan sudah diterima melalui WA. PT sesi personal harus sesuai penugasan management.</p><form method="post" action="{{ route('classes.enroll',$session) }}">
@csrf<div class="field"><label for="member_id">Anggota</label><select id="member_id" name="member_id" required><option value="">Pilih anggota aktif</option>
@foreach($members as $participant)<option value="{{ $participant->id }}">{{ $participant->full_name }} · {{ $participant->member_number }}</option>
@endforeach</select></div><label class="checkbox"><input type="checkbox" name="wa_confirmed" value="1" required> Permintaan booking sudah dikonfirmasi melalui WhatsApp</label><button class="button">Konfirmasi peserta</button></form></section>
@endif
@if(auth()->user()->role==='member' && $session->status==='scheduled' && $session->starts_at->isFuture() && !$enrollments->contains(fn($item)=>$item->status!=='cancelled'))
<section class="panel form-panel spaced"><h2>Booking melalui WhatsApp</h2><p>Admin memeriksa paket dan jadwal, lalu management menentukan PT Anda. Mengirim pesan belum mengonfirmasi booking.</p>
@if($bookingUrl)<a class="button" href="{{ $bookingUrl }}" target="_blank" rel="noopener noreferrer">Hubungi admin untuk booking</a>
@else<p>Hubungi front desk untuk booking. Nomor WhatsApp admin belum tersedia.</p>@endif</section>
@endif
<section class="panel spaced"><div class="panel-heading"><h2>{{ auth()->user()->role==='member'?'Pendaftaran saya':'Peserta & absensi' }}</h2></div><div class="table-wrap"><table><thead><tr><th>Anggota</th><th>Status</th>
@if(auth()->user()->role!=='member')<th>Absensi</th>
@endif</tr></thead><tbody>
@forelse($enrollments as $enrollment)<tr><td><strong>{{ $enrollment->member->full_name }}</strong><small>{{ $enrollment->member->member_number }}</small></td><td>{{ ['confirmed'=>'Terdaftar','attended'=>'Hadir','no_show'=>'Tidak hadir','cancelled'=>'Dibatalkan'][$enrollment->status] }}</td>
@if(auth()->user()->role!=='member')<td>
@if($session->status==='scheduled' && $enrollment->status==='confirmed' && (auth()->user()->can('booking.manage') || auth()->user()->role==='personal_trainer'))<form method="post" action="{{ route('classes.attendance',$enrollment) }}" class="table-actions">
@csrf
@method('PUT')<select name="status" aria-label="Status peserta {{ $enrollment->member->full_name }}"><option value="attended">Hadir</option><option value="no_show">Tidak hadir</option>
@if(auth()->user()->role!=='personal_trainer')<option value="cancelled">Batalkan peserta</option>
@endif</select><button class="button secondary compact">Simpan</button></form>
@else{{ $enrollment->attended_at?->timezone(config('gym.timezone'))->format('d M H:i') ?? '—' }}
@endif</td>
@endif</tr>
@empty<tr><td colspan="3" class="empty">{{ auth()->user()->role==='member'?'Anda belum terdaftar pada kelas ini.':'Belum ada peserta.' }}</td></tr>
@endforelse</tbody></table></div></section>
@if((auth()->user()->can('schedule.manage') || auth()->user()->role==='personal_trainer') && $session->status==='scheduled')<section class="panel form-panel spaced"><h2>Tutup kelas</h2><form method="post" action="{{ route('classes.close',$session) }}">
@csrf<div class="field"><label for="status">Tindakan</label><select id="status" name="status"><option value="completed">Selesaikan kelas (setelah sesi berakhir)</option>@can('schedule.manage')<option value="cancelled">Batalkan kelas</option>@endcan</select></div><x-field name="reason" label="Alasan pembatalan" minlength="5" maxlength="1000"/><label class="checkbox"><input type="checkbox" name="confirmed" value="1" required> Saya mengonfirmasi perubahan ini</label><button class="button secondary">Terapkan</button></form></section>
@endif
@endsection
