<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログイン</title>
</head>
<body>
    <h1>ログイン</h1>

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf
        <input type="hidden" name="login_type" value="user">

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

        <button type="submit">ログインする</button>
    </form>

    <a href="{{ route('register') }}">会員登録はこちら</a>
</body>
</html>