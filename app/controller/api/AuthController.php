<?php
namespace app\controller\api;

use app\controller\BaseApiController;
use app\model\User;
use think\facade\Cache;

class AuthController extends BaseApiController
{
    public function login()
    {
        $username = $this->request->param('username', '');
        $password = $this->request->param('password', '');

        if (empty($username) || empty($password)) {
            return $this->error('用户名和密码不能为空');
        }

        $user = User::where('username', $username)->find();
        if (!$user) {
            return $this->error('用户名或密码错误');
        }

        if (!password_verify($password, $user->password ?? '')) {
            return $this->error('用户名或密码错误');
        }

        if ($user->status != 1) {
            return $this->error('账号已被禁用');
        }

        $token = md5($user->id . time() . rand(1000, 9999));
        Cache::set('api_token:' . $token, $user->id, 86400 * 7);

        $user->last_login_time = date('Y-m-d H:i:s');
        $user->last_login_ip = $this->request->ip();
        $user->save();

        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'realname' => $user->realname,
                'avatar' => $user->avatar,
                'phone' => $user->phone,
            ],
        ], '登录成功');
    }

    public function register()
    {
        $data = $this->request->post();

        if (empty($data['username']) || empty($data['password']) || empty($data['realname'])) {
            return $this->error('用户名、密码和真实姓名不能为空');
        }

        $exists = User::where('username', $data['username'])->find();
        if ($exists) {
            return $this->error('用户名已存在');
        }

        if (!empty($data['phone'])) {
            $phoneExists = User::where('phone', $data['phone'])->find();
            if ($phoneExists) {
                return $this->error('手机号已被使用');
            }
        }

        $user = User::create([
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'realname' => $data['realname'],
            'phone' => $data['phone'] ?? '',
            'company' => $data['company'] ?? '',
            'status' => 1,
        ]);

        $token = md5($user->id . time() . rand(1000, 9999));
        Cache::set('api_token:' . $token, $user->id, 86400 * 7);

        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'realname' => $user->realname,
                'avatar' => $user->avatar,
                'phone' => $user->phone,
            ],
        ], '注册成功');
    }

    public function resetPassword()
    {
        $phone = $this->request->param('phone', '');
        $code = $this->request->param('code', '');
        $password = $this->request->param('password', '');

        if (empty($phone) || empty($code) || empty($password)) {
            return $this->error('参数不完整');
        }

        $cachedCode = Cache::get('sms_code:' . $phone);
        if ($cachedCode != $code) {
            return $this->error('验证码错误');
        }

        $user = User::where('phone', $phone)->find();
        if (!$user) {
            return $this->error('用户不存在');
        }

        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->save();
        Cache::delete('sms_code:' . $phone);

        return $this->success(null, '密码重置成功');
    }
}
