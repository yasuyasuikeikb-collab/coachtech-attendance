<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class StoreCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requested_clock_in' => ['required', 'date_format:H:i'],
            'requested_clock_out' => ['required', 'date_format:H:i', 'after:requested_clock_in'],
            'requested_comment' => ['required', 'string', 'max:255'],
            'requested_breaks' => ['array'],
            'requested_breaks.*.break_in' => ['nullable', 'date_format:H:i'],
            'requested_breaks.*.break_out' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function attributes(): array
    {
        return [
            'requested_clock_in' => '出勤時間',
            'requested_clock_out' => '退勤時間',
            'requested_comment' => '備考',
            'requested_breaks.*.break_in' => '休憩開始時間',
            'requested_breaks.*.break_out' => '休憩終了時間',
        ];
    }
}