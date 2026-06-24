@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/stamp.css') }}">
@endsection

@section('content')
<section class="stamp">
    <div class="stamp__inner">
        <p class="stamp__status">{{ $attendanceStatus }}</p>

        <p class="stamp__date">
            {{ now()->format('Y年m月d日') }}
        </p>

        <p class="stamp__time">
            {{ now()->format('H:i') }}
        </p>

        <div class="stamp__actions">
            @if ($canClockIn)
                <form class="stamp__form" action="/attendance/clock-in" method="post">
                    @csrf
                    <button class="stamp__button" type="submit">出勤</button>
                </form>
            @endif

            @if ($canClockOut)
                <form class="stamp__form" action="/attendance/clock-out" method="post">
                    @csrf
                    <button class="stamp__button" type="submit">退勤</button>
                </form>
            @endif

            @if ($canStartBreak)
                <form class="stamp__form" action="/attendance/break-start" method="post">
                    @csrf
                    <button class="stamp__button stamp__button--secondary" type="submit">休憩入</button>
                </form>
            @endif

            @if ($canEndBreak)
                <form class="stamp__form" action="/attendance/break-end" method="post">
                    @csrf
                    <button class="stamp__button stamp__button--secondary" type="submit">休憩戻</button>
                </form>
            @endif
        </div>

        @if ($attendanceStatus === '退勤済')
            <p class="stamp__message">お疲れ様でした。</p>
        @endif
    </div>
</section>
@endsection