<?php
namespace app\controller\api;

use app\controller\BaseApiController;
use app\model\Certificate;
use app\model\CourseResource;
use app\model\ExamRecord;
use app\model\StudyRecord;
use app\model\User;
use think\facade\Cache;

class UserController extends BaseApiController
{
    public function info()
    {
        $userId = $this->request->userId;
        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在');
        }

        $courseIds = StudyRecord::where('user_id', $userId)
            ->where('course_id', '>', 0)
            ->distinct(true)
            ->column('course_id');
        $courseCount = count(array_filter(array_unique($courseIds)));

        $examCount = ExamRecord::where('user_id', $userId)
            ->where('status', 2)
            ->count();

        $certCount = Certificate::where('user_id', $userId)
            ->where('status', 1)
            ->count();

        $resourceIds = StudyRecord::where('user_id', $userId)
            ->where('resource_id', '>', 0)
            ->distinct(true)
            ->column('resource_id');
        $completedResources = count(array_filter(array_unique($resourceIds)));

        $totalResources = 0;
        if (!empty($courseIds)) {
            $totalResources = CourseResource::whereIn('course_id', $courseIds)->count();
        }

        $learningProgress = $totalResources > 0
            ? (int) round(min(100, ($completedResources / $totalResources) * 100))
            : 0;

        return $this->success([
            'id' => $user->id,
            'username' => $user->username,
            'realname' => $user->realname,
            'avatar' => $user->avatar,
            'phone' => $user->phone,
            'email' => $user->email,
            'company' => $user->company,
            'department' => $user->department,
            'group_name' => '',
            'course_count' => $courseCount,
            'exam_count' => (int) $examCount,
            'cert_count' => (int) $certCount,
            'learning_progress' => $learningProgress,
        ]);
    }

    public function updateInfo()
    {
        $user = User::find($this->request->userId);
        if (!$user) {
            return $this->error('用户不存在');
        }

        $data = $this->request->put();
        unset($data['username'], $data['password']);

        if (isset($data['phone']) && $data['phone'] !== $user->phone) {
            $exists = User::where('phone', $data['phone'])->where('id', '<>', $user->id)->find();
            if ($exists) {
                return $this->error('手机号已被使用');
            }
        }

        foreach ($data as $k => $v) {
            if ($v !== '' && in_array($k, ['realname', 'phone', 'email', 'company', 'department'])) {
                $user->$k = $v;
            }
        }
        $user->save();
        return $this->success(null, '更新成功');
    }

    public function updatePassword()
    {
        $oldPassword = $this->request->param('old_password', '');
        $newPassword = $this->request->param('new_password', '');

        if (empty($oldPassword) || empty($newPassword)) {
            return $this->error('密码不能为空');
        }

        $user = User::find($this->request->userId);
        if (!password_verify($oldPassword, $user->password)) {
            return $this->error('原密码错误');
        }

        $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
        $user->save();
        return $this->success(null, '密码修改成功');
    }

    public function updateAvatar()
    {
        $user = User::find($this->request->userId);
        if (!$user) {
            return $this->error('用户不存在');
        }

        $avatar = $this->request->param('avatar', '');
        $user->avatar = $avatar;
        $user->save();

        return $this->success(['avatar' => $avatar], '头像更新成功');
    }
}
