<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'owner';
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'plan_type' => 'required|in:gym,pt,pilates,all_access',
            'description' => 'nullable|string|max:1000',
            'duration_unit' => 'required|in:day,month,year',
            'duration_value' => 'required|integer|min:1|max:120',
            'visit_limit' => 'nullable|integer|min:1|max:10000',
            'price_idr' => 'required|integer|min:1|max:1000000000',
            'is_active' => 'required|boolean',
        ];
    }
}
