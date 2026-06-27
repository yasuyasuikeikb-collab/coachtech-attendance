@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/correction/index.css') }}">
@endsection

@section('content')
<section class="correction-list">
    <div class="correction-list__inner">
        <h1 class="correction-list__title">申請一覧</h1>

        <div class="correction-list__tabs">
            <a
                class="correction-list__tab {{ $selectedStatus === 'pending' ? 'correction-list__tab--active' : '' }}"
                href="/stamp_correction_request/list?status=pending"
            >
                承認待ち
            </a>

            <a
                class="correction-list__tab {{ $selectedStatus === 'approved' ? 'correction-list__tab--active' : '' }}"
                href="/stamp_correction_request/list?status=approved"
            >
                承認済み
            </a>
        </div>

        <table class="correction-table">
            <thead class="correction-table__head">
                <tr class="correction-table__row">
                    <th class="correction-table__header">状態</th>
                    <th class="correction-table__header">名前</th>
                    <th class="correction-table__header">対象日時</th>
                    <th class="correction-table__header">申請理由</th>
                    <th class="correction-table__header">申請日時</th>
                    <th class="correction-table__header">詳細</th>
                </tr>
            </thead>
            <tbody class="correction-table__body">
                @forelse ($correctionRequestRows as $correctionRequestRow)
                    <tr class="correction-table__row">
                        <td class="correction-table__data">
                            {{ $correctionRequestRow['status'] }}
                        </td>
                        <td class="correction-table__data">
                            {{ $correctionRequestRow['name'] }}
                        </td>
                        <td class="correction-table__data">
                            {{ $correctionRequestRow['targetDate'] }}
                        </td>
                        <td class="correction-table__data">
                            {{ $correctionRequestRow['reason'] }}
                        </td>
                        <td class="correction-table__data">
                            {{ $correctionRequestRow['requestedAt'] }}
                        </td>
                        <td class="correction-table__data">
                            <a
                                class="correction-table__detail-link"
                                href="/attendance/{{ $correctionRequestRow['attendanceRecordId'] }}"
                            >
                                詳細
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr class="correction-table__row">
                        <td class="correction-table__empty" colspan="6">
                            申請はありません。
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection