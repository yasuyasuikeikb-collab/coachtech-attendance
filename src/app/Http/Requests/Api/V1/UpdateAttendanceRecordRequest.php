<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAttendanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'clock_in' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'clock_out' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'comment' => ['nullable', 'string', 'max:255'],
            'breaks' => ['nullable', 'array'],
            'breaks.*.break_in' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'breaks.*.break_out' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => '日付を入力してください。',
            'date.date_format' => '日付はYYYY-MM-DD形式で入力してください。',
            'clock_in.required' => '出勤時間を入力してください。',
            'clock_in.regex' => '出勤時間はHH:MMまたはHH:MM:SS形式で入力してください。',
            'clock_out.regex' => '退勤時間はHH:MMまたはHH:MM:SS形式で入力してください。',
            'comment.string' => '備考は文字列で入力してください。',
            'comment.max' => '備考は255文字以内で入力してください。',
            'breaks.array' => '休憩時間は配列で入力してください。',
            'breaks.*.break_in.regex' => '休憩開始時間はHH:MMまたはHH:MM:SS形式で入力してください。',
            'breaks.*.break_out.regex' => '休憩終了時間はHH:MMまたはHH:MM:SS形式で入力してください。',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $clockIn = $this->input('clock_in');
            $clockOut = $this->input('clock_out');

            if ($clockIn && $clockOut && $this->normalizeTime($clockIn) >= $this->normalizeTime($clockOut)) {
                $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です。');
            }

            foreach ($this->input('breaks', []) as $index => $break) {
                $breakIn = $break['break_in'] ?? null;
                $breakOut = $break['break_out'] ?? null;

                if (!$breakIn && !$breakOut) {
                    continue;
                }

                if (!$breakIn || !$breakOut) {
                    $validator->errors()->add("breaks.$index.break_in", '休憩時間が不適切な値です。');
                    continue;
                }

                if ($this->normalizeTime($breakIn) >= $this->normalizeTime($breakOut)) {
                    $validator->errors()->add("breaks.$index.break_in", '休憩時間が不適切な値です。');
                }

                if ($clockIn && $this->normalizeTime($breakIn) < $this->normalizeTime($clockIn)) {
                    $validator->errors()->add("breaks.$index.break_in", '休憩時間が不適切な値です。');
                }

                if ($clockOut && $this->normalizeTime($breakOut) > $this->normalizeTime($clockOut)) {
                    $validator->errors()->add("breaks.$index.break_out", '休憩時間もしくは退勤時間が不適切な値です。');
                }
            }
        });
    }

    private function normalizeTime(?string $time): string
    {
        if (!$time) {
            return '';
        }

        return strlen($time) === 5 ? $time . ':00' : $time;
    }
}