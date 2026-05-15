<?php
namespace app\model;

use app\BaseModel\BaseModel;

class TrainingEnroll extends BaseModel
{
    protected $table = 'training_enroll';
    protected $pk = 'id';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function task()
    {
        return $this->belongsTo(TrainingTask::class, 'task_id');
    }

    public function certificate()
    {
        return $this->belongsTo(Certificate::class, 'certificate_id');
    }

    public function getStatusTextAttr($value, $data): string
    {
        $map = [0 => '已取消', 1 => '进行中', 2 => '已完成'];
        return $map[$data['status']] ?? '未知';
    }
}
