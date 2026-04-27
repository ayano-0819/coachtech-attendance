@extends('layouts.app')

@section('title', '勤怠詳細')

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

            @if (session('success'))
                <p class="attendance-show__success-message">
                    {{ session('success') }}
                </p>
            @endif

            @if (session('error'))
                <p class="attendance-show__error-message">
                    {{ session('error') }}
                </p>
            @endif

            @php
                // 承認待ちの修正申請があれば、そのデータを表示用に使う
                $displayClockInAt = $pendingCorrection?->requested_clock_in_at ?? $attendance->clock_in_at;
                $displayClockOutAt = $pendingCorrection?->requested_clock_out_at ?? $attendance->clock_out_at;
                $displayNote = $pendingCorrection?->requested_note ?? '';

                // 休憩も承認待ちがあればそちらを優先
                $displayBreaks = $pendingCorrection
                    ? $pendingCorrection->correctionRequestBreaks
                    : $attendance->attendanceBreaks;

                // 表示件数
                // 承認待ち中は申請内容の件数だけ表示
                // それ以外は old があれば old を優先、なければ既存休憩数 + 1 行表示
                $breakCount = $pendingCorrection
                    ? $displayBreaks->count()
                    : (old('breaks') ? count(old('breaks')) : $displayBreaks->count() + 1);
            @endphp

            @if (!$pendingCorrection)
                <form method="POST" action="{{ route('correction-requests.store', $attendance->id) }}">
                    @csrf
            @endif

                <table class="attendance-show__table">
                    <tr>
                        <th>名前</th>
                        <td>{{ $attendance->user->name }}</td>
                    </tr>

                    <tr>
                        <th>日付</th>
                        <td>
                            {{ $attendance->work_date->format('Y年') }}
                            {{ $attendance->work_date->format('n月j日') }}
                        </td>
                    </tr>

                    <tr>
                        <th>出勤・退勤</th>
                        <td>
                            @if ($pendingCorrection)
                                {{ optional($displayClockInAt)->format('H:i') }}
                                〜
                                {{ optional($displayClockOutAt)->format('H:i') }}
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

                    @for ($i = 0; $i < $breakCount; $i++)
                        @php
                            $break = $displayBreaks[$i] ?? null;
                            $breakStart = $break?->requested_break_start_at ?? $break?->break_start_at;
                            $breakEnd = $break?->requested_break_end_at ?? $break?->break_end_at;
                        @endphp

                        <tr>
                            <th>{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</th>
                            <td>
                                @if ($pendingCorrection)
                                    {{ optional($breakStart)->format('H:i') }}
                                    〜
                                    {{ optional($breakEnd)->format('H:i') }}
                                @else
                                    <input
                                        type="time"
                                        name="breaks[{{ $i }}][break_start_at]"
                                        value="{{ old('breaks.' . $i . '.break_start_at', optional($breakStart)->format('H:i')) }}"
                                    >
                                    〜
                                    <input
                                        type="time"
                                        name="breaks[{{ $i }}][break_end_at]"
                                        value="{{ old('breaks.' . $i . '.break_end_at', optional($breakEnd)->format('H:i')) }}"
                                    >

                                    @error('breaks.' . $i . '.break_start_at')
                                        <p class="attendance-show__error">{{ $message }}</p>
                                    @enderror

                                    @error('breaks.' . $i . '.break_end_at')
                                        <p class="attendance-show__error">{{ $message }}</p>
                                    @enderror
                                @endif
                            </td>
                        </tr>
                    @endfor

                    <tr>
                        <th>備考</th>
                        <td>
                            @if ($pendingCorrection)
                                {{ $pendingCorrection->requested_note }}
                            @else
                                <textarea name="note" rows="4">{{ old('note') !== null ? old('note') : $attendance->note }}</textarea>

                            @error('note')
                                <p class="attendance-show__error">{{ $message }}</p>
                            @enderror
                        @endif
                        </td>
                    </tr>
                </table>

                <div class="attendance-show__button-wrap">
                    @if ($pendingCorrection)
                        <p class="attendance-show__pending-message">
                            *承認待ちのため修正はできません。
                        </p>
                    @else
                        <button type="submit" class="attendance-show__submit-button">
                            修正
                        </button>
                    @endif
                </div>

            @if (!$pendingCorrection)
                </form>
            @endif
        </div>
    </div>
@endsection
