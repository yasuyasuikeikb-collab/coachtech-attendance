@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/show.css') }}">
@endsection

@section('content')
<section class="attendance-detail">
    <div class="attendance-detail__inner">
        <h1 class="attendance-detail__title">勤怠詳細</h1>

        <div class="attendance-detail__card">
            <div class="attendance-detail__row">
                <div class="attendance-detail__label">名前</div>
                <div class="attendance-detail__value">
                    {{ $attendanceRecord->user->name }}
                </div>
            </div>

            <div class="attendance-detail__row">
                <div class="attendance-detail__label">日付</div>
                <div class="attendance-detail__value">
                    {{ $attendanceRecord->date->format('Y年m月d日') }}
                </div>
            </div>

            <div class="attendance-detail__row">
                <div class="attendance-detail__label">出勤・退勤</div>
                <div class="attendance-detail__value attendance-detail__time-pair">
                    <span>{{ $attendanceRecord->clock_in ? substr($attendanceRecord->clock_in, 0, 5) : '' }}</span>
                    <span class="attendance-detail__separator">〜</span>
                    <span>{{ $attendanceRecord->clock_out ? substr($attendanceRecord->clock_out, 0, 5) : '' }}</span>
                </div>
            </div>

            @forelse ($attendanceRecord->breaks as $index => $attendanceBreak)
                <div class="attendance-detail__row">
                    <div class="attendance-detail__label">
                        休憩{{ $index + 1 }}
                    </div>
                    <div class="attendance-detail__value attendance-detail__time-pair">
                        <span>{{ $attendanceBreak->break_in ? substr($attendanceBreak->break_in, 0, 5) : '' }}</span>
                        <span class="attendance-detail__separator">〜</span>
                        <span>{{ $attendanceBreak->break_out ? substr($attendanceBreak->break_out, 0, 5) : '' }}</span>
                    </div>
                </div>
            @empty
                <div class="attendance-detail__row">
                    <div class="attendance-detail__label">休憩</div>
                    <div class="attendance-detail__value">なし</div>
                </div>
            @endforelse

            <div class="attendance-detail__row">
                <div class="attendance-detail__label">備考</div>
                <div class="attendance-detail__value">
                    {{ $attendanceRecord->comment ?? '' }}
                </div>
            </div>
        </div>

        <div class="attendance-detail__actions">
            <a class="attendance-detail__back-link" href="/attendance/list">
                一覧に戻る
            </a>
        </div>
    </div>
</section>
@endsection