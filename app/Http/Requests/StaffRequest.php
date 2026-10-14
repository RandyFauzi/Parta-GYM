<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'owner';
    }

    public function rules(): array
    {
        return ['name' => 'required|string|max:120', 'email' => 'required|email|max:200|unique:users,email', 'password' => 'required|string|min:12|max:128|confirmed'];
    }
}
