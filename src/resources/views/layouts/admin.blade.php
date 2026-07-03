<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者画面</title>
    <link rel="stylesheet" href="{{ asset('css/common/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common/admin-header.css') }}">
    @yield('css')
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="admin-header__inner">
            <a class="admin-header__logo-link" href="/admin/attendance/list">
                <img
                    class="admin-header__logo"
                    src="{{ asset('images/common/coachtech-logo.png') }}"
                    alt="COACHTECH"
                >
            </a>

            <nav class="admin-header__nav">
                <a class="admin-header__nav-link" href="/admin/attendance/list">
                    勤怠一覧
                </a>
                <a class="admin-header__nav-link" href="/admin/staff/list">
                    スタッフ一覧
                </a>
                <a class="admin-header__nav-link" href="/stamp_correction_request/list">
                    申請一覧
                </a>
                <form class="admin-header__logout-form" action="/logout" method="post">
                    @csrf
                    <button class="admin-header__logout-button" type="submit">
                        ログアウト
                    </button>
                </form>
            </nav>
        </div>
    </header>

    <main class="admin-main">
        @yield('content')
    </main>
</body>
</html>