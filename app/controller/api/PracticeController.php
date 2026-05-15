<?php
namespace app\controller\api;

use app\controller\BaseApiController;
use app\model\Category;
use app\model\ExamAnswer;
use app\model\ExamRecord;
use app\model\Question;
use think\facade\Db;

class PracticeController extends BaseApiController
{
    /**
     * 获取练习统计信息
     */
    public function stats()
    {
        $userId = (int) $this->request->userId;
        $answerQuery = Db::name('exam_answer')
            ->alias('ea')
            ->join('exam_record er', 'er.id = ea.record_id')
            ->where('er.user_id', $userId)
            ->where('er.source', 'practice');

        $questionIds = array_values(array_unique(array_map('intval', (clone $answerQuery)->column('ea.question_id'))));
        $correctQuestionIds = array_values(array_unique(array_map('intval', (clone $answerQuery)
            ->where('ea.is_correct', 1)
            ->column('ea.question_id'))));
        $todayQuestionIds = array_values(array_unique(array_map('intval', (clone $answerQuery)
            ->whereTime('er.submit_time', 'today')
            ->column('ea.question_id'))));

        $totalDone = count($questionIds);
        $correctDone = count($correctQuestionIds);
        $todayDone = count($todayQuestionIds);

        $practiceTimes = ExamRecord::where('user_id', $userId)
            ->where('source', 'practice')
            ->where('status', 2)
            ->count();

        $wrongTotal = (clone $answerQuery)
            ->where('ea.is_correct', 0)
            ->count();
        $wrongQuestionIds = (clone $answerQuery)
            ->where('ea.is_correct', 0)
            ->distinct(true)
            ->column('ea.question_id');

        $practiceDates = ExamRecord::where('user_id', $userId)
            ->where('source', 'practice')
            ->where('status', 2)
            ->whereNotNull('submit_time')
            ->order('submit_time', 'desc')
            ->column('submit_time');

        $dateMap = [];
        foreach ($practiceDates as $submitTime) {
            if (!$submitTime) {
                continue;
            }
            $dateMap[date('Y-m-d', strtotime($submitTime))] = true;
        }

        $continueDays = 0;
        $cursor = date('Y-m-d');
        while (isset($dateMap[$cursor])) {
            $continueDays++;
            $cursor = date('Y-m-d', strtotime($cursor . ' -1 day'));
        }

        return $this->success([
            'total_done' => (int) $totalDone,
            'correct_done' => (int) $correctDone,
            'today_done' => (int) $todayDone,
            'correct_rate' => $totalDone > 0 ? round($correctDone / $totalDone * 100) : 0,
            'continue_days' => $continueDays,
            'practice_times' => (int) $practiceTimes,
            'wrong_total' => (int) $wrongTotal,
            'wrong_question_count' => count(array_unique(array_map('intval', $wrongQuestionIds))),
        ]);
    }

    /**
     * 获取练习分类（仅题库章节）
     */
    public function categories()
    {
        $categories = Category::where('status', 1)
            ->where('type', 'question')
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $questionCountRows = Question::where('status', 1)
            ->fieldRaw('category_id, COUNT(*) AS total')
            ->group('category_id')
            ->select()
            ->toArray();

        $questionCountMap = [];
        foreach ($questionCountRows as $row) {
            $questionCountMap[(int) ($row['category_id'] ?? 0)] = (int) ($row['total'] ?? 0);
        }

        foreach ($categories as &$category) {
            $categoryIds = $this->resolveCategoryIdsFromTree($categories, (int) ($category['id'] ?? 0));
            $category['question_count'] = $this->sumQuestionCountByCategoryIds($categoryIds, $questionCountMap);
        }
        unset($category);

        return $this->success($this->flattenCategories($categories));
    }

