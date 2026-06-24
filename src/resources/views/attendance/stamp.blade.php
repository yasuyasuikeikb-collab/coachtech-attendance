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
        </div>
    </div>
</section>
@endsection