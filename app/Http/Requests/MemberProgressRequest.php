<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->is_active && ($this->user()->role === 'owner' || ($this->user()->role === 'personal_trainer' && $this->route('member')->isCoachedBy($this->user())));
    }

    public function rules(): array
    {
        return [
            'recorded_at' => 'required|date|before_or_equal:'.now()->timezone(config('gym.timezone'))->format('Y-m-d H:i:s'),
            'training_session_id' => ['nullable', 'uuid', Rule::exists('class_enrollments', 'training_session_id')->where('member_id', $this->route('member')->id)->whereIn('status', ['confirmed', 'attended', 'no_show'])],
            'weight_kg' => 'nullable|numeric|min:20|max:300',
            'height_cm' => 'nullable|numeric|min:50|max:250',
            'body_fat_percentage' => 'nullable|numeric|min:1|max:70',
            'muscle_mass_kg' => 'nullable|numeric|min:5|max:150',
            'chest_cm' => 'nullable|numeric|min:30|max:200',
            'waist_cm' => 'nullable|numeric|min:30|max:200',
            'hips_cm' => 'nullable|numeric|min:30|max:200',
            'arms_cm' => 'nullable|numeric|min:10|max:100',
            'workout_summary' => 'required|string|max:2000',
            'diet_summary' => 'nullable|string|max:1000',
            'coach_notes' => 'nullable|string|max:1000',
            'next_goals' => 'nullable|string|max:1000',
        ];
    }
}
