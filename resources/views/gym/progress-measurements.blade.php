@if(!empty($measurements))
<section class="panel spaced" aria-label="Perbandingan pengukuran">
    <h2>Perkembangan pengukuran</h2>
    <p class="muted">Pengukuran pertama dibanding terbaru yang tersedia. Nilai kosong dilewati; perubahan bukan penilaian baik atau buruk.</p>
    <div class="table-wrap"><table><thead><tr><th scope="col">Pengukuran</th><th scope="col">Pertama</th><th scope="col">Terbaru</th><th scope="col">Perubahan</th></tr></thead><tbody>
    @foreach(['weight_kg' => ['Berat badan', 'kg'], 'body_fat_percentage' => ['Body fat', '%'], 'muscle_mass_kg' => ['Massa otot', 'kg'], 'waist_cm' => ['Lingkar pinggang', 'cm']] as $metric => [$label, $unit])
        @isset($measurements[$metric])
        @php($measurement = $measurements[$metric])
        <tr><th scope="row">{{ $label }}</th><td>{{ $measurement['first'] }} {{ $unit }}<br><small>{{ $measurement['from']->timezone(config('gym.timezone'))->format('d M Y') }}</small></td><td>{{ $measurement['latest'] }} {{ $unit }}<br><small>{{ $measurement['to']->timezone(config('gym.timezone'))->format('d M Y') }}</small></td><td>{{ $measurement['change'] > 0 ? '+' : '' }}{{ $measurement['change'] }} {{ $unit }}</td></tr>
        @endisset
    @endforeach
    </tbody></table></div>
</section>
@endif
