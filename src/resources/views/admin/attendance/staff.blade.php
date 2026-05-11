@extends('layouts.app')

@section('title', 'スタッフ別勤怠一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance/staff.css') }}">
@endsection

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
    <div class="admin-attendance-staff">
        <div class="admin-attendance-staff__inner">
            <h1 class="admin-attendance-staff__title">
                {{ $user->name }}さんの勤怠
            </h1>

            <div class="admin-attendance-staff__month-nav">
                <a
                    href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $prevMonth]) }}"
                    class="admin-attendance-staff__month-link"
                >
                    <img
                        src="{{ asset('images/arrow-left.svg') }}"
                        alt="前月"
                        class="admin-attendance-staff__arrow-icon"
                    >

                    前月
                </a>

                <span class="admin-attendance-staff__month">
                    <img
                        src="{{ asset('images/calendar-icon.png') }}"
                        alt="カレンダーアイコン"
                        class="admin-attendance-staff__calendar-icon"
                    >

                    {{ $currentMonth->format('Y/m') }}
                </span>

                <a
                    href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $nextMonth]) }}"
                    class="admin-attendance-staff__month-link"
                >
                    翌月

                    <img
                        src="{{ asset('images/arrow-right.svg') }}"
                        alt="翌月"
                        class="admin-attendance-staff__arrow-icon"
                    >
                </a>
            </div>

            <div class="admin-attendance-staff__table-wrap">
                <table class="admin-attendance-staff__table">
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
                        @foreach ($attendances as $attendance)
                            <tr>
                                <td>
                                    {{ $attendance->work_date ? \Carbon\Carbon::parse($attendance->work_date)->isoFormat('MM/DD(ddd)') : '' }}
                                </td>
                                <td>{{ optional($attendance->clock_in_at)->format('H:i') }}</td>
                                <td>{{ optional($attendance->clock_out_at)->format('H:i') }}</td>
                                <td>
                                    @if ($attendance->clock_in_at)
                                        {{ $attendance->break_total }}
                                    @endif
                                </td>
                                <td>
                                    @if ($attendance->clock_in_at)
                                        {{ $attendance->work_total }}
                                    @endif
                                </td>
                                <td>
                                    @if ($attendance->id)
                                        <a href="{{ route('admin.attendance.show', ['id' => $attendance->id]) }}">
                                            詳細
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="admin-attendance-staff__csv">
                <a
                    href="{{ route('admin.staff.csv', ['id' => $user->id, 'month' => $currentMonth->format('Y-m')]) }}"
                    class="admin-attendance-staff__csv-button"
                >
                    CSV出力
                </a>
            </div>
        </div>
    </div>
@endsection
