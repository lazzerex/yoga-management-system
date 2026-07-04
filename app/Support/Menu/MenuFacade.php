<?php

namespace App\Support\Menu;

use Illuminate\Support\Facades\Facade;

class MenuFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MenuRegistry::class;
    }
}