<?php
namespace app\model;

use app\BaseModel\BaseModel;

class Category extends BaseModel
{
    protected $table = 'category';
    protected $pk = 'id';

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->order('sort asc');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function getTypeTextAttr($value, $data): string
    {
        $map = ['question' => '试题分类', 'course' => '课程分类', 'knowledge' => '知识库分类'];
        return $map[$data['type']] ?? '未知';
    }
}
