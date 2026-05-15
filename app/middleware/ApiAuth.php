<?php
namespace app\middleware;

use app\model\User;
use think\facade\Cache;

class ApiAuth
{
    public function handle($request, \Closure $next)
    {
        $token = $request->header('Authorization', '');
        $token = str_replace('Bearer ', '', $token);

        if (empty($token)) {
            return json(['code' => 401, 'msg' => '未授权，请先登录']);
        }

        $userId = Cache::get('api_token:' . $token);
        if (!$userId) {
            return json(['code' => 401, 'msg' => 'Token已过期，请重新登录']);
        }

        $user = User::find($userId);
        if (!$user || $user->status != 1) {
            Cache::delete('api_token:' . $token);
            return json(['code' => 401, 'msg' => '账号已被禁用']);
        }

        $request->userId = $userId;
        $request->user = $user;
        return $next($request);
    }
}
