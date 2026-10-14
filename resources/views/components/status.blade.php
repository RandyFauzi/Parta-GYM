@props(['status'])
<span class="badge {{ in_array($status,['active','paid'])?'positive':($status==='unpaid'?'pending':'neutral') }}"><span class="status-dot"></span>{{ ['active'=>'Aktif','paid'=>'Lunas','unpaid'=>'Belum dibayar','expired'=>'Tidak aktif','suspended'=>'Ditangguhkan'][$status] ?? $status }}</span>
