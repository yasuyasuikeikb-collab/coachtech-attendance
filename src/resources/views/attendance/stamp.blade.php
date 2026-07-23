@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/stamp.css') }}">
@endsection

@section('content')
<section class="attendance-stamp">
    <div class="attendance-stamp__inner">
        <p class="attendance-stamp__status">
            {{ $attendanceStatus }}
        </p>

        <p class="attendance-stamp__date">
            {{ now()->format('Y年m月d日') }}
        </p>

        <p class="attendance-stamp__time">
            {{ now()->format('H:i') }}
        </p>

        @if ($attendanceStatus === '退勤済')
            <p class="attendance-stamp__message">
                お疲れ様でした。
            </p>
        @endif

        <div class="attendance-stamp__buttons">
            @if ($canClockIn)
                <form class="attendance-stamp__form" action="/attendance/clock-in" method="post">
                    @csrf
                    <button class="attendance-stamp__button" type="submit">
                        出勤
                    </button>
                </form>
            @endif

            @if ($canClockOut)
                <form class="attendance-stamp__form" action="/attendance/clock-out" method="post">
                    @csrf
                    <button class="attendance-stamp__button" type="submit">
                        退勤
                    </button>
                </form>
            @endif

            @if ($canStartBreak)
                <form class="attendance-stamp__form" action="/attendance/break-start" method="post">
                    @csrf
                    <button class="attendance-stamp__button attendance-stamp__button--white" type="submit">
                        休憩入
                    </button>
                </form>
            @endif

            @if ($canEndBreak)
                <form class="attendance-stamp__form" action="/attendance/break-end" method="post">
                    @csrf
                    <button class="attendance-stamp__button attendance-stamp__button--white" type="submit">
                        休憩戻
                    </button>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection