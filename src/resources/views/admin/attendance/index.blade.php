@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance/index.css') }}">
@endsection

@section('content')
<section class="admin-attendance-list">
    <div class="admin-attendance-list__inner">
        <h1 class="admin-attendance-list__title">
            {{ $targetDate->format('Y年n月j日') }}の勤怠
        </h1>

        <div class="admin-attendance-list__date-nav">
            <a
                class="admin-attendance-list__date-link"
                href="/admin/attendance/list?date={{ $targetDate->copy()->subDay()->toDateString() }}"
            >
                前日
            </a>

            <p class="admin-attendance-list__date">
                {{ $targetDate->format('Y/m/d') }}
            </p>

            <a
                class="admin-attendance-list__date-link"
                href="/admin/attendance/list?date={{ $targetDate->copy()->addDay()->toDateString() }}"
            >
                翌日
            </a>
        </div>

        <table class="admin-attendance-table">
            <thead class="admin-attendance-table__head">
                <tr class="admin-attendance-table__row">
                    <th class="admin-attendance-table__header">名前</th>
                    <th class="admin-attendance-table__header">出勤</th>
                    <th class="admin-attendance-table__header">退勤</th>
                    <th class="admin-attendance-table__header">休憩</th>
                    <th class="admin-attendance-table__header">合計</th>
                    <th class="admin-attendance-table__header">詳細</th>
                </tr>
            </thead>
            <tbody class="admin-attendance-table__body">
                @forelse ($attendanceRows as $attendanceRow)
                    <tr class="admin-attendance-table__row">
                        <td class="admin-attendance-table__data">
                            {{ $attendanceRow['name'] }}
                        </td>
                        <td class="admin-attendance-table__data">
                            {{ $attendanceRow['clockIn'] }}
                        </td>
                        <td class="admin-attendance-table__data">
                            {{ $attendanceRow['clockOut'] }}
                        </td>
                        <td class="admin-attendance-table__data">
                            {{ $attendanceRow['breakTime'] }}
                        </td>
                        <td class="admin-attendance-table__data">
                            {{ $attendanceRow['totalTime'] }}
                        </td>
                        <td class="admin-attendance-table__data">
                            <a
                                class="admin-attendance-table__detail-link"
                                href="/admin/attendance/{{ $attendanceRow['id'] }}"
                            >
                                詳細
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr class="admin-attendance-table__row">
                        <td class="admin-attendance-table__empty" colspan="6">
                            この日の勤怠はありません。
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection