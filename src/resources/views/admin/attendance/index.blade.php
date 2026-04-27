@extends('layouts.app')

@section('title', '勤怠一覧')

@section('header-nav')
    <div class="header__right">
        <a href="{{ route('admin.attendance.index') }}" class="header__link">勤怠一覧</a>
        <a href="{{ route('admin.staff.index') }}" class="header__link">スタッフ一覧</a>
        <a href="{{ route('correction-requests.index') }}" class="header__link">申請一覧</a>

        <form method="POST" action="/logout" class="header__logout-form">
            @csrf
            <input type="hidden" name="redirect_to" value="admin">
            <button type="submit" class="header__logout-button">ログアウト</button>
        </form>
    </div>
@endsection

@section('content')
    <div class="admin-attendance-index">
        <div class="admin-attendance-index__inner">

            <h1 class="admin-attendance-index__title">
                {{ $targetDate->format('Y年n月j日') }}の勤怠
            </h1>

            <div class="admin-attendance-index__date-nav">
                <a href="{{ route('admin.attendance.index', ['date' => $previousDate]) }}"
                    class="admin-attendance-index__date-link">
                    ← 前日
                </a>

                <div class="admin-attendance-index__date-current">
                    {{ $targetDate->format('Y/m/d') }}
                </div>

                <a href="{{ route('admin.attendance.index', ['date' => $nextDate]) }}"
                    class="admin-attendance-index__date-link">
                    翌日 →
                </a>
            </div>

            <table class="admin-attendance-index__table">
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendances as $attendance)
                        <tr>
                            <td>{{ $attendance->user->name }}</td>
                            <td>{{ $attendance->clock_in_at ? $attendance->clock_in_at->format('H:i') : '' }}</td>
                            <td>{{ $attendance->clock_out_at ? $attendance->clock_out_at->format('H:i') : '' }}</td>
                            <td>{{ $attendance->formatted_break_time }}</td>
                            <td>{{ $attendance->formatted_work_time }}</td>
                            <td>
                                <a href="{{ route('admin.attendance.show', ['id' => $attendance->id]) }}">
                                    詳細
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">勤怠データがありません</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>
    </div>
@endsection
