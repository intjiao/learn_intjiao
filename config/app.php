<?php
return [
    'default_return_type' => 'json',
    'default_ajax_return' => 'json',
    'default_jsonp_handler' => 'jsonpReturn',
    'var_jsonp_handler' => 'callback',

    'exception_handle' => \app\ExceptionHandle::class,

    'show_error_msg' => false,
    'exception_tmpl' => app()->getThinkPath() . 'tpl/think_exception.tpl',

    'default_timezone' => 'Asia/Shanghai',

    'default_lang' => 'zh-cn',

    'request_cache' => false,
    'request_cache_expire' => 3600,
    'request_cache_except' => [],

    'default_filter' => '',

    'default_validate' => true,

    'auto_bind_model' => true,
];
