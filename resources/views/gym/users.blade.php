@extends('layouts.app')
@section('title',$trainerPage?'Personal Trainer':'User')
@section('content')
<div class="page-heading"><div><span class="eyebrow">AKUN & AKSES</span><h1>{{ $trainerPage?'Personal Trainer':'User' }}<span class="gold">.</span></h1><p>{{ $trainerPage?'Kelola trainer, biodata profesional, dan akses jadwal.':'Kelola akun, peran, dan akses SPARTA. Hanya owner yang dapat mengubah akun.' }}</p></div><a class="button" href="{{ route('users.create',$trainerPage?['role'=>'personal_trainer']:[]) }}"><x-icon name="plus"/>{{ $trainerPage?'Tambah trainer':'Tambah user' }}</a></div>
<form class="account-filters" method="get"><input name="q" value="{{ request('q') }}" placeholder="Cari nama atau email" aria-label="Cari akun">
@unless($trainerPage)<select name="role" aria-label="Filter peran"><option value="">Semua peran</option>
@foreach(config('gym.account_roles') as $value=>$label)<option value="{{ $value }}" @selected(request('role')===$value)>{{ $label }}</option>
@endforeach</select>
@endunless<select name="status" aria-label="Filter status"><option value="">Akun tersedia</option>
@foreach(['active'=>'Aktif','inactive'=>'Nonaktif','archived'=>'Diarsipkan'] as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>
@endforeach</select><button class="button secondary">Terapkan</button></form>
@if($trainerPage)
<div class="trainer-grid">
@forelse($accounts as $account)
<article class="panel trainer-card"><div class="trainer-card-top"><span class="trainer-avatar" aria-hidden="true">{{ mb_substr($account->name,0,1) }}</span><span class="badge {{ $account->is_active&&!$account->trashed()?'success':'neutral' }}">{{ $account->trashed()?'Diarsipkan':($account->is_active?'Aktif':'Nonaktif') }}</span></div><h2>{{ $account->name }}</h2><p class="trainer-specialty">{{ $account->trainerProfile?->specialization ?? 'Personal Trainer' }}</p><p class="muted">{{ Str::limit($account->trainerProfile?->bio ?? 'Lengkapi profil trainer untuk memperkenalkan keahliannya.',160) }}</p><p>{{ $account->trainerProfile?->experience_years ?? 0 }} tahun pengalaman</p><div class="trainer-card-actions"><a class="button secondary" href="{{ route('users.show',$account) }}">Lihat profil <x-icon name="arrow"/></a>
@unless($account->trashed())<a class="text-link" href="{{ route('users.edit',$account) }}">Edit</a>@endunless
</div></article>
@empty<section class="panel form-panel"><h2>Trainer belum ditemukan</h2><p class="muted">Ubah pencarian atau tambahkan trainer baru.</p></section>
@endforelse
</div><div class="pagination">{{ $accounts->links() }}</div>
@else
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Akses</th><th>Aksi</th></tr></thead><tbody>
@forelse($accounts as $account)<tr><td><strong>{{ $account->name }}</strong></td><td>{{ $account->email }}</td><td>{{ config('gym.account_roles')[$account->role] ?? $account->role }}</td><td><span class="badge {{ $account->trashed()?'neutral':($account->is_active?'success':'pending') }}">{{ $account->trashed()?'Diarsipkan':($account->is_active?'Aktif':'Nonaktif') }}</span></td><td>
@if($account->trashed())<form method="post" action="{{ route('users.restore',$account->id) }}">
@csrf<label class="checkbox"><input type="checkbox" name="confirmed" value="1" required> Pulihkan akun</label><button class="button secondary compact">Pulihkan</button></form>
@else<div class="table-actions"><a class="text-link" href="{{ route('users.show',$account) }}">Profil <x-icon name="arrow"/></a><a class="button secondary compact" href="{{ route('users.edit',$account) }}">Edit</a></div>
@endif</td></tr>
@empty<tr><td colspan="5" class="empty">Tidak ada akun sesuai pencarian.</td></tr>
@endforelse</tbody></table></div><div class="pagination">{{ $accounts->links() }}</div></section>
@endif
@endsection
