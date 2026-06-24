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
            <a class="app-header__logo" href="/">
                <img class="app-header__logo-image" src="{{ asset('images/common/coachtech-logo.png.png') }}" alt="COACHTECH">
            </a>
        </div>
    </header>

    <main class="app-main">
        @yield('content')
    </main>
</body>
</html>