@extends('layouts.app')
@section('title',$account->exists?'Edit user':'Tambah user')
@section('content')
<div class="page-heading"><div><span class="eyebrow">AKUN SPARTA</span><h1>{{ $account->exists?'Edit user':'Tambah user' }}<span class="gold">.</span></h1><p>Perubahan akses tercatat di log aktivitas. Password tidak pernah ditampilkan kembali.</p></div><a class="button secondary" href="{{ route('users.index') }}">Kembali</a></div>
<div class="form-layout"><section class="panel form-panel"><form method="post" action="{{ $account->exists?route('users.update',$account):route('users.store') }}" data-user-form>
@csrf
@if($account->exists)
@method('PUT')
@endif
<div class="form-grid"><x-field name="name" label="Nama lengkap" :value="$account->name" required autocomplete="name"/><x-field name="email" label="Email login" type="email" :value="$account->email" required autocomplete="email"/></div>
<div class="form-grid"><div class="field"><label for="role">Peran</label><select name="role" id="role">
@foreach(config('gym.account_roles') as $value=>$label)<option value="{{ $value }}" @selected(old('role',$account->role)===$value)>{{ $label }}</option>
@endforeach</select></div><div class="field"><label for="is_active">Status akses</label><select name="is_active" id="is_active"><option value="1" @selected((string)old('is_active',$account->is_active?'1':'0')==='1')>Aktif</option><option value="0" @selected((string)old('is_active',$account->is_active?'1':'0')==='0')>Nonaktif</option></select></div></div>
<div data-user-member><div class="field"><label for="member_id">Hubungkan profil anggota</label><select name="member_id" id="member_id"><option value="">Pilih anggota tanpa akun login</option>
@foreach($members as $member)<option value="{{ $member->id }}" @selected(old('member_id',$account->member?->id)===$member->id)>{{ $member->full_name }} · {{ $member->member_number }}</option>
@endforeach</select></div><p class="muted">Untuk anggota baru, gunakan menu Daftar anggota terlebih dahulu. Akun yang sudah terhubung tidak bisa dipindahkan ke orang lain.</p></div>
<fieldset class="user-trainer-fields" data-user-trainer><legend>Biodata trainer</legend><x-field name="specialization" label="Spesialisasi" :value="$account->trainerProfile?->specialization ?? ''" placeholder="Pilates, strength, mobility"/><x-field name="experience_years" label="Pengalaman (tahun)" type="number" :value="$account->trainerProfile?->experience_years ?? ''" min="0" max="70"/><div class="field"><label for="qualifications">Sertifikasi / kualifikasi</label><textarea id="qualifications" name="qualifications" rows="3" maxlength="1000">{{ old('qualifications',$account->trainerProfile?->qualifications) }}</textarea></div><div class="field"><label for="bio">Tentang trainer</label><textarea id="bio" name="bio" rows="4" maxlength="2000">{{ old('bio',$account->trainerProfile?->bio) }}</textarea></div></fieldset>
<fieldset><legend>Permission resepsionis</legend><p class="muted">Berlaku hanya untuk peran resepsionis. Owner memiliki seluruh kewenangan management.</p>
<input type="hidden" name="permissions_present" value="1">
@foreach(config('gym.staff_permissions') as $permission => $label)
<label class="checkbox"><input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, old('permissions', $account->permissions ?? ['booking.manage']), true))> {{ $label }}</label>
@endforeach</fieldset>
<h2>{{ $account->exists?'Reset password (opsional)':'Password awal' }}</h2>
@if($account->exists)<p class="muted">Kosongkan kedua kolom untuk mempertahankan password. Penggantian password membatalkan sesi lain.</p>
@endif<div class="form-grid"><x-field name="password" label="Password (minimal 12 karakter)" type="password" :required="!$account->exists" minlength="12" autocomplete="new-password"/><x-field name="password_confirmation" label="Ulangi password" type="password" :required="!$account->exists" minlength="12" autocomplete="new-password"/></div><button class="button">{{ $account->exists?'Simpan perubahan':'Buat akun' }} <x-icon name="arrow"/></button></form></section><aside class="info-card"><span class="eyebrow">HAK AKSES</span><h2>Satu akun, satu peran.</h2><p>Owner mengelola seluruh fitur. Resepsionis menjalankan front desk dan pembayaran. Trainer melihat sesi yang ditugaskan. Member melihat data pribadi.</p><p>Owner tidak dapat menonaktifkan atau menghapus aksesnya sendiri. Akun dengan shift terbuka atau jadwal trainer mendatang perlu diselesaikan terlebih dahulu.</p></aside></div>
@endsection
