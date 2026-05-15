<?php
namespace app;

use think\App;
use think\exception\ValidateException;
use think\Request;
use think\Validate;
use think\Response;

abstract class BaseController
{
    protected $app;
    protected $request;
    protected $middleware;

    protected $paginate = 15;
    protected $allowEmptyFields = [];

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;
        $this->middleware = $this->app->middleware;
        $this->initialize();
    }

    protected function initialize()
    {
    }

    protected function success($data = [], $msg = '操作成功', $code = 200): Response
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
    }

    protected function error($msg = '操作失败', $code = 400, $data = []): Response
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
    }

    protected function page($list, $total, $page = 1, $limit = 15, $msg = 'success'): Response
    {
        return json([
            'code' => 200,
            'msg' => $msg,
            'count' => (int) $total,
            'page' => (int) $page,
            'limit' => (int) $limit,
            'list' => $list,
            'data' => $list,
        ]);
    }

    protected function validate(array $data, $validate, array $message = [], bool $batch = false): array
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (!strpos($validate, '.')) {
                $validate = "app\\validate\\" . $validate;
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass($validate, 'validate');
            $v = new $class();
        }

        if (!empty($message)) {
            $v->message($message);
        }

        if ($batch || $this->request->batch) {
            $v->batch(true);
        }

        if (!$v->check($data)) {
            throw new ValidateException($v->getError());
        }

        return $data;
    }

    protected function validateSuccess(array $data, $validate, array $message = []): bool|array
    {
        try {
            $this->validate($data, $validate, $message);
            return $data;
        } catch (ValidateException $e) {
            return false;
        }
    }

    public function __call($method, $args)
    {
        return json(['code' => 404, 'msg' => 'Method not found: ' . $method]);
    }
}
