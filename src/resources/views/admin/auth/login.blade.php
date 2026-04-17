<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>管理者ログイン</title>
</head>
<body>
    <h1>管理者ログイン</h1>

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf
        <input type="hidden" name="login_type" value="admin">

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

        <button type="submit">管理者ログインする</button>
    </form>
</body>
</html>
