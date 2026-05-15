<?php
namespace app\model;

use app\BaseModel\BaseModel;

class TrainingStage extends BaseModel
{
    protected $table = 'training_stage';
    protected $pk = 'id';

    public function task()
    {
        return $this->belongsTo(TrainingTask::class, 'task_id');
    }

    public function paper()
    {
        return $this->belongsTo(Paper::class, 'exam_paper_id');
    }

    public function getTypeTextAttr($value, $data): string
    {
        return $data['type'] == 1 ? '学习阶段' : '考试阶段';
    }
}
