<?php
namespace app\controller\api;

use app\controller\BaseApiController;
use app\model\Paper;
use app\model\ExamRecord;
use app\service\ExamService;
use app\service\PaperGenerateService;
use think\facade\Cache;

class ExamController extends BaseApiController
{
    protected $examService;
    protected $paperService;

    public function __construct()
    {
        parent::__construct(app());
        $this->examService = new ExamService();
        $this->paperService = new PaperGenerateService();
    }

    public function papers()
    {
        $userId = $this->request->userId;
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 10);
        $keyword = trim((string)$this->request->param('keyword', ''));

        $query = Paper::where('status', 2)
            ->where(function ($query) {
                $now = date('Y-m-d H:i:s');
                $query->whereNull('valid_start')->whereOr('valid_start', '<=', $now);
            })
            ->where(function ($query) {
                $now = date('Y-m-d H:i:s');
                $query->whereNull('valid_end')->whereOr('valid_end', '>=', $now);
            });

        if ($keyword !== '') {
            $query->where('title', 'like', "%{$keyword}%");
        }

        $total = $query->count();
        $papers = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $paperIds = array_column($papers, 'id');
        $latestRecords = [];
        if (!empty($paperIds)) {
            $records = ExamRecord::with('paper')
                ->where('user_id', $userId)
                ->whereIn('paper_id', $paperIds)
                ->order('id', 'desc')
                ->select()
                ->toArray();

            foreach ($records as $record) {
                $paperId = $record['paper_id'] ?? 0;
                if ($paperId && !isset($latestRecords[$paperId])) {
                    $latestRecords[$paperId] = $record;
                }
            }
        }

        $list = [];
        foreach ($papers as $paper) {
            $paperId = (int)($paper['id'] ?? 0);
            $record = $latestRecords[$paperId] ?? null;
            $list[] = [
                'id' => $paperId,
                'paper_id' => $paperId,
                'title' => $paper['title'] ?? '',
                'description' => $paper['description'] ?? '',
                'duration' => (int)($paper['duration'] ?? 0),
                'total_score' => (int)($paper['total_score'] ?? 0),
                'pass_score' => (int)($paper['pass_score'] ?? 0),
                'total_count' => (int)($paper['total_count'] ?? 0),
                'allow_retry' => (int)($paper['allow_retry'] ?? 0),
                'max_retry' => (int)($paper['max_retry'] ?? 0),
                'valid_start' => $paper['valid_start'] ?? null,
                'valid_end' => $paper['valid_end'] ?? null,
                'latest_record' => $record,
                'can_resume' => $record && (int)($record['status'] ?? 0) === 1,
                'is_finished' => $record && (int)($record['status'] ?? 0) === 2,
            ];
        }

        return $this->page($list, $total, $page, $limit);
    }

    public function list()
    {
        $userId = $this->request->userId;
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 10);
        $type = $this->request->param('type', 'my');

        $query = ExamRecord::where('user_id', $userId);

        if ($type === 'doing') {
            $query->where('status', 1);
        }

        $total = $query->count();
        $list = $query->with('paper')
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return $this->page($list, $total, $page, $limit);
    }

    public function detail($id)
    {
        $paper = Paper::find($id);
        if (!$paper || $paper->status != 2) {
            return $this->error('试卷不存在或未发布');
        }

        if ($paper->valid_start && $paper->valid_start > date('Y-m-d H:i:s')) {
            return $this->error('考试尚未开始');
        }
        if ($paper->valid_end && $paper->valid_end < date('Y-m-d H:i:s')) {
            return $this->error('考试已结束');
        }

        return $this->success([
            'id' => $paper->id,
            'title' => $paper->title,
            'type' => $paper->type,
            'total_score' => $paper->total_score,
            'pass_score' => $paper->pass_score,
            'duration' => $paper->duration,
            'total_count' => $paper->total_count,
        ]);
    }

    public function start($id)
    {
        $userId = $this->request->userId;
        $paper = Paper::find($id);

        if (!$paper || $paper->status != 2) {
            return $this->error('试卷不存在或未发布');
        }

        $existingRecord = ExamRecord::where('user_id', $userId)
            ->where('paper_id', $id)
            ->where('status', 1)
            ->find();

        if ($existingRecord) {
            $remainingTime = $paper->duration * 60 - (time() - strtotime($existingRecord->start_time));
            if ($remainingTime <= 0) {
                return $this->error('考试时间已结束，请重新开始');
            }

            $questions = $this->paperService->getPaperQuestions($id);
            return $this->success([
                'record_id' => $existingRecord->id,
                'questions' => $questions,
                'remaining_time' => $remainingTime,
                'start_time' => $existingRecord->start_time,
            ]);
        }

        if ($paper->allow_retry == 0) {
            $completedCount = ExamRecord::where('user_id', $userId)
                ->where('paper_id', $id)
                ->where('status', 2)
                ->count();
            if ($completedCount > 0) {
                return $this->error('该试卷不支持重新考试');
            }
        } elseif ($paper->max_retry > 0) {
            $completedCount = ExamRecord::where('user_id', $userId)
                ->where('paper_id', $id)
                ->where('status', 2)
                ->count();
            if ($completedCount >= $paper->max_retry) {
                return $this->error("已达到最大考试次数({$paper->max_retry}次)");
            }
        }

        $record = $this->examService->startExam($userId, $id);
        $questions = $this->paperService->getPaperQuestions($id);

        foreach ($questions as &$q) {
            unset($q['analysis']);
        }

        return $this->success([
            'record_id' => $record->id,
            'questions' => $questions,
            'remaining_time' => $paper->duration * 60,
            'start_time' => $record->start_time,
        ]);
    }

    public function submit($id)
    {
        $userId = $this->request->userId;
        $recordId = (int)$this->request->param('record_id', 0);
        $answersRaw = $this->request->param('answers', '');
        $answers = is_string($answersRaw) ? json_decode($answersRaw, true) : $answersRaw;
        if (!is_array($answers)) {
            $answers = [];
        }

        if ($recordId <= 0) {
            return $this->error('考试记录ID无效');
        }

        $record = ExamRecord::find($recordId);
        if (!$record || $record->user_id != $userId) {
            return $this->error('考试记录不存在');
        }

        if ($record->status != 1) {
            return $this->error('考试已提交');
        }

        try {
            $result = $this->examService->submitExam($recordId, $answers);
            return $this->success($result, '提交成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function record($id)
    {
        $userId = $this->request->userId;
        $record = ExamRecord::find($id);

        if (!$record || $record->user_id != $userId) {
            return $this->error('考试记录不存在');
        }

        $showAnswer = ($record->status == 2);
        $detail = $this->examService->getExamDetail($id, $showAnswer);

        return $this->success($detail);
    }
}
