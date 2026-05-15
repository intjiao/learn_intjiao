<?php
namespace app\controller;

use think\App;

abstract class BaseAdminController extends \app\BaseController
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

    protected function getAdminId(): int
    {
        return $this->request->admin->id ?? 0;
    }

    protected function getAdminName(): string
    {
        return $this->request->admin->username ?? '';
    }

    public function index()
    {
        return $this->error('Method not allowed');
    }
}
