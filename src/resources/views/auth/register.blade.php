@extends('layouts.auth')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">
@endsection

@section('content')
<section class="register">
    <div class="register__card">
        <h1 class="register__title">会員登録</h1>

        @if ($errors->any())
            <div class="register__errors">
                @foreach ($errors->all() as $error)
                    <p class="register__error">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form class="register-form" action="/register" method="post">
            @csrf

            <div class="register-form__group">
                <label class="register-form__label" for="name">名前</label>
                <input
                    class="register-form__input"
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                >
            </div>

            <div class="register-form__group">
                <label class="register-form__label" for="email">メールアドレス</label>
                <input
                    class="register-form__input"
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                >
            </div>

            <div class="register-form__group">
                <label class="register-form__label" for="password">パスワード</label>
                <input
                    class="register-form__input"
                    id="password"
                    type="password"
                    name="password"
                    required
                >
            </div>

            <div class="register-form__group">
                <label class="register-form__label" for="password-confirmation">確認用パスワード</label>
                <input
                    class="register-form__input"
                    id="password-confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                >
            </div>

            <button class="register-form__button" type="submit">登録する</button>
        </form>

        <div class="register__link-area">
            <a class="register__link" href="/login">ログインはこちら</a>
        </div>
    </div>
</section>
@endsection