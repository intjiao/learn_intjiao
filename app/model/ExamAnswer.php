<?php
namespace app\model;

use app\BaseModel\BaseModel;

class ExamAnswer extends BaseModel
{
    protected $table = 'exam_answer';
    protected $pk = 'id';

    public function record()
    {
        return $this->belongsTo(ExamRecord::class, 'record_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function getIsCorrectTextAttr($value, $data): string
    {
        $map = [0 => '错误', 1 => '正确', 2 => '待批阅'];
        return $map[$data['is_correct']] ?? '未知';
    }

    public function getTypeTextAttr($value, $data): string
    {
        $map = [1 => '单选题', 2 => '多选题', 3 => '填空题', 4 => '判断题', 5 => '问答题'];
        return $map[$data['question_type']] ?? '未知';
    }
}
