<?php
namespace app\controller\api;

use app\controller\BaseApiController;
use think\facade\Cache;

class CaptchaController extends BaseApiController
{
    public function index()
    {
        $code = rand(1000, 9999);
        $key = 'captcha_' . md5(time() . rand());
        Cache::set($key, $code, 300);

        return $this->success([
            'key' => $key,
            'code' => $code,
        ]);
    }
}
