@extends('layouts.app')

@section('title', '勤怠一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance/index.css') }}">
@endsection

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
    <div class="attendance-index">
        <div class="attendance-index__inner">
            <h1 class="attendance-index__title">勤怠一覧</h1>

            <nav
                class="attendance-index__month-nav"
                aria-label="月切り替え"
            >
                <a
                    href="{{ route('attendance.index', ['month' => $previousMonth]) }}"
                    class="attendance-index__month-link"
                >
                    <img
                        src="{{ asset('images/arrow-left.svg') }}"
                        alt="前月"
                        class="attendance-index__arrow-icon"
                    >

                    前月
                </a>

                <span class="attendance-index__month">
                    <img
                        src="{{ asset('images/calendar-icon.png') }}"
                        alt="カレンダー"
                        class="attendance-index__calendar-icon"
                    >

                    {{ $targetMonth->format('Y/m') }}
                </span>

                <a
                    href="{{ route('attendance.index', ['month' => $nextMonth]) }}"
                    class="attendance-index__month-link"
                >
                    翌月

                    <img
                        src="{{ asset('images/arrow-right.svg') }}"
                        alt="翌月"
                        class="attendance-index__arrow-icon"
                    >
                </a>
            </nav>

            <table class="attendance-index__table">
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dates as $item)
                        <tr>
                            <td>{{ $item['formatted_date'] }}</td>

                            <td>{{ $item['attendance']?->clock_in_time }}</td>

                            <td>{{ $item['attendance']?->clock_out_time }}</td>

                            <td>{{ $item['break_time'] }}</td>

                            <td>{{ $item['work_time'] }}</td>

                            <td>
                                @if ($item['attendance'])
                                    <a href="{{ route('attendance.show', ['id' => $item['attendance']->id]) }}">
                                        詳細
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
