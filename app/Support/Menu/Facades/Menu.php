<?php

namespace App\Support\Menu\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Menu facade
 *
 * @method static void addItems(array $items)
 * @method static array forUser(?\Illuminate\Contracts\Auth\Authenticatable $user)
 *
 * @see \App\Support\Menu\MenuRegistry
 */

class Menu extends Facade {
    protected static function getFacadeAccessor(): string {
        return 'app-menu';
    }
}