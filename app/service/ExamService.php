<?php
namespace app\service;

use app\model\ExamRecord;
use app\model\ExamAnswer;
use app\model\Question;
use app\model\QuestionOption;
use think\facade\Db;

class ExamService
{
    public function startExam(int $userId, int $paperId, ?int $taskId = null, ?int $stageId = null): ExamRecord
    {
        $record = ExamRecord::create([
            'user_id' => $userId,
            'paper_id' => $paperId,
            'task_id' => $taskId,
            'stage_id' => $stageId,
            'start_time' => date('Y-m-d H:i:s'),
            'status' => 1,
        ]);

        return $record;
    }

    public function submitExam(int $recordId, array $answers): array
    {
        Db::startTrans();
        try {
            $record = ExamRecord::with('paper')->find($recordId);
            if (!$record) {
                throw new \Exception('考试记录不存在');
            }

            if ($record->status != 1) {
                throw new \Exception('考试已结束');
            }

            $questions = Question::with('options')->whereIn('id', array_keys($answers))->select();
            $totalScore = 0;
            $correctCount = 0;
            $wrongCount = 0;
            $answerRecords = [];

            foreach ($questions as $question) {
                $userAnswer = $answers[$question->id] ?? '';
                $result = $this->checkAnswer($question, $userAnswer);
                $isCorrect = $result['is_correct'] ? 1 : 0;

                if ($isCorrect) {
                    $correctCount++;
                    $totalScore += $question->score;
                } else {
                    $wrongCount++;
                }

                $answerRecords[] = [
                    'record_id' => $recordId,
                    'question_id' => $question->id,
                    'question_type' => $question->type,
                    'user_answer' => is_array($userAnswer) ? implode(',', $userAnswer) : $userAnswer,
                    'correct_answer' => $result['correct_answer'],
                    'is_correct' => $isCorrect,
                    'score' => $isCorrect ? $question->score : 0,
                    'sort' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
            }

            if (!empty($answerRecords)) {
                (new ExamAnswer())->insertAll($answerRecords);
            }

            $passStatus = $totalScore >= ($record->paper->pass_score ?? 60) ? 1 : 0;
            $duration = time() - strtotime($record->start_time);

            $record->score = $totalScore;
            $record->total_score = $record->paper->total_score ?? 100;
            $record->correct_count = $correctCount;
            $record->wrong_count = $wrongCount;
            $record->pass_status = $passStatus;
            $record->status = 2;
            $record->submit_time = date('Y-m-d H:i:s');
            $record->duration = $duration;
            $record->save();

            Db::commit();

            return [
                'record_id' => $recordId,
                'score' => $totalScore,
                'total_score' => $record->paper->total_score ?? 100,
                'correct_count' => $correctCount,
                'wrong_count' => $wrongCount,
                'pass_status' => $passStatus,
                'duration' => $duration,
            ];
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    protected function checkAnswer(Question $question, $userAnswer): array
    {
        $isCorrect = false;
        $correctAnswer = '';

        switch ($question->type) {
            case 1:
            case 4:
                $correctLabel = QuestionOption::where('question_id', $question->id)
                    ->where('is_correct', 1)->value('label') ?? '';
                $correctAnswer = $correctLabel;
                $isCorrect = strtoupper(trim($userAnswer)) === strtoupper($correctLabel);
                break;

            case 2:
                $correctLabels = QuestionOption::where('question_id', $question->id)
                    ->where('is_correct', 1)->order('label')->column('label') ?? [];
                $correctAnswer = implode(',', $correctLabels);

                if (is_array($userAnswer)) {
                    sort($userAnswer);
                    sort($correctLabels);
                    $isCorrect = $userAnswer === $correctLabels;
                } else {
                    $userLabels = array_map('trim', explode(',', strtoupper($userAnswer)));
                    sort($userLabels);
                    $isCorrect = $userLabels === $correctLabels;
                }
                break;

            case 3:
            case 5:
                $correctAnswer = $question->answer ?? '';
                $isCorrect = trim($userAnswer) === trim($correctAnswer);
                break;
        }

        return [
            'is_correct' => $isCorrect,
            'correct_answer' => $correctAnswer,
        ];
    }

    public function getExamDetail(int $recordId, bool $showAnswer = false): array
    {
        $record = ExamRecord::with(['paper', 'answers.question.options'])->find($recordId);
        if (!$record) {
            return [];
        }

        $result = [
            'id' => $record->id,
            'paper_title' => $record->paper->title ?? '',
            'score' => $record->score,
            'total_score' => $record->total_score,
            'correct_count' => $record->correct_count,
            'wrong_count' => $record->wrong_count,
            'pass_status' => $record->pass_status,
            'duration' => $record->duration,
            'start_time' => $record->start_time,
            'submit_time' => $record->submit_time,
            'questions' => [],
        ];

        if ($showAnswer) {
            foreach ($record->answers as $answer) {
                $q = $answer->question;
                $item = [
                    'id' => $q->id,
                    'type' => $q->type,
                    'stem' => $q->stem,
                    'user_answer' => $answer->user_answer,
                    'correct_answer' => $answer->correct_answer,
                    'is_correct' => $answer->is_correct,
                    'score' => $answer->score,
                    'analysis' => $q->analysis,
                ];

                if (in_array($q->type, [1, 2, 4])) {
                    $item['options'] = $q->options;
                }

                $result['questions'][] = $item;
            }
        }

        return $result;
    }
}
