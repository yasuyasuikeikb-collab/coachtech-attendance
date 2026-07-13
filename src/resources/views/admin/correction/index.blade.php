@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/correction/index.css') }}">
@endsection

@section('content')
<section class="admin-correction-list">
    <div class="admin-correction-list__inner">
        <h1 class="admin-correction-list__title">申請一覧</h1>

        <div class="admin-correction-list__tabs">
            <a
                class="admin-correction-list__tab {{ $selectedStatus === 'pending' ? 'admin-correction-list__tab--active' : '' }}"
                href="/stamp_correction_request/list?status=pending"
            >
                承認待ち
            </a>

            <a
                class="admin-correction-list__tab {{ $selectedStatus === 'approved' ? 'admin-correction-list__tab--active' : '' }}"
                href="/stamp_correction_request/list?status=approved"
            >
                承認済み
            </a>
        </div>

        <table class="admin-correction-table">
            <thead class="admin-correction-table__head">
                <tr class="admin-correction-table__row">
                    <th class="admin-correction-table__header">状態</th>
                    <th class="admin-correction-table__header">名前</th>
                    <th class="admin-correction-table__header">対象日時</th>
                    <th class="admin-correction-table__header">申請理由</th>
                    <th class="admin-correction-table__header">申請日時</th>
                    <th class="admin-correction-table__header">詳細</th>
                </tr>
            </thead>
            <tbody class="admin-correction-table__body">
                @forelse ($correctionRequestRows as $correctionRequestRow)
                    <tr class="admin-correction-table__row">
                        <td class="admin-correction-table__data">
                            {{ $correctionRequestRow['status'] }}
                        </td>
                        <td class="admin-correction-table__data">
                            {{ $correctionRequestRow['name'] }}
                        </td>
                        <td class="admin-correction-table__data">
                            {{ $correctionRequestRow['targetDate'] }}
                        </td>
                        <td class="admin-correction-table__data">
                            {{ $correctionRequestRow['reason'] }}
                        </td>
                        <td class="admin-correction-table__data">
                            {{ $correctionRequestRow['requestedAt'] }}
                        </td>
                        <td class="admin-correction-table__data">
                            <a
                                class="admin-correction-table__detail-link"
                                href="/stamp_correction_request/approve/{{ $correctionRequestRow['id'] }}"
                            >
                                詳細
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr class="admin-correction-table__row">
                        <td class="admin-correction-table__empty" colspan="6">
                            申請はありません。
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection