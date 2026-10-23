<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'owner' && $this->user()->is_active;
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('permissions_present') && ! $this->has('permissions')) {
            $this->merge(['permissions' => []]);
        }
        $this->merge(['email' => strtolower(trim((string) $this->email))]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:200', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'role' => ['required', Rule::in(array_keys(config('gym.account_roles')))],
            'is_active' => 'required|boolean',
            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'min:12', 'max:200', 'confirmed'],
            'member_id' => ['exclude_unless:role,member', 'required', 'uuid', Rule::exists('members', 'id')],
            'permissions' => 'sometimes|array',
            'permissions.*' => ['string', 'distinct', Rule::in(array_keys(config('gym.staff_permissions')))],
            'bio' => 'nullable|string|max:2000',
            'specialization' => 'nullable|string|max:200',
            'qualifications' => 'nullable|string|max:1000',
            'experience_years' => 'nullable|integer|min:0|max:70',
        ];
    }
}
