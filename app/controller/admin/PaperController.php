<?php
namespace app\controller\admin;

use app\controller\BaseAdminController;
use app\model\Paper;
use app\model\Question;
use app\model\PaperQuestion;
use app\model\Category;
use app\service\PaperGenerateService;

class PaperController extends BaseAdminController
{
    protected $paperService;

    public function __construct()
    {
        parent::__construct(app());
        $this->paperService = new PaperGenerateService();
    }

    public function list()
    {
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 15);
        $keyword = $this->request->param('keyword', '');
        $type = $this->request->param('type', '');
        $status = $this->request->param('status', '');

        $where = [];
        if ($keyword) {
            $where[] = ['title', 'like', "%{$keyword}%"];
        }
        if ($type !== '') {
            $where[] = ['type', '=', (int)$type];
        }
        if ($status !== '') {
            $where[] = ['status', '=', (int)$status];
        }

        $query = Paper::where($where);
        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $categories = Category::where('type', 'question')->column('name', 'id');

        foreach ($list as &$item) {
            $item['category_name'] = $categories[$item['category_id']] ?? '未分类';
        }

        return $this->page($list, $total, $page, $limit);
    }

    public function read($id)
    {
        $paper = Paper::find($id);
        if (!$paper) {
            return $this->error('试卷不存在');
        }

        $questions = [];
        if ($paper->type == 1) {
            $questions = $this->paperService->getPaperQuestions($id);
        }

        return $this->success([
            'paper' => $paper,
            'questions' => $questions,
        ]);
    }

    public function save()
    {
        $data = $this->request->post();

        if (empty($data['title'])) {
            return $this->error('试卷标题不能为空');
        }

        $paper = Paper::create([
            'title' => $data['title'],
            'category_id' => $data['category_id'] ?? 0,
            'type' => $data['type'] ?? 1,
            'total_score' => $data['total_score'] ?? 100,
            'pass_score' => $data['pass_score'] ?? 60,
            'duration' => $data['duration'] ?? 60,
            'question_rules' => $data['question_rules'] ?? null,
            'valid_start' => $data['valid_start'] ?? null,
            'valid_end' => $data['valid_end'] ?? null,
            'allow_view_answer' => $data['allow_view_answer'] ?? 1,
            'allow_retry' => $data['allow_retry'] ?? 0,
            'max_retry' => $data['max_retry'] ?? 0,
            'show_score' => $data['show_score'] ?? 1,
            'status' => 1,
        ]);

        if ($paper->type == 1 && !empty($data['question_ids'])) {
            $questionIds = array_filter(explode(',', $data['question_ids']));
            $this->paperService->generateFixedPaper($paper->id, $questionIds);
        }

        return $this->success($paper, '添加成功');
    }

    public function update($id)
    {
        $paper = Paper::find($id);
        if (!$paper) {
            return $this->error('试卷不存在');
        }

        $data = $this->request->put();

        if (isset($data['question_ids']) && $paper->type == 1) {
            $questionIds = array_filter(explode(',', $data['question_ids']));
            $this->paperService->generateFixedPaper($id, $questionIds);
            unset($data['question_ids']);
        }

        $paper->save($data);
        return $this->success(null, '更新成功');
    }

    public function delete($id)
    {
        $paper = Paper::find($id);
        if (!$paper) {
            return $this->error('试卷不存在');
        }

        PaperQuestion::where('paper_id', $id)->delete();
        $paper->delete();
        return $this->success(null, '删除成功');
    }

    public function publish($id)
    {
        $paper = Paper::find($id);
        if (!$paper) {
            return $this->error('试卷不存在');
        }

        if ($paper->total_count == 0) {
            return $this->error('试卷没有题目，无法发布');
        }

        $paper->status = 2;
        $paper->save();
        return $this->success(null, '发布成功');
    }

    public function generate($id)
    {
        $paper = Paper::find($id);
        if (!$paper) {
            return $this->error('试卷不存在');
        }

        if ($paper->type != 2) {
            return $this->error('只有随机试卷才能生成');
        }

        try {
            $questionIds = $this->paperService->generateRandomPaper($id);
            return $this->success(['count' => count($questionIds)], '生成成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
