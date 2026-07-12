<?php

namespace App\Support\Menu;

class AppMenuItem
{
    protected string $label;
    protected ?string $href;
    protected ?string $icon = null;
    protected string $iconColor = '#666666';
    protected string $group = 'nav.main';
    protected string|array|null $permissions = null;
    protected int $order = 100;
    protected ?string $badge = null;
    protected bool $nolink = false;
    protected bool $separatorBefore = false;

    /** @var AppMenuItem[] */
    protected array $children = [];

    public static function make(string $label, ?string $href = null): static
    {
        $item = new static();
        $item->label = $label;
        $item->href = $href;

        return $item;
    }

    public function nolink(): static
    {
        $this->nolink = true;

        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function iconColor(string $color): static
    {
        $this->iconColor = $color;

        return $this;
    }

    public function group(string $group): static
    {
        $this->group = $group;

        return $this;
    }

    public function order(int $position): static
    {
        $this->order = $position;

        return $this;
    }

    public function permissions(string|array $permissions): static
    {
        $this->permissions = $permissions;

        return $this;
    }

    public function badge(string $key): static
    {
        $this->badge = $key;

        return $this;
    }

    public function separatorBefore(): static
    {
        $this->separatorBefore = true;

        return $this;
    }

    /** @param AppMenuItem[] $items */
    public function addItems(array $items): static
    {
        $this->children = [...$this->children, ...$items];

        return $this;
    }

    public function toArray(): array
    {
        return [
            'href'            => $this->nolink ? null : $this->href,
            'label'           => $this->label,
            'icon'            => $this->icon,
            'iconColor'       => $this->iconColor,
            'group'           => $this->group,
            'permissions'     => $this->permissions,
            'position'        => $this->order,
            'badge'           => $this->badge,
            'nolink'          => $this->nolink,
            'separatorBefore' => $this->separatorBefore,
            'children'        => array_map(fn (AppMenuItem $child) => $child->toArray(), $this->children),
        ];
    }
}