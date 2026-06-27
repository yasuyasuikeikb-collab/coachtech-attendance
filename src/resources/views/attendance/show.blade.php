@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/show.css') }}">
@endsection

@section('content')
@php
    $isPending = $pendingCorrectionRequest !== null;

    $displayClockIn = $isPending
        ? substr($pendingCorrectionRequest->requested_clock_in, 0, 5)
        : ($attendanceRecord->clock_in ? substr($attendanceRecord->clock_in, 0, 5) : '');

    $displayClockOut = $isPending
        ? substr($pendingCorrectionRequest->requested_clock_out, 0, 5)
        : ($attendanceRecord->clock_out ? substr($attendanceRecord->clock_out, 0, 5) : '');

    $displayComment = $isPending
        ? $pendingCorrectionRequest->requested_comment
        : ($attendanceRecord->comment ?? '');

    $displayBreaks = $isPending
        ? $pendingCorrectionRequest->correctionBreaks
        : $attendanceRecord->breaks;

    $nextBreakIndex = $attendanceRecord->breaks->count();
@endphp

<section class="attendance-detail">
    <div class="attendance-detail__inner">
        <h1 class="attendance-detail__title">勤怠詳細</h1>

        @if (session('success'))
            <p class="attendance-detail__message attendance-detail__message--success">
                {{ session('success') }}
            </p>
        @endif

        @if (session('error'))
            <p class="attendance-detail__message attendance-detail__message--error">
                {{ session('error') }}
            </p>
        @endif

        @if ($errors->any())
            <div class="attendance-detail__errors">
                @foreach ($errors->all() as $error)
                    <p class="attendance-detail__error">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if ($isPending)
            <div class="attendance-detail__card">
                <div class="attendance-detail__row">
                    <div class="attendance-detail__label">名前</div>
                    <div class="attendance-detail__value">
                        {{ $attendanceRecord->user->name }}
                    </div>
                </div>

                <div class="attendance-detail__row">
                    <div class="attendance-detail__label">日付</div>
                    <div class="attendance-detail__value attendance-detail__date">
                        <span>{{ $attendanceRecord->date->format('Y年') }}</span>
                        <span>{{ $attendanceRecord->date->format('n月j日') }}</span>
                    </div>
                </div>

                <div class="attendance-detail__row">
                    <div class="attendance-detail__label">出勤・退勤</div>
                    <div class="attendance-detail__value attendance-detail__time-pair">
                        <span class="attendance-detail__text-time">{{ $displayClockIn }}</span>
                        <span class="attendance-detail__separator">〜</span>
                        <span class="attendance-detail__text-time">{{ $displayClockOut }}</span>
                    </div>
                </div>

                @forelse ($displayBreaks as $index => $displayBreak)
                    <div class="attendance-detail__row">
                        <div class="attendance-detail__label">
                            休憩{{ $index + 1 }}
                        </div>
                        <div class="attendance-detail__value attendance-detail__time-pair">
                            <span class="attendance-detail__text-time">
                                {{ $displayBreak->requested_break_in ? substr($displayBreak->requested_break_in, 0, 5) : '' }}
                            </span>
                            <span class="attendance-detail__separator">〜</span>
                            <span class="attendance-detail__text-time">
                                {{ $displayBreak->requested_break_out ? substr($displayBreak->requested_break_out, 0, 5) : '' }}
                            </span>
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
                        {{ $displayComment }}
                    </div>
                </div>
            </div>

            <p class="attendance-detail__pending-message">
                * 承認待ちのため修正はできません。
            </p>
        @else
            <form
                class="attendance-detail__form"
                action="/attendance/{{ $attendanceRecord->id }}/correction"
                method="post"
            >
                @csrf

                <div class="attendance-detail__card">
                    <div class="attendance-detail__row">
                        <div class="attendance-detail__label">名前</div>
                        <div class="attendance-detail__value">
                            {{ $attendanceRecord->user->name }}
                        </div>
                    </div>

                    <div class="attendance-detail__row">
                        <div class="attendance-detail__label">日付</div>
                        <div class="attendance-detail__value attendance-detail__date">
                            <span>{{ $attendanceRecord->date->format('Y年') }}</span>
                            <span>{{ $attendanceRecord->date->format('n月j日') }}</span>
                        </div>
                    </div>

                    <div class="attendance-detail__row">
                        <div class="attendance-detail__label">出勤・退勤</div>
                        <div class="attendance-detail__value attendance-detail__time-pair">
                            <input
                                class="attendance-detail__input"
                                type="time"
                                name="requested_clock_in"
                                value="{{ old('requested_clock_in', $displayClockIn) }}"
                            >
                            <span class="attendance-detail__separator">〜</span>
                            <input
                                class="attendance-detail__input"
                                type="time"
                                name="requested_clock_out"
                                value="{{ old('requested_clock_out', $displayClockOut) }}"
                            >
                        </div>
                    </div>

                    @foreach ($attendanceRecord->breaks as $index => $attendanceBreak)
                        <div class="attendance-detail__row">
                            <div class="attendance-detail__label">
                                休憩{{ $index + 1 }}
                            </div>
                            <div class="attendance-detail__value attendance-detail__time-pair">
                                <input
                                    class="attendance-detail__input"
                                    type="time"
                                    name="requested_breaks[{{ $index }}][break_in]"
                                    value="{{ old('requested_breaks.' . $index . '.break_in', $attendanceBreak->break_in ? substr($attendanceBreak->break_in, 0, 5) : '') }}"
                                >
                                <span class="attendance-detail__separator">〜</span>
                                <input
                                    class="attendance-detail__input"
                                    type="time"
                                    name="requested_breaks[{{ $index }}][break_out]"
                                    value="{{ old('requested_breaks.' . $index . '.break_out', $attendanceBreak->break_out ? substr($attendanceBreak->break_out, 0, 5) : '') }}"
                                >
                            </div>
                        </div>
                    @endforeach

                    <div class="attendance-detail__row">
                        <div class="attendance-detail__label">
                            休憩{{ $nextBreakIndex + 1 }}
                        </div>
                        <div class="attendance-detail__value attendance-detail__time-pair">
                            <input
                                class="attendance-detail__input"
                                type="time"
                                name="requested_breaks[{{ $nextBreakIndex }}][break_in]"
                                value="{{ old('requested_breaks.' . $nextBreakIndex . '.break_in') }}"
                            >
                            <span class="attendance-detail__separator">〜</span>
                            <input
                                class="attendance-detail__input"
                                type="time"
                                name="requested_breaks[{{ $nextBreakIndex }}][break_out]"
                                value="{{ old('requested_breaks.' . $nextBreakIndex . '.break_out') }}"
                            >
                        </div>
                    </div>

                    <div class="attendance-detail__row attendance-detail__row--textarea">
                        <div class="attendance-detail__label">備考</div>
                        <div class="attendance-detail__value">
                            <textarea
                                class="attendance-detail__textarea"
                                name="requested_comment"
                            >{{ old('requested_comment', $displayComment) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="attendance-detail__actions">
                    <button class="attendance-detail__submit-button" type="submit">
                        修正
                    </button>
                </div>
            </form>
        @endif
    </div>
</section>
@endsection