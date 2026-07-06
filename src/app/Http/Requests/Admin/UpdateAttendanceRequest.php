<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requested_clock_in' => ['required', 'date_format:H:i'],
            'requested_clock_out' => ['required', 'date_format:H:i'],
            'requested_comment' => ['required', 'string', 'max:255'],
            'requested_breaks' => ['array'],
            'requested_breaks.*.break_in' => ['nullable', 'date_format:H:i'],
            'requested_breaks.*.break_out' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'requested_clock_in.required' => '出勤時間を入力してください',
            'requested_clock_in.date_format' => '出勤時間は時刻形式で入力してください',
            'requested_clock_out.required' => '退勤時間を入力してください',
            'requested_clock_out.date_format' => '退勤時間は時刻形式で入力してください',
            'requested_comment.required' => '備考を記入してください',
            'requested_comment.max' => '備考は255文字以内で入力してください',
            'requested_breaks.*.break_in.date_format' => '休憩開始時間は時刻形式で入力してください',
            'requested_breaks.*.break_out.date_format' => '休憩終了時間は時刻形式で入力してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $clockIn = $this->input('requested_clock_in');
            $clockOut = $this->input('requested_clock_out');

            if (!$clockIn || !$clockOut) {
                return;
            }

            if ($clockIn >= $clockOut) {
                $validator->errors()->add(
                    'requested_clock_out',
                    '退勤時間は出勤時間より後にしてください'
                );

                return;
            }

            foreach ($this->input('requested_breaks', []) as $index => $requestedBreak) {
                $breakIn = $requestedBreak['break_in'] ?? null;
                $breakOut = $requestedBreak['break_out'] ?? null;

                if (!$breakIn && !$breakOut) {
                    continue;
                }

                if (!$breakIn || !$breakOut) {
                    $validator->errors()->add(
                        "requested_breaks.$index",
                        '休憩時間を入力してください'
                    );

                    continue;
                }

                if ($breakIn >= $breakOut) {
                    $validator->errors()->add(
                        "requested_breaks.$index",
                        '休憩時間が不適切な値です'
                    );

                    continue;
                }

                if ($breakIn < $clockIn || $breakOut > $clockOut) {
                    $validator->errors()->add(
                        "requested_breaks.$index",
                        '休憩時間が勤務時間外です'
                    );
                }
            }
        });
    }
}