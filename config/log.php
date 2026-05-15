<?php
return [
    'default' => 'file',
    'channels' => [
        'file' => [
            'type' => 'File',
            'path' => '../runtime/log/',
            'level' => ['error', 'warning', 'info'],
            'single' => false,
            'apart_level' => ['error', 'warning'],
            'time_format' => 'Y-m-d H:i:s',
        ],
    ],
];
