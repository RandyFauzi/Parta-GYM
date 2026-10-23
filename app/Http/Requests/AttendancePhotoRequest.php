<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendancePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->is_active && in_array($this->user()->role, ['owner', 'receptionist', 'personal_trainer'], true);
    }

    public function rules(): array
    {
        return ['photo' => 'required|file|image|mimes:jpeg,png,webp|max:5120|dimensions:min_width=100,min_height=100,max_width=4096,max_height=4096'];
    }
}
