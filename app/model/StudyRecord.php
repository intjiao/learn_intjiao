<?php
namespace app\model;

use app\BaseModel\BaseModel;

class StudyRecord extends BaseModel
{
    protected $table = 'study_record';
    protected $pk = 'id';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
