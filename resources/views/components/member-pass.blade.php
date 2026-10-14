@props(['member'])
@php($period=$member->currentPeriod)
<section class="m-pass" aria-label="Kartu anggota">
    <div class="m-pass-top"><span>GYM OS <span class="m-pass-divider">/</span> MEMBER CARD</span><x-status :status="$member->status==='suspended'?'suspended':($period?'active':'expired')"/></div>
    <div class="m-pass-emblem"><x-icon name="gym"/></div>
    <h2>{{ $member->full_name }}</h2>
    <span class="m-pass-number">{{ $member->member_number }}</span>
    <div class="m-pass-bottom"><div><small>PAKET</small><strong>{{ $period?->invoice->snapshot['name'] ?? 'Belum ada paket aktif' }}</strong></div><div><small>BERLAKU SAMPAI</small><strong>{{ $period?->ends_at->timezone(config('gym.timezone'))->format('d M Y') ?? '—' }}</strong></div></div>
</section>
