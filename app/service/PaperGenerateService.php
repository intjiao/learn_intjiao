<?php
namespace app\service;

use app\model\Paper;
use app\model\Question;
use app\model\PaperQuestion;

class PaperGenerateService
{
    public function generateFixedPaper(int $paperId, array $questionIds): bool
    {
        $paper = Paper::find($paperId);
        if (!$paper) {
            throw new \Exception('试卷不存在');
        }

        PaperQuestion::where('paper_id', $paperId)->delete();

        $questions = Question::with('options')->whereIn('id', $questionIds)->select();
        $totalScore = 0;
        $sort = 0;
        $records = [];

        foreach ($questions as $question) {
            $score = $question->score;
            $totalScore += $score;
            $sort++;

            $records[] = [
                'paper_id' => $paperId,
                'question_id' => $question->id,
                'sort' => $sort,
                'score' => $score,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        if (!empty($records)) {
            (new PaperQuestion())->insertAll($records);
        }

        $paper->total_score = $totalScore;
        $paper->total_count = count($questionIds);
        $paper->save();

        return true;
    }

    public function generateRandomPaper(int $paperId): array
    {
        $paper = Paper::find($paperId);
        if (!$paper) {
            throw new \Exception('试卷不存在');
        }

        PaperQuestion::where('paper_id', $paperId)->delete();

        $rules = json_decode($paper->question_rules, true);
        if (empty($rules)) {
            throw new \Exception('抽题规则未配置');
        }

        $questionIds = [];
        $totalScore = 0;
        $sort = 0;

        foreach ($rules as $rule) {
            $query = Question::where('category_id', $rule['category_id'] ?? 0)
                ->where('type', $rule['type'])
                ->where('status', 1);

            if (!empty($rule['difficulty'])) {
                $query->where('difficulty', '<=', $rule['difficulty']);
            }

            $count = min($rule['quantity'] ?? 10, $query->count());
            $ids = $query->orderRaw('RAND()')->limit($count)->column('id');

            foreach ($ids as $id) {
                $question = Question::find($id);
                $questionIds[] = $id;
                $totalScore += $question->score ?? 1;
                $sort++;

                PaperQuestion::create([
                    'paper_id' => $paperId,
                    'question_id' => $id,
                    'sort' => $sort,
                    'score' => $question->score ?? 1,
                ]);
            }
        }

        $paper->total_score = $totalScore;
        $paper->total_count = count($questionIds);
        $paper->save();

        return $questionIds;
    }

    public function getPaperQuestions(int $paperId): array
    {
        $paper = Paper::find($paperId);
        if (!$paper) {
            return [];
        }

        if ($paper->type == 2 && $paper->total_count == 0) {
            $this->generateRandomPaper($paperId);
        }

        $questions = Question::with('options')
            ->alias('q')
            ->join('paper_question pq', 'q.id = pq.question_id')
            ->where('pq.paper_id', $paperId)
            ->order('pq.sort', 'asc')
            ->select()
            ->toArray();

        $result = [];
        foreach ($questions as $q) {
            $item = [
                'id' => $q['id'],
                'type' => $q['type'],
                'stem' => $q['stem'],
                'score' => $q['score'],
                'analysis' => $q['analysis'],
                'options' => [],
            ];

            if (in_array($q['type'], [1, 2, 4])) {
                $options = $q['options'] ?? [];
                usort($options, function ($a, $b) {
                    return $a['sort'] - $b['sort'];
                });
                $item['options'] = $options;
            }

            $result[] = $item;
        }

        return $result;
    }
}
