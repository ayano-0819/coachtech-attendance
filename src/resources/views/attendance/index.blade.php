@extends('layouts.app')

@section('title', '勤怠一覧')

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

            <div class="attendance-index__month-nav">
                <a href="{{ route('attendance.index', ['month' => $previousMonth]) }}">
                    ← 前月
                </a>

                <span>
                    {{ $targetMonth->format('Y/m') }}
                </span>

                <a href="{{ route('attendance.index', ['month' => $nextMonth]) }}">
                    翌月 →
                </a>
            </div>

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
                        <td>
                            {{ $item['date']->format('m/d') }}
                            ({{ ['日','月','火','水','木','金','土'][$item['date']->dayOfWeek] }})
                        </td>

                        <td>
                            {{ $item['attendance'] && $item['attendance']->clock_in_at
                                ? $item['attendance']->clock_in_at->format('H:i')
                                : '' }}
                        </td>

                        <td>
                            {{ $item['attendance'] && $item['attendance']->clock_out_at
                                ? $item['attendance']->clock_out_at->format('H:i')
                                : '' }}
                        </td>

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
