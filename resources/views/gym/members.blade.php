@extends('layouts.app')
@section('title','Anggota')
@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">DATABASE</span>
        <h1>Data Anggota<span class="gold">.</span></h1>
        <p>Database Member, Pelajar, dan Pengunjung Harian.</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a class="button" href="{{ route('members.new') }}"><x-icon name="plus"/>Tambah Anggota</a>
    </div>
</div>

{{-- Counter & Kategori Filter Pills --}}
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
    <a href="{{ route('members') }}" class="badge {{ !request('customer_type') ? 'positive' : 'neutral' }}" style="padding:8px 14px;font-size:0.9rem;text-decoration:none;">
        <strong>Semua:</strong> {{ $counts['all'] }}
    </a>
    <a href="{{ route('members', array_merge(request()->query(), ['customer_type' => 'member'])) }}" class="badge {{ request('customer_type') === 'member' ? 'positive' : 'neutral' }}" style="padding:8px 14px;font-size:0.9rem;text-decoration:none;">
        <strong>Member:</strong> {{ $counts['member'] }}
    </a>
    <a href="{{ route('members', array_merge(request()->query(), ['customer_type' => 'student'])) }}" class="badge {{ request('customer_type') === 'student' ? 'positive' : 'neutral' }}" style="padding:8px 14px;font-size:0.9rem;text-decoration:none;">
        <strong>Pelajar:</strong> {{ $counts['student'] }}
    </a>
    <a href="{{ route('members', array_merge(request()->query(), ['customer_type' => 'non_member'])) }}" class="badge {{ request('customer_type') === 'non_member' ? 'positive' : 'neutral' }}" style="padding:8px 14px;font-size:0.9rem;text-decoration:none;">
        <strong>Harian:</strong> {{ $counts['non_member'] }}
    </a>
</div>

<section class="panel">
    <form class="filter-toolbar" method="get">
        <div class="search-box">
            <x-icon name="search"/>
            <label class="sr-only" for="q">Cari anggota</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="Cari nama, nomor anggota, atau telepon">
        </div>

        <label class="sr-only" for="customer_type">Kategori</label>
        <select id="customer_type" name="customer_type">
            <option value="">Semua Kategori</option>
            <option value="member" @selected(request('customer_type')==='member')>Member Reguler</option>
            <option value="student" @selected(request('customer_type')==='student')>Pelajar / Student</option>
            <option value="non_member" @selected(request('customer_type')==='non_member')>Non-Member / Harian</option>
        </select>

        <label class="sr-only" for="status">Status membership</label>
        <select id="status" name="status">
            <option value="">Semua status</option>
            @foreach(['active'=>'Aktif','expired'=>'Tidak aktif','suspended'=>'Ditangguhkan'] as $value=>$label)
                <option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>
            @endforeach
        </select>

        <button class="button secondary">Filter</button>
        @if(request()->anyFilled(['q', 'status', 'customer_type']))
            <a class="button ghost" href="{{ route('members') }}">Reset</a>
        @endif
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Anggota</th>
                    <th>Kategori</th>
                    <th>Kontak (WA)</th>
                    <th>Membership</th>
                    <th>Berlaku sampai</th>
                    <th>Status</th>
                    <th>WhatsApp &amp; Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $m)
                    <tr>
                        <td>
                            <div class="table-person">
                                <span class="avatar">{{ mb_substr($m->full_name,0,1) }}</span>
                                <div>
                                    <strong>{{ $m->full_name }}</strong>
                                    <small>{{ $m->member_number }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge neutral" style="font-size:0.75rem;">{{ $m->customer_type_label }}</span>
                            @if($m->student_id_number)
                                <br><small class="muted">ID: {{ $m->student_id_number }}</small>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $m->phone }}</strong>
                            @if($m->email)<br><small class="muted">{{ $m->email }}</small>@endif
                        </td>
                        <td>{{ $m->currentPeriod?->invoice->snapshot['name'] ?? 'Belum ada paket aktif' }}</td>
                        <td>{{ $m->currentPeriod?->ends_at->timezone(config('gym.timezone'))->format('d M Y') ?? '—' }}</td>
                        <td><x-status :status="$m->status==='suspended'?'suspended':($m->currentPeriod?'active':'expired')"/></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <a class="button compact secondary" href="{{ $whatsapp->getMemberPassUrl($m) }}" target="_blank" rel="noopener noreferrer" title="Kirim Kartu & Barcode Anggota ke WhatsApp">
                                    WA Kartu
                                </a>
                                @if($m->currentPeriod)
                                    <a class="button compact ghost" href="{{ $whatsapp->getExpiryReminderUrl($m) }}" target="_blank" rel="noopener noreferrer" title="Kirim Pengingat Sisa Masa Aktif via WhatsApp">
                                        WA Reminder
                                    </a>
                                @endif
                                <a class="text-link" href="{{ route('members.show',$m) }}">Detail <x-icon name="arrow"/></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="7">Tidak ada anggota ditemukan. Coba pencarian lain atau daftarkan anggota baru.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $members->links() }}</div>
</section>
@endsection
