<?php

namespace App\Support\Menu\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Menu facade
 *
 * @method static void register(string $href, string $label, array $options = [])
 * @method static array forRole(?string $role)
 *
 * @see \App\Support\Menu\MenuRegistry
 */

class Menu extends Facade {
    protected static function getFacadeAccessor(): string {
        return 'app-menu';
    }
}