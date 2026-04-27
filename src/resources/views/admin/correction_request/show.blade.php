@extends('layouts.app')

@section('title', '勤怠詳細')

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
    <div class="attendance-show">
        <div class="attendance-show__inner">
            <h1 class="attendance-show__title">勤怠詳細</h1>

            <table class="attendance-show__table">
                <tr>
                    <th>名前</th>
                    <td>{{ $correctionRequest->user->name }}</td>
                </tr>

                <tr>
                    <th>日付</th>
                    <td>
                        {{ $correctionRequest->attendance->work_date->format('Y年') }}
                        {{ $correctionRequest->attendance->work_date->format('n月j日') }}
                    </td>
                </tr>

                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        {{ $correctionRequest->requested_clock_in_at->format('H:i') }}
                        〜
                        {{ $correctionRequest->requested_clock_out_at->format('H:i') }}
                    </td>
                </tr>

                @foreach ($correctionRequest->correctionRequestBreaks as $index => $break)
                    <tr>
                        <th>
                            {{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
                        </th>
                        <td>
                            {{ $break->requested_break_start_at->format('H:i') }}
                            〜
                            {{ $break->requested_break_end_at->format('H:i') }}
                        </td>
                    </tr>
                @endforeach

                <tr>
                    <th>備考</th>
                    <td>{{ $correctionRequest->requested_note }}</td>
                </tr>
            </table>

            <div class="attendance-show__button-wrap">
                @if ($correctionRequest->status === 0)
                    <form method="POST" action="{{ route('correction-requests.approve', $correctionRequest->id) }}">
                        @csrf
                        <button type="submit" class="attendance-show__submit-button">
                            承認
                        </button>
                    </form>
                @else
                    <button type="button" class="attendance-show__submit-button" disabled>
                        承認済み
                    </button>
                @endif
            </div>
        </div>
    </div>
@endsection
