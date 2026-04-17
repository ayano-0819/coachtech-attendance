<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>会員登録</title>
</head>
<body>
    <h1>会員登録</h1>

    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <div>
            <label for="name">名前</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}">
            @error('name')
                <p>{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email">メールアドレス</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}">
            @error('email')
                <p>{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password">パスワード</label>
            <input id="password" type="password" name="password">
            @error('password')
                <p>{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation">パスワード確認</label>
            <input id="password_confirmation" type="password" name="password_confirmation">
        </div>

        <button type="submit">登録する</button>
    </form>

    <a href="{{ route('login') }}">ログインはこちら</a>
</body>
</html>
