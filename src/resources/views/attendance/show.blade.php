@extends('layouts.app')

@section('title', '勤怠詳細')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance/show.css') }}">
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
    <div class="attendance-show">
        <div class="attendance-show__inner">
            <h1 class="attendance-show__title">勤怠詳細</h1>

            @if (!$isPending)
                <form method="POST" action="{{ route('correction-requests.store', $attendance->id) }}">
                    @csrf
            @endif

            <table class="attendance-show__table">
                <tr>
                    <th>名前</th>
                    <td class="attendance-show__name">
                        {{ $attendance->user->name }}
                    </td>
                </tr>

                <tr>
                    <th>日付</th>
                    <td class="attendance-show__date">
                        <span>{{ $attendance->work_date->format('Y年') }}</span>
                        <span>{{ $attendance->work_date->format('n月j日') }}</span>
                    </td>
                </tr>

                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        @if ($isPending)
                            <div class="attendance-show__time-text">
                                <span>{{ optional($displayClockInAt)->format('H:i') }}</span>
                                <span>〜</span>
                                <span>{{ optional($displayClockOutAt)->format('H:i') }}</span>
                            </div>
                        @else
                            <input
                                type="time"
                                name="clock_in_at"
                                value="{{ old('clock_in_at', optional($displayClockInAt)->format('H:i')) }}"
                            >
                            〜
                            <input
                                type="time"
                                name="clock_out_at"
                                value="{{ old('clock_out_at', optional($displayClockOutAt)->format('H:i')) }}"
                            >

                            @error('clock_in_at')
                                <p class="attendance-show__error">{{ $message }}</p>
                            @enderror

                            @error('clock_out_at')
                                <p class="attendance-show__error">{{ $message }}</p>
                            @enderror
                        @endif
                    </td>
                </tr>

                @foreach ($displayBreaks as $index => $break)
                    <tr>
                        <th>{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</th>

                        <td>
                            @if ($isPending)
                                <div class="attendance-show__time-text">
                                    <span>{{ optional($break?->requested_break_start_at)->format('H:i') }}</span>
                                    <span>〜</span>
                                    <span>{{ optional($break?->requested_break_end_at)->format('H:i') }}</span>
                                </div>
                            @else
                                <input
                                    type="time"
                                    name="breaks[{{ $index }}][break_start_at]"
                                    value="{{ old('breaks.' . $index . '.break_start_at', optional($break?->break_start_at)->format('H:i')) }}"
                                >

                                〜

                                <input
                                    type="time"
                                    name="breaks[{{ $index }}][break_end_at]"
                                    value="{{ old('breaks.' . $index . '.break_end_at', optional($break?->break_end_at)->format('H:i')) }}"
                                >

                                @error('breaks.' . $index . '.break_start_at')
                                    <p class="attendance-show__error">{{ $message }}</p>
                                @enderror

                                @error('breaks.' . $index . '.break_end_at')
                                    <p class="attendance-show__error">{{ $message }}</p>
                                @enderror
                            @endif
                        </td>
                    </tr>
                @endforeach

                <tr>
                    <th>備考</th>
                    <td>
                        @if ($isPending)
                            {{ $displayNote }}
                        @else
                            <textarea
                                name="note"
                                rows="4"
                            >{{ old('note', $attendance->note) }}</textarea>

                            @error('note')
                                <p class="attendance-show__error">{{ $message }}</p>
                            @enderror
                        @endif
                    </td>
                </tr>
            </table>
    
            <div class="attendance-show__button-wrap">
                @if ($isPending)
                    <p class="attendance-show__pending-message">
                        *承認待ちのため修正はできません。
                    </p>
                @else
                    <button type="submit" class="attendance-show__submit-button">
                        修正
                    </button>
                @endif
            </div>

            @if (!$isPending)
                </form>
            @endif
        </div>
    </div>
@endsection
