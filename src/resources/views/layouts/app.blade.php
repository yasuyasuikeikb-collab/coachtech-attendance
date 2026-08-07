<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>Coachtech Attendance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('css/common/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common/header.css') }}">

    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <a class="header__logo-link" href="/attendance">
                <img class="header__logo" src="{{ asset('images/coachtech.png') }}" alt="COACHTECH">
            </a>

            <nav class="header__nav">
                <a class="header__nav-link" href="/attendance">勤怠</a>
                <a class="header__nav-link" href="/attendance/list">勤怠一覧</a>
                <a class="header__nav-link" href="/stamp_correction_request/list">申請</a>
                <a class="header__nav-link" href="/attendance/report">レポート</a>

                <form class="header__logout-form" action="/logout" method="post">
                    @csrf
                    <button class="header__logout-button" type="submit">ログアウト</button>
                </form>
            </nav>
        </div>
    </header>

    @yield('content')
</body>

</html>