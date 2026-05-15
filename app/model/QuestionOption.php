<?php
namespace app\model;

use app\BaseModel\BaseModel;

class QuestionOption extends BaseModel
{
    protected $table = 'question_option';
    protected $pk = 'id';

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
