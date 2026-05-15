<?php
return [
    'default' => 'file',
    'stores' => [
        'file' => [
            'type' => 'File',
            'path' => '../runtime/cache/',
            'prefix' => 'peixun_',
            'expire' => 0,
        ],
    ],
];
