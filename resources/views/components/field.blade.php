@props(['name','label','type'=>'text','value'=>'','required'=>false,'id'=>null])
@php($fieldId = $id ?? $name)
<div class="field"><label for="{{ $fieldId }}">{{ $label }} @if($required)<span aria-hidden="true">*</span>@endif</label><input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name,$value) }}" @required($required) {{ $attributes }} @error($name) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @enderror>@error($name)<small class="field-error" id="{{ $fieldId }}-error">{{ $message }}</small>@enderror</div>
