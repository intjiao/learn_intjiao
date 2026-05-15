<?php
namespace app\model;

use app\BaseModel\BaseModel;

class CourseChapter extends BaseModel
{
    protected $table = 'course_chapter';
    protected $pk = 'id';

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function resources()
    {
        return $this->hasMany(CourseResource::class, 'chapter_id')->order('sort asc');
    }
}
