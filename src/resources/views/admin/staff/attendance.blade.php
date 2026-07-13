@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff/attendance.css') }}">
@endsection

@section('content')
<section class="admin-staff-attendance">
    <div class="admin-staff-attendance__inner">
        <h1 class="admin-staff-attendance__title">
            {{ $staffUser->name }}さんの勤怠
        </h1>

        <div class="admin-staff-attendance__month-nav">
            <a
                class="admin-staff-attendance__month-link"
                href="/admin/attendance/staff/{{ $staffUser->id }}?month={{ $currentMonth->copy()->subMonth()->format('Y-m') }}"
            >
                前月
            </a>

            <p class="admin-staff-attendance__month">
                {{ $currentMonth->format('Y/m') }}
            </p>

            <a
                class="admin-staff-attendance__month-link"
                href="/admin/attendance/staff/{{ $staffUser->id }}?month={{ $currentMonth->copy()->addMonth()->format('Y-m') }}"
            >
                翌月
            </a>
        </div>

        <table class="admin-staff-attendance-table">
            <thead class="admin-staff-attendance-table__head">
                <tr class="admin-staff-attendance-table__row">
                    <th class="admin-staff-attendance-table__header">日付</th>
                    <th class="admin-staff-attendance-table__header">出勤</th>
                    <th class="admin-staff-attendance-table__header">退勤</th>
                    <th class="admin-staff-attendance-table__header">休憩</th>
                    <th class="admin-staff-attendance-table__header">合計</th>
                    <th class="admin-staff-attendance-table__header">詳細</th>
                </tr>
            </thead>
            <tbody class="admin-staff-attendance-table__body">
                @forelse ($attendanceRows as $attendanceRow)
                    <tr class="admin-staff-attendance-table__row">
                        <td class="admin-staff-attendance-table__data">
                            {{ $attendanceRow['date'] }}
                        </td>
                        <td class="admin-staff-attendance-table__data">
                            {{ $attendanceRow['clockIn'] }}
                        </td>
                        <td class="admin-staff-attendance-table__data">
                            {{ $attendanceRow['clockOut'] }}
                        </td>
                        <td class="admin-staff-attendance-table__data">
                            {{ $attendanceRow['breakTime'] }}
                        </td>
                        <td class="admin-staff-attendance-table__data">
                            {{ $attendanceRow['totalTime'] }}
                        </td>
                        <td class="admin-staff-attendance-table__data">
                            <a
                                class="admin-staff-attendance-table__detail-link"
                                href="/admin/attendance/{{ $attendanceRow['id'] }}"
                            >
                                詳細
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr class="admin-staff-attendance-table__row">
                        <td class="admin-staff-attendance-table__empty" colspan="6">
                            この月の勤怠はありません。
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="admin-staff-attendance__actions">
            <a class="admin-staff-attendance__back-link" href="/admin/staff/list">
                スタッフ一覧に戻る
            </a>
        </div>
    </div>
</section>
@endsection