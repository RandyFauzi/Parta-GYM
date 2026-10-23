<section class="m-surface m-benefits" aria-label="Manfaat paket {{ ucfirst($dashboardActivity) }}">
    <div class="m-section-heading"><div><span class="m-kicker">MANFAAT &amp; KUOTA</span><h2>Akses {{ ucfirst($dashboardActivity) }} Anda</h2></div></div>
    @if($member->status !== 'active')
        <p class="alert error" role="status">Akses member sedang ditangguhkan. Hubungi front desk untuk aktivasi kembali.</p>
    @endif
    <div class="m-benefit-grid">
    @forelse($benefits as $benefit)
        <article class="m-benefit-card">
            <div class="m-benefit-heading"><h3>{{ $benefit['label'] }}</h3><span class="badge {{ $member->status !== 'active' ? 'negative' : ($benefit['remaining'] === 0 ? 'neutral' : 'positive') }}">{{ $member->status !== 'active' ? 'Ditangguhkan' : ($benefit['remaining'] === 0 ? 'Kuota habis' : 'Aktif') }}</span></div>
            <p>{{ $benefit['period']->invoice?->snapshot['name'] ?? 'Paket member' }}</p>
            <dl class="m-detail-list">
                <div><dt>Total kuota</dt><dd>{{ $benefit['quota'] === null ? 'Tanpa batas' : $benefit['quota'] }}</dd></div>
                <div><dt>Terpakai</dt><dd>{{ $benefit['used'] }}</dd></div>
                @if($benefit['key'] !== 'gym_access')<div><dt>Dipesan</dt><dd>{{ $benefit['reserved'] }}</dd></div>@endif
                <div><dt>Tersisa</dt><dd>{{ $benefit['remaining'] === null ? 'Tanpa batas' : $benefit['remaining'] }}</dd></div>
                <div><dt>Berlaku sampai</dt><dd>{{ $benefit['period']->ends_at->timezone(config('gym.timezone'))->format('d M Y H:i') }}</dd></div>
            </dl>
            @if($benefit['shared'])<p class="m-context-note">Kuota privat paket lama dipakai bersama Gym dan Pilates. Angka yang sama di kedua dashboard berasal dari satu kuota.</p>@endif
        </article>
    @empty
        <div class="m-empty"><x-icon name="card"/><h3>Belum ada manfaat {{ ucfirst($dashboardActivity) }} aktif</h3><p>Paket belum aktif, terjadwal, atau sudah berakhir. Pilih paket yang sesuai atau hubungi front desk.</p><a class="button secondary" href="{{ route('portal.membership', ['activity' => $dashboardActivity]) }}">Lihat paket {{ ucfirst($dashboardActivity) }}</a></div>
    @endforelse
    </div>
    <p class="m-context-note">Setiap manfaat dihitung terpisah. Booking mencadangkan kuota; hadir atau tidak hadir menggunakan sesi. Pembatalan mengembalikan kuota.</p>
</section>