    /**
     * 获取练习题目
     */
    public function questions()
    {
        $categoryId = (int) $this->request->param('category_id', 0);
        $mode = (string) $this->request->param('mode', 'normal');
        $limitParam = $this->request->param('limit', '');
        $limit = $limitParam === '' ? 0 : max(0, (int) $limitParam);

        $query = Question::where('status', 1);
        $categoryIds = [];

        if ($categoryId > 0) {
            $practiceCategories = Category::where('status', 1)
                ->where('type', 'question')
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray();

            $categoryIds = $this->resolveCategoryIdsFromTree($practiceCategories, $categoryId);
            if (empty($categoryIds)) {
                $categoryIds = [$categoryId];
            }
            $query->whereIn('category_id', $categoryIds);
        }

        if ($mode === 'random') {
            $query->orderRaw('RAND()');
        } elseif ($mode === 'wrong') {
            $userId = (int) $this->request->userId;
            $wrongIds = Db::name('exam_answer')
                ->alias('ea')
                ->join('exam_record er', 'er.id = ea.record_id')
                ->where('er.user_id', $userId)
                ->where('ea.is_correct', 0)
                ->order('ea.id', 'desc')
                ->column('ea.question_id');

            $wrongIds = array_values(array_unique(array_map('intval', $wrongIds)));
            if (empty($wrongIds)) {
                return $this->success([]);
            }

            $query->whereIn('id', $wrongIds);
            $query->orderRaw('FIELD(id,' . implode(',', $wrongIds) . ')');
        } else {
            if (!empty($categoryIds)) {
                $query->orderRaw('FIELD(category_id,' . implode(',', $categoryIds) . ')');
            } else {
                $query->order('category_id', 'asc');
            }
            $query->order('id', 'asc');
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $questions = $query
            ->with('options')
            ->select()
            ->toArray();

        $result = [];
        foreach ($questions as $question) {
            $options = $question['options'] ?? [];
            usort($options, fn($a, $b) => ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0));

            $correctAnswers = $this->extractCorrectAnswers($question, $options);
            $item = [
                'id' => (int) $question['id'],
                'category_id' => (int) ($question['category_id'] ?? 0),
                'type' => (int) $question['type'],
                'type_name' => $this->getTypeName($question['type']),
                'stem' => $question['stem'],
                'score' => $question['score'],
                'difficulty' => $question['difficulty'],
                'difficulty_name' => $this->getDifficultyName($question['difficulty']),
                'analysis' => $question['analysis'] ?? '',
                'correct_answer' => implode(',', $correctAnswers),
                'correct_answers' => $correctAnswers,
                'options' => [],
            ];

            if (in_array((int) $question['type'], [1, 2], true)) {
                foreach ($options as $option) {
                    $item['options'][] = [
                        'label' => $option['label'],
                        'content' => $option['content'],
                    ];
                }
            }

            $result[] = $item;
        }

        return $this->success($result);
    }

    /**
     * 获取用户错题本
     */
    public function wrongBook()
    {
        $userId = (int) $this->request->userId;
        $page = max(1, (int) $this->request->param('page', 1));
        $limit = max(1, (int) $this->request->param('limit', 20));
        $keyword = trim((string) $this->request->param('keyword', ''));

        $query = Db::name('exam_answer')
            ->alias('ea')
            ->join('exam_record er', 'er.id = ea.record_id')
            ->join('question q', 'q.id = ea.question_id')
            ->leftJoin('category c', 'c.id = q.category_id')
            ->where('er.user_id', $userId)
            ->where('er.source', 'practice')
            ->where('ea.is_correct', 0);

        if ($keyword !== '') {
            $query->whereLike('q.stem', '%' . $keyword . '%');
        }

        $total = count((clone $query)
            ->field('q.id')
            ->group('q.id')
            ->select()
            ->toArray());

        $aggregates = $query
            ->field([
                'q.id' => 'question_id',
                'q.category_id',
                'q.type',
                'q.stem',
                'q.analysis',
                'c.name' => 'category_name',
                Db::raw('COUNT(ea.id) AS wrong_count'),
                Db::raw('MAX(ea.created_at) AS latest_wrong_at'),
            ])
            ->group('q.id, q.category_id, q.type, q.stem, q.analysis, c.name')
            ->orderRaw('latest_wrong_at DESC, wrong_count DESC')
            ->page($page, $limit)
            ->select()
            ->toArray();

        if (empty($aggregates)) {
            return $this->page([], 0, $page, $limit);
        }

        $questionIds = array_column($aggregates, 'question_id');
        $questionRows = Question::with('options')
            ->whereIn('id', $questionIds)
            ->select()
            ->toArray();

        $questionMap = [];
        foreach ($questionRows as $question) {
            $questionMap[(int) $question['id']] = $question;
        }

        $latestWrongAnswerMap = Db::name('exam_answer')
            ->alias('ea')
            ->join('exam_record er', 'er.id = ea.record_id')
            ->where('er.user_id', $userId)
            ->where('er.source', 'practice')
            ->where('ea.is_correct', 0)
            ->whereIn('ea.question_id', $questionIds)
            ->order('ea.id', 'desc')
            ->column('ea.user_answer', 'ea.question_id');

        $list = [];
        foreach ($aggregates as $item) {
            $questionId = (int) $item['question_id'];
            $question = $questionMap[$questionId] ?? null;
            $correctAnswers = $question ? $this->extractCorrectAnswers($question, $question['options'] ?? []) : [];
            $list[] = [
                'question_id' => $questionId,
                'category_id' => (int) ($item['category_id'] ?? 0),
                'category_name' => $item['category_name'] ?? '',
                'type' => (int) ($item['type'] ?? 0),
                'type_name' => $this->getTypeName((int) ($item['type'] ?? 0)),
                'stem' => $item['stem'] ?? '',
                'analysis' => $item['analysis'] ?? '',
                'wrong_count' => (int) ($item['wrong_count'] ?? 0),
                'latest_wrong_at' => $item['latest_wrong_at'] ?? '',
                'latest_user_answer' => (string) ($latestWrongAnswerMap[$questionId] ?? ''),
                'correct_answer' => implode(',', $correctAnswers),
                'correct_answers' => $correctAnswers,
            ];
        }

        return $this->page($list, $total, $page, $limit);
    }

