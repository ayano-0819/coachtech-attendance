<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'coachtech-attendance')</title>
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>
<body>

    <header class="header">
        <div class="header__inner">
            <div class="header__left">
                <a href="{{ url('/attendance') }}" class="header__logo-link">
                    <img
                        src="{{ asset('images/COACHTECHヘッダーロゴ.png') }}"
                        alt="COACHTECHのロゴ"
                        class="header__logo"
                    >
                </a>
            </div>

            @yield('header-nav')
        </div>
    </header>

    <main class="main">
        @yield('content')
    </main>

</body>
</html>
