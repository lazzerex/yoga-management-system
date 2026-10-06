<?php

namespace App\Support\Menu;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use TorMorten\Eventy\Facades\Events as Eventy;

class MenuRegistry
{
    protected array $items = [];

    protected bool $hooked = false;

    /** @param AppMenuItem[] $items */
    public function addItems(array $items): void
    {
        foreach ($items as $item) {
            $this->items[] = $item->toArray();
        }
    }

    public function forUser(?Authenticatable $user): array
    {
        $this->fireRegisterHook();

        return collect($this->items)
            ->filter(fn (array $item): bool => $this->isVisible($item, $user))
            ->sortBy('position')
            ->groupBy('group')
            ->map(fn (Collection $items, string $group) => [
                'labelKey' => $group,
                'items' => $items->values()
                    ->map(fn (array $item) => $this->mapItem($item, $user))
                    ->all(),
            ])
            ->values()
            ->all();
    }

    protected function fireRegisterHook(): void
    {
        if ($this->hooked) {
            return;
        }

        $this->hooked = true;
        Eventy::action('register_backend_menu', $this);
    }

    protected function mapItem(array $item, ?Authenticatable $user): array
    {
        return [
            'href' => $item['href'],
            'labelKey' => $item['label'],
            'icon' => $item['icon'],
            'iconColor' => $item['iconColor'],
            'badgeKey' => $item['badge'],
            'separator' => $item['separatorBefore'],
            'children' => collect($item['children'])
                ->filter(fn (array $child): bool => $this->isVisible($child, $user))
                ->sortBy('position')
                ->values()
                ->map(fn (array $child) => $this->mapItem($child, $user))
                ->all(),
        ];
    }

    protected function isVisible(array $item, ?Authenticatable $user): bool
    {
        if (empty($item['permissions'])) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        foreach ((array) $item['permissions'] as $ability) {
            if ($user->can($ability)) {
                return true;
            }
        }

        return false;
    }
}
