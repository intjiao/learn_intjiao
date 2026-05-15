<?php
namespace app\controller\api;

use app\controller\BaseApiController;
use app\model\TrainingTask;
use app\model\TrainingStage;
use app\model\TrainingEnroll;
use app\model\StudyRecord;
use app\model\ExamRecord;

class TaskController extends BaseApiController
{
    public function list()
    {
        $userId = $this->request->userId;
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 10);
        $type = $this->request->param('type', '');

        $where = [['status', 'in', [1, 2]]];
        if ($type === 'enrolled') {
            $enrolledIds = TrainingEnroll::where('user_id', $userId)
                ->where('status', '<>', 0)
                ->column('task_id');
            $where[] = ['id', 'in', $enrolledIds];
        }

        $query = TrainingTask::where($where);
        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $enrollments = TrainingEnroll::where('user_id', $userId)
            ->where('status', '<>', 0)
            ->column('status,progress,is_complete', 'task_id');

        foreach ($list as &$item) {
            $enroll = $enrollments[$item['id']] ?? null;
            $item['is_enrolled'] = $enroll !== null;
            $item['progress'] = $enroll['progress'] ?? 0;
            $item['is_complete'] = $enroll['is_complete'] ?? false;
            $item['enroll_status'] = $enroll['status'] ?? 0;
        }

        return $this->page($list, $total, $page, $limit);
    }

    public function detail($id)
    {
        $userId = $this->request->userId;
        $task = TrainingTask::with('stages')->find($id);

        if (!$task) {
            return $this->error('培训任务不存在');
        }

        $enroll = TrainingEnroll::where('user_id', $userId)
            ->where('task_id', $id)
            ->find();

        $task->is_enrolled = $enroll !== null;
        $task->enroll_status = $enroll['status'] ?? 0;
        $task->progress = $enroll['progress'] ?? 0;

        $stagesProgress = $enroll && $enroll->stage_progress
            ? json_decode($enroll->stage_progress, true)
            : [];

        foreach ($task->stages as &$stage) {
            $stage->progress = $stagesProgress[$stage->id] ?? 0;
            $stage->is_complete = ($stagesProgress[$stage->id] ?? 0) >= $stage->required_progress;
        }

        return $this->success($task);
    }

    public function enroll($id)
    {
        $userId = $this->request->userId;
        $task = TrainingTask::find($id);

        if (!$task) {
            return $this->error('培训任务不存在');
        }

        if ($task->status == 0) {
            return $this->error('培训任务未发布');
        }

        $exists = TrainingEnroll::where('user_id', $userId)
            ->where('task_id', $id)
            ->find();

        if ($exists) {
            return $this->error('您已报名该培训');
        }

        TrainingEnroll::create([
            'task_id' => $id,
            'user_id' => $userId,
            'status' => 1,
            'enroll_time' => date('Y-m-d H:i:s'),
        ]);

        TrainingTask::where('id', $id)->inc('enroll_count')->update();

        return $this->success(null, '报名成功');
    }

    public function progress($id)
    {
        $userId = $this->request->userId;
        $enroll = TrainingEnroll::where('user_id', $userId)
            ->where('task_id', $id)
            ->find();

        if (!$enroll) {
            return $this->error('未报名该培训');
        }

        $task = TrainingTask::with('stages')->find($id);
        $stagesProgress = $enroll->stage_progress
            ? json_decode($enroll->stage_progress, true)
            : [];

        $result = [
            'total_progress' => $enroll->progress,
            'is_complete' => $enroll->is_complete == 1,
            'stages' => [],
        ];

        foreach ($task->stages as $stage) {
            $examRecord = ExamRecord::where('user_id', $userId)
                ->where('stage_id', $stage->id)
                ->where('status', 2)
                ->order('score', 'desc')
                ->find();

            $result['stages'][] = [
                'id' => $stage->id,
                'name' => $stage->name,
                'type' => $stage->type,
                'progress' => $stagesProgress[$stage->id] ?? 0,
                'is_complete' => ($stagesProgress[$stage->id] ?? 0) >= $stage->required_progress,
                'best_score' => $examRecord ? $examRecord->score : null,
                'pass_status' => $examRecord ? ($examRecord->pass_status == 1 ? 'pass' : 'fail') : null,
            ];
        }

        return $this->success($result);
    }
}
