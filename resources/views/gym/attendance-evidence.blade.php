@foreach(['clock_in' => 'Foto masuk', 'clock_out' => 'Foto pulang'] as $phase => $label)
@if($attendance->{$phase.'_photo_path'})
<small><a class="text-link" href="{{ route('staff.attendance.photo', [$attendance, $phase]) }}" target="_blank" rel="noopener noreferrer">{{ $label }}</a> · Bukti server {{ $attendance->{$phase.'_evidence_at'}->timezone(config('gym.timezone'))->format('d M Y H:i:s') }}</small>
@endif
@endforeach
