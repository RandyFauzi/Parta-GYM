@extends('layouts.app')
@section('title','Profil user')
@section('content')
<div class="page-heading"><div><span class="eyebrow">PROFIL SPARTA</span><h1>{{ $account->name }}<span class="gold">.</span></h1><p>{{ config('gym.account_roles')[$account->role] ?? $account->role }}</p></div>
@if(auth()->user()->role==='owner' && !$account->trashed())<a class="button" href="{{ route('users.edit',$account) }}">Edit akun <x-icon name="arrow"/></a>
@endif</div>
<div class="two-columns"><section class="panel form-panel"><div class="user-profile-heading"><span class="avatar">{{ mb_substr($account->name,0,1) }}</span><div><h2>{{ $account->name }}</h2><span class="badge {{ $account->is_active?'success':'pending' }}">{{ $account->trashed()?'Diarsipkan':($account->is_active?'Aktif':'Nonaktif') }}</span></div></div><dl class="m-detail-list"><div><dt>Email</dt><dd>{{ $account->email }}</dd></div><div><dt>Peran</dt><dd>{{ config('gym.account_roles')[$account->role] ?? $account->role }}</dd></div><div><dt>Bergabung</dt><dd>{{ $account->created_at->timezone(config('gym.timezone'))->format('d M Y') }}</dd></div>
@if($account->member)<div><dt>Nomor anggota</dt><dd>{{ $account->member->member_number }}</dd></div>
@endif</dl></section><section class="panel form-panel"><h2>{{ $account->role==='personal_trainer'?'Profil profesional':'Akses akun' }}</h2>
@if($account->role==='personal_trainer')<dl class="m-detail-list"><div><dt>Spesialisasi</dt><dd>{{ $account->trainerProfile?->specialization ?: 'Belum diisi' }}</dd></div><div><dt>Pengalaman</dt><dd>{{ $account->trainerProfile?->experience_years!==null?$account->trainerProfile->experience_years.' tahun':'Belum diisi' }}</dd></div><div><dt>Sertifikasi</dt><dd>{{ $account->trainerProfile?->qualifications ?: 'Belum diisi' }}</dd></div></dl><p>{{ $account->trainerProfile?->bio ?: 'Biodata trainer belum diisi.' }}</p><a class="button secondary" href="{{ route('classes') }}">Lihat jadwal kelas</a>
@else<p class="muted">Akses mengikuti peran dan status aktif akun. Pengelolaan akun hanya dilakukan owner.</p>
@endif</section></div>
@if(auth()->user()->role==='owner' && auth()->id()!==$account->id && !$account->trashed())<section class="panel form-panel spaced"><h2>Hapus / arsipkan akun</h2><p class="muted">Akun tidak bisa login setelah diarsipkan. Riwayat transaksi tetap tersimpan dan akun bisa dipulihkan dari filter Diarsipkan.</p><form method="post" action="{{ route('users.destroy',$account) }}">
@csrf
@method('DELETE')<label class="checkbox"><input type="checkbox" name="confirmed" value="1" required> Saya ingin mengarsipkan akun {{ $account->name }}</label><button class="button secondary">Arsipkan akun</button></form></section>
@endif
@endsection
