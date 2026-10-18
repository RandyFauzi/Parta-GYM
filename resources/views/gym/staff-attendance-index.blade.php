@extends('layouts.app')
@section('title', 'Absensi Karyawan & PT')
@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">OPERASIONAL</span>
        <h1>Absensi Karyawan &amp; PT<span class="gold">.</span></h1>
        <p>Pantau kehadiran, jam kerja, dan status staf &amp; Personal Trainer.</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <a class="button secondary" href="{{ route('staff.attendance.me') }}">Absensi Saya</a>
    </div>
</div>

{{-- KPI Ringkasan Kehadiran --}}
<div class="kpi-grid" style="margin-bottom:24px;">
    <section class="kpi">
        <div class="kpi-label">Total Staf &amp; PT <span class="kpi-icon"><x-icon name="users"/></span></div>
        <strong>{{ $stats['total_staff'] }}</strong>
        <small>Karyawan aktif</small>
    </section>
    <section class="kpi">
        <div class="kpi-label">Hadir Hari Ini <span class="kpi-icon"><x-icon name="check"/></span></div>
        <strong style="color:#8ed7a2;">{{ $stats['present'] }}</strong>
        <small>{{ $stats['late'] }} terlambat</small>
    </section>
    <section class="kpi">
        <div class="kpi-label">Izin / Sakit <span class="kpi-icon"><x-icon name="layers"/></span></div>
        <strong>{{ $stats['sick_or_permit'] }}</strong>
        <small>Izin tercatat</small>
    </section>
    <section class="kpi">
        <div class="kpi-label">Belum Absen <span class="kpi-icon"><x-icon name="clock"/></span></div>
        <strong style="color:{{ $stats['not_clocked_in'] > 0 ? 'var(--gold)' : 'var(--muted)' }};">{{ $stats['not_clocked_in'] }}</strong>
        <small>Perlu dicek</small>
    </section>
</div>

<section class="panel" style="margin-bottom:24px;">
    <form class="filter-toolbar" method="get" action="{{ route('staff.attendance.index') }}">
        <div class="field" style="margin:0;">
            <label class="sr-only" for="date">Tanggal</label>
            <input id="date" name="date" type="date" value="{{ $selectedDate }}" onchange="this.form.submit()">
        </div>

        <label class="sr-only" for="role">Jabatan</label>
        <select id="role" name="role" onchange="this.form.submit()">
            <option value="">Semua Jabatan</option>
            <option value="receptionist" @selected(request('role')==='receptionist')>Resepsionis</option>
            <option value="personal_trainer" @selected(request('role')==='personal_trainer')>Personal Trainer</option>
            <option value="owner" @selected(request('role')==='owner')>Owner / Admin</option>
        </select>

        <label class="sr-only" for="status">Status</label>
        <select id="status" name="status" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <option value="present" @selected(request('status')==='present')>Hadir</option>
            <option value="late" @selected(request('status')==='late')>Terlambat</option>
            <option value="sick" @selected(request('status')==='sick')>Sakit</option>
            <option value="permit" @selected(request('status')==='permit')>Izin</option>
            <option value="alpha" @selected(request('status')==='alpha')>Alpha</option>
        </select>

        <button class="button secondary compact">Filter</button>
        @if(request()->anyFilled(['date', 'role', 'status']))
            <a class="button ghost compact" href="{{ route('staff.attendance.index') }}">Reset</a>
        @endif
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Staf / PT</th>
                    <th>Jabatan</th>
                    <th>Masuk</th>
                    <th>Pulang</th>
                    <th>Durasi</th>
                    <th>Status</th>
                    <th>Catatan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $att)
                    <tr>
                        <td>
                            <div class="table-person">
                                <span class="avatar">{{ mb_substr($att->user->name, 0, 1) }}</span>
                                <div>
                                    <strong>{{ $att->user->name }}</strong>
                                    <small>{{ $att->user->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge neutral" style="font-size:0.75rem;">
                                {{ ['receptionist' => 'Resepsionis', 'personal_trainer' => 'Personal Trainer', 'owner' => 'Owner'][$att->user->role] ?? ucfirst($att->user->role) }}
                            </span>
                        </td>
                        <td>
                            @if($att->clock_in_at)
                                <strong style="color:#8ed7a2;">{{ $att->clock_in_at->timezone(config('gym.timezone'))->format('H:i') }}</strong>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($att->clock_out_at)
                                <strong style="color:var(--gold);">{{ $att->clock_out_at->timezone(config('gym.timezone'))->format('H:i') }}</strong>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>
                            {{ $att->work_duration_formatted ?? '—' }}
                        </td>
                        <td>
                            <span class="badge {{ in_array($att->status, ['present', 'late']) ? 'positive' : ($att->status === 'alpha' ? 'error' : 'pending') }}">
                                {{ $att->status_label }}
                            </span>
                        </td>
                        <td>
                            <small class="muted">{{ $att->notes ?: '—' }}</small>
                        </td>
                        <td>
                            <form action="{{ route('staff.attendance.destroy', $att) }}" method="post" onsubmit="return confirm('Hapus data absensi ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="button compact ghost" style="color:#ef4444;padding:2px 8px;font-size:0.75rem;">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty">Belum ada absensi pada {{ \Carbon\CarbonImmutable::parse($selectedDate)->translatedFormat('d F Y') }}.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $attendances->links() }}</div>
</section>

{{-- Form Pencatatan / Koreksi Manual oleh Owner --}}
<section class="panel form-panel spaced">
    <div class="panel-heading">
        <div>
            <h2>Input Presensi Manual</h2>
            <p>Catat izin, sakit, cuti, atau koreksi jam kerja karyawan.</p>
        </div>
    </div>

    <form action="{{ route('staff.attendance.manual') }}" method="post">
        @csrf
        <div class="form-grid">
            <div class="field">
                <label for="manual_user_id">Pilih Karyawan / PT *</label>
                <select id="manual_user_id" name="user_id" required>
                    <option value="">Pilih staf...</option>
                    @foreach($eligibleStaff as $st)
                        <option value="{{ $st->id }}" @selected(old('user_id') == $st->id)>
                            {{ $st->name }} ({{ ['receptionist' => 'Resepsionis', 'personal_trainer' => 'Personal Trainer', 'owner' => 'Owner'][$st->role] ?? $st->role }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="manual_date">Tanggal *</label>
                <input id="manual_date" name="date" type="date" value="{{ old('date', $selectedDate) }}" required>
            </div>

            <div class="field">
                <label for="manual_status">Status Kehadiran *</label>
                <select id="manual_status" name="status" required>
                    <option value="present">Hadir</option>
                    <option value="late">Terlambat</option>
                    <option value="sick">Sakit</option>
                    <option value="permit">Izin</option>
                    <option value="alpha">Alpha (Tanpa Keterangan)</option>
                </select>
            </div>

            <div class="field">
                <label for="manual_in">Jam Masuk (Clock In)</label>
                <input id="manual_in" name="clock_in_time" type="time" value="{{ old('clock_in_time') }}">
            </div>

            <div class="field">
                <label for="manual_out">Jam Pulang (Clock Out)</label>
                <input id="manual_out" name="clock_out_time" type="time" value="{{ old('clock_out_time') }}">
            </div>

            <div class="field">
                <label for="manual_notes">Catatan / Alasan</label>
                <input id="manual_notes" name="notes" type="text" placeholder="Contoh: Izin keluarga / sakit demam" value="{{ old('notes') }}">
            </div>
        </div>

        <button class="button"><x-icon name="check"/> Simpan Presensi</button>
    </form>
</section>
@endsection
