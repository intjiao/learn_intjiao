<?php
namespace app\model;

use app\BaseModel\BaseModel;

class TrainingTask extends BaseModel
{
    protected $table = 'training_task';
    protected $pk = 'id';

    public function stages()
    {
        return $this->hasMany(TrainingStage::class, 'task_id')->order('sort asc');
    }

    public function getTypeTextAttr($value, $data): string
    {
        $map = [1 => '在线学习', 2 => '混合培训'];
        return $map[$data['type']] ?? '未知';
    }

    public function getStatusTextAttr($value, $data): string
    {
        $map = [0 => '草稿', 1 => '进行中', 2 => '已结束'];
        return $map[$data['status']] ?? '未知';
    }

    public function getProgressAttr($value): int
    {
        if ($this->enroll_count == 0) return 0;
        return (int)round($this->complete_count / $this->enroll_count * 100);
    }
}
