<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class OperatingHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'owner';
    }

    public function rules(): array
    {
        return [
            'days' => 'required|array:0,1,2,3,4,5,6|size:7',
            'days.*' => 'required|array:closed,opens_at,closes_at',
            'days.*.closed' => 'required|boolean',
            'days.*.opens_at' => 'nullable|date_format:H:i',
            'days.*.closes_at' => 'nullable|date_format:H:i',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            foreach ($this->input('days') as $index => $day) {
                if (! $day['closed'] && (empty($day['opens_at']) || empty($day['closes_at']) || $day['opens_at'] === $day['closes_at'])) {
                    $validator->errors()->add('days.'.$index, 'Isi jam buka dan tutup yang berbeda untuk hari operasional.');
                }
                $next = $this->input('days.'.(((int) $index + 1) % 7));
                if (! $day['closed'] && $day['opens_at'] && $day['closes_at'] && $day['closes_at'] < $day['opens_at']
                    && ! $next['closed'] && $next['opens_at'] && $next['opens_at'] < $day['closes_at']) {
                    $validator->errors()->add('days.'.$index, 'Jam operasional lintas malam bertumpuk dengan hari berikutnya.');
                }
            }
        }];
    }
}
