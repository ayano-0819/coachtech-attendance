@extends('layouts.app')

@section('title', '勤怠登録')

@section('header-nav')
    <div class="header__right">
        <a href="{{ route('attendance.create') }}" class="header__link">勤怠</a>
        <a href="{{ route('attendance.index') }}" class="header__link">勤怠一覧</a>
        <a href="{{ route('correction-requests.index') }}" class="header__link">申請</a>

        <form method="POST" action="/logout" class="header__logout-form">
            @csrf
            <button type="submit" class="header__logout-button">ログアウト</button>
        </form>
    </div>
@endsection

@section('content')
    <div class="attendance-create">
        <div class="attendance-create__main">

            <div class="attendance-create__status">
                {{ $status }}
            </div>

            <p class="attendance-create__date">
                {{ now()->format('Y年n月j日') }}
                ({{ ['日','月','火','水','木','金','土'][now()->dayOfWeek] }})
            </p>

            <p class="attendance-create__time">
                {{ now()->format('H:i') }}
            </p>

            @if ($status === '勤務外')
                <form method="POST" action="{{ route('attendance.clockIn') }}">
                    @csrf
                    <button type="submit" class="attendance-create__clock-in-button">
                        出勤
                    </button>
                </form>
            @elseif ($status === '出勤中')
                <div class="attendance-create__buttons">
                    <form method="POST" action="{{ route('attendance.clockOut') }}">
                        @csrf
                        <button type="submit" class="attendance-create__clock-out-button">
                            退勤
                        </button>
                    </form>

                    <form method="POST" action="{{ route('attendance.breakStart') }}">
                        @csrf
                        <button type="submit" class="attendance-create__break-start-button">
                            休憩入
                        </button>
                    </form>
                </div>
            @elseif ($status === '休憩中')
                <form method="POST" action="{{ route('attendance.breakEnd') }}">
                    @csrf
                    <button type="submit" class="attendance-create__break-end-button">
                        休憩戻
                    </button>
                </form>
            @elseif ($status === '退勤済')
                <p class="attendance-create__message">
                    お疲れ様でした。
                </p>
            @endif

        </div>
    </div>
@endsection
