@if($record->exercises)
<details open><summary>Rincian latihan</summary><div class="table-wrap"><table>
    <thead><tr><th scope="col">Gerakan</th><th scope="col">Set</th><th scope="col">Repetisi</th><th scope="col">Beban (kg)</th><th scope="col">Durasi (menit)</th></tr></thead>
    <tbody>@foreach($record->exercises as $exercise)<tr><th scope="row">{{ $exercise['name'] }}</th>@foreach(['sets', 'reps', 'load_kg', 'duration_minutes'] as $key)<td>{{ $exercise[$key] ?? '—' }}</td>@endforeach</tr>@endforeach</tbody>
</table></div></details>
@endif
