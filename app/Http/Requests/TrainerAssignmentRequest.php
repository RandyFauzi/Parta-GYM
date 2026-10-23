<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrainerAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('assignments.manage');
    }

    public function rules(): array
    {
        return [
            'trainer_profile_id' => 'required|uuid|exists:trainer_profiles,id',
            'activity' => 'required|in:gym,pilates',
            'ends_at' => 'nullable|date_format:Y-m-d\TH:i|after:'.now()->timezone(config('gym.timezone'))->format('Y-m-d\TH:i'),
            'reason' => 'required|string|min:5|max:500',
        ];
    }
}
