<?php

namespace App\Support\Menu\Facades;

use App\Support\Menu\MenuRegistry;
use Illuminate\Support\Facades\Facade;

/**
 * Menu facade
 *
 * @method static void addItems(array $items)
 * @method static array forUser(?\Illuminate\Contracts\Auth\Authenticatable $user)
 *
 * @see MenuRegistry
 */
class Menu extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'app-menu';
    }
}
