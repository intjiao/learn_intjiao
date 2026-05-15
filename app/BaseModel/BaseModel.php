<?php
namespace app\BaseModel;

use think\Model;

abstract class BaseModel extends Model
{
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $dissociate = [];

    public function getStatusTextAttr($value, $data): string
    {
        $status = $data['status'] ?? null;
        $map = [1 => '正常', 0 => '禁用'];
        return $map[$status] ?? '未知';
    }

    public static function onAfterDelete($model): void
    {
        if (method_exists($model, 'getDissociate') && !empty($model->getDissociate())) {
            foreach ($model->getDissociate() as $table => $foreignKey) {
                $model->destroy([$foreignKey => $model->id]);
            }
        }
    }

    public function scopeStatus($query, $status = 1)
    {
        return $query->where('status', $status);
    }
}
