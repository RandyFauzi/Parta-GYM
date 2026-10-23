@php
    $selectedActivity ??= $dashboardActivity ?? app(\App\Services\MemberDashboardService::class)->selectedActivity($member, session('portal.activity.'.$member->id));
@endphp
<div class="m-activity-context">
    <div><span class="m-kicker">AREA MEMBER</span><p>Dashboard {{ $selectedActivity === 'pilates' ? 'Pilates' : 'Gym' }}</p></div>
    <form action="{{ route('portal.activity') }}" method="post" aria-label="Pilih area latihan" class="m-activity-switch">
        @csrf
        @foreach(['gym' => 'Gym', 'pilates' => 'Pilates'] as $value => $label)
        <button type="submit" name="activity" value="{{ $value }}" aria-pressed="{{ $selectedActivity === $value ? 'true' : 'false' }}"><x-icon :name="$value === 'gym' ? 'gym' : 'layers'"/>{{ $label }}</button>
        @endforeach
    </form>
</div>
