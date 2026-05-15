<?php
namespace app\middleware;

use app\model\AdminUser;
use think\facade\Session;
use think\facade\Db;

class AdminAuth
{
    public function handle($request, \Closure $next)
    {
        $adminId = null;
        $token = $request->header('Admin-Token', '');

        if ($token) {
            $admin = Db::query("SELECT * FROM admin_user WHERE admin_token=? AND token_expire > NOW()", [$token]);
            if ($admin) {
                $adminId = $admin[0]['id'];
            }
        }

        if (!$adminId) {
            $adminId = Session::get('admin_id');
        }

        if (!$adminId) {
            if ($request->isAjax() || $request->header('Admin-Token')) {
                return json(['code' => 401, 'msg' => '请先登录']);
            }
            return redirect('/admin/login.html');
        }

        $admin = AdminUser::find($adminId);
        if (!$admin || $admin->status != 1) {
            Session::delete('admin_id');
            if ($request->isAjax() || $request->header('Admin-Token')) {
                return json(['code' => 401, 'msg' => '账号已被禁用']);
            }
            return redirect('/admin/login.html');
        }

        $request->admin = $admin;
        return $next($request);
    }
}
