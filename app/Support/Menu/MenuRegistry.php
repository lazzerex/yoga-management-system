<?php

namespace App\Support\Menu;

use Illuminate\Support\Collection;

class MenuRegistry
{
    protected array $items = [];

    public function register(string $href, string $label, array $options = []): void
    {
        $this->items[] = array_merge([
            'href'      => $href,
            'label'     => $label,
            'icon'      => null,
            'iconColor' => '#666666',
            'group'     => 'Main',
            'roles'     => [],
            'position'  => 100,
            'badge'     => null,
        ], $options);
    }

    public function forRole(?string $role): array
    {
        return collect($this->items)
            ->filter(function (array $item) use ($role): bool {
                if (empty($item['roles'])) {
                    return true;
                }

                return $role !== null && in_array($role, $item['roles'], true);
            })
            ->sortBy('position')
            ->groupBy('group')
            ->map(fn (Collection $items, string $group) => [
                'label' => $group,
                'items' => $items->values()->map(fn (array $item) => [
                    'href'      => $item['href'],
                    'label'     => $item['label'],
                    'icon'      => $item['icon'],
                    'iconColor' => $item['iconColor'],
                    'badge'     => $item['badge'],
                ])->all(),
            ])
            ->values()
            ->all();
    }
}