    /**
     * 提交练习答案并记录错题本
     */
    public function submit()
    {
        $data = $this->request->post();
        $answers = $data['answers'] ?? [];

        if (is_string($answers)) {
            $decodedAnswers = json_decode($answers, true);
            if (is_array($decodedAnswers)) {
                $answers = $decodedAnswers;
            }
        }

        if (empty($answers) || !is_array($answers)) {
            return $this->error('没有提交答案');
        }

        $details = [];
        $questionIds = [];
        foreach ($answers as $questionId => $userAnswer) {
            $questionIds[] = (int) $questionId;
        }

        $questions = Question::with('options')
            ->whereIn('id', $questionIds)
            ->select()
            ->toArray();

        $questionMap = [];
        foreach ($questions as $question) {
            $questionMap[(int) $question['id']] = $question;
        }

        foreach ($answers as $questionId => $userAnswer) {
            $questionId = (int) $questionId;
            if (!isset($questionMap[$questionId])) {
                continue;
            }

            $question = $questionMap[$questionId];
            $check = $this->checkAnswer($question, $userAnswer);
            $details[] = [
                'question_id' => $questionId,
                'question_type' => (int) $question['type'],
                'user_answer' => is_array($userAnswer) ? implode(',', $userAnswer) : (string) $userAnswer,
                'correct_answer' => $check['correct_answer'],
                'is_correct' => $check['is_correct'] ? 1 : 0,
                'analysis' => $question['analysis'] ?? '',
                'score' => $check['is_correct'] ? (float) ($question['score'] ?? 0) : 0,
            ];
        }

        if (empty($details)) {
            return $this->error('未匹配到有效题目');
        }

        $correctCount = count(array_filter($details, fn($item) => (int) $item['is_correct'] === 1));
        $totalCount = count($details);
        $wrongCount = $totalCount - $correctCount;
        $now = date('Y-m-d H:i:s');
        $userId = (int) $this->request->userId;

        Db::startTrans();
        try {
            $record = ExamRecord::create([
                'user_id' => $userId,
                'paper_id' => 0,
                'paper_title' => '刷题练习',
                'score' => $correctCount,
                'total_score' => $totalCount,
                'correct_count' => $correctCount,
                'wrong_count' => $wrongCount,
                'pass_status' => $totalCount > 0 && $correctCount === $totalCount ? 1 : 0,
                'status' => 2,
                'start_time' => $now,
                'submit_time' => $now,
                'duration' => 0,
                'source' => 'practice',
                'created_at' => $now,
            ]);

            $answerRows = [];
            foreach ($details as $index => $detail) {
                $answerRows[] = [
                    'record_id' => $record->id,
                    'question_id' => $detail['question_id'],
                    'question_type' => $detail['question_type'],
                    'user_answer' => $detail['user_answer'],
                    'correct_answer' => $detail['correct_answer'],
                    'is_correct' => $detail['is_correct'],
                    'score' => $detail['score'],
                    'sort' => $index + 1,
                    'created_at' => $now,
                ];

                Question::where('id', $detail['question_id'])->inc('use_count', 1)->update();
                if ((int) $detail['is_correct'] === 1) {
                    Question::where('id', $detail['question_id'])->inc('correct_count', 1)->update();
                }
            }

            if (!empty($answerRows)) {
                (new ExamAnswer())->insertAll($answerRows);
            }

            $record->save([
                'answer_ids' => json_encode(array_column($answerRows, 'question_id'), JSON_UNESCAPED_UNICODE),
            ]);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->error('提交失败：' . $e->getMessage(), 500);
        }

        return $this->success([
            'correct_count' => $correctCount,
            'wrong_count' => $wrongCount,
            'total_count' => $totalCount,
            'correct_rate' => $totalCount > 0 ? round($correctCount / $totalCount * 100) : 0,
            'details' => $details,
        ]);
    }

