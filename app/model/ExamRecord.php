<?php
namespace app\model;

use app\BaseModel\BaseModel;

class ExamRecord extends BaseModel
{
    protected $table = 'exam_record';
    protected $pk = 'id';
    protected $updateTime = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function paper()
    {
        return $this->belongsTo(Paper::class, 'paper_id');
    }

    public function answers()
    {
        return $this->hasMany(ExamAnswer::class, 'record_id');
    }

    public function getStatusTextAttr($value, $data): string
    {
        $map = [0 => '已取消', 1 => '进行中', 2 => '已完成'];
        return $map[$data['status']] ?? '未知';
    }

    public function getPassStatusTextAttr($value, $data): string
    {
        if ($data['status'] != 2) return '未完成';
        return ($data['pass_status'] ?? '') == 1 ? '及格' : '不及格';
    }

    public function getDurationTextAttr($value, $data): string
    {
        $seconds = $data['duration'] ?? 0;
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return sprintf('%d分%d秒', $minutes, $secs);
    }
}
