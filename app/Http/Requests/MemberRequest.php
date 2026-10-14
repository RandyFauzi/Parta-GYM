<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role, ['owner', 'receptionist'], true);
    }

    protected function prepareForValidation(): void
    {
        $phone = preg_replace('/[\s\-()]/', '', (string) $this->phone);
        if (str_starts_with($phone, '08')) {
            $phone = '+62'.substr($phone, 1);
        } elseif (str_starts_with($phone, '62')) {
            $phone = '+'.$phone;
        }
        $this->merge(['phone' => $phone]);
    }

    public function rules(): array
    {
        return ['full_name' => 'required|string|max:120', 'phone' => ['required', 'regex:/^\+628[0-9]{7,12}$/', Rule::unique('members')->ignore($this->route('member'))], 'email' => 'nullable|email|max:200', 'plan_id' => 'nullable|uuid|exists:membership_plans,id', 'request_key' => 'required|uuid'];
    }
}
