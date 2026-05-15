<?php
namespace app;

use think\App;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

class ExceptionHandle extends Handle
{
    protected $ignoreReport = [
        HttpException::class,
        ValidateException::class,
    ];

    public function render($request, Throwable $e): Response
    {
        if ($e instanceof ValidateException) {
            return json([
                'code' => 422,
                'msg' => $e->getError(),
                'data' => null,
            ]);
        }

        if ($e instanceof HttpException) {
            return json([
                'code' => $e->getStatusCode(),
                'msg' => $e->getMessage(),
                'data' => null,
            ]);
        }

        if (app('app')->isDebug()) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage(),
                'data' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString()),
                ],
            ]);
        }

        return json([
            'code' => 500,
            'msg' => '服务器内部错误',
            'data' => null,
        ]);
    }
}
