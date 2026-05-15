<?php
namespace app\model;

use app\BaseModel\BaseModel;

class UserGroup extends BaseModel
{
    protected $table = 'user_group';
    protected $pk = 'id';

    public function users()
    {
        return $this->hasMany(User::class, 'user_group_id');
    }

    public function parent()
    {
        return $this->belongsTo(UserGroup::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(UserGroup::class, 'parent_id');
    }
}
