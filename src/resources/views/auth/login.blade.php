@extends('layouts.auth')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
@endsection

@section('content')
<section class="login">
    <div class="login__card">
        <h1 class="login__title">ログイン</h1>

        @if ($errors->any())
            <div class="login__errors">
                @foreach ($errors->all() as $error)
                    <p class="login__error">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form class="login-form" action="/login" method="post">
            @csrf

            <div class="login-form__group">
                <label class="login-form__label" for="email">メールアドレス</label>
                <input
                    class="login-form__input"
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                >
            </div>

            <div class="login-form__group">
                <label class="login-form__label" for="password">パスワード</label>
                <input
                    class="login-form__input"
                    id="password"
                    type="password"
                    name="password"
                    required
                >
            </div>

            <button class="login-form__button" type="submit">ログインする</button>
        </form>

        <div class="login__link-area">
            <a class="login__link" href="/register">会員登録はこちら</a>
        </div>
    </div>
</section>
@endsection