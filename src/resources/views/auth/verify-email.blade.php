@extends('layouts.app')

@section('title', 'メール認証')

{{-- @section('css')
    <link rel="stylesheet" href="{{ asset('css/auth/verify-email.css') }}">
@endsection --}}

@section('header-nav')
@endsection

@section('content')
    <div class="verify">
        <div class="verify__inner">

            <p class="verify__message">
                登録していただいたメールアドレスに認証メールを送付しました。<br>
                メール認証を完了してください。
            </p>

            <div class="verify__button-area">
                <a
                    href="https://mailtrap.io/inboxes"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="verify__link-button"
                >
                    認証はこちらから
                </a>
            </div>

            <div class="verify__resend-area">
                <form method="POST" action="{{ route('verification.send') }}" class="verify__form">
                    @csrf
                    <button type="submit" class="verify__resend-button">
                        認証メールを再送する
                    </button>
                </form>
            </div>

            @if (session('status'))
                <p>{{ session('status') }}</p>
            @endif

            @if (session('message'))
                <p class="verify__status">
                    {{ session('message') }}
                </p>
            @endif
            
        </div>
    </div>
@endsection
