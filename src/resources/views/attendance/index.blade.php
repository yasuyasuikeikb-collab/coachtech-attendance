@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/index.css') }}">
@endsection

@section('content')
<section class="attendance-list">
    <div class="attendance-list__inner">
        <h1 class="attendance-list__title">勤怠一覧</h1>

        <div class="attendance-list__month-nav">
            <a
                class="attendance-list__month-link"
                href="/attendance/list?month={{ $currentMonth->copy()->subMonth()->format('Y-m') }}"
            >
                前月
            </a>

            <p class="attendance-list__month">
                {{ $currentMonth->format('Y/m') }}
            </p>

            <a
                class="attendance-list__month-link"
                href="/attendance/list?month={{ $currentMonth->copy()->addMonth()->format('Y-m') }}"
            >
                翌月
            </a>
        </div>

        <table class="attendance-table">
            <thead class="attendance-table__head">
                <tr class="attendance-table__row">
                    <th class="attendance-table__header">日付</th>
                    <th class="attendance-table__header">出勤</th>
                    <th class="attendance-table__header">退勤</th>
                    <th class="attendance-table__header">休憩</th>
                    <th class="attendance-table__header">合計</th>
                    <th class="attendance-table__header">詳細</th>
                </tr>
            </thead>
            <tbody class="attendance-table__body">
                @forelse ($attendanceRecords as $attendanceRecord)
                    <tr class="attendance-table__row">
                        <td class="attendance-table__data">
                            {{ $attendanceRecord->date->format('m/d') }}
                        </td>
                        <td class="attendance-table__data">
                            {{ $attendanceRecord->clock_in ? substr($attendanceRecord->clock_in, 0, 5) : '' }}
                        </td>
                        <td class="attendance-table__data">
                            {{ $attendanceRecord->clock_out ? substr($attendanceRecord->clock_out, 0, 5) : '' }}
                        </td>
                        <td class="attendance-table__data">
                            -
                        </td>
                        <td class="attendance-table__data">
                            -
                        </td>
                        <td class="attendance-table__data">
                            <a class="attendance-table__detail-link" href="#">詳細</a>
                        </td>
                    </tr>
                @empty
                    <tr class="attendance-table__row">
                        <td class="attendance-table__empty" colspan="6">
                            この月の勤怠はありません。
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection