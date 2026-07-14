@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/correction/show.css') }}">
@endsection

@section('content')
@php
    $isApproved = $correctionRequest->status === 'approved';

    $displayClockIn = $correctionRequest->requested_clock_in
        ? substr($correctionRequest->requested_clock_in, 0, 5)
        : '';

    $displayClockOut = $correctionRequest->requested_clock_out
        ? substr($correctionRequest->requested_clock_out, 0, 5)
        : '';
@endphp

<section class="admin-correction-detail">
    <div class="admin-correction-detail__inner">
        <h1 class="admin-correction-detail__title">勤怠詳細</h1>

        @if (session('success'))
            <p class="admin-correction-detail__message admin-correction-detail__message--success">
                {{ session('success') }}
            </p>
        @endif

        <div class="admin-correction-detail__card">
            <div class="admin-correction-detail__row">
                <div class="admin-correction-detail__label">名前</div>
                <div class="admin-correction-detail__value">
                    {{ $correctionRequest->applicant->name }}
                </div>
            </div>

            <div class="admin-correction-detail__row">
                <div class="admin-correction-detail__label">日付</div>
                <div class="admin-correction-detail__value admin-correction-detail__date">
                    <span>{{ $correctionRequest->attendanceRecord->date->format('Y年') }}</span>
                    <span>{{ $correctionRequest->attendanceRecord->date->format('n月j日') }}</span>
                </div>
            </div>

            <div class="admin-correction-detail__row">
                <div class="admin-correction-detail__label">出勤・退勤</div>
                <div class="admin-correction-detail__value admin-correction-detail__time-pair">
                    <span class="admin-correction-detail__time">{{ $displayClockIn }}</span>
                    <span class="admin-correction-detail__separator">〜</span>
                    <span class="admin-correction-detail__time">{{ $displayClockOut }}</span>
                </div>
            </div>

            @forelse ($correctionRequest->correctionBreaks as $index => $correctionBreak)
                <div class="admin-correction-detail__row">
                    <div class="admin-correction-detail__label">
                        休憩{{ $index + 1 }}
                    </div>
                    <div class="admin-correction-detail__value admin-correction-detail__time-pair">
                        <span class="admin-correction-detail__time">
                            {{ $correctionBreak->requested_break_in ? substr($correctionBreak->requested_break_in, 0, 5) : '' }}
                        </span>
                        <span class="admin-correction-detail__separator">〜</span>
                        <span class="admin-correction-detail__time">
                            {{ $correctionBreak->requested_break_out ? substr($correctionBreak->requested_break_out, 0, 5) : '' }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="admin-correction-detail__row">
                    <div class="admin-correction-detail__label">休憩</div>
                    <div class="admin-correction-detail__value">
                        なし
                    </div>
                </div>
            @endforelse

            <div class="admin-correction-detail__row admin-correction-detail__row--textarea">
                <div class="admin-correction-detail__label">備考</div>
                <div class="admin-correction-detail__value">
                    {{ $correctionRequest->requested_comment }}
                </div>
            </div>
        </div>

        <div class="admin-correction-detail__actions">
            @if ($isApproved)
                <button class="admin-correction-detail__approved-button" type="button" disabled>
                    承認済み
                </button>
            @else
                <form
                    class="admin-correction-detail__approve-form"
                    action="/stamp_correction_request/approve/{{ $correctionRequest->id }}"
                    method="post"
                >
                    @csrf
                    <button class="admin-correction-detail__approve-button" type="submit">
                        承認
                    </button>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection