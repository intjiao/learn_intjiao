<?php
namespace app\model;

use app\BaseModel\BaseModel;

class Course extends BaseModel
{
    protected $table = 'course';
    protected $pk = 'id';

    public function chapters()
    {
        return $this->hasMany(CourseChapter::class, 'course_id')->order('sort asc');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function getDurationTextAttr($value, $data): string
    {
        $seconds = $data['total_duration'] ?? 0;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }
        return sprintf('%d:%02d', $minutes, $secs);
    }

    public function getIsFreeTextAttr($value, $data): string
    {
        return ($data['is_free'] ?? 0) == 1 ? '免费' : '付费';
    }
}
