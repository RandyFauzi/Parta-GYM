@foreach($entitlements as $entitlement)
<small>{{ $entitlement->label }}: {{ $entitlement->used }} terpakai · {{ $entitlement->reserved }} terjadwal · {{ $entitlement->remaining === null ? 'Tanpa batas' : $entitlement->remaining.' tersisa' }}</small>
@endforeach
