@extends('layouts.app')
@section('title','Member binaan')
@section('content')
<div class="page-heading"><div><span class="eyebrow">COACHING</span><h1>Member binaan<span class="gold">.</span></h1><p>Catat progres anggota yang terdaftar pada kelas Anda.</p></div></div>
<div class="trainer-grid">
@forelse($members as $member)
<section class="panel form-panel"><h2>{{ $member->full_name }}</h2><p class="muted">{{ $member->member_number }}</p><a class="button secondary" href="{{ route('coaching.member',$member) }}">Catat & lihat progres <x-icon name="arrow"/></a></section>
@empty
<section class="panel form-panel"><h2>Belum ada member binaan</h2><p>Anggota akan muncul setelah terdaftar pada kelas Anda.</p></section>
@endforelse
</div>{{ $members->links() }}
@endsection
