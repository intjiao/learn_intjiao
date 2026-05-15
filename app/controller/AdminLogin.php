<?php
namespace app\controller;

use app\BaseController;

class AdminLogin extends BaseController
{
    public function index()
    {
        if ($this->request->isPost()) {
            return $this->login();
        }
        return $this->error('Method not allowed');
    }

    public function login()
    {
        $username = $this->request->param('username', '');
        $password = $this->request->param('password', '');

        if (empty($username) || empty($password)) {
            return $this->error('请输入用户名和密码');
        }

        $admin = \app\model\AdminUser::where('username', $username)->find();

        if (!$admin) {
            return $this->error('用户不存在');
        }

        if ($admin->status != 1) {
            return $this->error('账号已被禁用');
        }

        if (!password_verify($password, $admin->password)) {
            return $this->error('密码错误');
        }

        $admin->last_login_time = date('Y-m-d H:i:s');
        $admin->login_count = $admin->login_count + 1;
        $admin->save();

        session('admin_id', $admin->id);
        session('admin_name', $admin->realname);
        session('admin_username', $admin->username);

        return $this->success([
            'id' => $admin->id,
            'username' => $admin->username,
            'realname' => $admin->realname,
        ], '登录成功');
    }

    public function logout()
    {
        session('admin_id', null);
        session('admin_name', null);
        return $this->success([], '退出成功');
    }
}
