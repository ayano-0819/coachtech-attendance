@extends('layouts.app')

@section('title', '管理者ログイン')

@section('header-nav')
@endsection

@section('content')
    <div class="login">
        <div class="login__inner">
            <h1 class="login__title">管理者ログイン</h1>

            <form method="POST" action="{{ route('login') }}" novalidate class="login__form">
                @csrf

                <input type="hidden" name="login_type" value="admin">

                <div class="login__group">
                    <label for="email" class="login__label">メールアドレス</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="login__input"
                    >
                    @error('email')
                        <p class="login__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="login__group">
                    <label for="password" class="login__label">パスワード</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="login__input"
                    >
                    @error('password')
                        <p class="login__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="login__button-area">
                    <button type="submit" class="login__button">管理者ログインする</button>
                </div>
            </form>
        </div>
    </div>
@endsection
