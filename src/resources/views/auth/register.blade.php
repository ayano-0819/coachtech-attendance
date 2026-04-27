@extends('layouts.app')

@section('title', '会員登録')

@section('header-nav')
@endsection

@section('content')
    <div class="register">
        <div class="register__inner">
            <h1 class="register__title">会員登録</h1>

            <form method="POST" action="{{ route('register') }}" novalidate class="register__form">
                @csrf

                <div class="register__group">
                    <label for="name" class="register__label">お名前</label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        class="register__input"
                    >
                    @error('name')
                        <p class="register__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="register__group">
                    <label for="email" class="register__label">メールアドレス</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="register__input"
                    >
                    @error('email')
                        <p class="register__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="register__group">
                    <label for="password" class="register__label">パスワード</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="register__input"
                    >
                    @error('password')
                        <p class="register__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="register__group">
                    <label for="password_confirmation" class="register__label">確認用パスワード</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        class="register__input"
                    >
                </div>

                <div class="register__button-area">
                    <button type="submit" class="register__button">登録する</button>
                </div>

                <p class="register__link-text">
                    <a href="{{ route('login') }}" class="register__link">ログインはこちら</a>
                </p>
            </form>
        </div>
    </div>
@endsection
