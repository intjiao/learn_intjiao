<?php
use app\middleware\AdminAuth;
use app\middleware\ApiAuth;
use app\middleware\Cors;

return [
    'alias' => [
        'adminAuth' => AdminAuth::class,
        'apiAuth' => ApiAuth::class,
        'cors' => Cors::class,
    ],
    'priority' => [],
    'controller' => [],
    'route' => [],
    'middleware' => [
        \think\middleware\SessionInit::class,
    ],
];
