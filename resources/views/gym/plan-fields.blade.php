@php($suffix=$plan?->id ?? 'new')
<div class="form-grid">
    <div class="field">
        <label for="name-{{ $suffix }}">Nama paket</label>
        <input id="name-{{ $suffix }}" name="name" value="{{ old('name',$plan?->name) }}" required maxlength="100">
    </div>
    <div class="field">
        <label for="type-{{ $suffix }}">Kategori / Jenis Paket</label>
        <select id="type-{{ $suffix }}" name="plan_type">
            @foreach(['gym'=>'Gym Reguler','pt'=>'Personal Training (PT)','pilates'=>'Pilates Studio','all_access'=>'All-Access (Gym + PT/Pilates)'] as $value=>$label)
                <option value="{{ $value }}" @selected(old('plan_type',$plan?->plan_type ?? 'gym')===$value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="price-{{ $suffix }}">Harga (rupiah)</label>
        <input id="price-{{ $suffix }}" name="price_idr" type="number" min="1" max="1000000000" value="{{ old('price_idr',$plan?->price_idr) }}" required>
    </div>
    <div class="field">
        <label for="unit-{{ $suffix }}">Satuan durasi</label>
        <select id="unit-{{ $suffix }}" name="duration_unit">
            @foreach(['day'=>'Hari','month'=>'Bulan','year'=>'Tahun'] as $value=>$label)
                <option value="{{ $value }}" @selected(old('duration_unit',$plan?->duration_unit ?? 'month')===$value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="duration-{{ $suffix }}">Jumlah durasi</label>
        <input id="duration-{{ $suffix }}" name="duration_value" type="number" min="1" max="120" value="{{ old('duration_value',$plan?->duration_value ?? 1) }}" required>
    </div>
    <div class="field">
        <label for="limit-{{ $suffix }}">Kuota sesi / kunjungan (kosong = tanpa batas)</label>
        <input id="limit-{{ $suffix }}" name="visit_limit" type="number" min="1" value="{{ old('visit_limit',$plan?->visit_limit) }}" placeholder="Contoh: 10 atau 15 sesi">
    </div>
    <div class="field">
        <label for="active-{{ $suffix }}">Ketersediaan</label>
        <select id="active-{{ $suffix }}" name="is_active">
            <option value="1" @selected($plan?->is_active ?? true)>Aktif</option>
            <option value="0" @selected($plan && !$plan->is_active)>Nonaktif</option>
        </select>
    </div>
</div>
<div class="field">
    <label for="description-{{ $suffix }}">Deskripsi paket</label>
    <textarea id="description-{{ $suffix }}" name="description" rows="2" maxlength="1000" placeholder="Keterangan manfaat paket, misal: Termasuk pendampingan trainer 1-on-1...">{{ old('description',$plan?->description) }}</textarea>
</div>
