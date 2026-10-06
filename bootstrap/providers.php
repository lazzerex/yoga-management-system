<?php

use App\Modules\Admin\Providers\AdminServiceProvider;
use App\Modules\Operations\Providers\OperationsServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    AdminServiceProvider::class,
    OperationsServiceProvider::class,
];