    protected function resolveCategoryIdsFromTree(array $categories, int $categoryId): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        $ids = [$categoryId];
        $ids = array_merge($ids, $this->collectChildCategoryIds($categories, $categoryId));

        return array_values(array_unique(array_map('intval', $ids)));
    }

    protected function collectChildCategoryIds(array $categories, int $parentId): array
    {
        $result = [];
        foreach ($categories as $category) {
            if ((int) ($category['parent_id'] ?? 0) !== $parentId) {
                continue;
            }

            $childId = (int) ($category['id'] ?? 0);
            if ($childId <= 0) {
                continue;
            }

            $result[] = $childId;
            $result = array_merge($result, $this->collectChildCategoryIds($categories, $childId));
        }

        return $result;
    }

    protected function sumQuestionCountByCategoryIds(array $categoryIds, array $questionCountMap): int
    {
        $total = 0;
        foreach ($categoryIds as $categoryId) {
            $total += (int) ($questionCountMap[$categoryId] ?? 0);
        }

        return $total;
    }

    protected function flattenCategories(array $categories, int $parentId = 0, int $level = 0): array
    {
        $result = [];
        foreach ($categories as $category) {
            if ((int) ($category['parent_id'] ?? 0) !== $parentId) {
                continue;
            }

            $category['display_name'] = str_repeat('　', $level) . $category['name'];
            $result[] = $category;
            $result = array_merge($result, $this->flattenCategories($categories, (int) $category['id'], $level + 1));
        }

        return $result;
    }

    protected function extractCorrectAnswers(array $question, array $options = []): array
    {
        $type = (int) ($question['type'] ?? 0);
        $rawAnswer = trim((string) ($question['answer'] ?? ''));

        if ($type === 4) {
            return in_array(strtolower($rawAnswer), ['true', '1', 'a', '正确'], true) ? ['A'] : ['B'];
        }

        if ($rawAnswer !== '') {
            $parts = array_filter(array_map('trim', explode(',', $rawAnswer)), fn($item) => $item !== '');
            if (!empty($parts)) {
                return array_values($parts);
            }
        }

        if (empty($options) && !empty($question['options']) && is_array($question['options'])) {
            $options = $question['options'];
        }

        $labels = [];
        foreach ($options as $option) {
            if ((int) ($option['is_correct'] ?? 0) === 1) {
                $labels[] = (string) $option['label'];
            }
        }

        sort($labels);
        return $labels;
    }

    protected function checkAnswer(array $question, $userAnswer): array
    {
        $correctAnswers = $this->extractCorrectAnswers($question, $question['options'] ?? []);
        $correctAnswer = implode(',', $correctAnswers);
        $type = (int) ($question['type'] ?? 0);
        $isCorrect = false;

        if ($type === 2) {
            $userAnswers = is_array($userAnswer)
                ? $userAnswer
                : array_filter(array_map('trim', explode(',', (string) $userAnswer)), fn($item) => $item !== '');
            $userAnswers = array_values(array_map('strtoupper', $userAnswers));
            $standardAnswers = array_values(array_map('strtoupper', $correctAnswers));
            sort($userAnswers);
            sort($standardAnswers);
            $isCorrect = $userAnswers === $standardAnswers;
        } elseif (in_array($type, [1, 4], true)) {
            $userValue = strtoupper(trim(is_array($userAnswer) ? ($userAnswer[0] ?? '') : (string) $userAnswer));
            $isCorrect = $userValue === strtoupper($correctAnswers[0] ?? '');
        } else {
            $userValue = trim(is_array($userAnswer) ? ($userAnswer[0] ?? '') : (string) $userAnswer);
            $isCorrect = $userValue === trim((string) ($question['answer'] ?? ''));
            $correctAnswer = (string) ($question['answer'] ?? '');
        }

        return [
            'is_correct' => $isCorrect,
            'correct_answer' => $correctAnswer,
        ];
    }

    protected function getTypeName($type)
    {
        $map = [1 => '单选题', 2 => '多选题', 3 => '填空题', 4 => '判断题', 5 => '简答题'];
        return $map[$type] ?? '未知';
    }

    protected function getDifficultyName($difficulty)
    {
        $map = [1 => '简单', 2 => '一般', 3 => '困难', 4 => '较难', 5 => '很难'];
        return $map[$difficulty] ?? '一般';
    }
}