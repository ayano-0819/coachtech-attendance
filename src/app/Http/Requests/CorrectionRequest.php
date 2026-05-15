<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectionRequest extends FormRequest
{
    private const CLOCK_TIME_ERROR = '出勤時間もしくは退勤時間が不適切な値です';
    private const BREAK_START_ERROR = '休憩時間が不適切な値です';
    private const BREAK_END_ERROR = '休憩時間もしくは退勤時間が不適切な値です';
    private const NOTE_REQUIRED_ERROR = '備考を記入してください';

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'clock_in_at' => ['required', 'date_format:H:i'],

            'clock_out_at' => [
                'required',
                'date_format:H:i',
                'after:clock_in_at',
            ],

            'note' => ['required', 'string'],

            'breaks' => ['nullable', 'array'],

            'breaks.*.break_start_at' => [
                'nullable',
                'date_format:H:i',
            ],

            'breaks.*.break_end_at' => [
                'nullable',
                'date_format:H:i',
            ],
        ];
    }

    public function messages()
    {
        return [
            'clock_in_at.required' => self::CLOCK_TIME_ERROR,
            'clock_in_at.date_format' => self::CLOCK_TIME_ERROR,

            'clock_out_at.required' => self::CLOCK_TIME_ERROR,
            'clock_out_at.date_format' => self::CLOCK_TIME_ERROR,
            'clock_out_at.after' => self::CLOCK_TIME_ERROR,

            'breaks.*.break_start_at.date_format' => self::BREAK_START_ERROR,

            'breaks.*.break_end_at.date_format' => self::BREAK_END_ERROR,

            'note.required' => self::NOTE_REQUIRED_ERROR,
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $clockIn = $this->input('clock_in_at');

            $clockOut = $this->input('clock_out_at');

            $breaks = $this->input('breaks', []);

            foreach ($breaks as $index => $break) {

                $breakStart = $break['break_start_at'] ?? null;

                $breakEnd = $break['break_end_at'] ?? null;

                if (empty($breakStart) && empty($breakEnd)) {
                    continue;
                }

                $hasError = false;

                if (empty($breakStart)) {
                    $validator->errors()->add(
                        "breaks.$index.break_start_at",
                        self::BREAK_START_ERROR
                    );

                    $hasError = true;
                }

                if (empty($breakEnd)) {
                    $validator->errors()->add(
                        "breaks.$index.break_end_at",
                        self::BREAK_END_ERROR
                    );

                    $hasError = true;
                }

                if ($hasError) {
                    continue;
                }

                if (!empty($clockIn) && $breakStart < $clockIn) {
                    $validator->errors()->add(
                        "breaks.$index.break_start_at",
                        self::BREAK_START_ERROR
                    );

                    continue;
                }

                if (!empty($clockOut) && $breakStart >= $clockOut) {
                    $validator->errors()->add(
                        "breaks.$index.break_start_at",
                        self::BREAK_START_ERROR
                    );

                    continue;
                }

                if (!empty($clockOut) && $breakEnd >= $clockOut) {
                    $validator->errors()->add(
                        "breaks.$index.break_end_at",
                        self::BREAK_END_ERROR
                    );

                    continue;
                }

                if ($breakEnd <= $breakStart) {
                    $validator->errors()->add(
                        "breaks.$index.break_end_at",
                        self::BREAK_END_ERROR
                    );
                }
            }
        });
    }
}
