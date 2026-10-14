<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrainingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['owner', 'personal_trainer'], true) && $this->user()->is_active;
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()?->role === 'personal_trainer') {
            $this->merge(['trainer_profile_id' => $this->user()->trainerProfile?->id]);
        }
    }

    public function rules(): array
    {
        return ['title' => 'required|string|max:120', 'activity' => 'required|in:gym,pilates', 'kind' => 'required|in:group,personal', 'trainer_profile_id' => 'required|uuid|exists:trainer_profiles,id', 'studio_room_id' => ['required', 'uuid', Rule::exists('studio_rooms', 'id')->where('is_active', true)], 'starts_at' => 'required|date_format:Y-m-d\TH:i', 'ends_at' => 'required|date_format:Y-m-d\TH:i|after:starts_at', 'capacity' => 'required|integer|min:1|max:100', 'notes' => 'nullable|string|max:2000'];
    }
}
