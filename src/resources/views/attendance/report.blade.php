@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/report.css') }}">
@endsection

@section('content')
<main class="report">
    <div class="report__inner">
        <h1 class="report__title">マイ勤怠レポート</h1>

        <p class="report__lead">過去６ヶ月の勤怠データから集計しています。</p>

        <section class="report__section">
            <h2 class="report__section-title">基本サマリー</h2>

            <div class="report__summary">
                <div class="report__card">
                    <p class="report__card-label">総労働時間</p>
                    <p class="report__card-value">{{ $summary['totalWorkTime'] }}</p>
                </div>

                <div class="report__card">
                    <p class="report__card-label">総残業時間</p>
                    <p class="report__card-value">{{ $summary['totalOvertimeTime'] }}</p>
                </div>

                <div class="report__card">
                    <p class="report__card-label">平均労働時間 / 日</p>
                    <p class="report__card-value">{{ $summary['averageWorkTime'] }}</p>
                </div>
            </div>
        </section>

        <section class="report__section">
            <h2 class="report__section-title">月次推移（過去６ヶ月）</h2>

            <table class="report__table">
                <thead>
                    <tr>
                        <th>月</th>
                        <th>労働時間</th>
                        <th>残業時間</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($monthlyTrend as $trend)
                        <tr>
                            <td>{{ $trend['month'] }}</td>
                            <td>{{ $trend['workTime'] }}</td>
                            <td>{{ $trend['overtimeTime'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="report__section">
            <h2 class="report__section-title">今月の異常検知</h2>

            <p class="report__note">基準：始業 09:00 / 終業 18:00 / 長時間労働は１日 10 時間超</p>

            <div class="report__summary">
                <div class="report__card">
                    <p class="report__card-label">遅刻回数</p>
                    <p class="report__card-value">{{ $anomalies['lateCount'] }}回</p>
                </div>

                <div class="report__card">
                    <p class="report__card-label">早退回数</p>
                    <p class="report__card-value">{{ $anomalies['earlyLeaveCount'] }}回</p>
                </div>

                <div class="report__card">
                    <p class="report__card-label">長時間労働日数</p>
                    <p class="report__card-value">{{ $anomalies['longWorkDayCount'] }}日</p>
                </div>
            </div>
        </section>
    </div>
</main>
@endsection