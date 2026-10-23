<section class="m-surface m-upcoming" aria-label="Jadwal pribadi {{ ucfirst($dashboardActivity) }}">
    <div class="m-section-heading"><div><span class="m-kicker">JADWAL ANDA</span><h2>Sesi {{ ucfirst($dashboardActivity) }} berikutnya</h2></div><a class="m-subtle-link" href="{{ route('classes', ['activity' => $dashboardActivity, 'mine' => 1]) }}">Semua jadwal <x-icon name="arrow"/></a></div>
    @forelse($upcoming as $enrollment)
        <article class="m-upcoming-row"><div><h3><a href="{{ route('classes.show', $enrollment->session) }}">{{ $enrollment->session->title }}</a></h3><p>{{ $enrollment->session->starts_at->timezone(config('gym.timezone'))->format('d M Y H:i') }} – {{ $enrollment->session->ends_at->timezone(config('gym.timezone'))->format('H:i') }}</p><small>{{ $enrollment->session->trainer?->user?->name ?? 'PT belum tersedia' }} · {{ $enrollment->session->room?->name ?? 'Ruang belum ditentukan' }} · {{ $enrollment->session->kind === 'personal' ? 'Privat' : 'Grup' }}</small></div><span class="badge positive">Dikonfirmasi</span></article>
    @empty
        <p class="muted">Belum ada jadwal {{ ucfirst($dashboardActivity) }} yang dikonfirmasi. Hubungi admin melalui WhatsApp untuk memilih jadwal.</p>
    @endforelse
    <div class="m-booking-actions">
    @if($bookingUrl)
        <a class="button" href="{{ $bookingUrl }}" target="_blank" rel="noopener noreferrer">Booking {{ ucfirst($dashboardActivity) }} via WhatsApp <x-icon name="arrow"/></a>
    @else
        <p class="m-context-note">Nomor WhatsApp booking belum diatur. Hubungi front desk untuk konfirmasi jadwal.</p>
    @endif
        <a class="button secondary" href="{{ route('classes', ['activity' => $dashboardActivity]) }}">Lihat kelas {{ ucfirst($dashboardActivity) }}</a>
    </div>
    <p class="m-context-note">Booking baru aktif setelah dikonfirmasi staf. PT ditugaskan oleh management.</p>
    @foreach($assignments as $assignment)
        <p class="m-context-note">PT {{ ucfirst($dashboardActivity) }} Anda: <strong>{{ $assignment->trainer->user->name }}</strong>@if($assignment->ends_at) · sampai {{ $assignment->ends_at->timezone(config('gym.timezone'))->format('d M Y') }}@endif</p>
    @endforeach
</section>
