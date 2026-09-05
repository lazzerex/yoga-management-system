<?php

namespace App\Support\Table;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait SortsQueries
{
    /**
     * Order an index query from `?sort=&dir=`, and return the pair the page echoes back.
     *
     * $allowed maps the key a column header sends to the column it may order by, so no
     * request value ever reaches orderBy(). An unknown key falls back to the default.
     *
     * A value may also be a list, which means the key is its own column and holds a status
     * whose alphabetical order is meaningless: the list gives the order to sort it in.
     *
     * @param  array<string, string|array<int, string>>  $allowed
     * @return array{sort: string, dir: string}
     */
    protected function applySort(Builder $query, Request $request, array $allowed, string $defaultColumn, string $defaultDir = 'desc'): array
    {
        $sort = $request->string('sort')->toString();
        $dir = $request->string('dir')->toString() === 'asc' ? 'asc' : 'desc';
        $column = $allowed[$sort] ?? null;

        if ($column === null) {
            $query->orderBy($defaultColumn, $defaultDir);

            return ['sort' => '', 'dir' => ''];
        }

        if (is_array($column)) {
            $this->orderBySequence($query, $sort, $column, $dir);
        } else {
            $query->orderBy($column, $dir);
        }

        return ['sort' => $sort, 'dir' => $dir];
    }

    /**
     * Order a status column by where each value sits in its lifecycle rather than by
     * spelling, so ascending reads scheduled -> done -> cancelled, not cancelled first.
     *
     * @param  array<int, string>  $sequence
     */
    private function orderBySequence(Builder $query, string $column, array $sequence, string $dir): void
    {
        $cases = '';
        foreach (array_keys($sequence) as $position) {
            $cases .= " when ? then {$position}";
        }

        // The column name comes from the controller's whitelist; only the values bind.
        $query->orderByRaw("case {$column}{$cases} else ".count($sequence)." end {$dir}", $sequence);
    }
}
