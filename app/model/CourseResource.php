<?php
namespace app\model;

use app\BaseModel\BaseModel;

class CourseResource extends BaseModel
{
    protected $table = 'course_resource';
    protected $pk = 'id';

    public function chapter()
    {
        return $this->belongsTo(CourseChapter::class, 'chapter_id');
    }

    public function getTypeTextAttr($value, $data): string
    {
        $map = ['video' => '视频', 'audio' => '音频', 'document' => '文档', 'image' => '图片', 'file' => '附件'];
        return $map[$data['type']] ?? '未知';
    }

    public function getDurationTextAttr($value, $data): string
    {
        $seconds = $data['duration'] ?? 0;
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $secs);
    }
}
