<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TrainerAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('schedule.manage');
    }

    protected function prepareForValidation(): void
    {
        $availability = $this->input('availability');
        if (is_array($availability)) {
            foreach ($availability as $day => $slots) {
                if (is_array($slots)) {
                    $availability[$day] = array_values(array_filter($slots, fn (mixed $slot): bool => ! is_array($slot) || ! empty($slot['from']) || ! empty($slot['to'])));
                }
            }
            $this->merge(['availability' => $availability]);
        }
    }

    public function rules(): array
    {
        return [
            'availability' => 'present|array:0,1,2,3,4,5,6',
            'availability.*' => 'array|max:4',
            'availability.*.*' => 'array:from,to',
            'availability.*.*.from' => 'required|date_format:H:i',
            'availability.*.*.to' => 'required|date_format:H:i',
            'session_buffer_minutes' => 'required|integer|min:0|max:120',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $intervals = [];
            foreach ($this->input('availability') as $weekday => $slots) {
                foreach ($slots as $slot) {
                    [$hour, $minute] = array_map('intval', explode(':', $slot['from']));
                    $start = (int) $weekday * 1440 + $hour * 60 + $minute;
                    [$hour, $minute] = array_map('intval', explode(':', $slot['to']));
                    $end = (int) $weekday * 1440 + $hour * 60 + $minute;
                    if ($start === $end) {
                        $validator->errors()->add('availability', 'Jam mulai dan selesai PT tidak boleh sama.');

                        return;
                    }
                    if ($end < $start) {
                        $end += 1440;
                    }
                    foreach ([-10080, 0, 10080] as $offset) {
                        foreach ($intervals as [$otherStart, $otherEnd]) {
                            if ($start + $offset < $otherEnd && $end + $offset > $otherStart) {
                                $validator->errors()->add('availability', 'Rentang jam kerja PT saling bertumpuk.');

                                return;
                            }
                        }
                    }
                    $intervals[] = [$start, $end];
                }
            }
        }];
    }
}
