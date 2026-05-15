<?php
namespace app\model;

use app\BaseModel\BaseModel;

class Certificate extends BaseModel
{
    protected $table = 'certificate';
    protected $pk = 'id';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function task()
    {
        return $this->belongsTo(TrainingTask::class, 'task_id');
    }

    public function getStatusTextAttr($value, $data): string
    {
        $map = [1 => '有效', 2 => '已撤销', 3 => '已过期'];
        return $map[$data['status']] ?? '未知';
    }
}
