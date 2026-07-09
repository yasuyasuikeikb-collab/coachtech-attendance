@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff/index.css') }}">
@endsection

@section('content')
<section class="admin-staff-list">
    <div class="admin-staff-list__inner">
        <h1 class="admin-staff-list__title">スタッフ一覧</h1>

        <table class="admin-staff-table">
            <thead class="admin-staff-table__head">
                <tr class="admin-staff-table__row">
                    <th class="admin-staff-table__header">名前</th>
                    <th class="admin-staff-table__header">メールアドレス</th>
                    <th class="admin-staff-table__header">月次勤怠</th>
                </tr>
            </thead>
            <tbody class="admin-staff-table__body">
                @forelse ($staffRows as $staffRow)
                    <tr class="admin-staff-table__row">
                        <td class="admin-staff-table__data">
                            {{ $staffRow['name'] }}
                        </td>
                        <td class="admin-staff-table__data">
                            {{ $staffRow['email'] }}
                        </td>
                        <td class="admin-staff-table__data">
                            <a
                                class="admin-staff-table__detail-link"
                                href="/admin/attendance/staff/{{ $staffRow['id'] }}"
                            >
                                詳細
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr class="admin-staff-table__row">
                        <td class="admin-staff-table__empty" colspan="3">
                            スタッフはいません。
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection