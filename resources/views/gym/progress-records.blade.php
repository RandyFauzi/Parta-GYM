{{-- Catatan & Laporan Progres PT --}}
<section class="panel spaced">
    <div class="panel-heading">
        <div>
            <h2>Catatan &amp; Evaluasi Progres PT</h2>
            <p>Pencatatan perkembangan fisik, beban latihan, evaluasi nutrisi, dan target oleh Personal Trainer.</p>
        </div>
        @if(in_array(auth()->user()->role, ['owner', 'personal_trainer'], true))
            <span class="badge positive">PT Mode</span>
        @endif
    </div>

    @if(in_array(auth()->user()->role, ['owner', 'personal_trainer'], true))
        <details class="form-panel" style="margin-bottom:20px;padding:16px;background:rgba(255,255,255,0.02);border-radius:8px;border:1px solid rgba(255,255,255,0.08);">
            <summary class="text-link"><strong>+ Tambah Catatan Progres / Evaluasi Latihan</strong></summary>
            <form action="{{ route('members.progress.store', $member) }}" method="post" style="margin-top:16px;">
                @csrf
                <input type="hidden" name="request_key" value="{{ old('request_key', (string) \Illuminate\Support\Str::uuid()) }}">
                @include('gym.progress-program')
                <div class="form-grid">
                    <div class="field">
                        <label for="progress_recorded_at">Tanggal &amp; Waktu Sesi *</label>
                        <input id="progress_recorded_at" name="recorded_at" type="datetime-local" value="{{ old('recorded_at', now()->timezone(config('gym.timezone'))->format('Y-m-d\TH:i')) }}" required>
                    </div>
                    <div class="field">
                        <label for="progress_weight">Berat Badan (kg)</label>
                        <input id="progress_weight" name="weight_kg" type="number" step="0.1" min="20" max="300" value="{{ old('weight_kg') }}" placeholder="Contoh: 70.5">
                    </div>
                    <div class="field">
                        <label for="progress_height">Tinggi Badan (cm)</label>
                        <input id="progress_height" name="height_cm" type="number" step="0.5" min="50" max="250" value="{{ old('height_cm') }}" placeholder="Contoh: 175">
                    </div>
                    <div class="field">
                        <label for="progress_body_fat">Body Fat (%)</label>
                        <input id="progress_body_fat" name="body_fat_percentage" type="number" step="0.1" min="1" max="70" value="{{ old('body_fat_percentage') }}" placeholder="Contoh: 18.5">
                    </div>
                    <div class="field">
                        <label for="progress_muscle_mass">Massa Otot (kg)</label>
                        <input id="progress_muscle_mass" name="muscle_mass_kg" type="number" step="0.1" min="5" max="150" value="{{ old('muscle_mass_kg') }}" placeholder="Contoh: 32.0">
                    </div>
                    <div class="field">
                        <label for="progress_waist">Lingkar Pinggang (cm)</label>
                        <input id="progress_waist" name="waist_cm" type="number" step="0.5" min="30" max="200" value="{{ old('waist_cm') }}" placeholder="Contoh: 82.5">
                    </div>
                </div>

                <div class="field">
                    <label for="progress_workout">Program &amp; Catatan Latihan Hari Ini *</label>
                    <textarea id="progress_workout" name="workout_summary" rows="3" required placeholder="Tuliskan gerakan, set, beban, atau fokus latihan hari ini (misal: Chest Day: Bench Press 60kg 4x10, Incline Dumbbell...)">{{ old('workout_summary') }}</textarea>
                </div>

                <div class="field">
                    <label for="progress_coach_notes">Saran &amp; Catatan Khusus Pelatih (opsional)</label>
                    <textarea id="progress_coach_notes" name="coach_notes" rows="2" placeholder="Catatan teknik gerakan, fleksibilitas, atau hal yang perlu diperhatikan member">{{ old('coach_notes') }}</textarea>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="progress_diet">Evaluasi Pola Makan / Nutrisi (opsional)</label>
                        <textarea id="progress_diet" name="diet_summary" rows="2" placeholder="Evaluasi asupan kalori, protein harian, atau konsumsi air">{{ old('diet_summary') }}</textarea>
                    </div>
                    <div class="field">
                        <label for="progress_goals">Target Sesi Berikutnya (opsional)</label>
                        <textarea id="progress_goals" name="next_goals" rows="2" placeholder="Target beban, durasi kardio, atau target repetisi berikutnya">{{ old('next_goals') }}</textarea>
                    </div>
                </div>

                <button class="button compact">Simpan Catatan Progres <x-icon name="arrow"/></button>
            </form>
        </details>
    @endif

    @include('gym.progress-measurements')
    <div class="progress-timeline">
        @forelse(($records ?? $member->progressRecords) as $pr)
            <div style="padding:16px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);border-radius:10px;margin-bottom:12px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;margin-bottom:8px;">
                    <div>
                        <strong>{{ $pr->recorded_at->timezone(config('gym.timezone'))->format('d M Y · H:i') }}</strong>
                        <small class="muted" style="margin-left:8px;">Pelatih: <strong>{{ $pr->trainer->name }}</strong></small>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        @if($pr->weight_kg) <span class="badge neutral">BB: {{ $pr->weight_kg }} kg</span> @endif
                        @if($pr->body_fat_percentage) <span class="badge neutral">Fat: {{ $pr->body_fat_percentage }}%</span> @endif
                        @if($pr->bmi) <span class="badge positive">BMI: {{ $pr->bmi }}</span> @endif

                        @if(auth()->user()->role === 'owner' || (auth()->user()->role === 'personal_trainer' && $pr->trainer_id === auth()->id()))
                            <form action="{{ route('members.progress.destroy', $pr) }}" method="post" onsubmit="return confirm('Hapus catatan progres ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="button compact ghost" style="color:#ef4444;padding:2px 8px;font-size:0.75rem;">Hapus</button>
                            </form>
                        @endif
                    </div>
                </div>

                <p><strong>{{ $pr->program_name ?? 'Evaluasi latihan' }}</strong> · {{ $pr->activity ? ucfirst($pr->activity) : 'Catatan lama' }}</p>
                <a class="text-link" href="{{ route('members.progress.show', [$member, $pr]) }}">Lihat laporan lengkap</a>
                <p style="margin:4px 0 8px;line-height:1.5;white-space:pre-line;">{{ $pr->workout_summary }}</p>

                @if($pr->coach_notes)
                    <div style="margin-top:6px;font-size:0.85rem;color:#e5e7eb;background:rgba(255,255,255,0.03);padding:6px 10px;border-radius:6px;">
                        <strong>Saran Trainer:</strong> {{ $pr->coach_notes }}
                    </div>
                @endif
                @if($pr->next_goals)
                    <div style="margin-top:6px;font-size:0.85rem;color:#f59e0b;">
                        <strong>Target Berikutnya:</strong> {{ $pr->next_goals }}
                    </div>
                @endif
            </div>
        @empty
            <div class="empty">Belum ada catatan progres atau hasil latihan yang dicatat untuk anggota ini.</div>
        @endforelse
    </div>
    @isset($records) {{ $records->links() }} @endisset
</section>
