<?php

namespace App\Modules\Dashboard\Actions;

use App\Models\ClassSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Aggregates only: the session list itself lives on the Classes page. */
class StudioPulseAction
{
    private const WEEKS = 8;

    public function execute(?int $branchId): array
    {
        $from = today()->startOfWeek()->subWeeks(self::WEEKS - 1);

        // room needs branch_id selected or its branch relation resolves to null.
        $sessions = ClassSession::with(['classType:id,name', 'room:id,name,branch_id', 'room.branch:id,name'])
            ->withCount(['enrollments as booked_count' => fn ($q) => $q->where('status', 'booked')])
            ->whereBetween('session_date', [$from->toDateString(), today()->toDateString()])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();

        return [
            'weeks' => self::WEEKS,
            'heatmap' => $this->heatmap($sessions),
            'byClassType' => $this->byClassType($sessions),
            'topRooms' => $this->topRooms($sessions),
            'trend' => $this->trend($sessions, $from),
            'cancelled' => $sessions->where('status', 'cancelled')->count(),
            'total' => $sessions->count(),
        ];
    }

    /** @param  Collection<int, ClassSession>  $sessions */
    private function heatmap(Collection $sessions): array
    {
        return $sessions
            ->where('status', '!=', 'cancelled')
            ->groupBy(fn (ClassSession $session) => Carbon::parse($session->session_date)->dayOfWeek)
            ->map(fn (Collection $ofDay) => $ofDay
                ->groupBy(fn (ClassSession $session) => substr($session->start_time, 0, 2).':00')
                ->map(fn (Collection $ofHour) => [
                    'booked' => (int) $ofHour->sum('booked_count'),
                    'capacity' => (int) $ofHour->sum('capacity'),
                    'rate' => $ofHour->sum('capacity') > 0
                        ? (int) round($ofHour->sum('booked_count') / $ofHour->sum('capacity') * 100)
                        : 0,
                ])
                ->all())
            ->all();
    }

    /** @param  Collection<int, ClassSession>  $sessions */
    private function byClassType(Collection $sessions): array
    {
        return $sessions
            ->groupBy('class_type_id')
            ->map(fn (Collection $group) => [
                'name' => $group->first()->classType->name,
                'sessions' => $group->count(),
                'rate' => $group->sum('capacity') > 0
                    ? (int) round($group->sum('booked_count') / $group->sum('capacity') * 100)
                    : 0,
            ])
            ->sortByDesc('sessions')
            ->values()
            ->all();
    }

    /** @param  Collection<int, ClassSession>  $sessions */
    private function topRooms(Collection $sessions): array
    {
        return $sessions
            ->groupBy('room_id')
            ->map(fn (Collection $group) => [
                'name' => $group->first()->room->name.' · '.$group->first()->room->branch->name,
                'booked' => (int) $group->sum('booked_count'),
                'sessions' => $group->count(),
            ])
            ->sortByDesc('booked')
            ->take(6)
            ->values()
            ->all();
    }

    /** @param  Collection<int, ClassSession>  $sessions */
    private function trend(Collection $sessions, Carbon $from): array
    {
        $byWeek = $sessions
            ->where('status', '!=', 'cancelled')
            ->groupBy(fn (ClassSession $session) => Carbon::parse($session->session_date)->startOfWeek()->toDateString());

        return collect(range(0, self::WEEKS - 1))
            ->map(function (int $offset) use ($byWeek, $from) {
                $week = $from->copy()->addWeeks($offset);
                $group = $byWeek[$week->toDateString()] ?? collect();

                return [
                    'week' => $week->format('d/m'),
                    'booked' => (int) $group->sum('booked_count'),
                    'capacity' => (int) $group->sum('capacity'),
                ];
            })
            ->all();
    }
}
