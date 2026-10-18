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
        } elseif (str_starts_with($phone, '8')) {
            $phone = '+62'.$phone;
        } elseif (str_starts_with($phone, '62')) {
            $phone = '+'.$phone;
        }
        $this->merge(['phone' => $phone, 'email' => $this->filled('email') ? strtolower(trim((string) $this->email)) : null]);
    }

    public function rules(): array
    {
        $rules = [
            'full_name' => 'required|string|max:120',
            'phone' => ['required', 'regex:/^\+628[0-9]{7,12}$/', Rule::unique('members')->ignore($this->route('member'))],
            'email' => 'nullable|email|max:200',
            'customer_type' => 'sometimes|required|in:member,non_member,student',
            'student_id_number' => 'nullable|string|max:60',
            'notes' => 'nullable|string|max:500',
            'plan_id' => ['nullable', 'uuid', Rule::exists('membership_plans', 'id')->where('is_active', true)],
            'request_key' => 'required|uuid',
        ];
        if ($this->isMethod('post')) {
            $rules += [
                'payment_status' => 'nullable|in:pending,paid',
                'method' => ['exclude_unless:payment_status,paid', 'required', Rule::in(array_keys(config('gym.payment_methods')))],
                'reference' => 'exclude_unless:payment_status,paid|nullable|required_if:method,bank_transfer,other|string|max:120',
                'confirmed' => 'exclude_unless:payment_status,paid|required|accepted',
                'create_account' => 'nullable|boolean',
                'password' => 'exclude_unless:create_account,1|required|string|min:12|max:200',
            ];
            if ($this->input('payment_status') === 'paid') {
                $rules['plan_id'][] = 'required';
            }
            if ($this->boolean('create_account')) {
                $rules['email'] = ['required', 'email', 'max:200', Rule::unique('users', 'email')];
            }
        }

        return $rules;
    }
}
