<?php
namespace app\controller\admin;

use app\controller\BaseAdminController;
use app\model\TrainingTask;
use app\model\TrainingStage;
use app\model\TrainingEnroll;

class TaskController extends BaseAdminController
{
    public function list()
    {
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 15);
        $keyword = $this->request->param('keyword', '');
        $status = $this->request->param('status', '');

        $where = [];
        if ($keyword) {
            $where[] = ['title', 'like', "%{$keyword}%"];
        }
        if ($status !== '') {
            $where[] = ['status', '=', (int)$status];
        }

        $query = TrainingTask::where($where);
        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['stages_count'] = TrainingStage::where('task_id', $item['id'])->count();
            $item['enroll_count'] = TrainingEnroll::where('task_id', $item['id'])->count();
            $item['complete_count'] = TrainingEnroll::where('task_id', $item['id'])->where('status', 2)->count();
            $item['start_date'] = $item['valid_start'] ?? null;
            $item['end_date'] = $item['valid_end'] ?? null;
        }

        return $this->page($list, $total, $page, $limit);
    }

    public function read($id)
    {
        $task = TrainingTask::with('stages')->find($id);
        if (!$task) {
            return $this->error('培训任务不存在');
        }

        $data = $task->toArray();
        $data['start_date'] = $data['valid_start'] ?? null;
        $data['end_date'] = $data['valid_end'] ?? null;
        return $this->success($data);
    }

    public function save()
    {
        $data = $this->request->post();

        if (!isset($data['valid_start']) && isset($data['start_date'])) {
            $data['valid_start'] = $data['start_date'];
        }
        if (!isset($data['valid_end']) && isset($data['end_date'])) {
            $data['valid_end'] = $data['end_date'];
        }

        if (empty($data['title'])) {
            return $this->error('任务名称不能为空');
        }

        $task = TrainingTask::create([
            'title' => $data['title'],
            'cover' => $data['cover'] ?? '',
            'description' => $data['description'] ?? '',
            'type' => $data['type'] ?? 1,
            'stages' => $data['stages'] ?? null,
            'target_type' => $data['target_type'] ?? 1,
            'target_value' => $data['target_value'] ?? null,
            'valid_start' => $data['valid_start'] ?? null,
            'valid_end' => $data['valid_end'] ?? null,
            'certificate_template' => $data['certificate_template'] ?? '',
            'certificate_expire_years' => $data['certificate_expire_years'] ?? null,
            'status' => 1,
            'sort' => $data['sort'] ?? 0,
        ]);

        return $this->success($task, '添加成功');
    }

    public function update($id)
    {
        $task = TrainingTask::find($id);
        if (!$task) {
            return $this->error('培训任务不存在');
        }

        $data = $this->request->put();
        if (!isset($data['valid_start']) && isset($data['start_date'])) {
            $data['valid_start'] = $data['start_date'];
        }
        if (!isset($data['valid_end']) && isset($data['end_date'])) {
            $data['valid_end'] = $data['end_date'];
        }
        $task->save($data);
        return $this->success(null, '更新成功');
    }

    public function delete($id)
    {
        $task = TrainingTask::find($id);
        if (!$task) {
            return $this->error('培训任务不存在');
        }

        TrainingStage::where('task_id', $id)->delete();
        $task->delete();
        return $this->success(null, '删除成功');
    }

    public function export()
    {
        $keyword = $this->request->param('keyword', '');
        $status = $this->request->param('status', '');

        $where = [];
        if ($keyword) {
            $where[] = ['title', 'like', "%{$keyword}%"];
        }
        if ($status !== '') {
            $where[] = ['status', '=', (int)$status];
        }

        $list = TrainingTask::where($where)->order('id', 'desc')->select()->toArray();

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="tasks_' . date('Ymd_His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['ID', '任务名称', '类型', '阶段数', '报名人数', '完成人数', '开始时间', '结束时间', '状态', '创建时间']);

        foreach ($list as $item) {
            fputcsv($fp, [
                $item['id'],
                $item['title'],
                (int)($item['type'] ?? 1) === 2 ? '混合培训' : '在线学习',
                TrainingStage::where('task_id', $item['id'])->count(),
                TrainingEnroll::where('task_id', $item['id'])->count(),
                TrainingEnroll::where('task_id', $item['id'])->where('status', 2)->count(),
                $item['valid_start'] ?? '',
                $item['valid_end'] ?? '',
                ((int)($item['status'] ?? 0) === 2 ? '已结束' : ((int)($item['status'] ?? 0) === 1 ? '进行中' : '草稿')),
                $item['created_at'] ?? '',
            ]);
        }

        fclose($fp);
        exit;
    }

    public function stages($id)
    {
        $stages = TrainingStage::where('task_id', $id)
            ->with('paper')
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        foreach ($stages as &$item) {
            $courseIds = $item['course_ids'] ?? [];
            if (is_string($courseIds) && $courseIds !== '') {
                $decoded = json_decode($courseIds, true);
                $courseIds = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($courseIds)) {
                $courseIds = [];
            }

            $item['title'] = $item['name'] ?? '';
            $item['stage_type'] = (int)($item['type'] ?? 1) === 2 ? 'exam' : 'course';
            $item['target_id'] = (int)($item['type'] ?? 1) === 2 ? ($item['exam_paper_id'] ?? '') : ($courseIds[0] ?? '');
            $item['status'] = 1;
        }
        unset($item);

        return $this->success($stages);
    }

    public function saveStage($id)
    {
        $task = TrainingTask::find($id);
        if (!$task) {
            return $this->error('培训任务不存在');
        }

        $data = $this->request->post();
        $title = trim((string)($data['name'] ?? $data['title'] ?? ''));
        if ($title === '') {
            return $this->error('阶段名称不能为空');
        }

        $stageType = $data['stage_type'] ?? null;
        $type = isset($data['type']) ? (int)$data['type'] : ($stageType === 'exam' ? 2 : 1);
        $targetId = trim((string)($data['target_id'] ?? ''));

        $payload = [
            'name' => $title,
            'type' => $type,
            'sort' => (int)($data['sort'] ?? 0),
            'required_score' => (float)($data['required_score'] ?? 60),
            'required_progress' => (int)($data['required_progress'] ?? 100),
        ];

        if ($type === 2) {
            $payload['exam_paper_id'] = $data['exam_paper_id'] ?? ($targetId !== '' ? (int)$targetId : null);
            $payload['course_ids'] = null;
        } else {
            $courseIds = $data['course_ids'] ?? null;
            if (is_array($courseIds)) {
                $courseIds = array_values(array_filter(array_map('intval', $courseIds)));
                $payload['course_ids'] = empty($courseIds) ? null : json_encode($courseIds, JSON_UNESCAPED_UNICODE);
            } elseif (is_string($courseIds) && $courseIds !== '') {
                $payload['course_ids'] = $courseIds;
            } elseif ($targetId !== '') {
                $payload['course_ids'] = json_encode([(int)$targetId], JSON_UNESCAPED_UNICODE);
            } else {
                $payload['course_ids'] = null;
            }
            $payload['exam_paper_id'] = null;
        }

        if (!empty($data['id'])) {
            $stage = TrainingStage::where('task_id', $id)->find($data['id']);
            if (!$stage) {
                return $this->error('阶段不存在');
            }
            $stage->save($payload);
        } else {
            $stage = TrainingStage::create(array_merge($payload, [
                'task_id' => $id,
            ]));
        }

        return $this->success($stage, '保存成功');
    }

    public function deleteStage($id)
    {
        $stage = TrainingStage::find($id);
        if (!$stage) {
            return $this->error('阶段不存在');
        }
        $stage->delete();
        return $this->success(null, '删除成功');
    }
}
