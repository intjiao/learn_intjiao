<?php
namespace app\model;

use app\BaseModel\BaseModel;

class Knowledge extends BaseModel
{
    protected $table = 'knowledge';
    protected $pk = 'id';

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function getTypeTextAttr($value, $data): string
    {
        $map = ['video' => '视频', 'audio' => '音频', 'document' => '文档', 'image' => '图片', 'file' => '附件'];
        return $map[$data['type']] ?? '未知';
    }
}
