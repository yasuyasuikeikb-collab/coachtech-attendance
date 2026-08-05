@extends('layouts.auth')

@section('title', 'メール認証')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/verify-email.css') }}">
@endsection

@section('content')
<main class="verify-email">
    <div class="verify-email__inner">
        @if (session('status') === 'verification-link-sent')
            <p class="verify-email__success">
                認証メールを再送しました。
            </p>
        @endif

        <p class="verify-email__message">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </p>

        <a
            class="verify-email__button"
            href="http://localhost:8025"
            target="_blank"
            rel="noopener noreferrer"
        >
            認証はこちらから
        </a>

        <form
            class="verify-email__resend-form"
            method="POST"
            action="{{ route('verification.send') }}"
        >
            @csrf
            <button class="verify-email__resend-button" type="submit">
                認証メールを再送する
            </button>
        </form>
    </div>
</main>
@endsection