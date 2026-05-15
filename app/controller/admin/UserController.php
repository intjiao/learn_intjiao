<?php
namespace app\controller\admin;

use app\controller\BaseAdminController;
use app\model\ExamRecord;
use app\model\User;
use app\model\UserGroup;
use think\facade\Db;

class UserController extends BaseAdminController
{
    public function list()
    {
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 15);
        $keyword = $this->request->param('keyword', '');
        $status = $this->request->param('status', '');
        $groupId = $this->request->param('user_group_id', '');

        $where = [];
        if ($keyword) {
            $where[] = ['username|realname|phone', 'like', "%{$keyword}%"];
        }
        if ($status !== '') {
            $where[] = ['status', '=', (int)$status];
        }
        if ($groupId) {
            $where[] = ['user_group_id', '=', (int)$groupId];
        }

        $query = User::where($where);
        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $groups = UserGroup::where('status', 1)->column('name', 'id');

        foreach ($list as &$item) {
            $item['group_name'] = $groups[$item['user_group_id']] ?? '未分组';
        }

        return $this->page($list, $total, $page, $limit);
    }

    public function read($id)
    {
        $user = User::with('group')->find($id);
        if (!$user) {
            return $this->error('用户不存在');
        }
        return $this->success($user);
    }

    public function studyRecords($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->error('用户不存在');
        }

        $page = (int) $this->request->param('page', 1);
        $limit = (int) $this->request->param('limit', 15);

        $query = Db::name('study_record')
            ->alias('sr')
            ->leftJoin('course c', 'c.id = sr.course_id')
            ->leftJoin('course_chapter cc', 'cc.id = sr.chapter_id')
            ->leftJoin('course_resource cr', 'cr.id = sr.resource_id')
            ->where('sr.user_id', (int) $id);

        $total = $query->count();
        $list = $query
            ->field([
                'sr.id',
                'sr.course_id',
                'sr.chapter_id',
                'sr.resource_id',
                'sr.watch_duration',
                'sr.total_duration',
                'sr.progress',
                'sr.is_complete',
                'sr.last_study_time',
                'sr.created_at',
                'c.title' => 'course_title',
                'cc.title' => 'chapter_title',
                'cr.title' => 'resource_title',
                'cr.type' => 'resource_type',
            ])
            ->order('sr.last_study_time', 'desc')
            ->order('sr.id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['watch_duration_text'] = $this->formatStudyDuration((int) ($item['watch_duration'] ?? 0));
            $item['total_duration_text'] = $this->formatStudyDuration((int) ($item['total_duration'] ?? 0));
            $item['progress_text'] = ((int) ($item['progress'] ?? 0)) . '%';
            $item['is_complete_text'] = (int) ($item['is_complete'] ?? 0) === 1 ? '已完成' : '学习中';
            $item['resource_type_text'] = $this->formatResourceType($item['resource_type'] ?? '');
        }
        unset($item);

        return $this->page($list, $total, $page, $limit);
    }

    public function examRecords($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->error('用户不存在');
        }

        $page = (int) $this->request->param('page', 1);
        $limit = (int) $this->request->param('limit', 15);

        $query = ExamRecord::where('user_id', (int) $id);
        $total = $query->count();
        $list = $query
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $status = (int) ($item['status'] ?? 0);
            $item['status_text'] = [0 => '已取消', 1 => '进行中', 2 => '已完成'][$status] ?? '未知';
            $item['pass_status_text'] = $status !== 2
                ? '未完成'
                : ((int) ($item['pass_status'] ?? 0) === 1 ? '及格' : '不及格');
            $item['duration_text'] = $this->formatStudyDuration((int) ($item['duration'] ?? 0));
            $item['source_text'] = $this->formatExamSource($item['source'] ?? '');
        }
        unset($item);

        return $this->page($list, $total, $page, $limit);
    }

    public function groups()
    {
        $groups = UserGroup::where('status', 1)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        return $this->success($this->flattenGroups($groups));
    }

    protected function flattenGroups(array $groups, int $parentId = 0, int $level = 0): array
    {
        $result = [];

        foreach ($groups as $group) {
            if ((int) ($group['parent_id'] ?? 0) !== $parentId) {
                continue;
            }

            $group['display_name'] = str_repeat('　', $level) . $group['name'];
            $result[] = $group;
            $result = array_merge($result, $this->flattenGroups($groups, (int) $group['id'], $level + 1));
        }

        return $result;
    }

    protected function formatStudyDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0秒';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;
        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours . '小时';
        }
        if ($minutes > 0) {
            $parts[] = $minutes . '分';
        }
        if ($secs > 0 || empty($parts)) {
            $parts[] = $secs . '秒';
        }

        return implode('', $parts);
    }

    protected function formatResourceType(string $type): string
    {
        $map = [
            'video' => '视频',
            'audio' => '音频',
            'document' => '文档',
            'pdf' => 'PDF',
        ];

        return $map[$type] ?? ($type !== '' ? $type : '未知');
    }

    protected function formatExamSource(string $source): string
    {
        $map = [
            'practice' => '刷题练习',
            'exam' => '在线考试',
            'task' => '培训任务',
        ];

        return $map[$source] ?? ($source !== '' ? $source : '考试');
    }

    public function save()
    {
        $data = $this->request->post();
        $data['username'] = trim((string) ($data['username'] ?? ''));
        $data['realname'] = trim((string) ($data['realname'] ?? ''));
        $data['phone'] = trim((string) ($data['phone'] ?? ''));
        $data['company'] = trim((string) ($data['company'] ?? ''));
        $data['department'] = trim((string) ($data['department'] ?? ''));
        $data['gender'] = isset($data['gender']) ? (int) $data['gender'] : 0;
        $data['status'] = isset($data['status']) ? (int) $data['status'] : 1;
        $data['user_group_id'] = empty($data['user_group_id']) ? null : (int) $data['user_group_id'];

        if ($data['username'] === '' || $data['realname'] === '') {
            return $this->error('用户名和真实姓名不能为空');
        }
        if (User::where('username', $data['username'])->find()) {
            return $this->error('用户名已存在');
        }
        if ($data['phone'] !== '') {
            $exists = User::where('phone', $data['phone'])->find();
            if ($exists) {
                return $this->error('手机号已被使用');
            }
        } else {
            $data['phone'] = null;
        }

        $plainPassword = trim((string) ($data['password'] ?? ''));
        $data['password'] = password_hash($plainPassword !== '' ? $plainPassword : '123456', PASSWORD_DEFAULT);

        $user = User::create($data);
        return $this->success($user, '添加成功');
    }

    public function update($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->error('用户不存在');
        }

        $data = $this->request->put();
        if (isset($data['username'])) {
            $data['username'] = trim((string) $data['username']);
            if ($data['username'] === '') {
                unset($data['username']);
            } elseif ($data['username'] !== $user->username) {
                $exists = User::where('username', $data['username'])->where('id', '<>', $id)->find();
                if ($exists) {
                    return $this->error('用户名已存在');
                }
            }
        }
        if (isset($data['realname'])) {
            $data['realname'] = trim((string) $data['realname']);
            if ($data['realname'] === '') {
                return $this->error('真实姓名不能为空');
            }
        }
        if (isset($data['company'])) {
            $data['company'] = trim((string) $data['company']);
        }
        if (isset($data['department'])) {
            $data['department'] = trim((string) $data['department']);
        }
        if (isset($data['gender'])) {
            $data['gender'] = (int) $data['gender'];
        }
        if (isset($data['status'])) {
            $data['status'] = (int) $data['status'];
        }
        if (array_key_exists('user_group_id', $data)) {
            $data['user_group_id'] = empty($data['user_group_id']) ? null : (int) $data['user_group_id'];
        }
        if (isset($data['phone'])) {
            $data['phone'] = trim((string) $data['phone']);
            if ($data['phone'] === '') {
                $data['phone'] = null;
            } elseif ($data['phone'] !== $user->phone) {
                $exists = User::where('phone', $data['phone'])->where('id', '<>', $id)->find();
                if ($exists) {
                    return $this->error('手机号已被使用');
                }
            }
        }
        if (array_key_exists('password', $data)) {
            $plainPassword = trim((string) $data['password']);
            if ($plainPassword === '') {
                unset($data['password']);
            } else {
                $data['password'] = password_hash($plainPassword, PASSWORD_DEFAULT);
            }
        }

        $user->save($data);
        return $this->success(null, '更新成功');
    }

    public function delete($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->error('用户不存在');
        }

        $hasExam = Db::name('exam_record')->where('user_id', $id)->count();
        if ($hasExam > 0) {
            $user->status = 0;
            $user->save();
            return $this->success(null, '已禁用（存在考试记录，无法删除）');
        }

        $user->delete();
        return $this->success(null, '删除成功');
    }

    public function import()
    {
        $file = $this->request->file('file');
        if (!$file) {
            return $this->error('请上传文件');
        }

        $uploadPath = root_path() . 'runtime/uploads/import/' . date('Ymd');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $info = $file->move($uploadPath);
        if (!$info) {
            return $this->error('文件上传失败：' . $file->getError());
        }

        $filePath = $uploadPath . '/' . $info->getSaveName();

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $success = 0;
            $errors = [];

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $lineNum = $i + 1;

                if (empty(trim($row[0] ?? ''))) {
                    continue;
                }

                try {
                    $exists = User::where('username', trim($row[0]))->find();
                    if ($exists) {
                        $errors[] = "第{$lineNum}行：用户名 {$row[0]} 已存在";
                        continue;
                    }

                    User::create([
                        'username' => trim($row[0]),
                        'password' => password_hash(trim($row[1] ?? '123456'), PASSWORD_DEFAULT),
                        'realname' => trim($row[2] ?? ''),
                        'phone' => trim($row[3] ?? ''),
                        'company' => trim($row[4] ?? ''),
                        'department' => trim($row[5] ?? ''),
                        'status' => 1,
                    ]);
                    $success++;
                } catch (\Exception $e) {
                    $errors[] = "第{$lineNum}行：{$e->getMessage()}";
                }
            }

            @unlink($filePath);

            return $this->success([
                'success' => $success,
                'total' => count($rows) - 1,
                'errors' => array_slice($errors, 0, 20),
            ], '导入完成');
        } catch (\Exception $e) {
            @unlink($filePath);
            return $this->error('文件处理失败：' . $e->getMessage());
        }
    }

    public function export()
    {
        $keyword = $this->request->param('keyword', '');
        $status = $this->request->param('status', '');
        $groupId = $this->request->param('user_group_id', '');

        $where = [];
        if ($keyword) {
            $where[] = ['username|realname|phone', 'like', "%{$keyword}%"];
        }
        if ($status !== '') {
            $where[] = ['status', '=', (int)$status];
        }
        if ($groupId) {
            $where[] = ['user_group_id', '=', (int)$groupId];
        }

        $list = User::where($where)->order('id', 'desc')->select()->toArray();
        $groups = UserGroup::where('status', 1)->column('name', 'id');

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('学员列表');

        $headers = ['用户名', '真实姓名', '手机号', '性别', '公司', '部门', '用户组', '状态', '注册时间'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        foreach ($list as $row => $item) {
            $sheet->setCellValueByColumnAndRow(1, $row + 2, $item['username']);
            $sheet->setCellValueByColumnAndRow(2, $row + 2, $item['realname']);
            $sheet->setCellValueByColumnAndRow(3, $row + 2, $item['phone']);
            $sheet->setCellValueByColumnAndRow(4, $row + 2, $item['gender'] == 1 ? '男' : ($item['gender'] == 2 ? '女' : '未知'));
            $sheet->setCellValueByColumnAndRow(5, $row + 2, $item['company']);
            $sheet->setCellValueByColumnAndRow(6, $row + 2, $item['department']);
            $sheet->setCellValueByColumnAndRow(7, $row + 2, $groups[$item['user_group_id']] ?? '未分组');
            $sheet->setCellValueByColumnAndRow(8, $row + 2, $item['status'] == 1 ? '正常' : '禁用');
            $sheet->setCellValueByColumnAndRow(9, $row + 2, $item['created_at']);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="学员列表_' . date('Ymd') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    public function batchDelete()
    {
        $ids = $this->request->post('ids', '');
        if (empty($ids)) {
            return $this->error('请选择要删除的数据');
        }

        $idArr = array_filter(array_map('intval', explode(',', $ids)));
        if (empty($idArr)) {
            return $this->error('参数错误');
        }

        $deleted = 0;
        foreach ($idArr as $id) {
            $user = User::find($id);
            if ($user) {
                $hasExam = Db::name('exam_record')->where('user_id', $id)->count();
                if ($hasExam > 0) {
                    $user->status = 0;
                    $user->save();
                } else {
                    $user->delete();
                }
                $deleted++;
            }
        }

        return $this->success(null, '已处理 ' . $deleted . ' 条数据');
    }
}
