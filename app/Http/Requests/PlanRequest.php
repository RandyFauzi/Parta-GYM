<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'owner';
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('benefits_editor')) {
            $benefits = [];
            foreach ((array) $this->input('benefit_keys', []) as $key) {
                if (is_string($key)) {
                    $benefits[$key] = $this->input('benefit_quotas.'.$key);
                }
            }
            $this->merge(['benefits' => $benefits]);
        }
    }

    public function rules(): array
    {
        return [
            'benefits' => 'sometimes|required|array:gym_access,gym_group,gym_personal,pilates_group,pilates_personal,legacy_personal|min:1',
            'benefits.*' => 'nullable|integer|min:1|max:10000',
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
