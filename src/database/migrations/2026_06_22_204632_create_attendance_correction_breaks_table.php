<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceCorrectionBreaksTable extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_correction_breaks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attendance_correction_request_id');
            $table->integer('break_order');
            $table->time('requested_break_in')->nullable();
            $table->time('requested_break_out')->nullable();
            $table->timestamps();

            $table->foreign(
                'attendance_correction_request_id',
                'acr_breaks_request_id_foreign'
            )
                ->references('id')
                ->on('attendance_correction_requests')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_correction_breaks');
    }
}