@extends('layouts.app')
@section('title','Registrasi anggota')
@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">REGISTRASI &amp; DATABASE ANGGOTA</span>
        <h1>Daftar. Bayar. Mulai<span class="gold">.</span></h1>
        <p>Registrasi baru, perpanjangan member lama, tiket harian (non-member), atau pelajar/mahasiswa.</p>
    </div>
    <a class="button secondary" href="{{ route('members') }}">Kembali ke anggota</a>
</div>

{{-- Panel Reaktivasi Member Lama --}}
<section class="panel spaced" style="margin-bottom: 24px;">
    <div class="panel-heading">
        <div>
            <h2>Cari Member Lama (Reaktivasi Cepat)</h2>
            <p>Untuk anggota yang pernah terdaftar, cari nama atau WhatsApp untuk langsung menambah paket tanpa perlu mendaftar ulang.</p>
        </div>
        <span class="badge neutral"><x-icon name="search"/> Member Lama</span>
    </div>
    <form method="get" action="{{ route('members.new') }}" class="filter-toolbar">
        <div class="search-box">
            <x-icon name="search"/>
            <label class="sr-only" for="search_returning">Cari nama atau telepon member lama</label>
            <input id="search_returning" name="search_returning" value="{{ request('search_returning') }}" placeholder="Ketik nama, WhatsApp, atau nomor member (contoh: Budi / 0812...)">
        </div>
        <button class="button secondary">Cari Member Lama</button>
        @if(request('search_returning'))
            <a class="button ghost" href="{{ route('members.new') }}">Reset</a>
        @endif
    </form>
    @if(request()->filled('search_returning'))
        <div style="margin-top: 16px;">
            @if($returningMembers->isNotEmpty())
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Anggota</th>
                                <th>Kontak</th>
                                <th>Kategori</th>
                                <th>Status Terakhir</th>
                                <th>Aksi Reaktivasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($returningMembers as $rm)
                                <tr>
                                    <td>
                                        <div class="table-person">
                                            <span class="avatar">{{ mb_substr($rm->full_name,0,1) }}</span>
                                            <div>
                                                <strong>{{ $rm->full_name }}</strong>
                                                <small>{{ $rm->member_number }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $rm->phone }}</td>
                                    <td><span class="badge neutral">{{ $rm->customer_type_label }}</span></td>
                                    <td><x-status :status="$rm->currentPeriod ? 'active' : 'expired'"/></td>
                                    <td>
                                        <a class="button compact" href="{{ route('members.show', $rm) }}">
                                            Pilih &amp; Tambah Paket <x-icon name="arrow"/>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="muted">Tidak ada member lama ditemukan dengan kata kunci "{{ request('search_returning') }}". Silakan gunakan form pendaftaran di bawah.</p>
            @endif
        </div>
    @endif
</section>

<div class="form-layout">
    <section class="panel form-panel">
        <form method="post" action="{{ route('members.store') }}" data-quick-registration>
            @csrf
            <input type="hidden" name="request_key" value="{{ old('request_key',(string)Illuminate\Support\Str::uuid()) }}">

            <h2>1. Kategori &amp; Informasi Anggota</h2>

            <div class="field">
                <label for="customer_type">Kategori Customer <span aria-hidden="true">*</span></label>
                <select id="customer_type" name="customer_type" required>
                    <option value="member" @selected(old('customer_type', 'member') === 'member')>Member Reguler</option>
                    <option value="student" @selected(old('customer_type') === 'student')>Pelajar / Mahasiswa (Student)</option>
                    <option value="non_member" @selected(old('customer_type') === 'non_member')>Non-Member (Harian / Visit)</option>
                </select>
                <small class="muted">Pilih jenis keanggotaan untuk penyesuaian paket dan data identitas.</small>
            </div>

            <x-field name="full_name" label="Nama lengkap" required autocomplete="name"/>
            <x-field name="phone" label="WhatsApp" type="tel" required placeholder="0812 3456 7890" autocomplete="tel"/>
            <x-field name="email" label="Email (opsional)" type="email" autocomplete="email"/>

            <div class="field">
                <x-field name="student_id_number" label="Nomor Kartu Pelajar / Mahasiswa (khusus Student)" :value="old('student_id_number')" placeholder="Contoh: NIS / NIM / No. Kartu"/>
            </div>

            <div class="field">
                <x-field name="notes" label="Catatan Tambahan (opsional)" :value="old('notes')" placeholder="Contoh: Info kesehatan khusus, rujukan teman, atau catatan kasir"/>
            </div>

            <details class="registration-account" @if(old('create_account')) open @endif>
                <summary class="text-link">Tambahkan akses login anggota</summary>
                <label class="checkbox">
                    <input type="checkbox" name="create_account" value="1" @checked(old('create_account'))> Buat akun member dengan email di atas
                </label>
                <div data-account-fields>
                    <x-field name="password" label="Password awal" type="password" autocomplete="new-password" minlength="12"/>
                    <p class="muted">Minimal 12 karakter. Sampaikan secara pribadi; member dapat menggantinya dari profil.</p>
                </div>
            </details>

            <h2>2. Paket &amp; Pembayaran</h2>
            <div class="field">
                <label for="plan_id">Paket membership</label>
                <select id="plan_id" name="plan_id">
                    <option value="">Daftar tanpa paket dulu</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" data-price="{{ $plan->price_idr }}" @selected(old('plan_id')===$plan->id)>
                            {{ $plan->name }} · Rp {{ number_format($plan->price_idr,0,',','.') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="payment_status">Status pembayaran</label>
                <select id="payment_status" name="payment_status">
                    <option value="pending" @selected(old('payment_status','pending')==='pending')>Bayar nanti — buat tagihan</option>
                    <option value="paid" @selected(old('payment_status')==='paid')>Sudah dibayar — aktifkan membership</option>
                </select>
            </div>

            <fieldset class="registration-payment" data-registration-payment>
                <legend>Pembayaran diterima</legend>
                <div class="field">
                    <label for="method">Metode pembayaran</label>
                    <select id="method" name="method">
                        @foreach(config('gym.payment_methods') as $value=>$label)
                            <option value="{{ $value }}" @selected(old('method')===$value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <x-field name="reference" label="Referensi / catatan" placeholder="Nomor transaksi atau keterangan pembayaran" maxlength="120"/>
                <p class="muted">Transfer dan metode lainnya membutuhkan catatan. Pembayaran tunai membutuhkan shift kasir terbuka.</p>
                <label class="checkbox">
                    <input type="checkbox" name="confirmed" value="1" @checked(old('confirmed'))> Saya sudah menerima dana sesuai harga paket
                </label>
            </fieldset>

            <button class="button wide">Simpan pendaftaran <x-icon name="arrow"/></button>
        </form>
    </section>

    <aside class="info-card">
        <span class="eyebrow">RINGKASAN</span>
        <h2>Siap untuk mulai.</h2>
        <p>Total paket</p>
        <strong class="registration-total" data-registration-total>Rp 0</strong>
        <p data-registration-note>Anggota tersimpan. Membership aktif setelah pembayaran dikonfirmasi tim.</p>
        <a class="text-link" href="{{ route('shifts') }}">Buka / periksa shift kasir <x-icon name="arrow"/></a>
        <hr>
        <p><strong>Member Baru / Lama:</strong> Diberikan nomor unik PG-XXXX. Riwayat transaksi dan kehadiran tercatat rapi.</p>
        <p><strong>WhatsApp Connect:</strong> Setelah pendaftaran, Anda dapat langsung mengirimkan bukti struk dan barcode kartu member via WhatsApp.</p>
    </aside>
</div>
@endsection
