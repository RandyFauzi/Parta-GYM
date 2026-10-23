<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MembershipChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('memberships.change');
    }

    public function rules(): array
    {
        return [
            'source_period_id' => 'required|uuid|exists:membership_periods,id',
            'plan_id' => 'required|uuid|exists:membership_plans,id',
            'quote' => 'required|string|max:30000',
            'method' => 'required|in:cash,bank_transfer,qris,card,other',
            'reference' => 'nullable|required_if:method,bank_transfer,other|string|max:120',
            'reason' => 'required|string|min:5|max:500',
            'request_key' => 'required|uuid',
            'confirmed' => 'required|accepted',
        ];
    }
}
