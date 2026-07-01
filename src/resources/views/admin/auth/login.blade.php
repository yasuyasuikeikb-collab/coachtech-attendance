@extends('layouts.auth')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/auth/login.css') }}">
@endsection

@section('content')
<section class="admin-login">
    <div class="admin-login__card">
        <h1 class="admin-login__title">管理者ログイン</h1>

        @if ($errors->any())
            <div class="admin-login__errors">
                @foreach ($errors->all() as $error)
                    <p class="admin-login__error">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form class="admin-login-form" action="/admin/login" method="post">
            @csrf

            <div class="admin-login-form__group">
                <label class="admin-login-form__label" for="email">メールアドレス</label>
                <input
                    class="admin-login-form__input"
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    autofocus
                >
            </div>

            <div class="admin-login-form__group">
                <label class="admin-login-form__label" for="password">パスワード</label>
                <input
                    class="admin-login-form__input"
                    id="password"
                    type="password"
                    name="password"
                >
            </div>

            <button class="admin-login-form__button" type="submit">
                管理者ログインする
            </button>
        </form>
    </div>
</section>
@endsection