<?php
namespace app\controller\admin;

use app\controller\BaseAdminController;
use app\model\Category;
use app\model\ExamAnswer;
use app\model\ExamRecord;
use app\model\Question;
use think\facade\Db;

class PracticeController extends BaseAdminController
{
    public function stats()
    {
        $questionTotal = Question::where('status', 1)->count();
        $categoryTotal = Category::where('status', 1)->where('type', 'question')->count();
        $practiceTotal = ExamRecord::where('source', 'practice')->count();
        $wrongTotal = Db::name('exam_answer')
            ->alias('ea')
            ->join('exam_record er', 'er.id = ea.record_id')
            ->where('er.source', 'practice')
            ->where('ea.is_correct', 0)
            ->count();
        $wrongQuestionTotal = Db::name('exam_answer')
            ->alias('ea')
            ->join('exam_record er', 'er.id = ea.record_id')
            ->where('er.source', 'practice')
            ->where('ea.is_correct', 0)
            ->group('ea.question_id')
            ->count();

        return $this->success([
            'question_total' => $questionTotal,
            'category_total' => $categoryTotal,
            'practice_total' => $practiceTotal,
            'wrong_total' => $wrongTotal,
            'wrong_question_total' => $wrongQuestionTotal,
        ]);
    }

    public function wrongBook()
    {
        $page = (int) $this->request->param('page', 1);
        $limit = (int) $this->request->param('limit', 15);
        $keyword = trim((string) $this->request->param('keyword', ''));
        $categoryId = (int) $this->request->param('category_id', 0);

        $query = Db::name('exam_answer')
            ->alias('ea')
            ->join('exam_record er', 'er.id = ea.record_id')
            ->join('question q', 'q.id = ea.question_id')
            ->leftJoin('category c', 'c.id = q.category_id')
            ->where('ea.is_correct', 0);

        if ($keyword !== '') {
            $query->whereLike('q.stem', '%' . $keyword . '%');
        }

        if ($categoryId > 0) {
            $query->where('q.category_id', $categoryId);
        }

        $countQuery = clone $query;
        $total = count($countQuery
            ->field('q.id')
            ->group('q.id')
            ->select()
            ->toArray());

        $list = $query
            ->field([
                'q.id' => 'question_id',
                'q.type',
                'q.stem',
                'q.use_count',
                'q.correct_count',
                'q.category_id',
                'c.name' => 'category_name',
                Db::raw('COUNT(ea.id) AS wrong_count'),
                Db::raw('COUNT(DISTINCT er.user_id) AS user_count'),
                Db::raw('MAX(ea.created_at) AS latest_wrong_at'),
            ])
            ->group('q.id, q.type, q.stem, q.use_count, q.correct_count, q.category_id, c.name')
            ->orderRaw('wrong_count DESC, latest_wrong_at DESC')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['type_name'] = $this->getTypeName($item['type']);
            $useCount = (int) ($item['use_count'] ?? 0);
            $correctCount = (int) ($item['correct_count'] ?? 0);
            $item['correct_rate'] = $useCount > 0 ? round($correctCount / $useCount * 100, 1) : 0;
        }

        return $this->page($list, $total, $page, $limit);
    }

    protected function getTypeName($type): string
    {
        $map = [1 => '单选题', 2 => '多选题', 3 => '填空题', 4 => '判断题', 5 => '问答题'];
        return $map[(int) $type] ?? '未知';
    }
}