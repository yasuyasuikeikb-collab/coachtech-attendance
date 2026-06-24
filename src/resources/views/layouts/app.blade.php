<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? '勤怠管理アプリ' }}</title>
    <link rel="stylesheet" href="{{ asset('css/common/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common/header.css') }}">
    @yield('css')
</head>
<body>
    <header class="app-header">
        <div class="app-header__inner">
            <a class="app-header__logo" href="/attendance">
                <img
                    class="app-header__logo-image"
                    src="{{ asset('images/common/coachtech-logo.png') }}"
                    alt="COACHTECH"
                >
            </a>

            <nav class="app-header__nav">
                <a class="app-header__nav-link" href="/attendance">勤怠</a>
                <a class="app-header__nav-link" href="/attendance/list">勤怠一覧</a>
                <a class="app-header__nav-link" href="/stamp_correction_request/list">申請</a>

                <form class="app-header__logout-form" action="/logout" method="post">
                    @csrf
                    <button class="app-header__logout-button" type="submit">ログアウト</button>
                </form>
            </nav>
        </div>
    </header>

    <main class="app-main">
        @yield('content')
    </main>
</body>
</html>