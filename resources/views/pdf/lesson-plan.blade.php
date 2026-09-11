@extends('pdf.layout')

@section('content')
    <table class="meta">
        <tr>
            <td class="label">{{ __('pdf.planCoach') }}</td>
            <td class="strong">{{ $plan->coachProfile->user->name }}</td>
            <td class="label">{{ __('pdf.planClassType') }}</td>
            <td>{{ $plan->classType->name }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('pdf.planBranch') }}</td>
            <td>{{ $plan->branch->name }}</td>
            <td class="label">{{ __('pdf.planLevel') }}</td>
            <td>{{ __('pdf.level.'.$plan->level) }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('pdf.planDuration') }}</td>
            <td>{{ __('pdf.planMinutes', ['count' => $plan->duration_minutes]) }}</td>
            <td class="label">{{ __('pdf.planStatus') }}</td>
            <td>
                <span class="tag {{ $plan->status === 'approved' ? 'tag-paid' : 'tag-due' }}">
                    {{ __('pdf.planStatuses.'.$plan->status) }}
                </span>
            </td>
        </tr>
    </table>

    @if ($plan->objective)
        <h2>{{ __('pdf.planObjective') }}</h2>
        <p>{{ $plan->objective }}</p>
    @endif

    <h2>{{ __('pdf.planSequence') }}</h2>
    <p style="white-space: pre-wrap;">{{ $plan->asana_sequence }}</p>

    <h2>{{ __('pdf.planReviews') }}</h2>
    @if ($plan->reviews->isEmpty())
        <p class="empty">{{ __('pdf.planNoReviews') }}</p>
    @else
        <table class="grid">
            <thead>
                <tr>
                    <th>{{ __('pdf.reviewDate') }}</th>
                    <th>{{ __('pdf.reviewAction') }}</th>
                    <th>{{ __('pdf.reviewBy') }}</th>
                    <th>{{ __('pdf.reviewComment') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($plan->reviews->sortByDesc('reviewed_at') as $review)
                    <tr>
                        <td>{{ $review->reviewed_at->format('d/m/Y H:i') }}</td>
                        <td>{{ __('pdf.planStatuses.'.$review->action) }}</td>
                        <td>{{ $review->reviewer?->name ?? '-' }}</td>
                        <td class="muted">{{ $review->comment ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
