@extends('layouts.app')
@section('title','Progres member')
@section('content')
<div class="page-heading"><div><span class="eyebrow">MEMBER BINAAN</span><h1>{{ $member->full_name }}</h1><p>{{ $member->member_number }}</p></div><a class="button secondary" href="{{ route('coaching.members') }}">Kembali</a></div>
@include('gym.progress-records')
@endsection
