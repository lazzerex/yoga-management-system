<?php

namespace App\Models;

use Database\Factories\ClassTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'is_active'])]
class ClassType extends Model
{
    /** @use HasFactory<ClassTypeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function classSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function classSessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
