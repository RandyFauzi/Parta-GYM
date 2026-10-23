<section class="panel form-panel spaced">
    <h2>Penugasan PT oleh management</h2>
    <p>Member dan PT tidak memilih pasangan sendiri. Penugasan terpisah untuk Gym dan Pilates.</p>
    @forelse($assignments as $assignment)
        <div class="list-row"><div><strong>{{ ucfirst($assignment->activity) }} · {{ $assignment->trainer->user->name }}</strong><small>{{ $assignment->revoked_at ? 'Dicabut' : ($assignment->ends_at?->lte(now()) ? 'Berakhir' : 'Aktif') }} · Mulai {{ $assignment->starts_at->timezone(config('gym.timezone'))->format('d M Y H:i') }} · {{ $assignment->ends_at?->timezone(config('gym.timezone'))->format('d M Y H:i') ?? 'Tanpa batas akhir' }}</small><small>{{ $assignment->reason }}</small></div></div>
        @can('assignments.manage')
            @if(!$assignment->revoked_at)
                <form action="{{ route('trainer-assignments.destroy', $assignment) }}" method="post">@csrf @method('DELETE')<x-field name="reason" :id="'revoke-reason-'.$assignment->id" label="Alasan pencabutan" required minlength="5" maxlength="500"/><button class="button secondary">Cabut penugasan</button></form>
            @endif
        @endcan
    @empty<p class="muted">Belum ada PT yang ditugaskan.</p>@endforelse
    @can('assignments.manage')
        <details class="spaced"><summary>Tetapkan / ganti PT</summary>
            <form method="post" action="{{ route('trainer-assignments.store', $member) }}">@csrf
                <div class="field"><label for="assignment-activity">Aktivitas</label><select id="assignment-activity" name="activity"><option value="gym" @selected(old('activity', 'gym')==='gym')>Gym</option><option value="pilates" @selected(old('activity')==='pilates')>Pilates</option></select></div>
                <div class="field"><label for="assignment-trainer">PT aktif</label><select id="assignment-trainer" name="trainer_profile_id" required><option value="">Pilih PT</option>@foreach($trainers as $trainer)<option value="{{ $trainer->id }}" @selected(old('trainer_profile_id')===$trainer->id)>{{ $trainer->user->name }}</option>@endforeach</select></div>
                <x-field name="ends_at" label="Akhir penugasan (opsional)" type="datetime-local"/>
                <x-field name="reason" id="assignment-reason" label="Alasan penugasan / pergantian" required minlength="5" maxlength="500"/>
                <button class="button">Simpan penugasan</button>
            </form>
        </details>
    @endcan
</section>
