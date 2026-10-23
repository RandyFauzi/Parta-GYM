@extends('layouts.app')
@section('title','Profil anggota')
@section('content')
<div class="page-heading">
    <div>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
            <span class="eyebrow">{{ $member->member_number }}</span>
            <span class="badge neutral" style="font-size:0.75rem;">{{ $member->customer_type_label }}</span>
            @if($member->student_id_number)
                <span class="badge neutral" style="font-size:0.75rem;">ID: {{ $member->student_id_number }}</span>
            @endif
        </div>
        <h1>{{ $member->full_name }}</h1>
        <p>{{ $member->phone }} @if($member->email) · {{ $member->email }} @endif</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <a class="button secondary" href="{{ $whatsapp->getMemberPassUrl($member) }}" target="_blank" rel="noopener noreferrer">
            Kirim Kartu WA <x-icon name="arrow"/>
        </a>
        @if($member->currentPeriod)
            <a class="button ghost" href="{{ $whatsapp->getExpiryReminderUrl($member) }}" target="_blank" rel="noopener noreferrer">
                Kirim Pengingat WA
            </a>
        @endif
        <a class="button secondary" href="{{ route('members') }}">Kembali ke anggota</a>
    </div>
</div>

<div class="two-columns">
    <section class="membership-card">
        <div class="card-top">
            <span>SPARTA Gym &amp; Pilates / {{ strtoupper($member->customer_type) }}</span>
            <x-status :status="$member->currentPeriod?'active':'expired'"/>
        </div>
        <h2>{{ $member->full_name }}</h2>
        <span class="card-number">{{ $member->member_number }}</span>
        <div class="card-bottom">
            <div>
                <small>MEMBERSHIP</small>
                <strong>{{ $member->currentPeriod?->invoice->snapshot['name'] ?? 'Belum aktif' }}</strong>
            </div>
            <div>
                <small>BERLAKU SAMPAI</small>
                <strong>{{ $member->currentPeriod?->ends_at->timezone(config('gym.timezone'))->format('d M Y, H:i') ?? '—' }}</strong>
            </div>
        </div>
        @if($member->notes)
            <div style="margin-top:16px;padding-top:12px;border-top:1px dashed rgba(255,255,255,0.2);font-size:0.85rem;color:rgba(255,255,255,0.8);">
                <strong>Catatan:</strong> {{ $member->notes }}
            </div>
        @endif
    </section>

    <section class="panel form-panel">
        <h2>Beli / perpanjang paket</h2>
        <p class="muted">Perpanjangan kategori dan susunan manfaat yang sama dimulai setelah periode terakhir. Paket dengan manfaat berbeda dapat berjalan bersamaan.</p>
        <form action="{{ route('invoices.store',$member) }}" method="post">
            @csrf
            <input type="hidden" name="request_key" value="{{ (string)Illuminate\Support\Str::uuid() }}">
            <div class="field">
                <label for="plan_id">Pilih paket</label>
                <select id="plan_id" name="plan_id" required>
                    @foreach($plans as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} · Rp {{ number_format($p->price_idr,0,',','.') }}</option>
                    @endforeach
                </select>
            </div>
            <button class="button" @disabled($plans->isEmpty())>Buat invoice <x-icon name="arrow"/></button>
        </form>
    </section>
</div>

<div class="two-columns spaced">
    <section class="panel">
        <div class="panel-heading">
            <h2>Invoice &amp; pembayaran</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $i)
                        <tr>
                            <td>
                                <a href="{{ route('invoices.show',$i) }}">{{ $i->invoice_number }}</a>
                                <small>{{ $i->snapshot['name'] }}</small>
                            </td>
                            <td>Rp {{ number_format($i->total_idr,0,',','.') }}</td>
                            <td><x-status :status="$i->status"/></td>
                            <td>
                                @if($i->status === 'paid')
                                    <a class="text-link" href="{{ $whatsapp->getReceiptUrl($i) }}" target="_blank" rel="noopener noreferrer">
                                        WA Struk
                                    </a>
                                @else
                                    <a class="text-link" href="{{ route('payments') }}">Bayar</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty">Belum ada invoice.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <h2>Riwayat kunjungan</h2>
        </div>
        @forelse($visits as $v)
            <div class="list-row">
                <x-icon name="scan"/>
                <div>
                    <strong>{{ $v->checked_in_at->timezone(config('gym.timezone'))->format('d M Y') }}</strong>
                    <small>Check-in oleh front desk</small>
                </div>
                <span>{{ $v->checked_in_at->timezone(config('gym.timezone'))->format('H:i') }}</span>
            </div>
        @empty
            <div class="empty">Belum ada kunjungan tercatat.</div>
        @endforelse
    </section>
</div>

@if(auth()->user()->role==='owner')
@include('gym.progress-records')
@endif

<details class="panel form-panel spaced">
    <summary>Ubah informasi profil</summary>
    <form action="{{ route('members.update',$member) }}" method="post">
        @csrf
        @method('PUT')
        <input name="request_key" type="hidden" value="{{ (string)Illuminate\Support\Str::uuid() }}">
        <div class="field">
            <label for="edit_customer_type">Kategori Customer</label>
            <select id="edit_customer_type" name="customer_type">
                <option value="member" @selected($member->customer_type==='member')>Member Reguler</option>
                <option value="student" @selected($member->customer_type==='student')>Pelajar / Mahasiswa (Student)</option>
                <option value="non_member" @selected($member->customer_type==='non_member')>Non-Member (Harian / Visit)</option>
            </select>
        </div>
        <x-field name="full_name" label="Nama lengkap" :value="$member->full_name" required/>
        <x-field name="phone" label="Telepon" :value="$member->phone" required/>
        <x-field name="email" label="Email" type="email" :value="$member->email"/>
        <x-field name="student_id_number" label="Nomor Kartu Pelajar (khusus Student)" :value="$member->student_id_number"/>
        <x-field name="notes" label="Catatan Tambahan" :value="$member->notes"/>
        <button class="button">Simpan profil</button>
    </form>
</details>

@if(auth()->user()->role==='owner')
    <details class="panel form-panel spaced">
        <summary>Kontrol akses anggota</summary>
        <form method="post" action="{{ route('members.status',$member) }}">
            @csrf
            <div class="field">
                <label for="access-status">Status akses</label>
                <select id="access-status" name="status">
                    <option value="active" @selected($member->status==='active')>Aktif</option>
                    <option value="suspended" @selected($member->status==='suspended')>Ditangguhkan</option>
                </select>
            </div>
            <x-field name="reason" label="Alasan perubahan" required minlength="5" maxlength="500"/>
            <button class="button">Simpan status &amp; catat audit</button>
        </form>
    </details>
@endif

@include('gym.trainer-assignments')
@include('gym.periods')
@endsection
