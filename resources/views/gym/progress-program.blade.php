<p class="muted">Laporan ini langsung terlihat oleh member. Isi hanya catatan yang ingin dibagikan kepadanya.</p>
<div class="form-grid">
    <div class="field"><label for="progress_program_name">Nama program</label><input id="progress_program_name" name="program_name" maxlength="120" value="{{ old('program_name') }}" placeholder="Contoh: Kekuatan dasar minggu 4"></div>
    <div class="field"><label for="progress_activity">Aktivitas *</label><select id="progress_activity" name="activity" required>
        @foreach(['gym' => 'Gym', 'pilates' => 'Pilates'] as $value => $label)
            @if(auth()->user()->role === 'owner' || $member->isCoachedBy(auth()->user(), $value))
                <option value="{{ $value }}" @selected(old('activity') === $value)>{{ $label }}</option>
            @endif
        @endforeach
    </select>@error('activity')<small role="alert">{{ $message }}</small>@enderror</div>
    @isset($sessions)
    <div class="field"><label for="progress_session">Sesi terkait (opsional)</label><select id="progress_session" name="training_session_id"><option value="">Evaluasi di luar sesi</option>@foreach($sessions as $enrollment)<option value="{{ $enrollment->training_session_id }}" @selected(old('training_session_id') === $enrollment->training_session_id)>{{ $enrollment->session->title }} · {{ $enrollment->session->starts_at->timezone(config('gym.timezone'))->format('d M Y H:i') }}</option>@endforeach</select></div>
    @endisset
</div>
<details data-progress-exercises @if(old('exercises')) open @endif>
    <summary class="text-link">Rincian gerakan (opsional, maksimal 20)</summary>
    <p class="muted">Isi gerakan yang dilakukan. Kosongkan baris yang tidak digunakan. Beban 0 kg berarti tanpa beban tambahan.</p>
    <div data-exercise-rows>
    @for($i = 0; $i < max(5, count(old('exercises', []))); $i++)
    <fieldset data-exercise-row><legend>Gerakan {{ $i + 1 }}</legend><div class="form-grid">
        @foreach(['name' => ['Nama gerakan', 'text', null, null], 'sets' => ['Set', 'number', '1', '100'], 'reps' => ['Repetisi per set', 'number', '1', '1000'], 'load_kg' => ['Beban (kg)', 'number', '0', '1000'], 'duration_minutes' => ['Durasi (menit)', 'number', '0.1', '600']] as $key => [$label, $type, $min, $max])
        <div class="field"><label for="exercise_{{ $i }}_{{ $key }}">{{ $label }}</label><input id="exercise_{{ $i }}_{{ $key }}" name="exercises[{{ $i }}][{{ $key }}]" type="{{ $type }}" value="{{ old('exercises.'.$i.'.'.$key) }}" @if($type === 'text') maxlength="120" @else min="{{ $min }}" max="{{ $max }}" step="{{ in_array($key, ['sets', 'reps']) ? '1' : '0.1' }}" @endif>@error('exercises.'.$i.'.'.$key)<small role="alert">{{ $message }}</small>@enderror</div>
        @endforeach
    </div></fieldset>
    @endfor
    </div>
    <button type="button" class="button secondary" data-exercise-add>Tambah gerakan</button>
    <p class="muted" role="status" data-exercise-status></p>
</details>
