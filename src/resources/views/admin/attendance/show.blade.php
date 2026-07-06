@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance/show.css') }}">
@endsection

@section('content')
@php
    $nextBreakIndex = $attendanceRecord->breaks->count();

    $displayClockIn = $attendanceRecord->clock_in
        ? substr($attendanceRecord->clock_in, 0, 5)
        : '';

    $displayClockOut = $attendanceRecord->clock_out
        ? substr($attendanceRecord->clock_out, 0, 5)
        : '';
@endphp

<section class="admin-attendance-detail">
    <div class="admin-attendance-detail__inner">
        <h1 class="admin-attendance-detail__title">勤怠詳細</h1>

        @if (session('success'))
            <p class="admin-attendance-detail__message admin-attendance-detail__message--success">
                {{ session('success') }}
            </p>
        @endif

        @if ($errors->any())
            <div class="admin-attendance-detail__errors">
                @foreach ($errors->all() as $error)
                    <p class="admin-attendance-detail__error">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form
            class="admin-attendance-detail__form"
            action="/admin/attendance/{{ $attendanceRecord->id }}/update"
            method="post"
        >
            @csrf

            <div class="admin-attendance-detail__card">
                <div class="admin-attendance-detail__row">
                    <div class="admin-attendance-detail__label">名前</div>
                    <div class="admin-attendance-detail__value">
                        {{ $attendanceRecord->user->name }}
                    </div>
                </div>

                <div class="admin-attendance-detail__row">
                    <div class="admin-attendance-detail__label">日付</div>
                    <div class="admin-attendance-detail__value admin-attendance-detail__date">
                        <span>{{ $attendanceRecord->date->format('Y年') }}</span>
                        <span>{{ $attendanceRecord->date->format('n月j日') }}</span>
                    </div>
                </div>

                <div class="admin-attendance-detail__row">
                    <div class="admin-attendance-detail__label">出勤・退勤</div>
                    <div class="admin-attendance-detail__value admin-attendance-detail__time-pair">
                        <input
                            class="admin-attendance-detail__input"
                            type="time"
                            name="requested_clock_in"
                            value="{{ old('requested_clock_in', $displayClockIn) }}"
                        >
                        <span class="admin-attendance-detail__separator">〜</span>
                        <input
                            class="admin-attendance-detail__input"
                            type="time"
                            name="requested_clock_out"
                            value="{{ old('requested_clock_out', $displayClockOut) }}"
                        >
                    </div>
                </div>

                @foreach ($attendanceRecord->breaks as $index => $attendanceBreak)
                    <div class="admin-attendance-detail__row">
                        <div class="admin-attendance-detail__label">
                            休憩{{ $index + 1 }}
                        </div>
                        <div class="admin-attendance-detail__value admin-attendance-detail__time-pair">
                            <input
                                class="admin-attendance-detail__input"
                                type="time"
                                name="requested_breaks[{{ $index }}][break_in]"
                                value="{{ old('requested_breaks.' . $index . '.break_in', $attendanceBreak->break_in ? substr($attendanceBreak->break_in, 0, 5) : '') }}"
                            >
                            <span class="admin-attendance-detail__separator">〜</span>
                            <input
                                class="admin-attendance-detail__input"
                                type="time"
                                name="requested_breaks[{{ $index }}][break_out]"
                                value="{{ old('requested_breaks.' . $index . '.break_out', $attendanceBreak->break_out ? substr($attendanceBreak->break_out, 0, 5) : '') }}"
                            >
                        </div>
                    </div>
                @endforeach

                <div class="admin-attendance-detail__row">
                    <div class="admin-attendance-detail__label">
                        休憩{{ $nextBreakIndex + 1 }}
                    </div>
                    <div class="admin-attendance-detail__value admin-attendance-detail__time-pair">
                        <input
                            class="admin-attendance-detail__input"
                            type="time"
                            name="requested_breaks[{{ $nextBreakIndex }}][break_in]"
                            value="{{ old('requested_breaks.' . $nextBreakIndex . '.break_in') }}"
                        >
                        <span class="admin-attendance-detail__separator">〜</span>
                        <input
                            class="admin-attendance-detail__input"
                            type="time"
                            name="requested_breaks[{{ $nextBreakIndex }}][break_out]"
                            value="{{ old('requested_breaks.' . $nextBreakIndex . '.break_out') }}"
                        >
                    </div>
                </div>

                <div class="admin-attendance-detail__row admin-attendance-detail__row--textarea">
                    <div class="admin-attendance-detail__label">備考</div>
                    <div class="admin-attendance-detail__value">
                        <textarea
                            class="admin-attendance-detail__textarea"
                            name="requested_comment"
                        >{{ old('requested_comment', $attendanceRecord->comment ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="admin-attendance-detail__actions">
                <button class="admin-attendance-detail__submit-button" type="submit">
                    修正
                </button>
            </div>
        </form>
    </div>
</section>
@endsection