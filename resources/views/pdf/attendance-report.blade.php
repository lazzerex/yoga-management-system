@extends('pdf.layout')

@section('content')
    <h2>{{ __('pdf.reportTaughtHours') }}</h2>
    @if ($taughtHours->isEmpty())
        <p class="empty">{{ __('pdf.reportNoHours') }}</p>
    @else
        <table class="grid">
            <thead>
                <tr>
                    <th>{{ __('pdf.reportCoach') }}</th>
                    <th class="num">{{ __('pdf.reportSessions') }}</th>
                    <th class="num">{{ __('pdf.reportHours') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($taughtHours as $row)
                    <tr>
                        <td class="strong">{{ $row['coach_name'] }}</td>
                        <td class="num">{{ $row['sessions'] }}</td>
                        <td class="num">{{ number_format($row['minutes'] / 60, 1) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>{{ __('pdf.reportAttendanceRates') }}</h2>
    @if ($attendanceRates->isEmpty())
        <p class="empty">{{ __('pdf.reportNoAttendance') }}</p>
    @else
        <table class="grid">
            <thead>
                <tr>
                    <th>{{ __('pdf.reportStudent') }}</th>
                    <th class="num">{{ __('pdf.reportPresent') }}</th>
                    <th class="num">{{ __('pdf.reportLate') }}</th>
                    <th class="num">{{ __('pdf.reportAbsent') }}</th>
                    <th class="num">{{ __('pdf.reportRate') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attendanceRates as $row)
                    <tr>
                        <td class="strong">{{ $row['student_name'] }}</td>
                        <td class="num">{{ $row['present'] }}</td>
                        <td class="num">{{ $row['late'] }}</td>
                        <td class="num">{{ $row['absent'] }}</td>
                        <td class="num">{{ $row['rate'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
