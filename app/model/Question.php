<?php
namespace app\model;

use app\BaseModel\BaseModel;

class Question extends BaseModel
{
    protected $table = 'question';
    protected $pk = 'id';

    public function options()
    {
        return $this->hasMany(QuestionOption::class, 'question_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function getTypeTextAttr($value, $data): string
    {
        $map = [1 => '单选题', 2 => '多选题', 3 => '填空题', 4 => '判断题', 5 => '问答题'];
        return $map[$data['type']] ?? '未知';
    }

    public function getDifficultyTextAttr($value, $data): string
    {
        $map = [1 => '简单', 2 => '较简单', 3 => '中等', 4 => '较难', 5 => '困难'];
        return $map[$data['difficulty']] ?? '未知';
    }

    public function getCorrectRateAttr(): float
    {
        if ($this->use_count == 0) return 0;
        return round($this->correct_count / $this->use_count * 100, 1);
    }
}
