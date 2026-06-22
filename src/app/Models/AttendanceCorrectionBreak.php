<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrectionBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_correction_request_id',
        'break_order',
        'requested_break_in',
        'requested_break_out',
    ];

    public function correctionRequest(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceCorrectionRequest::class,
            'attendance_correction_request_id'
        );
    }
}