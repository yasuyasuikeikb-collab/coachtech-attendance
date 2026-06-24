@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/stamp.css') }}">
@endsection

@section('content')
<section class="stamp">
    <div class="stamp__inner">
        <p class="stamp__status">勤務外</p>

        <p class="stamp__date">
            {{ now()->format('Y年m月d日') }}
        </p>

        <p class="stamp__time">
            {{ now()->format('H:i') }}
        </p>

        <div class="stamp__actions">
            <button class="stamp__button" type="button">出勤</button>
        </div>
    </div>
</section>
@endsection