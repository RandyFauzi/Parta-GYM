<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MemberProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'member';
    }

    public function rules(): array
    {
        return ['full_name' => 'required|string|max:120'];
    }

    public function messages(): array
    {
        return ['full_name.required' => 'Nama lengkap wajib diisi.', 'full_name.max' => 'Nama lengkap maksimal 120 karakter.'];
    }
}
