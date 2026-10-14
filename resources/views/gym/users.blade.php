@extends('layouts.app')
@section('title',$trainerPage?'Personal Trainer':'User')
@section('content')
<div class="page-heading"><div><span class="eyebrow">AKUN & AKSES</span><h1>{{ $trainerPage?'Personal Trainer':'User' }}<span class="gold">.</span></h1><p>{{ $trainerPage?'Kelola trainer, biodata profesional, dan akses jadwal.':'Kelola akun, peran, dan akses SPARTA. Hanya owner yang dapat mengubah akun.' }}</p></div><a class="button" href="{{ route('users.create',$trainerPage?['role'=>'personal_trainer']:[]) }}"><x-icon name="plus"/>{{ $trainerPage?'Tambah trainer':'Tambah user' }}</a></div>
<form class="filter-bar" method="get"><input name="q" value="{{ request('q') }}" placeholder="Cari nama atau email" aria-label="Cari akun">
@unless($trainerPage)<select name="role" aria-label="Filter peran"><option value="">Semua peran</option>
@foreach(config('gym.account_roles') as $value=>$label)<option value="{{ $value }}" @selected(request('role')===$value)>{{ $label }}</option>
@endforeach</select>
@endunless<select name="status" aria-label="Filter status"><option value="">Akun tersedia</option>
@foreach(['active'=>'Aktif','inactive'=>'Nonaktif','archived'=>'Diarsipkan'] as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>
@endforeach</select><button class="button secondary">Terapkan</button></form>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Akses</th><th>Aksi</th></tr></thead><tbody>
@forelse($accounts as $account)<tr><td><strong>{{ $account->name }}</strong></td><td>{{ $account->email }}</td><td>{{ config('gym.account_roles')[$account->role] ?? $account->role }}</td><td><span class="badge {{ $account->trashed()?'neutral':($account->is_active?'success':'pending') }}">{{ $account->trashed()?'Diarsipkan':($account->is_active?'Aktif':'Nonaktif') }}</span></td><td>
@if($account->trashed())<form method="post" action="{{ route('users.restore',$account->id) }}">
@csrf<label class="checkbox"><input type="checkbox" name="confirmed" value="1" required> Pulihkan akun</label><button class="button secondary compact">Pulihkan</button></form>
@else<div class="table-actions"><a class="text-link" href="{{ route('users.show',$account) }}">Profil <x-icon name="arrow"/></a><a class="button secondary compact" href="{{ route('users.edit',$account) }}">Edit</a></div>
@endif</td></tr>
@empty<tr><td colspan="5" class="empty">Tidak ada akun sesuai pencarian.</td></tr>
@endforelse</tbody></table></div><div class="pagination">{{ $accounts->links() }}</div></section>
@endsection
