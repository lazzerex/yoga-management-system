<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/** A short code people can read out and search, e.g. BK-000123, derived from the id. The model sets REFERENCE_PREFIX. */
trait HasReference
{
    public function reference(): string
    {
        return sprintf('%s-%06d', static::REFERENCE_PREFIX, $this->id);
    }

    /** The digits of a typed code ("BK-58", "0058", "58"), or null when the text is not one. */
    public static function referenceDigits(string $text, bool $requirePrefix = false): ?string
    {
        $prefix = '(?:'.static::REFERENCE_PREFIX.'-?)'.($requirePrefix ? '' : '?');

        return preg_match('/^\s*'.$prefix.'(\d+)\s*$/i', $text, $m) ? $m[1] : null;
    }

    /** Codes containing the digits, so "58" finds BK-000058 and BK-001580 alike. */
    public function scopeWhereReferenceContains(Builder $query, string $digits): Builder
    {
        $column = $query->qualifyColumn('id');
        $padded = $query->getConnection()->getDriverName() === 'sqlite'
            ? "printf('%06d', {$column})"
            : "LPAD({$column}, 6, '0')";

        return $query->whereRaw("{$padded} like ?", ['%'.$digits.'%']);
    }
}
