<?php
namespace app\controller;

use think\App;
use think\exception\ValidateException;

abstract class BaseApiController extends \app\BaseController
{
    protected $middleware = [];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->middlewareInit();
    }

    protected function middlewareInit(): void
    {
    }

    public function index()
    {
        return $this->error('Method not allowed');
    }
}
