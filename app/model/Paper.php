<?php
namespace app\model;

use app\BaseModel\BaseModel;

class Paper extends BaseModel
{
    protected $table = 'paper';
    protected $pk = 'id';

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'paper_question', 'question_id', 'paper_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function getTypeTextAttr($value, $data): string
    {
        return $data['type'] == 1 ? '固定试卷' : '随机试卷';
    }

    public function getStatusTextAttr($value, $data): string
    {
        $map = [0 => '禁用', 1 => '草稿', 2 => '已发布'];
        return $map[$data['status']] ?? '未知';
    }
}
