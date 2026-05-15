<?php
namespace app\controller\admin;

use app\controller\BaseAdminController;
use app\model\AdminUser;
use think\facade\Session;
use think\facade\Db;

class LoginController extends BaseAdminController
{
    public function login()
    {
        $username = $this->request->param('username', '');
        $password = $this->request->param('password', '');

        if (empty($username) || empty($password)) {
            return $this->error('用户名和密码不能为空');
        }

        $admin = AdminUser::where('username', $username)->find();
        if (!$admin) {
            return $this->error('用户名或密码错误');
        }

        if (!password_verify($password, $admin->password)) {
            return $this->error('用户名或密码错误');
        }

        if ($admin->status != 1) {
            return $this->error('账号已被禁用');
        }

        $token = bin2hex(random_bytes(32));
        $expireTime = date('Y-m-d H:i:s', time() + 86400 * 7);
        Db::execute("UPDATE admin_user SET last_login_time=NOW(), last_login_ip=?, login_count=login_count+1, admin_token=?, token_expire=? WHERE id=?", [$this->request->ip(), $token, $expireTime, $admin->id]);

        Session::set('admin_id', $admin->id);
        Session::set('admin_name', $admin->username);

        return $this->success([
            'id' => $admin->id,
            'username' => $admin->username,
            'realname' => $admin->realname,
            'role_id' => $admin->role_id,
            'token' => $token,
        ], '登录成功');
    }

    public function logout()
    {
        Session::delete('admin_id');
        Session::delete('admin_name');
        return $this->success(null, '退出登录成功');
    }
}
